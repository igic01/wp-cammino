import http_support as support
from http_support import *

fixtures = fixture('setup')
prefix = fixtures['prefix']
try:
    one, two, admin, guest = session(), session(), session(), session()
    login(one, fixtures['users']['one']['username'], fixtures['password'])
    login(two, fixtures['users']['two']['username'], fixtures['password'])
    request(admin, '/wp-login.php')
    native = request(admin, '/wp-login.php', {'log': fixtures['users']['admin']['username'], 'pwd': fixtures['password'], 'wp-submit': 'Log In', 'redirect_to': base + '/wp-admin/', 'testcookie': '1'})
    check(native[0] == 200 and '/wp-admin/' in native[1], 'Native administrator login succeeds')
    check(request(guest, '/tipsters/new/')[1].endswith('/tipsters/login/'), 'Anonymous cannot open submission form')
    check(request(admin, '/tipsters/new/')[0] == 403, 'Administrator cannot use tipster submission form')
    form = new_form(one)
    check('email' not in form and 'submission_token' in form, 'Submission needs no email and has retry identifier')
    form.update(title='', short_description='Keep this short text', long_description='Keep this long text')
    result = request(one, '/tipsters/new/', form)
    check(b'role="alert"' in result[3] and b'Keep this short text' in result[3] and b'Keep this long text' in result[3], 'Validation errors preserve entered descriptions')
    bad_nonce = new_form(one); bad_nonce.update(title='Bad CSRF', short_description='Short', long_description='Long', _wpnonce='invalid')
    check(b'role="alert"' in request(one, '/tipsters/new/', bad_nonce)[3], 'Missing or invalid submission nonce rejected')
    form = new_form(one); form.update(title='Private stage two title', short_description='First line\nSecond line', long_description='Long description with a \\ backslash and "quotes".', owner=fixtures['users']['two']['id'], status='approved')
    pdf = b'%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n'
    result = request(one, '/tipsters/new/', form, [('report.pdf', pdf)])
    check(result[0] == 200 and '/tipsters/tip/' in result[1], 'Real PDF upload redirects to saved tip detail')
    tip_url = result[1]
    tip_id = re.search(r'/tip/(\d+)/', tip_url).group(1)
    check('Odoslaný'.encode() in result[3] and b'First line\nSecond line' in result[3] and b'&quot;quotes&quot;' in result[3] and b'\\ backslash' in result[3], 'Saved tip is submitted and text is safely preserved')
    links = [html.unescape(url) for url in re.findall(r'href="([^"]+)"', result[3].decode()) if '/file/' in url]
    check(len(links) == 1, 'Tip detail exposes only authorized download route')
    file_url = links[0]
    file_id = re.search(r'/file/([a-f0-9]{32})/', file_url).group(1)
    download = request(one, file_url)
    check(download[0] == 200 and download[3] == pdf, 'Owner receives exact uploaded bytes')
    check(download[2].get('Content-Type') == 'application/octet-stream' and download[2].get('Content-Disposition', '').startswith('attachment;') and download[2].get('X-Content-Type-Options') == 'nosniff', 'Downloads are attachments with MIME-sniffing blocked')
    check('no-store' in download[2].get('Cache-Control', '') and download[2].get('X-Robots-Tag') == 'noindex, nofollow', 'Download never cached or indexed')
    head = request(one, file_url, method='HEAD')
    check(head[0] == 200 and not head[3] and int(head[2]['Content-Length']) == len(pdf), 'HEAD checks permissions and returns size without body')
    check(request(admin, file_url)[3] == pdf, 'Administrator downloads the same private file')
    native_edit = request(admin, '/wp-admin/post.php?post=' + tip_id + '&action=edit')
    check(native_edit[0] >= 400 and b'not allowed' in native_edit[3] and b'Private stage two title' not in native_edit[3], 'Native administrator editor cannot bypass the private tip workflow')
    check(request(guest, file_url)[0] == 404 and request(two, file_url)[0] == 404, 'Anonymous and other tipster downloads denied')
    check(request(two, tip_url)[0] == 404 and request(guest, tip_url)[1].endswith('/tipsters/login/'), 'Cross-account detail denied and anonymous detail redirects')
    check(request(one, file_url.replace(file_id, 'f' * 32))[0] == 404, 'Altered file ID denied')
    check(request(one, file_url.replace('/tip/' + tip_id + '/', '/tip/999999/'))[0] == 404, 'Altered tip ID denied')
    check(request(one, tip_url, {'title': 'Forged edit', 'status': 'discussion'})[0] == 403, 'Submitted form cannot be edited by crafted POST')
    check(request(one, file_url, {'operation': 'replace'})[0] == 405, 'Download route rejects writes')
    check(request(one, '/tipsters/new/', form, [('report.pdf', pdf)])[1] == tip_url, 'Repeated multipart submission returns the original tip')
    snapshot = fixture('snapshot', prefix)
    check(snapshot['one']['tips'] == 1 and len(snapshot['one']['files']) == 1 and snapshot['two']['tips'] == 0, 'Retry has no duplicate record or upload and cannot forge another owner')
    disk_file = snapshot['one']['files'][0]
    check(disk_file['exists'] and disk_file['path'] != 'report.pdf' and disk_file['path'].encode() not in result[3], 'Random private path stays out of page HTML')
    for path in ['/wp-content/uploads/' + disk_file['path'], '/private-files/' + disk_file['path']]:
        direct = request(guest, path)
        check(pdf not in direct[3] and direct[2].get('Content-Type', '').startswith('text/html'), 'Direct public path cannot serve the private file')
    # Public discovery must not return the private title, even with explicit type/ID queries.
    for path in ['/wp-json/wp/v2/cammino_tip', '/?s=Private', '/?feed=rss2', '/?post_type=cammino_tip', '/?p=' + tip_id, '/wp-sitemap.xml']:
        response = request(guest, path)
        check(b'Private stage two title' not in response[3] and pdf not in response[3], 'No private content in public endpoint ' + path)
    for name, content in [('wrong.pdf', b'Plain text'), ('bad.png', b'<?php echo 1;'), ('bad.exe', b'MZ')]:
        invalid = new_form(one); invalid.update(title='Invalid upload', short_description='Short', long_description='Long')
        check(b'role="alert"' in request(one, '/tipsters/new/', invalid, [(name, content)])[3], 'Invalid upload rejected: ' + name)
    invalid = new_form(one); invalid.update(title='Too many files', short_description='Short', long_description='Long')
    check(b'role="alert"' in request(one, '/tipsters/new/', invalid, [(f'file{i}.pdf', pdf) for i in range(6)])[3], 'Six real uploads rejected')
    # This local test server needs upload_max_filesize >= 10M and post_max_size >= 64M.
    invalid = new_form(one); invalid.update(title='Oversized file', short_description='Short', long_description='Long')
    check(b'role="alert"' in request(one, '/tipsters/new/', invalid, [('large.pdf', pdf + b'0' * (10 * 1024 * 1024))])[3], 'Oversized real upload rejected')
    check(fixture('snapshot', prefix)['one']['tips'] == 1, 'Rejected uploads leave no partial tips')
    other = new_form(two); other.update(title='Second owner private', short_description='Short', long_description='Long')
    second_tip = request(two, '/tipsters/new/', other)
    check(second_tip[0] == 200 and '/tipsters/tip/' in second_tip[1], 'Second account submits without files')
    second = new_form(one); second.update(title='Another private tip', short_description='Short', long_description='Long')
    import base64
    png = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jV3sAAAAASUVORK5CYII=')
    package = io.BytesIO()
    with zipfile.ZipFile(package, 'w', zipfile.ZIP_DEFLATED) as document:
        document.writestr('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>')
        document.writestr('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Test</w:t></w:r></w:p></w:body></w:document>')
    second_result = request(one, '/tipsters/new/', second, [('image.png', png), ('document.docx', package.getvalue())])
    check('/tipsters/tip/' in second_result[1], 'First account submits a second tip with PNG and DOCX uploads')
    multiple_links = [html.unescape(url) for url in re.findall(r'href="([^"]+)"', second_result[3].decode()) if '/file/' in url]
    check(len(multiple_links) == 2 and request(one, multiple_links[0])[3] == png and request(one, multiple_links[1])[3] == package.getvalue(), 'Multiple real uploads download correctly')
    for client, own, foreign in [(one, b'Private stage two title', b'Second owner private'), (two, b'Second owner private', b'Private stage two title')]:
        dashboard = request(client, '/tipsters/')
        check(own in dashboard[3] and foreign not in dashboard[3], 'Dashboard isolates owners')
    check(request(one, '/?cammino_tipsters=tip&cammino_tip_id=' + tip_id)[0] == 200, 'Plain-query detail route works')
    check(request(one, '/?cammino_tipsters=download&cammino_tip_id=' + tip_id + '&cammino_file_id=' + file_id)[3] == pdf, 'Plain-query download route works')
    admin_path = '/wp-admin/admin.php?page=cammino-tipsters&account_id=' + str(fixtures['users']['one']['id'])
    disable = fields(request(admin, admin_path)[3], 'disable')
    request(admin, '/wp-admin/admin-post.php', disable)
    check(request(one, file_url)[0] == 404 and request(one, '/tipsters/new/')[1].endswith('/tipsters/login/'), 'Disabling invalidates downloads and submission access')
    check(fixture('snapshot', prefix)['one']['files'][0]['exists'], 'Disabling retains physical files')
    check(request(admin, file_url)[3] == pdf, 'Disabled owner files remain available to administrator')
    enable = fields(request(admin, admin_path)[3], 'enable'); request(admin, '/wp-admin/admin-post.php', enable)
    login(one, fixtures['users']['one']['username'], fixtures['password'])
    check(request(one, file_url)[3] == pdf, 'Re-enabled owner regains file access')
    deletion = fields(request(admin, admin_path + '&view=delete')[3], 'delete')
    uploaded_paths = [Path(fixtures['storage_root']) / item['path'] for item in fixture('snapshot', prefix)['one']['files']]
    deletion.update(confirm_username=fixtures['users']['one']['username'], confirm_delete='yes')
    deleted = request(admin, '/wp-admin/admin-post.php', deletion)
    check(deleted[0] == 200 and request(admin, file_url)[0] == 404, 'Dedicated account deletion removes private file access')
    check(all(not path.exists() for path in uploaded_paths), 'Account deletion erases every physically uploaded file')
    check('one' not in fixture('snapshot', prefix) and request(two, second_tip[1])[0] == 200, 'Deleting one account preserves the other account and tip')
    print(f'Passed {support.checks} HTTP submission/upload checks.')
finally:
    fixture('cleanup', prefix)
