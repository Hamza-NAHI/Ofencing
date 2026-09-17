(() => {
  const entries = [
    ['404 · Off the piste — ONescrime', '404 · Hors piste — ONescrime', '404 · خارج الحلبة — ONescrime'],
    ['This page is off the piste. Return to ONescrime or contact Omar Nahi.', 'Cette page est hors piste. Retrouvez ONescrime ou contactez Omar Nahi.', 'هذه الصفحة خارج الحلبة. عد إلى ONescrime أو تواصل مع عمر ناحي.'],
    ['ONescrime · Halt!', 'ONescrime · Halte !', 'ONescrime · توقف!'],
    ['Off the piste.', 'Hors piste.', 'خارج الحلبة.'],
    ['This page has stepped out of bounds. It may have moved, or the link may be incorrect.', 'Cette page est sortie des limites de la piste. Elle a peut-être été déplacée, ou le lien est incorrect.', 'هذه الصفحة تجاوزت حدود الحلبة. ربما نُقلت أو أن الرابط غير صحيح.'],
    ['Back to the piste', 'Retour en piste', 'العودة إلى الحلبة'],
    ['Contact Omar', 'Contacter Omar', 'تواصل مع عمر'],
    ['A fresh start is just one step away.', 'Un pas suffit pour repartir du bon pied.', 'خطوة واحدة تكفي لبداية جديدة.'],
    ['404 · Page not found', '404 · Page introuvable', '404 · الصفحة غير موجودة']
  ];
  for (const [key, fr, ar] of entries) {
    window.siteTranslations.fr[key] = fr;
    window.siteTranslations.ar[key] = ar;
  }
})();
