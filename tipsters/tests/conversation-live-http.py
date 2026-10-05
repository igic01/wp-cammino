"""Incremental conversation API against a disposable WordPress installation."""
import http_support as support
from http_support import *


def live(client, body, **extra):
    text = body.decode()
    attrs = dict(re.findall(r'data-(endpoint|tip|nonce|cursor)="([^"]*)"', text))
    values = dict(action='cammino_conversation', tip_id=attrs['tip'], _wpnonce=html.unescape(attrs['nonce']),
                  after=attrs['cursor'], operation='poll')
    values.update(extra)
    response = request(client, html.unescape(attrs['endpoint']), values)
    return response, json.loads(response[3])


fixtures = fixture('setup')
prefix = fixtures['prefix']
try:
    owner, other, admin, guest = session(), session(), session(), session()
    login(owner, fixtures['users']['one']['username'], fixtures['password'])
    login(other, fixtures['users']['two']['username'], fixtures['password'])
    request(admin, '/wp-login.php')
    request(admin, '/wp-login.php', {'log': fixtures['users']['admin']['username'], 'pwd': fixtures['password'], 'redirect_to': base + '/wp-admin/', 'testcookie': '1'})
    form = new_form(owner); form.update(title='Live conversation', short_description='Short', long_description='Long')
    tip = submit_tip(owner, form); path = tip[1]
    tip_id = re.search(r'/tip/(\d+)/', path).group(1)
    admin_path = '/wp-admin/admin.php?page=cammino-tips&tip_id=' + tip_id
    response, data = live(owner, tip[3])
    check(response[0] == 200 and data['success'] and data['data']['messages'] == [] and not data['data']['can_send'], 'Submitted conversation polls without enabling writes')
    check('no-store' in response[2].get('Cache-Control', ''), 'Private polling is never cached')
    for client in [other, guest]:
        response, data = live(client, tip[3])
        check(response[0] == 403 and not data['success'] and 'messages' not in data['data'], 'Other session cannot use owner poll nonce')
    response, data = live(owner, tip[3], _wpnonce='invalid')
    check(response[0] == 403, 'Invalid poll nonce rejected')
    response, data = live(owner, tip[3], operation='delete_message')
    check(response[0] == 400, 'API exposes no message mutation operation')
    detail = request(admin, admin_path)
    status_form = next(form for form in re.findall(r'<form\b[^>]*>.*?</form>', detail[3].decode(), re.S) if 'name="status" value="discussion"' in form)
    request(admin, '/wp-admin/admin-post.php', fields(status_form.encode()))
    response, data = live(owner, tip[3])
    check(data['data']['can_send'], 'Already open page discovers discussion automatically')
    own = request(owner, path); owner_send = fields(own[3], 'send_message')
    response, data = live(owner, own[3], operation='send_message', message_body='Live owner & text', message_token=owner_send['message_token'])
    check(response[0] == 200 and len(data['data']['messages']) == 1 and data['data']['messages'][0]['body'] == 'Live owner & text', 'AJAX sends and returns persisted plain message')
    cursor = data['data']['cursor']; token = data['data']['message_token']
    sent = data['data']['sent_message']
    check(sent['id'] == cursor and sent['request_key'] and sent['own'], 'Send explicitly acknowledges the persisted record and sender key')
    response, retry = live(owner, own[3], operation='send_message', message_body='Live owner & text', message_token=owner_send['message_token'])
    check(retry['data']['cursor'] == cursor and len(retry['data']['messages']) == 1, 'AJAX retry cannot duplicate a message')
    response, acknowledged = live(owner, own[3], after=cursor, operation='send_message', message_body='Live owner & text', message_token=owner_send['message_token'])
    check(acknowledged['data']['messages'] == [] and acknowledged['data']['sent_message']['id'] == cursor, 'Retry acknowledges a record even after the polling cursor advanced')
    response, fresh = live(owner, own[3], after=cursor, need_message_token='yes')
    check(fresh['data']['message_token'] != owner_send['message_token'] and fresh['data']['messages'] == [], 'A new send can obtain an independent token after a failed request')
    response, empty = live(owner, own[3], after=cursor)
    check(empty['data']['messages'] == [] and empty['data']['cursor'] == cursor and not empty['data']['more'], 'Unchanged polls return only a small empty delta')
    adm = request(admin, admin_path); admin_send = fields(adm[3], 'send_message')
    response, reply = live(admin, adm[3], operation='send_message', message_body='Live admin reply', message_token=admin_send['message_token'])
    response, incoming = live(owner, own[3], after=cursor)
    check(len(incoming['data']['messages']) == 1 and incoming['data']['messages'][0]['body'] == 'Live admin reply' and 'admin' in incoming['data']['messages'][0]['sender'], 'Owner receives only new admin reply with its sender')
    check(incoming['data']['messages'][0]['request_key'] == '', 'Another sender never receives the outgoing reconciliation key')
    response, empty = live(owner, own[3], after=incoming['data']['cursor'])
    check(empty['data']['messages'] == [], 'Advancing cursor prevents redisplaying previous replies')
    for client, page, send_token in [(owner, own[3], token), (admin, adm[3], admin_send['message_token'])]:
        response, oversized = live(client, page, operation='send_message', message_body='x' * 601, message_token=send_token)
        check(response[0] == 422 and not oversized['success'], 'Both parties reject 601 characters through AJAX')
    response, invalid = live(owner, own[3], operation='send_message', message_body=' ', message_token=token)
    check(response[0] == 422 and not invalid['success'], 'AJAX validation rejects empty sends')
    account_path = '/wp-admin/admin.php?page=cammino-tipsters&account_id=' + str(fixtures['users']['one']['id'])
    request(admin, '/wp-admin/admin-post.php', fields(request(admin, account_path)[3], 'disable'))
    response, closed = live(admin, adm[3])
    check(response[0] == 200 and not closed['data']['can_send'], 'Admin live form closes when owner is disabled')
    response, revoked = live(owner, own[3])
    check(response[0] == 403 and not revoked['success'] and 'messages' not in revoked['data'], 'Revoked session cannot continue polling')
    response, rejected = live(admin, adm[3], operation='send_message', message_body='Disabled write', message_token=admin_send['message_token'])
    check(response[0] == 422 and not rejected['data']['can_send'], 'Disabled account rejects AJAX writes as well')
    request(admin, '/wp-admin/admin-post.php', fields(request(admin, account_path)[3], 'enable'))
    login(owner, fixtures['users']['one']['username'], fixtures['password'])
    own = request(owner, path)
    delete = fields(own[3], 'delete_tip'); delete['confirm_delete_tip'] = 'yes'
    request(owner, path, delete)
    response, denied = live(owner, own[3])
    check(response[0] == 404 and 'messages' not in denied['data'], 'Deleting tip revokes existing owner live view')
    response, retained = live(admin, adm[3], after=0)
    check(response[0] == 200 and len(retained['data']['messages']) == 2 and not retained['data']['can_send'], 'Deleted tip remains read-only in admin live view')
    purge = fields(request(admin, account_path + '&view=delete')[3], 'delete'); purge.update(confirm_delete='yes', confirm_username=fixtures['users']['one']['username'])
    request(admin, '/wp-admin/admin-post.php', purge)
    response, missing = live(admin, adm[3])
    check(response[0] == 404, 'Account purge revokes administrator polling')
    print(f'Passed {support.checks} live conversation HTTP checks.')
finally:
    fixture('cleanup', prefix)
