#!/usr/bin/env python3
"""HTTP integration checks against an isolated, disposable LOCAL PHP/MySQL install.

No third-party Python dependencies. Never run against a production database.
Required env: OFENCING_TEST_BASE_URL, OFENCING_TEST_USERNAME, OFENCING_TEST_PASSWORD,
OFENCING_TEST_ALLOW_WRITES=yes. Optional OFENCING_TEST_SETUP_TOKEN creates the first
admin via the real setup form. OFENCING_TEST_APACHE=yes tests .htaccess/404 as well.
The contact-rate-limit check exhausts that local IP's allowance for ten minutes.
"""
import html
import http.cookiejar
import json
import os
import re
import secrets
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('OFENCING_TEST_BASE_URL', '')
if (os.environ.get('OFENCING_TEST_ALLOW_WRITES') != 'yes'
        or urllib.parse.urlparse(BASE).hostname not in ('localhost', '127.0.0.1', '::1')):
    raise SystemExit('Use an explicitly authorized, disposable localhost installation (see module docstring).')
BASE = BASE.rstrip('/') + '/'
USERNAME = os.environ['OFENCING_TEST_USERNAME']
PASSWORD = os.environ['OFENCING_TEST_PASSWORD']
PREFIX = 'integration-' + secrets.token_hex(5)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Client:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, path, expected=200, method='GET', data=None, as_json=False, headers=None, raw=None):
        headers = dict(headers or {})
        if data is not None:
            if as_json:
                raw = json.dumps(data).encode()
                headers['Content-Type'] = 'application/json'
            else:
                raw = urllib.parse.urlencode(data).encode()
                headers['Content-Type'] = 'application/x-www-form-urlencoded'
        req = urllib.request.Request(BASE + path, data=raw, headers=headers, method=method)
        try:
            response = self.opener.open(req, timeout=20)
        except urllib.error.HTTPError as error:
            response = error
        text = response.read().decode('utf-8')
        assert response.status == expected, (method, path, response.status, expected, text[:400])
        if path.split('?')[0] in ['api/events.php', 'api/training.php', 'api/messages.php']:
            assert response.headers.get_content_type() == 'application/json', text[:100]
            body = json.loads(text)
            assert body['success'] is (200 <= expected < 300), body
        return text, response.headers

    def token(self, path):
        text, _ = self.request(path)
        return token(text)

    def cookie(self):
        return [(c.name, c.value, c.path) for c in self.cookies]


def token(text):
    return re.search(r'name="csrf_token" value="([a-f0-9]+)"', text)[1]


def data(path):
    return json.loads(public.request(path)[0])['data']


def admin_record_id(resource, text):
    listing = admin.request('admin/' + resource + '/')[0]
    row = next(row for row in re.findall(r'<tr>(.*?)</tr>', listing, re.S) if html.escape(text, quote=True) in row)
    return int(re.search(r'edit\.php\?id=(\d+)', row)[1])


def post(client, path, values, expected=303, as_json=False):
    return client.request(path, expected, 'POST', values, as_json)


public = Client()
admin = Client()
for page in ['admin/', 'admin/events/', 'admin/events/create.php', 'admin/events/edit.php?id=1',
             'admin/events/delete.php?id=1', 'admin/training/', 'admin/training/create.php',
             'admin/training/edit.php?id=1', 'admin/training/delete.php?id=1',
             'admin/messages/', 'admin/messages/view.php?id=1', 'admin/messages/delete.php?id=1',
             'admin/messages/toggle-read.php', 'admin/logout.php']:
    _, headers = public.request(page, 303)
    assert headers['Location'].endswith('/admin/login.php')
for endpoint in ['api/events.php', 'api/training.php']:
    assert isinstance(data(endpoint), list)
    for action in ['create', 'update', 'delete']:
        post(public, endpoint, {'action': action, 'id': 1}, 401, True)
    public.request(endpoint, 405, 'DELETE')
public.request('api/messages.php', 405)
print('PASS public reads, private pages, unauthenticated writes and message privacy')

setup_secret = os.environ.get('OFENCING_TEST_SETUP_TOKEN')
if setup_secret:
    csrf = admin.token('admin/setup.php')
    post(admin, 'admin/setup.php', {'csrf_token': csrf, 'setup_token': 'wrong'}, 403)
    post(admin, 'admin/setup.php', {'csrf_token': csrf, 'setup_token': setup_secret,
                                  'username': USERNAME, 'password': PASSWORD,
                                  'password_confirm': PASSWORD})
    admin.request('admin/setup.php', 404)
    print('PASS token-gated first admin and setup lockout')
csrf = admin.token('admin/login.php')
post(admin, 'admin/login.php', {'username': USERNAME, 'password': PASSWORD}, 403)
post(admin, 'admin/login.php', {'csrf_token': csrf, 'username': USERNAME, 'password': 'incorrect'}, 401)
before = admin.cookie()
post(admin, 'admin/login.php', {'csrf_token': csrf, 'username': USERNAME, 'password': PASSWORD})
assert before != admin.cookie(), 'Session must rotate after login'
csrf = admin.token('admin/')
admin.request('api/messages.php', 405)
for endpoint in ['api/events.php', 'api/training.php']:
    post(admin, endpoint, {'action': 'create'}, 403, True)
    post(admin, endpoint, {'csrf_token': csrf, 'action': 'delete', 'id': '1 OR 1=1'}, 422, True)
    post(admin, endpoint, {'csrf_token': csrf, 'action': 'delete', 'id': 2147483647}, 404, True)
admin.request('admin/events/edit.php?id[]=1', 422)
admin.request('admin/messages/?filter[]=all', 422)
print('PASS login, password verification, session rotation, CSRF and ID validation')

xss = '<img src=x onerror=alert(1)> مرحباً'
event = {'event_date': '2099-10-24', 'title': PREFIX + ' ' + xss, 'description': 'Test <script>alert(1)</script>',
         'location': 'Rabat', 'type': 'Épée', 'is_published': '1', 'csrf_token': csrf}
post(admin, 'admin/events/create.php', {**event, 'event_date': '2099-02-31'}, 422)
post(admin, 'admin/events/create.php', event)
created = next(row for row in data('api/events.php') if row['title'] == event['title'])
event_id = admin_record_id('events', event['title'])
assert [row['event_date'] for row in data('api/events.php')] == sorted(row['event_date'] for row in data('api/events.php'))
text, _ = admin.request('admin/events/')
assert html.escape(xss, quote=True) in text and xss not in text
text, _ = admin.request(f'admin/events/delete.php?id={event_id}')
assert 'Confirmer la suppression' in text
assert any(row['title'] == event['title'] for row in data('api/events.php')), 'GET must not delete'
post(admin, f'admin/events/edit.php?id={event_id}', {**event, 'is_published': '0'})
assert not any(row['title'] == event['title'] for row in data('api/events.php'))
post(admin, 'api/events.php', {**event, 'id': event_id, 'action': 'update'}, 200, True)
assert any(row['title'] == event['title'] for row in data('api/events.php'))
text, _ = post(admin, 'api/events.php', {**event, 'title': PREFIX + '-draft', 'is_published': False}, 201, True)
draft_id = json.loads(text)['data']['id']
assert not any(row['title'] == PREFIX + '-draft' for row in data('api/events.php'))
post(admin, 'api/events.php', {'csrf_token': csrf, 'action': 'delete', 'id': draft_id}, 200, True)
post(admin, f'admin/events/delete.php?id={event_id}', {'csrf_token': 'wrong'}, 403)
post(admin, f'admin/events/delete.php?id={event_id}', {'csrf_token': csrf})
assert not any(row['title'] == event['title'] for row in data('api/events.php'))
print('PASS events form/API CRUD, chronological order, publishing, escaping and confirmed deletion')

slot = {'day_of_week': 7, 'start_time': '10:00', 'end_time': '12:00', 'type': PREFIX,
        'location': 'الرباط', 'display_order': 1, 'is_active': 1, 'csrf_token': csrf}
post(admin, 'admin/training/create.php', {**slot, 'end_time': '09:00'}, 422)
post(admin, 'admin/training/create.php', slot)
slot_id = admin_record_id('training', PREFIX)
post(admin, f'admin/training/edit.php?id={slot_id}', {**slot, 'is_active': 0})
assert not any(row['type'] == PREFIX for row in data('api/training.php'))
post(admin, 'api/training.php', {**slot, 'id': slot_id, 'action': 'update', 'display_order': 0}, 200, True)
assert data('api/training.php')[0]['type'] == PREFIX
text, _ = post(admin, 'api/training.php', {**slot, 'type': PREFIX + '-inactive', 'is_active': 0}, 201, True)
other_id = json.loads(text)['data']['id']
assert not any(row['type'] == PREFIX + '-inactive' for row in data('api/training.php'))
post(admin, 'api/training.php', {'csrf_token': csrf, 'action': 'delete', 'id': other_id}, 200, True)
admin.request(f'admin/training/delete.php?id={slot_id}')
post(admin, f'admin/training/delete.php?id={slot_id}', {'csrf_token': csrf})
assert not any(row['type'] == PREFIX for row in data('api/training.php'))
print('PASS training form/API CRUD, time validation, active visibility and display order')

message = {'name': PREFIX, 'contact': 'test@example.com', 'level': 'Beginner', 'message': xss}
post(public, 'api/messages.php', {**message, 'contact': 'not-a-contact'}, 422, True)
post(public, 'api/messages.php', {**message, 'name': '\u00a0 '}, 422, True)
public.request('api/messages.php', 400, 'POST', raw=b'{', headers={'Content-Type': 'application/json'})
post(public, 'api/messages.php', message, 201, True)
post(public, 'api/messages.php', {**message, 'name': PREFIX + '-phone', 'contact': '+212 (6) 12 34 56 78'}, 201)
_, headers = post(public, 'api/messages.php', message, 429, True)
assert int(headers['Retry-After']) > 0
text, _ = admin.request('admin/messages/?filter=unread')
assert PREFIX in text and text.index(PREFIX + '-phone') < text.index('>' + PREFIX + '<')
ids = re.findall(r'view\.php\?id=(\d+)', text)
matched = []
for message_id in ids:
    text, _ = admin.request(f'admin/messages/view.php?id={message_id}')
    if PREFIX not in text:
        continue
    assert html.escape(xss, quote=True) in text and xss not in text
    matched.append(message_id)
    post(admin, 'admin/messages/toggle-read.php', {'id': message_id, 'is_read': 1}, 403)
    post(admin, 'admin/messages/toggle-read.php', {'csrf_token': csrf, 'id': message_id, 'is_read': 1})
    assert PREFIX in admin.request('admin/messages/?filter=read')[0]
    post(admin, 'admin/messages/toggle-read.php', {'csrf_token': csrf, 'id': message_id, 'is_read': 0})
    admin.request(f'admin/messages/delete.php?id={message_id}')
    post(admin, f'admin/messages/delete.php?id={message_id}', {'csrf_token': csrf})
assert len(matched) == 2
print('PASS contact validation, JSON/form bodies, Unicode, real inbox, newest first, read filters, deletion and throttling')

admin.request('admin/logout.php', 405)
post(admin, 'admin/logout.php', {}, 403)
post(admin, 'admin/logout.php', {'csrf_token': csrf})
assert not admin.cookie(), 'Logout should remove the session cookie'
admin.request('admin/', 303)
post(admin, 'api/events.php', {'csrf_token': csrf, 'action': 'delete', 'id': 1}, 401, True)
print('PASS CSRF-protected logout and loss of access')

if os.environ.get('OFENCING_TEST_APACHE') == 'yes':
    for path in ['config/local.php', 'config/database.php', 'database/schema.sql', 'tests/integration.py',
                 '.git/config', 'api/_common.php', 'admin/_content.php']:
        public.request(path, 403)
    text, _ = public.request('nested/missing/page', 404)
    assert 'Off the piste.' in text
    base_path = urllib.parse.urlparse(BASE).path
    assert f'href="{base_path}styles2.css"' in text
    print('PASS Apache private-file protection and nested/subdirectory branded 404')
print('ALL INTEGRATION CHECKS PASSED')
