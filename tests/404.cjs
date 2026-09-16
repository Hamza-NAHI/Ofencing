const fs = require('node:fs');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const html = fs.readFileSync('404.html', 'utf8');
assert(html.includes('content="noindex, follow"'));
assert.equal((html.match(/<h1>/g) || []).length, 1);
assert(!html.includes('http-equiv="refresh"'));
const urls = [...html.matchAll(/(?:src|href)="([^"]+)"/g)].map(m => m[1]);
for (const base of ['https://example.com/missing', 'https://example.com/deep/missing/page']) {
  for (const path of urls) {
    assert(path.startsWith('/'), '404 resources must be root-relative');
    const url = new URL(path, base);
    assert(fs.existsSync('.' + url.pathname), url.pathname);
  }
}
const window = {};
vm.runInNewContext(fs.readFileSync('js/translations.js', 'utf8'), {window});
vm.runInNewContext(fs.readFileSync('js/404-translations.js', 'utf8'), {window});
for (const key of ['Off the piste.', 'Back to the piste', 'Contact Omar', '404 · Off the piste — ONescrime']) {
  for (const lang of ['fr','ar']) assert(window.siteTranslations[lang][key]);
}
assert(html.indexOf('/js/404-translations.js') < html.indexOf('/js/i18n.js'));
assert(!fs.readFileSync('sitemap.xml','utf8').includes('404.html'));
console.log('PASS: nested-path asset/navigation resolution, translations, script order, noindex, no redirect and sitemap exclusion.');
