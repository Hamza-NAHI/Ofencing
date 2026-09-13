document.querySelectorAll("[data-year]").forEach(el => {
  el.textContent = new Date().getFullYear();
});

const toggle = document.querySelector(".menu-toggle");
const nav = document.querySelector(".main-nav");
if (toggle && nav) {
  toggle.addEventListener("click", () => {
    const isOpen = nav.classList.toggle("open");
    toggle.setAttribute("aria-expanded", String(isOpen));
  });
}

function renderSiteData() {
  if (!window.siteData) return;
  const translate = window.siteI18n?.t || (value => value);
  const escape = value => String(value).replace(/[&<>"']/g, character => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[character]));
  const t = value => escape(translate(value));
  const homeEvents = document.getElementById("home-events");
  if (homeEvents) {
    homeEvents.innerHTML = `<div class="events-cards">${window.siteData.events.slice(0, 3).map(event => `
      <article class="event-card">
        <div class="date">${t(event.date)}<small>${t(event.month)}</small></div>
        <div>
          <h3>${t(event.title)}</h3>
          <p>${t(event.description)}</p>
        </div>
        <div class="event-meta"><span>${t(event.location)}</span><span>${t(event.type)}</span></div>
      </article>
    `).join("")}</div>`;
  }

  const allEvents = document.getElementById("all-events");
  if (allEvents) {
    allEvents.innerHTML = window.siteData.events.map(event => `
      <article class="event-row">
        <div class="event-date">${t(event.date)}<br><small>${t(event.month)}</small></div>
        <div>
          <h2>${t(event.title)}</h2>
          <p>${t(event.description)}</p>
        </div>
        <div class="event-side">
          <span>${t(event.location)}</span>
          <span>${t(event.type)}</span>
        </div>
      </article>
    `).join("");
  }

}

renderSiteData();
document.addEventListener("languagechange", renderSiteData);
