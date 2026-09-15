// Extend the shared dictionaries without changing the existing inner pages.
(() => {
  const entries = [
    ["Skip to content", "Aller au contenu", "انتقل إلى المحتوى"],
    ["Main navigation", "Navigation principale", "التنقل الرئيسي"],
    ["The approach", "L’approche", "المنهج"],
    ["THE ART OF FENCING · MOROCCO", "L’ART DE L’ESCRIME · MAROC", "فن المبارزة · المغرب"],
    ["A clear mind.", "L’esprit libre.", "ذهن صافٍ."],
    ["A precise touch.", "Le geste juste.", "لمسة دقيقة."],
    ["More than a sport. A way to move, to think, to find your confidence. Discover fencing with Maître Omar Nahi.", "Plus qu’un sport. Une façon de bouger, de penser, de prendre confiance. Découvrez l’escrime avec Maître Omar Nahi.", "أكثر من رياضة. طريقة للحركة والتفكير وبناء الثقة. اكتشف المبارزة مع الأستاذ عمر ناحي."],
    ["Take the first step", "Faites le premier pas", "ابدأ خطوتك الأولى"],
    ["THREE WEAPONS. ONE SPIRIT.", "TROIS ARMES. UN MÊME ESPRIT.", "ثلاثة أسلحة. روح واحدة."],
    ["Play animation", "Lancer l’animation", "تشغيل الحركة"],
    ["Pause animation", "Mettre en pause", "إيقاف الحركة مؤقتًا"],
    ["Precision. Presence. Progress.", "Précision. Présence. Progression.", "دقة. حضور. تطور."],
    ["Explore the discipline", "Découvrir la discipline", "اكتشف المبارزة"],
    ["01 / THE APPROACH", "01 / L’APPROCHE", "01 / المنهج"],
    ["Find your rhythm.", "Trouvez votre rythme.", "اعثر على إيقاعك."],
    ["Make it your own.", "Exprimez votre style.", "عبّر عن أسلوبك."],
    ["Read the moment.", "Lire l’instant.", "اقرأ اللحظة."],
    ["Learn to observe, choose your distance and act with intention.", "Apprenez à observer, à choisir votre distance et à agir avec intention.", "تعلّم الملاحظة واختيار المسافة والتحرك بوعي."],
    ["Build your confidence.", "Gagner en confiance.", "ابنِ ثقتك."],
    ["From your first steps on the piste to a more composed, precise technique.", "De vos premiers pas sur la piste à une technique plus sereine et plus précise.", "من خطواتك الأولى على الحلبة إلى أسلوب أكثر هدوءًا ودقة."],
    ["Enjoy the journey.", "Aimer le chemin.", "استمتع بالرحلة."],
    ["A demanding discipline, taught with attention to your own pace.", "Une discipline exigeante, transmise dans le respect de votre rythme.", "رياضة تتطلب الالتزام، تُدرَّس بما يناسب وتيرتك."],
    ["02 / YOUR COACH", "02 / VOTRE MAÎTRE D’ARMES", "02 / مدربك"],
    ["Maître", "Maître", "الأستاذ"],
    ["Moroccan épée fencer and coach. International experience, shared with you on the piste.", "Épéiste marocain et coach. Une expérience internationale, partagée avec vous sur la piste.", "مبارز مغربي بسيف المبارزة ومدرب. خبرة دولية يشاركها معك على الحلبة."],
    ["Discover his story", "Découvrir son parcours", "اكتشف مسيرته"],
    ["YOUR FIRST TOUCH STARTS HERE", "VOTRE PREMIÈRE TOUCHE COMMENCE ICI", "لمستك الأولى تبدأ هنا"],
    ["Shall we", "Et si on", "هل أنت مستعد"],
    ["begin?", "commençait ?", "للبداية؟"],
    ["Talk to Omar", "Échanger avec Omar", "تواصل مع عمر"],
    ["Curiosity is all you need to get started.", "L’envie de découvrir suffit pour commencer.", "الرغبة في الاكتشاف هي كل ما تحتاجه للبدء."],
    ["A fencing mask with a foil, épée and sabre crossed behind it", "Un masque d’escrime avec un fleuret, une épée et un sabre croisés derrière", "قناع مبارزة تتقاطع خلفه أسلحة الشيش وسيف المبارزة والسيف"],
    ["Discover fencing with Omar Nahi. Precision, confidence and personal coaching in Morocco. Start your journey with ONescrime.", "Découvrez l’escrime avec Omar Nahi. Précision, confiance et accompagnement personnalisé au Maroc. Faites vos premiers pas avec ONescrime.", "اكتشف المبارزة مع عمر ناحي. دقة وثقة وتدريب شخصي في المغرب. ابدأ رحلتك مع ONescrime."]
  ];
  for (const [key, fr, ar] of entries) {
    window.siteTranslations.fr[key] = fr;
    window.siteTranslations.ar[key] = ar;
  }
})();
