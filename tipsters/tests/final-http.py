"""Rejected tips and permanent administrator deletion over real WordPress HTTP."""
import http_support as support
from http_support import *


def action_form(body, action, status=None):
    for form in re.findall(r'<form\b[^>]*>.*?</form>', body.decode(), re.S):
        values = fields(form.encode())
        if values.get('action') == action and (status is None or values.get('status') == status):
            return values
    raise AssertionError('Missing action: ' + action)


def live(client, body, **extra):
    attrs = dict(re.findall(r'data-(endpoint|tip|nonce|cursor)="([^"]*)"', body.decode()))
    data = dict(action='cammino_conversation', tip_id=attrs['tip'], _wpnonce=html.unescape(attrs['nonce']),
                after=attrs['cursor'], operation='poll')
    data.update(extra)
    response = request(client, html.unescape(attrs['endpoint']), data)
    return response, json.loads(response[3])


fixtures = fixture('setup')
prefix = fixtures['prefix']
try:
    owner, other, admin, guest = session(), session(), session(), session()
    login(owner, fixtures['users']['one']['username'], fixtures['password'])
    login(other, fixtures['users']['two']['username'], fixtures['password'])
    request(admin, '/wp-login.php')
    request(admin, '/wp-login.php', {'log': fixtures['users']['admin']['username'], 'pwd': fixtures['password'], 'testcookie': '1'})
    check(b'footer-tipster-login' in request(guest, '/')[3], 'Footer offers tipster login on public pages')
    check(b'data-password-toggle' in request(guest, '/tipsters/login/')[3], 'Password visibility control is available on login')
    data = new_form(owner); data.update(title='Final review tip', short_description='Short', long_description='Long', file_link='https://drive.google.com/example')
    tip = request(owner, '/tipsters/new/', data); path = tip[1]
    tip_id = re.search(r'/tip/(\d+)/', path).group(1)
    admin_path = '/wp-admin/admin.php?page=cammino-tips&tip_id=' + tip_id
    legacy = fixture('legacy', prefix)
    request(admin, '/wp-admin/admin-post.php', action_form(request(admin, admin_path)[3], 'cammino_tip_status', 'discussion'))
    history = fixture('chat-history', prefix)
    check(history['messages'] == 45, 'Long persisted history is seeded in the disposable installation')
    detail = request(owner, path)
    check(b'Historical message 45' in detail[3] and b'Historical message 1\n' not in detail[3], 'Default conversation opens its latest history page')
    old = request(owner, path + '?messages_page=1')
    check(b'Historical message 1\n' in old[3] and b'Historical message 45' not in old[3], 'Older persisted pages remain accessible')
    check('no-store' in detail[2].get('Cache-Control', ''), 'Private history has no-store headers')
    editable = fields(detail[3], 'edit_tip'); send = fields(detail[3], 'send_message')
    delete_form = action_form(request(admin, admin_path)[3], 'cammino_admin_delete_tip')
    request(admin, '/wp-admin/admin-post.php', action_form(request(admin, admin_path)[3], 'cammino_tip_status', 'rejected'))
    rejected = request(owner, path)
    check('Zamietnutý'.encode() in rejected[3] and 'Zamietnutý'.encode() in request(owner, '/tipsters/')[3], 'Rejected status is visible in Slovak on detail and dashboard')
    check(b'value="edit_tip"' not in rejected[3] and b'value="send_message"' not in rejected[3], 'Rejected tip exposes no editable content or active message form')
    response, delta = live(owner, detail[3])
    check(delta['data']['status'] == 'rejected' and not delta['data']['can_send'], 'Already open browser receives rejected state and disabled composer')
    response, denied = live(owner, detail[3], operation='send_message', message_token=send['message_token'], message_body='Rejected attempt')
    check(response[0] == 422 and not denied['success'], 'Rejected AJAX write is denied')
    editable.update(title='Forbidden edit', short_description='S', long_description='L')
    check(b'Forbidden edit' not in request(owner, path, editable)[3], 'Stale content form cannot modify rejected tip')
    check(b'Historical message 45' in rejected[3], 'Rejection preserves conversation history')
    reopen = action_form(request(admin, admin_path)[3], 'cammino_tip_status', 'discussion')
    request(admin, '/wp-admin/admin-post.php', reopen)
    check('Zamietnutý'.encode() in request(owner, path)[3], 'Reopening requires explicit confirmation')
    reopen['confirm_reopen'] = 'yes'; request(admin, '/wp-admin/admin-post.php', reopen)
    check(b'value="edit_tip"' in request(owner, path)[3], 'Confirmed reopen enables a fresh discussion form')
    for client in [owner, other, guest]:
        result = request(client, '/wp-admin/admin-post.php', delete_form)
        check((result[0] in (400, 403, 404) or '/tipsters/' in result[1] or '/wp-login.php' in result[1]) and request(owner, path)[0] == 200, f'Non-admin deletion is denied or redirected without modifying tip ({result[0]}, {result[1]})')
    signed = action_form(request(admin, admin_path)[3], 'cammino_admin_delete_tip')
    invalid = dict(signed, confirm_title='Final review tip', confirm_delete='yes', _wpnonce='invalid')
    check(request(admin, '/wp-admin/admin-post.php', invalid)[0] == 403, 'Invalid deletion nonce rejected')
    for extra in [dict(confirm_title='Final review tip'), dict(confirm_title='Wrong', confirm_delete='yes')]:
        request(admin, '/wp-admin/admin-post.php', dict(signed, **extra))
        check(request(owner, path)[0] == 200, 'Missing checkbox or wrong title preserves tip')
    request(admin, '/wp-admin/admin-post.php', dict(delete_form, confirm_title='Final review tip', confirm_delete='yes'))
    check(request(owner, path)[0] == 200, 'Stale permanent deletion form preserves changed tip')
    second = new_form(owner); second.update(title='Preserved other tip', short_description='Short', long_description='Long')
    second_path = request(owner, '/tipsters/new/', second)[1]
    result = request(admin, '/wp-admin/admin-post.php', dict(signed, confirm_title='Final review tip', confirm_delete='yes'))
    check(result[0] == 200 and 'tip_id=' not in result[1], 'Successful permanent deletion returns to admin list')
    check(request(owner, path)[0] == 404 and request(admin, admin_path)[0] == 404, 'Deleted tip is unavailable to owner and admin')
    snapshot = fixture('snapshot', prefix)['one']
    check(snapshot['tips'] == 1 and snapshot['messages'] == 0 and not snapshot['files'], 'Purge removes only target tip, all replies and legacy file records')
    check(request(owner, second_path)[0] == 200, 'Account session and its other tip remain usable')
    check(request(admin, legacy['url'])[0] == 404, 'Purged legacy download is unavailable')
    response, missing = live(admin, detail[3])
    check(response[0] in (403, 404) and not missing['success'], 'Existing polling cannot access a purged tip')
    print(f'Passed {support.checks} final-stage HTTP checks.')
finally:
    fixture('cleanup', prefix)
