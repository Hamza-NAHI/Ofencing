# Connect the coach’s Google Calendar

The `QA` branch was created from `main`. The homepage training calendar now uses Google's embedded calendar instead of the hard-coded weekly slots. Events are maintained in Google Calendar, not copied into this repository.

## One-time setup

1. In Google Calendar on a computer, create a **separate training calendar** containing only information intended for website visitors. Do not publish a personal calendar or private attendee details.
2. Open Settings → Settings for my calendars → select that training calendar → Access permissions for events. For anonymous website visitors, enable public access with the appropriate event visibility. This exposes those events publicly; make this choice deliberately. Workspace administrators may restrict public sharing.
3. Under Integrate calendar, copy **Calendar ID**. Set `calendarId` in `js/calendar-config.js` to that value. Alternatively set `embedUrl` to the HTTPS `src` URL inside Google's embed code; do not paste the whole iframe. If both are provided, `calendarId` wins.
4. Keep `timeZone: "Africa/Casablanca"` for Morocco, or use the calendar's intended IANA timezone. This respects Morocco's timezone changes rather than hard-coding UTC+1.
5. Deploy the QA branch and test while signed out of Google. The site cannot verify cross-origin calendar permissions: an inaccessible calendar may show a Google permission message. The external link remains available if an iframe is blocked.

Never put secret iCal links, API keys, access tokens or passwords in the public repository. No Google API credentials or app connection are required for this public, read-only embed. The code does not change the calendar's sharing permissions.

## Daily use

Omar adds, edits, cancels or repeats sessions in Google Calendar. Visitors see Google's current calendar when the embed loads or refreshes, without a site rebuild for each event change. Google controls its refresh timing; instant updates in an already-open tab are not guaranteed. Visitors can use Google's Add to Google Calendar option to subscribe; this does not grant editing rights.

The site supports French, English and Arabic controls and passes the selected language to Google. Event titles and descriptions stay in the language Omar entered. Agenda view is the default, and the iframe is contained in a horizontally scrollable area on narrow phones. A separate open-in-Google link is provided.

Until an ID or valid embed URL is configured, the site displays an honest “available soon” message, without fabricated training slots or an unrelated sample calendar. Changing language does not overwrite the calendar with the former demo table. This is a shared schedule, not a booking/payment system.

The separate upcoming-events cards in `js/data.js` remain existing demo content; they are not synchronized with Google Calendar by this change.

Official setup instructions: [Google Calendar: Add a calendar to your website](https://support.google.com/calendar/answer/41207?hl=en).
