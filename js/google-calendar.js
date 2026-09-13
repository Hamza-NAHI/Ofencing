(() => {
  function calendarURL(config, language) {
    let id = typeof config.calendarId === "string" ? config.calendarId.trim() : "";
    if (!id && config.embedUrl) {
      const supplied = new URL(config.embedUrl);
      if (supplied.protocol !== "https:" || supplied.hostname !== "calendar.google.com" ||
          supplied.username || supplied.password || supplied.port) {
        throw new Error("Use the Google Calendar embed URL or calendar ID.");
      }
      if (supplied.pathname === "/calendar/embed") {
        id = supplied.searchParams.get("src") || "";
      } else if (/^\/calendar(?:\/u\/\d+)?\/?$/.test(supplied.pathname)) {
        // Google sharing links encode the calendar ID in cid (Base64/Base64url).
        const encoded = supplied.searchParams.get("cid") || "";
        if (!/^[A-Za-z0-9+/_-]+={0,2}$/.test(encoded)) throw new Error("Invalid calendar sharing link.");
        id = atob(encoded.replace(/-/g, "+").replace(/_/g, "/"));
      } else {
        throw new Error("Use the Google Calendar embed URL, sharing link or calendar ID.");
      }
      if (!id) throw new Error("The calendar link is missing its calendar ID.");
    }
    if (!id) return null;
    if (!/^[^\s<>@]+@[^\s<>@]+$/.test(id)) throw new Error("Invalid calendar ID.");
    const zone = config.timeZone || "Africa/Casablanca";
    new Intl.DateTimeFormat("en", { timeZone: zone });
    const url = new URL("https://calendar.google.com/calendar/embed");
    url.search = new URLSearchParams({
      src: id, ctz: zone, hl: ["fr", "en", "ar"].includes(language) ? language : "fr",
      mode: "AGENDA", showTitle: "0", showPrint: "0", showTabs: "1",
      showCalendars: "0", showTz: "1", showNav: "1"
    }).toString();
    return url.href;
  }

  function renderCalendar() {
    const container = document.getElementById("training-calendar");
    if (!container) return;
    const t = window.siteI18n?.t || (text => text);
    let url;
    try { url = calendarURL(window.coachCalendar || {}, window.siteI18n?.language || "fr"); }
    catch (_) { url = null; }
    container.replaceChildren();
    if (!url) {
      const message = document.createElement("p");
      message.className = "calendar-empty";
      message.textContent = t("The coach’s shared calendar will be available soon. Contact Omar for current availability.");
      container.append(message);
      return;
    }
    const viewport = document.createElement("div");
    viewport.className = "google-calendar-viewport";
    const frame = document.createElement("iframe");
    frame.src = url;
    frame.title = t("Omar Nahi’s Google Calendar");
    frame.loading = "lazy";
    frame.referrerPolicy = "strict-origin-when-cross-origin";
    frame.width = "800";
    frame.height = "600";
    viewport.append(frame);
    const actions = document.createElement("div");
    actions.className = "google-calendar-actions";
    const open = document.createElement("a");
    open.href = url;
    open.target = "_blank";
    open.rel = "noopener noreferrer";
    open.className = "text-link";
    open.textContent = t("Open Google Calendar (new tab)");
    const note = document.createElement("p");
    note.textContent = t("Calendar not showing? Open it in Google Calendar. Use its Add to Google Calendar option to follow the schedule.");
    actions.append(open, note);
    container.append(viewport, actions);
  }

  window.ONescrimeCalendar = { calendarURL, renderCalendar };
  renderCalendar();
  document.addEventListener("languagechange", renderCalendar);
})();
