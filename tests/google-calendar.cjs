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
vm.runInNewContext(fs.readFileSync('js/google-calendar.js', 'utf8'), { window, document, URL, URLSearchParams, Intl });
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
