"""Stage 4 real HTTP form/session checks. Arguments match submissions-http.py."""
import http_support as support
from http_support import *


def status_form(body, target):
    for form in re.findall(r'<form\b[^>]*>(.*?)</form>', body.decode(), re.S):
        if f'name="status" value="{target}"' in form:
            return fields(('<form>' + form + '</form>').encode())
    raise AssertionError('Status form missing')


fixtures = fixture('setup')
prefix = fixtures['prefix']
try:
    one, two, admin, guest = session(), session(), session(), session()
    login(one, fixtures['users']['one']['username'], fixtures['password'])
    login(two, fixtures['users']['two']['username'], fixtures['password'])
    request(admin, '/wp-login.php')
    request(admin, '/wp-login.php', {'log': fixtures['users']['admin']['username'], 'pwd': fixtures['password'], 'redirect_to': base + '/wp-admin/', 'testcookie': '1'})
    form = new_form(one)
    form.update(title='Stage four private', short_description='Short', long_description='Long', file_link='javascript:alert(1)')
    rejected = request(one, '/tipsters/new/', form)
    check(b'aria-invalid="true"' in rejected[3] and b'Stage four private' in rejected[3] and fixture('snapshot', prefix)['one']['tips'] == 0, 'Unsafe link rejected with preserved form')
    form['file_link'] = 'https://drive.google.com/drive/folders/example?usp=sharing&key=abc'
    submitted = request(one, '/tipsters/new/', form)
    tip_url = submitted[1]; tip_id = re.search(r'/tip/(\d+)/', tip_url).group(1)
    admin_url = '/wp-admin/admin.php?page=cammino-tips&tip_id=' + tip_id
    check(b'name="operation" value="send_message"' not in submitted[3] and b'name="operation" value="send_message"' not in request(admin, admin_url)[3], 'Submitted conversations have no send form for either party')
    link = 'https://drive.google.com/drive/folders/example?usp=sharing&amp;key=abc'.encode()
    check(link in submitted[3] and link in request(admin, admin_url)[3] and b'rel="noopener noreferrer"' in submitted[3], 'Both parties see safely rendered shared link')
    request(admin, '/wp-admin/admin-post.php', status_form(request(admin, admin_url)[3], 'discussion'))
    admin_message = fields(request(admin, admin_url)[3], 'send_message')
    admin_message['message_body'] = 'Admin first reply\nLine two \\ path and "quotes".'
    bad = dict(admin_message); bad['_wpnonce'] = 'bad'
    check(request(admin, '/wp-admin/admin-post.php', bad)[0] == 403, 'Invalid admin message nonce denied')
    bad = dict(admin_message); bad['message_body'] = ' '
    check(b'role="alert"' in request(admin, '/wp-admin/admin-post.php', bad)[3], 'Admin message validation renders readable error')
    sent = request(admin, '/wp-admin/admin-post.php', admin_message)
    check(sent[0] == 200 and b'Admin first reply\nLine two \\ path' in sent[3] and b'&quot;quotes&quot;' in sent[3], 'Admin sends and safely reads saved reply')
    retry = request(admin, '/wp-admin/admin-post.php', admin_message)
    check(retry[3].count(b'Admin first reply') == 1, 'Admin retry creates one message')
    own = request(one, tip_url)
    check(b'Admin first reply' in own[3] and fixtures['users']['admin']['username'].encode() in own[3], 'Owner sees admin sender and saved reply')
    owner_message = fields(own[3], 'send_message'); owner_message['message_body'] = 'Owner reply <script>alert("bad")</script> & text'
    bad = dict(owner_message); bad['_wpnonce'] = 'bad'
    check(request(one, tip_url, bad)[0] == 403, 'Invalid owner nonce rejected')
    bad = dict(owner_message); bad['message_body'] = 'x' * 5001
    oversized = request(one, tip_url, bad)
    check(b'role="alert"' in oversized[3] and b'x' * 100 in oversized[3], 'Oversized message rejected and text preserved')
    check(fields(oversized[3], 'edit_tip').get('title') == 'Stage four private' and fields(oversized[3], 'edit_tip').get('tip_version'), 'Message errors preserve discussion edit form values and version')
    sent = request(one, tip_url, owner_message)
    check(sent[0] == 200 and b'Owner reply' in sent[3] and b'<script>alert(' not in sent[3] and b'&amp; text' in sent[3], 'Owner message stored and safely escaped')
    check(request(one, tip_url, owner_message)[3].count(b'Owner reply') == 1, 'Owner retry is idempotent')
    check(b'Owner reply' in request(admin, admin_url)[3] and b'name="operation" value="edit_message"' not in sent[3], 'Admin reads owner reply without mutation controls')
    check(request(two, tip_url)[0] == 404 and request(two, tip_url, owner_message)[0] == 404 and request(guest, tip_url)[1].endswith('/tipsters/login/'), 'Other owner and anonymous sessions cannot read or post')
    check(request(two, '/wp-admin/admin-post.php', admin_message)[0] == 403, 'Tipster cannot use administrator message action')
    other_form = new_form(one); other_form.update(title='Another conversation', short_description='Short', long_description='Long')
    other = request(one, '/tipsters/new/', other_form); other_id = re.search(r'/tip/(\d+)/', other[1]).group(1)
    other_admin_url = '/wp-admin/admin.php?page=cammino-tips&tip_id=' + other_id
    request(admin, '/wp-admin/admin-post.php', status_form(request(admin, other_admin_url)[3], 'discussion'))
    other_message = fields(request(one, other[1])[3], 'send_message'); other_message['message_body'] = 'Second conversation only'
    forged = dict(other_message); forged['message_token'] = owner_message['message_token']
    check(b'role="alert"' in request(one, other[1], forged)[3], 'Cross-tip message token rejected')
    request(one, other[1], other_message)
    check(b'Second conversation only' not in request(one, tip_url)[3] and b'Owner reply' not in request(admin, other_admin_url)[3], 'Two conversations remain separate')
    submitted_form = new_form(two); submitted_form.update(title='Still submitted', short_description='Short', long_description='Long')
    closed = request(two, '/tipsters/new/', submitted_form)
    forged = dict(owner_message); forged['message_token'] = fields(request(two, closed[1])[3], 'delete_tip').get('tip_version', '')
    check(request(two, closed[1], forged)[0] == 403, 'Crafted send cannot open submitted conversation')
    request(admin, '/wp-admin/admin-post.php', status_form(request(admin, admin_url)[3], 'approved'))
    approved = request(one, tip_url)
    check(b'name="operation" value="edit_tip"' not in approved[3] and b'name="operation" value="send_message"' in approved[3], 'Approval locks editing while allowing communication')
    approved_message = fields(approved[3], 'send_message'); approved_message['message_body'] = 'Reply after approval'
    request(one, tip_url, approved_message)
    request(one, '/tipsters/', fields(request(one, '/tipsters/')[3], 'logout'))
    login(one, fixtures['users']['one']['username'], fixtures['password'])
    check(b'Reply after approval' in request(one, tip_url)[3], 'Messages survive logout and login')
    # Neither public discovery nor unsupported operation names expose/update messages.
    for path in ['/wp-json/wp/v2/cammino_tip_message', '/?post_type=cammino_tip_message', '/?s=Reply', '/?feed=rss2']:
        check(b'Admin first reply' not in request(guest, path)[3] and b'Reply after approval' not in request(guest, path)[3], 'Public endpoint hides messages: ' + path)
    for operation in ['edit_message', 'delete_message']:
        forged = dict(approved_message); forged['operation'] = operation
        check(request(one, tip_url, forged)[0] == 403, 'Unsupported message mutation rejected: ' + operation)
    account_url = '/wp-admin/admin.php?page=cammino-tipsters&account_id=' + str(fixtures['users']['one']['id'])
    request(admin, '/wp-admin/admin-post.php', fields(request(admin, account_url)[3], 'disable'))
    disabled = request(admin, admin_url)
    check(b'Reply after approval' in disabled[3] and b'name="operation" value="send_message"' not in disabled[3], 'Disabling retains admin history and hides send form')
    check(request(one, tip_url, approved_message)[1].endswith('/tipsters/login/'), 'Disabled owner message submission blocked')
    bad = dict(admin_message); bad['message_body'] = 'Disabled admin write'
    check(b'role="alert"' in request(admin, '/wp-admin/admin-post.php', bad)[3] and b'Disabled admin write' not in request(admin, admin_url)[3], 'Admin cannot post to disabled account')
    request(admin, '/wp-admin/admin-post.php', fields(request(admin, account_url)[3], 'enable'))
    login(one, fixtures['users']['one']['username'], fixtures['password'])
    deletion = fields(request(one, tip_url)[3], 'delete_tip'); deletion['confirm_delete_tip'] = 'yes'
    request(one, tip_url, deletion)
    deleted = request(admin, admin_url)
    check(request(one, tip_url)[0] == 404 and b'Reply after approval' in deleted[3] and b'name="operation" value="send_message"' not in deleted[3], 'Owner deletion retains read-only admin conversation')
    purge = fields(request(admin, account_url + '&view=delete')[3], 'delete'); purge.update(confirm_delete='yes', confirm_username=fixtures['users']['one']['username'])
    request(admin, '/wp-admin/admin-post.php', purge)
    check(request(admin, admin_url)[0] == 404 and request(admin, other_admin_url)[0] == 404 and request(two, closed[1])[0] == 200, 'Full purge removes conversations and preserves unrelated account')
    print(f'Passed {support.checks} HTTP conversation/shared-link checks.')
finally:
    fixture('cleanup', prefix)
