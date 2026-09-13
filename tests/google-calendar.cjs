const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
function element() {
  return { children: [], append(...items) { this.children.push(...items); }, replaceChildren() { this.children = []; } };
}
const container = element();
const listeners = {};
const window = { coachCalendar: {}, siteI18n: { language: 'fr', t: value => value } };
const document = {
  getElementById: () => container,
  createElement: element,
  addEventListener: (name, listener) => { listeners[name] = listener; }
};
vm.runInNewContext(fs.readFileSync('js/google-calendar.js', 'utf8'), { window, document, URL, URLSearchParams, Intl, atob });
const { calendarURL, renderCalendar } = window.ONescrimeCalendar;
assert.match(container.children[0].textContent, /available soon/);
assert.equal(calendarURL({}, 'fr'), null);
const id = 'training+qa@group.calendar.google.com';
const url = new URL(calendarURL({ calendarId: id }, 'ar'));
assert.equal(url.origin, 'https://calendar.google.com');
assert.equal(url.searchParams.get('src'), id);
assert.equal(url.searchParams.get('hl'), 'ar');
assert.equal(url.searchParams.get('ctz'), 'Africa/Casablanca');
assert.equal(url.searchParams.get('mode'), 'AGENDA');
assert.equal(new URL(calendarURL({ embedUrl: url.href }, 'en')).searchParams.get('src'), id);
for (const embedUrl of ['https://evil.example/calendar/embed?src=x@y', 'javascript:alert(1)', 'https://calendar.google.com/calendar/ical/secret/basic.ics']) {
  assert.throws(() => calendarURL({ embedUrl }, 'fr'));
}
assert.throws(() => calendarURL({ calendarId: '<script>@test' }, 'fr'));
assert.throws(() => calendarURL({ calendarId: id, timeZone: 'wrong' }, 'fr'));
window.coachCalendar = { calendarId: id };
renderCalendar();
let frame = container.children[0].children[0];
assert.equal(frame.loading, 'lazy');
assert.match(frame.title, /Google Calendar/);
assert.equal(container.children[1].children[0].rel, 'noopener noreferrer');
window.siteI18n.language = 'ar';
listeners.languagechange();
frame = container.children[0].children[0];
assert.equal(new URL(frame.src).searchParams.get('hl'), 'ar');
window.coachCalendar = { embedUrl: 'https://evil.example' };
renderCalendar();
assert.match(container.children[0].textContent, /available soon/);
const html = fs.readFileSync('index.html', 'utf8');
assert(html.indexOf('js/calendar-config.js') < html.indexOf('js/google-calendar.js'));
assert(!fs.readFileSync('js/app.js', 'utf8').includes('siteData.training'));
console.log('PASS: configuration, safe Google URLs, timezone, languages, iframe rendering, fallback and script wiring.');

// Exercise the checked-in configuration: this caught the original sharing-link failure.
vm.runInNewContext(fs.readFileSync('js/calendar-config.js', 'utf8'), { window });
const configuredId = 'deec69a1adb75c123213c0f8eb97d6a46d9e5981351f763a507016f27027fcf9@group.calendar.google.com';
for (const language of ['fr', 'en', 'ar']) {
  window.siteI18n.language = language;
  renderCalendar();
  const configuredFrame = container.children[0].children[0];
  const configuredURL = new URL(configuredFrame.src);
  assert.equal(configuredURL.pathname, '/calendar/embed');
  assert.equal(configuredURL.searchParams.get('src'), configuredId);
  assert.equal(configuredURL.searchParams.get('hl'), language);
}
for (const path of ['/calendar', '/calendar/', '/calendar/u/0', '/calendar/u/1/']) {
  for (const encoding of ['base64', 'base64url']) {
    const share = new URL('https://calendar.google.com' + path);
    share.searchParams.set('cid', Buffer.from(id).toString(encoding));
    assert.equal(new URL(calendarURL({ embedUrl: share.href }, 'fr')).searchParams.get('src'), id);
  }
}
for (const embedUrl of [
  'https://calendar.google.com/calendar/u/0?cid=%%%invalid',
  'https://calendar.google.com/calendar/u/0?cid=YWJj',
  'https://calendar.google.com/calendar/u/0',
  'https://calendar.google.com/calendar/embed',
  'https://calendar.google.com.evil.example/calendar/u/0?cid=YWJj',
  'https://user:pass@calendar.google.com/calendar/u/0?cid=YWJj',
  'http://calendar.google.com/calendar/u/0?cid=YWJj'
]) assert.throws(() => calendarURL({ embedUrl }, 'fr'));
assert.equal(new URL(calendarURL({ calendarId: id, embedUrl: 'invalid' }, 'en')).searchParams.get('src'), id);
console.log('PASS: actual QA configuration, sharing links, Base64 variants and invalid links.');
