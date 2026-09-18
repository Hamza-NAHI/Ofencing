(() => {
  const container = document.getElementById('gallery-content');
  if (!container) return;
  const dialog = document.getElementById('gallery-viewer');
  const viewerImage = document.getElementById('viewer-image');
  const viewerError = document.getElementById('viewer-image-error');
  const viewerCaption = document.getElementById('viewer-caption');
  const viewerCount = document.getElementById('viewer-count');
  const previous = document.getElementById('viewer-previous');
  const next = document.getElementById('viewer-next');
  const state = {albums: null, album: null, photos: [], error: '', loading: true, index: 0};
  const t = key => window.siteI18n?.t(key) ?? key;
  const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]));
  const e = key => escape(t(key));
  const language = () => window.siteI18n?.language || 'fr';
  const albumQuery = new URLSearchParams(location.search).get('album');
  let trigger = null;
  let swipe = null;
  function pageURL(id) {
    const url = new URL('gallery.html', location.href);
    if (id != null) url.searchParams.set('album', String(id));
    url.searchParams.set('lang', language());
    return url.href;
  }
  function imageURL(value) {
    // Only same-origin, generated raster images; never turn API strings into arbitrary URLs.
    try {
      const url = new URL(value, location.href);
      return url.origin === location.origin && /\/uploads\/gallery\/album-[1-9][0-9]*\/[a-f0-9]{32}(?:-thumb)?\.(?:webp|jpg)$/.test(url.pathname) ? url.href : '';
    } catch (_) { return ''; }
  }
  function photoAlt(photo, index) {
    return photo.alt_text || `${state.album.title} — ${t('Photo')} ${index + 1}`;
  }
  function photoImage(photo, alt) {
    const url = imageURL(photo.thumbnail_url);
    return `<div class="gallery-image-wrap">${url ? `<img src="${escape(url)}" alt="${escape(alt)}" width="640" height="480" loading="lazy" decoding="async">` : `<span class="gallery-placeholder">${e('Image unavailable')}</span>`}</div>`;
  }
  function render() {
    container.setAttribute('aria-busy', String(state.loading));
    if (state.loading || state.error) {
      container.innerHTML = `<p role="status">${e(state.error || 'Loading gallery…')}</p>${state.error ? `<a class="text-link" href="${escape(pageURL())}">${e('All albums')}</a>` : ''}`;
      return;
    }
    if (state.album) {
      const album = state.album;
      const date = album.event_date ? new Intl.DateTimeFormat({fr:'fr-FR',en:'en-GB',ar:'ar-MA'}[language()], {dateStyle:'long',timeZone:'UTC'}).format(new Date(album.event_date + 'T12:00:00Z')) : '';
      container.innerHTML = `<a class="text-link" href="${escape(pageURL())}">${e('All albums')}</a>
        <div class="gallery-album-heading"><h2 dir="auto">${escape(album.title)}</h2>
        ${album.description ? `<p class="gallery-album-description" dir="auto">${escape(album.description)}</p>` : ''}
        <p class="gallery-album-meta"><span>${state.photos.length} ${e('Photos')}</span>${date ? `<span>${escape(date)}</span>` : ''}${album.location ? `<span dir="auto">${escape(album.location)}</span>` : ''}</p></div>
        ${state.photos.length ? `<div class="gallery-photos">${state.photos.map((photo,index)=>`<figure class="gallery-photo"><button class="gallery-photo-button" type="button" data-photo-index="${index}" aria-label="${escape(t('Open photo') + ': ' + photoAlt(photo,index))}">${photoImage(photo,photoAlt(photo,index))}</button>${photo.caption ? `<figcaption dir="auto">${escape(photo.caption)}</figcaption>` : ''}</figure>`).join('')}</div>` : `<p role="status">${e('No photos yet')}</p>`}`;
    } else {
      container.innerHTML = state.albums.length ? `<div class="gallery-albums">${state.albums.map(album => `
        <a class="gallery-album" href="${escape(pageURL(album.id))}">
          ${album.cover ? photoImage(album.cover,album.cover.alt_text || `${album.title} — ${t('Album cover')}`) : `<div class="gallery-image-wrap"><span class="gallery-placeholder">${e('No photos yet')}</span></div>`}
          <div class="gallery-album-copy"><h2 dir="auto">${escape(album.title)}</h2><p>${album.photo_count} ${e('Photos')}</p>${album.location ? `<p dir="auto">${escape(album.location)}</p>` : ''}<span class="gallery-view-link">${e('View album')} ↗</span></div>
        </a>`).join('')}</div>` : `<p role="status">${e('No gallery albums are available yet.')}</p>`;
    }
  }
  // Capture error events (they do not bubble) and replace broken thumbnails with readable text.
  container.addEventListener('error', event => {
    if (event.target.tagName !== 'IMG') return;
    const placeholder = document.createElement('span');
    placeholder.className = 'gallery-placeholder';
    placeholder.textContent = t('Image unavailable');
    event.target.replaceWith(placeholder);
  }, true);
  function showPhoto(index, load = true) {
    state.index = (index + state.photos.length) % state.photos.length;
    const photo = state.photos[state.index];
    viewerImage.alt = photoAlt(photo,state.index);
    viewerCaption.textContent = photo.caption || '';
    const position = document.createElement('bdi');
    position.dir = 'ltr';
    position.textContent = `${state.index + 1} / ${state.photos.length}`;
    viewerCount.replaceChildren(document.createTextNode(t('Photo') + ' '), position);
    previous.disabled = next.disabled = state.photos.length < 2;
    if (load) {
      viewerError.hidden = true;
      viewerImage.hidden = false;
      const url = imageURL(photo.image_url);
      if (url) viewerImage.src = url;
      else { viewerImage.removeAttribute('src'); viewerImage.hidden = true; viewerError.hidden = false; }
    }
  }
  viewerImage.addEventListener('error', () => { viewerImage.hidden = true; viewerError.hidden = false; });
  container.addEventListener('click', event => {
    const button = event.target.closest('[data-photo-index]');
    if (!button) return;
    trigger = button;
    const index = Number(button.dataset.photoIndex);
    showPhoto(index);
    if (typeof dialog.showModal === 'function') {
      dialog.showModal();
      document.body.classList.add('gallery-viewer-open');
    } else {
      // Older browsers can still open the optimized photo without a dialog polyfill.
      location.href = imageURL(state.photos[index].image_url);
    }
  });
  const close = () => dialog.close();
  document.getElementById('viewer-close').addEventListener('click', close);
  previous.addEventListener('click', () => showPhoto(state.index - 1));
  next.addEventListener('click', () => showPhoto(state.index + 1));
  dialog.addEventListener('keydown', event => {
    if (event.key === 'Tab') {
      const controls = Array.from(dialog.querySelectorAll('button:not(:disabled)'));
      const first = controls[0];
      const last = controls[controls.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      event.preventDefault();
      showPhoto(state.index + (event.key === 'ArrowLeft' ? -1 : 1));
    }
  });
  dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
  dialog.addEventListener('close', () => {
    document.body.classList.remove('gallery-viewer-open');
    viewerImage.removeAttribute('src');
    trigger?.focus();
  });
  const figure = dialog.querySelector('figure');
  figure.addEventListener('touchstart', event => {
    swipe = event.touches.length === 1 ? {x:event.touches[0].clientX,y:event.touches[0].clientY} : null;
  }, {passive:true});
  figure.addEventListener('touchend', event => {
    if (!swipe || !event.changedTouches.length) return;
    const dx = event.changedTouches[0].clientX - swipe.x;
    const dy = event.changedTouches[0].clientY - swipe.y;
    swipe = null;
    if (Math.abs(dx)>50 && Math.abs(dx)>Math.abs(dy)*1.5) showPhoto(state.index + (dx<0 ? 1 : -1));
  }, {passive:true});
  document.addEventListener('languagechange', () => {
    render();
    if (dialog.open) {
      trigger = container.querySelector(`[data-photo-index="${state.index}"]`);
      showPhoto(state.index,false);
    }
  });
  render();
  (async () => {
    try {
      if (albumQuery !== null) {
        if (!/^[1-9][0-9]{0,9}$/.test(albumQuery) || Number(albumQuery)>2147483647) {
          state.error = 'Album not found.';
        } else {
          const data = await window.siteAPI.getGalleryAlbum(albumQuery);
          state.album = data.album;
          state.photos = data.photos;
        }
      } else { state.albums = await window.siteAPI.getGalleryAlbums(); }
    } catch (error) {
      state.error = error.status === 404 ? 'Album not found.' : 'Gallery temporarily unavailable';
    } finally {
      state.loading = false;
      render();
    }
  })();
})();
