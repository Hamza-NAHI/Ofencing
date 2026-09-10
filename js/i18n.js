// Keep the existing static HTML as the English fallback. Translate text nodes
// in place so switching languages never replaces controls or user input.
(() => {
  const languages = ["fr", "en", "ar"];
  const storageKey = "ofencing.language";
  const normalize = text => text.replace(/\s+/g, " ").trim();
  const dictionaries = window.siteTranslations || {};
  const supported = value => languages.includes(value);
  const textRecords = [];
  const attributeRecords = [];
  let language = "fr";

  try {
    const saved = localStorage.getItem(storageKey);
    if (supported(saved)) language = saved;
  } catch (_) { /* Storage can be disabled or unavailable for local files. */ }
  const requested = new URL(window.location.href).searchParams.get("lang");
  if (supported(requested)) language = requested;

  const walker = document.createTreeWalker(document.documentElement, NodeFilter.SHOW_TEXT);
  while (walker.nextNode()) {
    const node = walker.currentNode;
    if (node.parentElement.closest("script, style, [data-no-translate]")) continue;
    const key = normalize(node.nodeValue);
    if (Object.hasOwn(dictionaries.fr || {}, key)) {
      textRecords.push({ node, key, original: node.nodeValue });
    }
  }
  document.querySelectorAll("[placeholder], [alt], [aria-label], meta[name='description'], meta[data-seo-translatable]").forEach(element => {
    for (const attribute of ["placeholder", "alt", "aria-label", "content"]) {
      if (!element.hasAttribute(attribute)) continue;
      const key = element.getAttribute(attribute);
      if (Object.hasOwn(dictionaries.fr || {}, key)) attributeRecords.push({ element, attribute, key });
    }
  });

  function t(key) {
    return language === "en" ? key : dictionaries[language]?.[key] ?? key;
  }

  function setLanguage(next, persist = true) {
    if (!supported(next)) return;
    language = next;
    document.documentElement.lang = language;
    document.documentElement.dir = language === "ar" ? "rtl" : "ltr";
    const locale = document.querySelector('meta[property="og:locale"]');
    if (locale) locale.setAttribute("content", { fr: "fr_FR", en: "en_US", ar: "ar_MA" }[language]);
    textRecords.forEach(({ node, key, original }) => {
      node.nodeValue = language === "en" ? original : original.replace(/\S[\s\S]*\S|\S/, () => t(key));
    });
    attributeRecords.forEach(({ element, attribute, key }) => element.setAttribute(attribute, t(key)));
    document.querySelectorAll("[data-language-select]").forEach(select => { select.value = language; });

    // Carry the choice between static pages even when localStorage is blocked.
    document.querySelectorAll("a[href]").forEach(link => {
      const url = new URL(link.getAttribute("href"), window.location.href);
      if (url.origin !== window.location.origin || !url.pathname.endsWith(".html")) return;
      url.searchParams.set("lang", language);
      link.setAttribute("href", url.href);
    });
    if (persist) {
      try { localStorage.setItem(storageKey, language); } catch (_) { /* URL links remain usable. */ }
      try {
        const url = new URL(window.location.href);
        url.searchParams.set("lang", language);
        window.history.replaceState(null, "", url);
      } catch (_) { /* Some local-file browsers restrict history updates. */ }
    }
    document.dispatchEvent(new CustomEvent("languagechange", { detail: { language } }));
  }

  window.siteI18n = { t, setLanguage, get language() { return language; } };
  document.querySelectorAll("[data-language-select]").forEach(select => {
    select.addEventListener("change", event => setLanguage(event.target.value));
    select.closest(".language-switcher").hidden = false;
  });
  setLanguage(language, false);
})();
