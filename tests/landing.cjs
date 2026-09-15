const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const html = fs.readFileSync('index.html', 'utf8');
const context = { window: {} };
vm.runInNewContext(fs.readFileSync('js/translations.js', 'utf8'), context);
vm.runInNewContext(fs.readFileSync('js/landing-translations.js', 'utf8'), context);
assert(html.includes('preload="none"'));
assert(html.includes('muted loop playsinline'));
assert(html.includes('data-src="assets/fencing-sculpture-loop.mp4"'));
assert(!html.includes('js/app.js'));
assert.equal((html.match(/<h1\b/g) || []).length, 1);
for (const match of html.matchAll(/(?:src|href|poster|data-src)="([^"#?]+)(?:[?#][^"]*)?"/g)) {
  if (!match[1].startsWith('http')) assert(fs.existsSync(match[1]), match[1]);
}
for (const key of ['A clear mind.', 'A precise touch.', 'Play animation', 'Pause animation', 'The approach']) {
  for (const lang of ['fr', 'ar']) assert(context.window.siteTranslations[lang][key]);
}
assert(html.indexOf('js/landing-translations.js') < html.indexOf('js/i18n.js'));
assert(html.indexOf('js/i18n.js') < html.indexOf('js/landing.js'));
assert(fs.statSync('assets/fencing-sculpture-loop.mp4').size < 3000000);
console.log('PASS: assets, script order, translation keys, metadata structure and video budget.');
