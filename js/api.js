// Resolve against this script, so both / and /ofencing/ installations work.
// Optional override before loading: window.API_BASE = '/ofencing/api/';
(() => {
  const scriptURL = document.currentScript.src;
  const base = new URL(window.API_BASE || '../api/', scriptURL);
  if (!base.pathname.endsWith('/')) base.pathname += '/';

  async function request(endpoint, options = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await fetch(new URL(endpoint, base), {
        ...options, credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
        headers: { Accept: 'application/json', ...options.headers }
      });
      let body;
      try { body = await response.json(); } catch (_) { throw new Error('Service unavailable.'); }
      if (!response.ok || body.success !== true) {
        const error = new Error(body.error || 'Service unavailable.');
        error.status = response.status;
        throw error;
      }
      return body;
    } finally {
      clearTimeout(timer);
    }
  }

  window.siteAPI = {
    async getEvents() {
      const body = await request('events.php');
      if (!Array.isArray(body.data) || !/^\d{4}-\d{2}-\d{2}$/.test(body.today)) throw new Error('Invalid events response.');
      return body;
    },
    async getTraining() {
      const body = await request('training.php');
      if (!Array.isArray(body.data)) throw new Error('Invalid training response.');
      return body.data;
    },
    async getGalleryAlbums() {
      const body = await request('gallery.php');
      if (!Array.isArray(body.data)) throw new Error('Invalid gallery response.');
      return body.data;
    },
    async getGalleryAlbum(id) {
      const body = await request('gallery.php?album=' + encodeURIComponent(id));
      if (!body.data?.album || !Array.isArray(body.data.photos)) throw new Error('Invalid album response.');
      return body.data;
    },
    sendContactMessage: formData => request('messages.php', { method: 'POST', body: formData })
  };
})();
