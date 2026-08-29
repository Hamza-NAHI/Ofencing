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

if (window.siteData) {
  const homeEvents = document.getElementById("home-events");
  if (homeEvents) {
    homeEvents.innerHTML = `<div class="events-cards">${window.siteData.events.slice(0, 3).map(event => `
      <article class="event-card">
        <div class="date">${event.date}<small>${event.month}</small></div>
        <div>
          <h3>${event.title}</h3>
          <p>${event.description}</p>
        </div>
        <div class="event-meta"><span>${event.location}</span><span>${event.type}</span></div>
      </article>
    `).join("")}</div>`;
  }

  const allEvents = document.getElementById("all-events");
  if (allEvents) {
    allEvents.innerHTML = window.siteData.events.map(event => `
      <article class="event-row">
        <div class="event-date">${event.date}<br><small>${event.month}</small></div>
        <div>
          <h2>${event.title}</h2>
          <p>${event.description}</p>
        </div>
        <div class="event-side">
          <span>${event.location}</span>
          <span>${event.type}</span>
        </div>
      </article>
    `).join("");
  }

  const calendar = document.getElementById("training-calendar");
  if (calendar) {
    calendar.innerHTML = window.siteData.training.map(slot => `
      <div class="calendar-row">
        <span class="day">${slot.day}</span>
        <span class="slot">${slot.time}</span>
        <span class="type">${slot.type}</span>
      </div>
    `).join("");
  }
}
