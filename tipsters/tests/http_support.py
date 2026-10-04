"""Real HTTP uploads against an opted-in disposable WordPress installation.

python tipsters/tests/submissions-http.py --php /path/to/php --wp-load /path/to/wp-load.php --url http://127.0.0.1:8765
Creates temporary accounts through the guarded CLI helper; cleans them in finally.
"""
import argparse
import html
import http.cookiejar
import io
import json
from pathlib import Path
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
import uuid
import zipfile

parser = argparse.ArgumentParser()
parser.add_argument('--php', required=True)
parser.add_argument('--wp-load', required=True)
parser.add_argument('--url', required=True)
args = parser.parse_args()
base = args.url.rstrip('/')
helper = Path(__file__).with_name('http-fixtures.php')
checks = 0

def fixture(mode, prefix=None):
    cmd = [args.php, str(helper), args.wp_load, mode] + ([prefix] if prefix else [])
    return json.loads(subprocess.check_output(cmd, text=True, encoding='utf-8'))

def session():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def request(client, path, data=None, files=None, method=None):
    headers = {}
    if files is not None:
        boundary = 'Cammino' + uuid.uuid4().hex
        body = bytearray()
        for key, value in data.items():
            body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
        for name, content in files:
            body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="tip_files[]"; filename="{name}"\r\nContent-Type: application/octet-stream\r\n\r\n'.encode())
            body.extend(content); body.extend(b'\r\n')
        body.extend(f'--{boundary}--\r\n'.encode())
        headers['Content-Type'] = f'multipart/form-data; boundary={boundary}'
    else:
        body = urllib.parse.urlencode(data).encode() if data is not None else None
    req = urllib.request.Request(path if path.startswith('http') else base + path, data=body, headers=headers, method=method)
    try:
        res = client.open(req, timeout=30)
    except urllib.error.HTTPError as error:
        res = error
    return res.status, res.geturl(), dict(res.headers), res.read()

def fields(body, operation=None):
    text = body.decode('utf-8')
    for form in re.findall(r'<form\b[^>]*>(.*?)</form>', text, re.S):
        values = {}
        for tag in re.findall(r'<input\b[^>]*>', form):
            attrs = dict(re.findall(r'([\w-]+)="([^"]*)"', tag))
            if attrs.get('type') in ('checkbox', 'radio') and not re.search(r'\schecked(?:\s|=|>)', tag):
                continue
            if 'name' in attrs:
                values[attrs['name']] = html.unescape(attrs.get('value', ''))
        if operation is None or values.get('operation') == operation:
            return values
    raise AssertionError('Expected form missing: ' + str(operation))

def check(condition, message):
    global checks
    checks += 1
    if not condition:
        raise AssertionError(message)

def new_form(client):
    return fields(request(client, '/tipsters/new/')[3], 'submit_tip')

def login(client, user, password):
    form = fields(request(client, '/tipsters/login/')[3])
    form.update(username=user, password=password)
    result = request(client, '/tipsters/login/', form)
    check(result[0] == 200 and result[1].endswith('/tipsters/'), 'Tipster login succeeds')

