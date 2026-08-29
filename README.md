# Maison d'Escrime — static fencing coach website

A responsive static website concept for a fencing coach in Morocco.

## Pages
- `index.html` — welcoming homepage, fencing introduction, coach preview, upcoming events, weekly training calendar
- `coach.html` — coach profile and coaching philosophy
- `events.html` — upcoming events list
- `contact.html` — static inquiry form / contact information
- `styles2.css` — all visual styles
- `js/data.js` — event and training data
- `js/app.js` — rendering + mobile navigation
- `assets/` — logo and illustration placeholders

## Design direction
Warm editorial New York café / matcha aesthetic:
- cream + soft matcha palette
- oversized serif typography
- restrained modern sans-serif UI
- rounded forms and editorial spacing
- minimal, premium, approachable

## Run locally
Open `index.html` directly in your browser.

For a local web server:
```bash
python -m http.server 8000
```
Then visit `http://localhost:8000`.

## Where to edit content now
Events and training calendar are stored in `js/data.js`.

Coach biography and general website copy are currently inside the HTML pages.

## Future admin space
Keep the public website as-is and later replace `js/data.js` with data from a CMS/API.

Recommended options:
1. **Simple**: Sanity, Contentful or Strapi for events, calendar and coach content.
2. **Custom**: Next.js + Supabase/Postgres with `/admin`.
3. **Very light**: Decap CMS if the site stays mostly static.

A future content model could be:

### Event
- title
- date
- city
- venue
- category
- description
- registration link
- status
- cover image

### Training slot
- weekday
- start time
- end time
- location
- audience
- recurring / one-off

### Coach profile
- name
- biography
- languages
- experience
- certifications
- social links
- contact details

The current separation of event/calendar data makes this migration easy.
