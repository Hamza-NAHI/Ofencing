// One file per request respects PHP max_file_uploads/post_max_size while selecting up to 50 at once.
(() => {
  const form = document.getElementById('gallery-upload');
  if (!form) return;
  const input = form.querySelector('input[type=file]');
  const submit = form.querySelector('button[type=submit]');
  const status = document.getElementById('upload-status');
  const results = document.getElementById('upload-results');
  const refresh = document.getElementById('upload-refresh');
  let active = false;
  window.addEventListener('beforeunload', event => {
    if (active) { event.preventDefault(); event.returnValue = ''; }
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (active) return;
    const files = Array.from(input.files);
    if (!files.length || files.length > 50) {
      status.textContent = 'Sélectionnez entre 1 et 50 photos.';
      return;
    }
    active = true;
    submit.disabled = input.disabled = true;
    form.setAttribute('aria-busy', 'true');
    results.replaceChildren();
    refresh.hidden = true;
    let added = 0;
    const failedFiles = [];
    try {
      for (const [index, file] of files.entries()) {
        status.textContent = `Traitement de la photo ${index + 1} sur ${files.length}…`;
        const item = document.createElement('li');
        results.append(item);
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 90000);
        try {
          if (file.size > Number(form.dataset.maxBytes)) throw new Error('Image trop volumineuse pour la limite de ce serveur.');
          const body = new FormData();
          body.append('csrf_token', form.elements.csrf_token.value);
          body.append('photos[]', file, file.name);
          const response = await fetch(form.action, {method: 'POST', body, credentials: 'same-origin',
            headers: {Accept: 'application/json', 'X-CSRF-Token': form.elements.csrf_token.value}, signal: controller.signal});
          let result;
          try { result = await response.json(); } catch (_) { throw new Error('Réponse du serveur indisponible. Vérifiez les photos déjà reçues avant de réessayer.'); }
          if (!response.ok || !result.success) throw new Error(result.error || 'Photo non enregistrée.');
          const stored = result.data.results[0];
          added++;
          item.textContent = `${file.name} : ajoutée, ${stored.width} × ${stored.height} px, ${Math.round(stored.stored_bytes / 1024)} Kio avec la miniature.`;
        } catch (error) {
          failedFiles.push(file);
          item.textContent = `${file.name} : ${error.name === 'AbortError' || error instanceof TypeError ? 'Réception non confirmée. Vérifiez l’album avant de réessayer cette photo.' : error.message}`;
          item.className = 'admin-error';
        } finally {
          clearTimeout(timeout);
        }
      }
      status.textContent = `${added} photo(s) ajoutée(s), ${failedFiles.length} échec(s). Les photos reçues sont conservées.`;
      // Retain only failures where the browser supports DataTransfer; never silently resend successes.
      if (failedFiles.length && typeof DataTransfer === 'function') {
        const transfer = new DataTransfer();
        failedFiles.forEach(file => transfer.items.add(file));
        input.files = transfer.files;
      } else { input.value = ''; }
      refresh.hidden = false;
    } finally {
      active = false;
      submit.disabled = input.disabled = false;
      form.setAttribute('aria-busy', 'false');
    }
  });
})();
