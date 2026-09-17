(() => {
  const form = document.querySelector('.contact-form');
  if (!form) return;
  const button = form.querySelector('button[type="submit"]');
  const note = document.getElementById('form-note');
  let sending = false;
  let message = '';
  const translate = key => window.siteI18n?.t(key) ?? key;
  function render() {
    button.textContent = translate(sending ? 'Sending…' : 'Send inquiry');
    note.hidden = !message;
    note.textContent = translate(message);
  }
  document.addEventListener('languagechange', render);
  button.disabled = false; // Enabled only after the real submission handler is available.
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (sending || !form.reportValidity()) return;
    sending = true;
    button.disabled = true;
    form.setAttribute('aria-busy', 'true');
    message = '';
    render();
    try {
      await window.siteAPI.sendContactMessage(new FormData(form));
      message = 'Your message has been received. Thank you!';
      note.setAttribute('role', 'status');
      form.reset();
    } catch (error) {
      message = error.status === 422
        ? 'Message not sent. Check your name, email or phone, and field lengths.'
        : error.status === 429
          ? 'Message not sent. Too many attempts; please try again in a few minutes.'
          : 'We could not confirm that your message was sent. Please try again later.';
      note.setAttribute('role', 'alert');
    } finally {
      sending = false;
      button.disabled = false;
      form.setAttribute('aria-busy', 'false');
      render();
    }
  });
  render();
})();
