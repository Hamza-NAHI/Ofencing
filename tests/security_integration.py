#!/usr/bin/env python3
"""Security regression: disposable LOCAL PHP/MySQL only. Python + Pillow.
Same OFENCING_TEST_* variables as gallery_integration.py. Deliberately exhausts
login/contact/upload budgets. Never use a real customer database. Server-side
failure/concurrency injection and TLS checks are documented in security-audit.md.
"""
import html
import http.cookiejar
import io
import json
import os
from pathlib import Path
import re
import secrets
import urllib.error
import urllib.parse
import urllib.request
from PIL import Image

BASE = os.environ.get('OFENCING_TEST_BASE_URL', '').rstrip('/') + '/'
if os.environ.get('OFENCING_TEST_ALLOW_WRITES') != 'yes' or urllib.parse.urlparse(BASE).hostname not in ['localhost', '127.0.0.1', '::1']:
    raise SystemExit('Requires an explicitly authorized disposable localhost install.')
REPO = Path(__file__).resolve().parents[1]
APACHE = os.environ.get('OFENCING_TEST_APACHE') == 'yes'
PREFIX = 'security-' + secrets.token_hex(6)

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args): return None

class Client:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, path, expected=200, method='GET', fields=None, body=None, files=None, headers=None, json_body=False):
        headers = dict(headers or {})
        if files is not None:
            boundary = 'boundary-' + secrets.token_hex(10)
            parts = [f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode() for key, value in (fields or {}).items()]
            for name, raw, mime in files:
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="photos[]"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode() + raw + b'\r\n')
            body = b''.join(parts) + f'--{boundary}--\r\n'.encode()
            headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
        elif fields is not None:
            body = (json.dumps(fields) if json_body else urllib.parse.urlencode(fields)).encode()
            headers['Content-Type'] = 'application/json' if json_body else 'application/x-www-form-urlencoded'
        req = urllib.request.Request(BASE + path, data=body, method=method, headers=headers)
        try: response = self.opener.open(req, timeout=30)
        except urllib.error.HTTPError as error: response = error
        raw = response.read()
        assert response.status == expected, (path, response.status, expected, raw[:300])
        assert not response.headers.get('Access-Control-Allow-Origin'), 'No permissive CORS'
        return raw, response.headers

    def token(self, path):
        return re.search(rb'name="csrf_token" value="([a-f0-9]+)"', self.request(path)[0])[1].decode()

    def post(self, path, fields, expected=303, **kwargs):
        return self.request(path, expected, 'POST', fields=fields, **kwargs)

    def login(self):
        token = self.token('admin/login.php')
        before = [(c.name, c.value) for c in self.cookies]
        self.post('admin/login.php', {'csrf_token': token, 'username': os.environ['OFENCING_TEST_USERNAME'], 'password': os.environ['OFENCING_TEST_PASSWORD']})
        assert before != [(c.name, c.value) for c in self.cookies]
        return self.token('admin/')

public = Client()
admin = Client()
mutations = []
albums = []

def api(path): return json.loads(public.request(path)[0])['data']

def create_album():
    _, headers = admin.post('admin/gallery/create.php', {'csrf_token': csrf, 'title': PREFIX, 'description': '', 'event_date': '', 'location': '', 'is_published': 0, 'display_order': 0})
    aid = int(headers['Location'].rsplit('=', 1)[1]); albums.append(aid); return aid

def upload(aid, files, expected=201, extra=None, client=admin, token=None):
    raw, headers = client.request(f'admin/gallery/upload.php?id={aid}', expected, 'POST', fields={'csrf_token': csrf if token is None else token, **(extra or {})}, files=files, headers={'Accept': 'application/json'})
    assert headers.get_content_type() == 'application/json'
    return json.loads(raw)

try:
    # Enumerate every actual Admin PHP route: navigation visibility is irrelevant.
    count = 0
    for path in sorted((REPO / 'admin').rglob('*.php')):
        if path.name.startswith('_') or path.name in ['login.php', 'setup.php']: continue
        route = path.relative_to(REPO).as_posix()
        public.request(route, 303)
        public.post(route, {'is_admin': 1, 'role': 'admin', 'csrf_token': 'forged'}, 303)
        count += 1
    for route in ['api/events.php', 'api/training.php']:
        public.post(route, {'action': 'delete', 'id': 1, 'is_admin': True}, 401, json_body=True)
    for route, allowed in [('api/events.php', 'POST'), ('api/training.php', 'POST'), ('api/gallery.php', 'GET'), ('api/messages.php', 'POST')]:
        for method in ['DELETE', 'PUT', 'PATCH', 'OPTIONS']:
            _, headers = public.request(route, 405, method)
            assert headers['Allow'] == allowed
    public.request('api/messages.php', 405)
    public.request('admin/gallery/upload.php?id=1', 401, 'POST', fields={}, headers={'Accept': 'application/json'})
    print(f'PASS {count} direct Admin routes: unauthenticated GET/POST, privileged API forgery, unsupported methods')

    raw, headers = Client().request('admin/login.php')
    cookie = headers.get('Set-Cookie', '')
    cookie_flags = [flag.strip().lower() for flag in cookie.split(';')[1:]]
    assert 'httponly' in cookie_flags and 'samesite=lax' in cookie_flags and 'secure' not in cookie_flags
    name = cookie.split('=', 1)[0]
    fixed = 'a' * 32
    _, fixation_headers = Client().request('admin/login.php', headers={'Cookie': name + '=' + fixed})
    assert name + '=' + fixed not in fixation_headers.get('Set-Cookie', '')
    csrf = admin.login()
    assert re.fullmatch('[a-f0-9]{64}', csrf)
    for route in ['admin/', 'admin/messages/', 'admin/gallery/', 'api/messages.php']:
        _, headers = admin.request(route, 405 if route == 'api/messages.php' else 200)
        assert 'no-store' in headers['Cache-Control']
        assert headers['X-Frame-Options'] == 'DENY'
    print('PASS cookie flags, strict fixation refusal, rotated session/CSRF and private response caching')

    event = {'csrf_token': csrf, 'action': 'create', 'title': PREFIX + ' <script>alert(1)</script>', 'event_date': '2099-09-21', 'description': "' OR 1=1 --", 'location': '../../config/database.php', 'type': 'Épée', 'is_published': 1}
    raw, _ = admin.post('api/events.php', {**event, 'id': 999999999, 'is_admin': True, 'created_at': '1900-01-01'}, 201, json_body=True)
    eid = json.loads(raw)['data']['id']; mutations.append(('events', eid))
    assert eid != 999999999 and set(json.loads(raw)['data']) == {'id'}
    slot = {'csrf_token': csrf, 'action': 'create', 'day_of_week': 1, 'start_time': '10:00', 'end_time': '11:00', 'type': PREFIX, 'location': '', 'display_order': 0, 'is_active': 1}
    raw, _ = admin.post('api/training.php', slot, 201, json_body=True)
    tid = json.loads(raw)['data']['id']; mutations.append(('training', tid))
    aid = create_album()
    for route, fields in [
        ('admin/events/create.php', event), (f'admin/events/edit.php?id={eid}', event), (f'admin/events/delete.php?id={eid}', {}),
        ('admin/training/create.php', slot), (f'admin/training/edit.php?id={tid}', slot), (f'admin/training/delete.php?id={tid}', {}),
        ('admin/gallery/create.php', {}), (f'admin/gallery/edit.php?id={aid}', {}), (f'admin/gallery/delete-album.php?id={aid}', {}),
        ('admin/gallery/publish.php', {'album_id': aid}), ('admin/gallery/reorder.php', {}), ('admin/gallery/set-cover.php', {}),
        ('admin/gallery/save-photo.php', {}), ('admin/messages/toggle-read.php', {}), ('admin/logout.php', {})]:
        for bad in ['', 'invalid']:
            admin.post(route, {**fields, 'csrf_token': bad}, 403)
    for route in ['api/events.php', 'api/training.php']:
        admin.post(route, {'action': 'delete', 'id': 1, 'csrf_token': 'invalid'}, 403, json_body=True)
    upload(aid, [], 403, token='invalid')
    print('PASS missing/invalid CSRF across event/training/album/photo/message/logout mutations')

    for bad_id in [-1, 0, True, 1.5, '1 OR 1=1', '../../config/database.php', '', [], {}]:
        admin.post('api/events.php', {'csrf_token': csrf, 'action': 'delete', 'id': bad_id}, 422, json_body=True)
    admin.post('api/events.php', {'csrf_token': csrf, 'action': 'delete', 'id': 999999999}, 404, json_body=True)
    for raw in [b'{', b'[]', b'null', b'{"x":' + b'[' * 40 + b'0' + b']' * 40 + b'}']:
        admin.request('api/events.php', 400, 'POST', body=raw, headers={'Content-Type': 'application/json', 'X-CSRF-Token': csrf})
    for change in [{'event_date': '2026-02-30'}, {'title': ' '}, {'title': 'x' * 201}, {'is_published': 2}, {'title': []}]:
        admin.post('api/events.php', {**event, **change}, 422, json_body=True)
    for change in [{'day_of_week': 8}, {'start_time': '24:00'}, {'end_time': '09:00'}, {'is_active': 'yes'}]:
        admin.post('api/training.php', {**slot, **change}, 422, json_body=True)
    admin.request('api/events.php', 415, 'POST', body=b'bad', headers={'Content-Type': 'text/plain', 'X-CSRF-Token': csrf})
    for route in ['api/events.php', 'admin/events/create.php', 'admin/login.php']:
        admin.request(route, 413, 'POST', body=b'x' * 32769, headers={'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrf})
    assert any(row['title'] == event['title'] for row in api('api/events.php'))
    raw, _ = admin.request('admin/events/')
    assert html.escape(event['title']).encode() in raw and event['title'].encode() not in raw
    print('PASS SQL-like strings stay data; strict IDs, dates, times, enums, JSON schema/depth, body caps and stored HTML escaping')

    assert all(set(row) == {'event_date', 'title', 'description', 'location', 'type'} for row in api('api/events.php'))
    assert all(set(row) == {'day_of_week', 'start_time', 'end_time', 'type', 'location'} for row in api('api/training.php'))
    admin.post('api/events.php', {**event, 'action': 'update', 'id': eid, 'event_date': '2000-01-01'}, 200, json_body=True)
    assert not any(row['title'] == event['title'] for row in api('api/events.php'))
    assert event['title'].encode() not in public.request('api/events.php?include_past=1&is_published=0')[0]
    public.request(f'api/gallery.php?album={aid}', 404)
    admin.request(f'api/gallery.php?album={aid}', 404)
    b = io.BytesIO(); Image.new('RGB', (80, 60), 'red').save(b, 'PNG'); photo = b.getvalue()
    result = upload(aid, [('photo.png', photo, 'application/x-httpd-php')], extra={'width': '99999', 'height': '99999', 'file_size': '1', 'filename': '../../config/local.php', 'album_id': '999999999', 'is_published': '1'})
    stored = result['data']['results'][0]
    assert (stored['width'], stored['height']) == (80, 60)
    public.request(f'api/gallery.php?album={aid}', 404)
    pid = stored['id']
    admin.post(f'admin/gallery/delete-photo.php?album={aid}&id={pid}', {'csrf_token': 'bad'}, 403)
    second_album = create_album()
    for route in ['save-photo.php', 'set-cover.php', 'reorder.php']:
        admin.post('admin/gallery/' + route, {'csrf_token': csrf, 'album_id': second_album, 'photo_id': pid, 'caption': '', 'alt_text': '', 'direction': 'up'}, 404)
    admin.post('admin/gallery/publish.php', {'csrf_token': csrf, 'album_id': aid, 'is_published': 1})
    detail = api(f'api/gallery.php?album={aid}')
    assert set(detail['photos'][0]) == {'caption', 'alt_text', 'thumbnail_url', 'image_url'}
    assert set(detail['album']['cover']) == {'thumbnail_url', 'alt_text'}
    assert 'slug' not in detail['album']
    for filename, payload in [('shell.php', photo), ('polyglot.jpg', photo), ('shell.phtml', photo), ('bad.webp', b'RIFF0000WEBP'), ('bad.svg', b'<svg onload="alert(1)"/>')]:
        upload(aid, [(filename, payload, 'image/jpeg')], 422)
    assert len(api(f'api/gallery.php?album={aid}')['photos']) == 1
    print('PASS minimized public fields, server-side upcoming filter, draft privacy, cross-album refusal and forged upload metadata/format rejection')

    message = {'name': PREFIX, 'contact': 'audit@example.com', 'level': 'Beginner', 'message': '<script>alert(1)</script>'}
    public.post('api/messages.php', {**message, 'website': 'spam.example'}, 422, json_body=True)
    public.post('api/messages.php', {**message, 'is_read': 1, 'created_at': '1900-01-01'}, 201, json_body=True)
    listing = admin.request('admin/messages/?filter=unread')[0].decode()
    row = next(r for r in re.findall(r'<tr[^>]*>(.*?)</tr>', listing, re.S) if PREFIX in r)
    mid = int(re.search(r'view\.php\?id=(\d+)', row)[1])
    raw, _ = admin.request(f'admin/messages/view.php?id={mid}')
    assert b'&lt;script&gt;alert(1)&lt;/script&gt;' in raw
    admin.post(f'admin/messages/delete.php?id={mid}', {'csrf_token': 'bad'}, 403)
    admin.post(f'admin/messages/delete.php?id={mid}', {'csrf_token': csrf})
    public.request(f'api/messages.php?id={mid}', 405)
    print('PASS honeypot, message metadata tampering, private unread inbox, escaped stored XSS and protected deletion')

    if APACHE:
        for route in ['index.html', 'admin/login.php', 'api/events.php', 'nested/missing/path']:
            _, headers = public.request(route, 404 if route.startswith('nested') else 200)
            policy = headers['Content-Security-Policy']
            assert "script-src 'self'" in policy and "frame-ancestors 'none'" in policy
            assert 'unsafe-inline' not in policy and 'unsafe-eval' not in policy
            assert 'https://fonts.googleapis.com' in policy and 'https://fonts.gstatic.com' in policy
            assert headers['X-Content-Type-Options'] == 'nosniff' and headers['Permissions-Policy']
        for route in ['config/local.php', 'config/security.php', 'database/schema.sql', 'docs/security-audit.md', '.git/config', '.env', 'uploads/', 'config/', 'database/']:
            public.request(route, 403)
        # Harmless canaries, never real credentials; only create paths owned by this run.
        for suffix in ['.php.old', '.php.bak', '.sql.gz', '.zip', '.PHP.OLD', '.php~']:
            path = REPO / (PREFIX + suffix)
            assert not path.exists()
            try:
                path.write_text('harmless audit canary')
                public.request(path.name, 403)
            finally: path.unlink(missing_ok=True)
        for suffix in ['.php', '.phtml', '.phar', '.php5', '.htaccess']:
            path = REPO / 'uploads' / (PREFIX + suffix)
            assert not path.exists()
            try:
                path.write_text('<?php echo "must-not-execute";')
                public.request('uploads/' + path.name, 403)
            finally: path.unlink(missing_ok=True)
        print('PASS Apache CSP/headers, backups/dotfiles/private files/index denial and uploaded script execution denial')

    # Upload allowance is per authenticated account, not browser/JavaScript/session.
    # One valid + five rejected images above have already consumed six slots.
    for _ in range(94):
        upload(aid, [('bad.jpg', b'not an image', 'image/jpeg')], 422)
    upload(aid, [('photo.png', photo, 'image/png')], 429)
    other = Client(); other_csrf = other.login()
    result = upload(aid, [('photo.png', photo, 'image/png')], 429, client=other, token=other_csrf)
    assert result['success'] is False
    print('PASS server-side upload throttle persists across a new authenticated session')

    attacker = Client(); attack_csrf = attacker.token('admin/login.php')
    for _ in range(10):
        attacker.post('admin/login.php', {'csrf_token': attack_csrf, 'username': "' OR 1=1 --", 'password': 'not-the-password'}, 401)
    _, headers = attacker.post('admin/login.php', {'csrf_token': attack_csrf, 'username': os.environ['OFENCING_TEST_USERNAME'], 'password': 'wrong'}, 429)
    assert int(headers['Retry-After']) > 0
    print('PASS generic failed-login response and temporary brute-force lockout')
finally:
    if 'csrf' in globals():
        for resource, rid in mutations:
            admin.post('api/' + resource + '.php', {'csrf_token': csrf, 'action': 'delete', 'id': rid}, 200, json_body=True)
        for aid in albums:
            admin.post(f'admin/gallery/delete-album.php?id={aid}', {'csrf_token': csrf})

admin.post('admin/logout.php', {'csrf_token': csrf})
assert not list(admin.cookies)
admin.request('admin/', 303)
print('ALL SECURITY INTEGRATION CHECKS PASSED')
