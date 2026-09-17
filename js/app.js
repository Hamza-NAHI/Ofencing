document.querySelectorAll('[data-year]').forEach(el => {
  el.textContent = new Date().getFullYear();
});

const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('.main-nav');
if (toggle && nav) {
  toggle.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(isOpen));
  });
}

(() => {
  const homeEvents = document.getElementById('home-events');
  const allEvents = document.getElementById('all-events');
  const calendar = document.getElementById('training-calendar');
  if (!homeEvents && !allEvents && !calendar) return;

  const state = { events: null, training: null, eventsError: false, trainingError: false, today: '' };
  const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const t = key => escape(window.siteI18n?.t(key) ?? key);
  const locale = () => ({ fr: 'fr-FR', en: 'en-GB', ar: 'ar-MA' }[window.siteI18n?.language] || 'fr-FR');
  const status = (element, key, busy = false) => {
    element.setAttribute('aria-busy', String(busy));
    element.innerHTML = `<p role="status">${t(key)}</p>`;
  };
  const dateParts = date => {
    // UTC prevents a date-only value moving to yesterday in another time zone.
    const value = new Date(`${date}T12:00:00Z`);
    return {
      day: escape(new Intl.DateTimeFormat(locale(), { day: '2-digit', timeZone: 'UTC' }).format(value)),
      month: escape(new Intl.DateTimeFormat(locale(), { month: 'short', year: 'numeric', timeZone: 'UTC' }).format(value))
    };
  };

  function renderEvents() {
    [homeEvents, allEvents].filter(Boolean).forEach(element => {
      if (state.eventsError) return status(element, 'Events are temporarily unavailable. Please try again later.');
      if (state.events === null) return status(element, 'Loading events…', true);
      // These sections promise upcoming dates. The API also returns published past events.
      const upcoming = state.events.filter(event => event.event_date >= state.today);
      const events = element === homeEvents ? upcoming.slice(0, 3) : upcoming;
      if (!events.length) return status(element, 'No upcoming events. Please check back soon.');
      element.setAttribute('aria-busy', 'false');
      const cards = events.map(event => {
        const date = dateParts(event.event_date);
        if (element === homeEvents) return `
          <article class="event-card">
            <div class="date">${date.day}<small>${date.month}</small></div>
            <div><h3 dir="auto">${escape(event.title)}</h3><p dir="auto">${escape(event.description)}</p></div>
            <div class="event-meta"><span dir="auto">${escape(event.location)}</span><span dir="auto">${escape(event.type)}</span></div>
          </article>`;
        return `
          <article class="event-row">
            <div class="event-date">${date.day}<br><small>${date.month}</small></div>
            <div><h2 dir="auto">${escape(event.title)}</h2><p dir="auto">${escape(event.description)}</p></div>
            <div class="event-side"><span dir="auto">${escape(event.location)}</span><span dir="auto">${escape(event.type)}</span></div>
          </article>`;
      }).join('');
      element.innerHTML = element === homeEvents ? `<div class="events-cards">${cards}</div>` : cards;
    });
  }

  function renderTraining() {
    if (!calendar) return;
    if (state.trainingError) return status(calendar, 'Training times are temporarily unavailable. Please try again later.');
    if (state.training === null) return status(calendar, 'Loading training times…', true);
    if (!state.training.length) return status(calendar, 'No training slots are currently available. Contact Omar for details.');
    calendar.setAttribute('aria-busy', 'false');
    calendar.innerHTML = state.training.map(slot => {
      const day = new Intl.DateTimeFormat(locale(), { weekday: 'short', timeZone: 'UTC' }).format(new Date(Date.UTC(2024, 0, Number(slot.day_of_week))));
      return `<div class="calendar-row">
        <span class="day">${escape(day)}</span>
        <span class="slot" dir="ltr">${escape(String(slot.start_time).slice(0, 5))} — ${escape(String(slot.end_time).slice(0, 5))}</span>
        <span class="type" dir="auto">${escape(slot.type)}${slot.location ? `<br><small>${escape(slot.location)}</small>` : ''}</span>
      </div>`;
    }).join('');
  }

  renderEvents();
  renderTraining();
  document.addEventListener('languagechange', () => { renderEvents(); renderTraining(); });
  // Independent loads: a calendar error must not hide available events, or vice versa.
  if (homeEvents || allEvents) {
    Promise.resolve().then(() => window.siteAPI.getEvents()).then(body => {
      state.events = body.data;
      state.today = body.today;
      renderEvents();
    }).catch(() => { state.eventsError = true; renderEvents(); });
  }
  if (calendar) {
    Promise.resolve().then(() => window.siteAPI.getTraining()).then(slots => {
      state.training = slots;
      renderTraining();
    }).catch(() => { state.trainingError = true; renderTraining(); });
  }
})();
