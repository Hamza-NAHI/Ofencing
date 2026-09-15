(() => {
  document.querySelectorAll('[data-year]').forEach(el => { el.textContent = new Date().getFullYear(); });
  const video = document.getElementById('hero-video');
  const toggle = document.getElementById('motion-toggle');
  if (!video || !toggle) return;
  const label = toggle.querySelector('[data-motion-label]');
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  let wanted = !reduced.matches && !navigator.connection?.saveData;
  let visible = true;
  let failed = false;
  function updateLabel() {
    const playing = !video.paused && !video.ended;
    label.textContent = window.siteI18n?.t(playing ? 'Pause animation' : 'Play animation') || (playing ? 'Pause animation' : 'Play animation');
    toggle.setAttribute('aria-pressed', String(playing));
  }
  async function play() {
    if (failed) return;
    if (!video.hasAttribute('src')) { video.src = video.dataset.src; video.load(); }
    try { await video.play(); }
    catch (_) { /* Autoplay may be blocked on phones: leave the still and manual play button. */ }
    updateLabel();
  }
  function sync() {
    if (wanted && visible && !document.hidden) void play();
    else video.pause();
  }
  video.addEventListener('playing', () => { video.classList.add('is-playing'); updateLabel(); });
  video.addEventListener('pause', updateLabel);
  video.addEventListener('error', () => { failed = true; video.classList.remove('is-playing'); toggle.hidden = true; });
  toggle.addEventListener('click', () => { wanted = video.paused; sync(); });
  document.addEventListener('languagechange', updateLabel);
  document.addEventListener('visibilitychange', sync);
  reduced.addEventListener('change', () => { wanted = !reduced.matches && !navigator.connection?.saveData; sync(); });
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; sync(); }, { threshold: 0.05 }).observe(video);
  }
  toggle.hidden = false;
  updateLabel();
  sync();
})();
