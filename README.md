# ONescrime — static fencing coach website

A responsive static website for Moroccan épée fencer and coach Omar Nahi.

## Pages
- `index.html` — welcoming homepage, fencing introduction, coach preview, upcoming events, weekly training calendar
- `coach.html` — coach biography, selected competition results and training goals
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

## Omar Nahi content

Coach copy is in `index.html`, `coach.html` and `contact.html`. Research sources and the limits of each claim are recorded in [docs/coach-sources.md](docs/coach-sources.md).

The user-supplied coach portrait is stored in `assets/omar-nahi-coach.jpeg` and used on the homepage and coach profile. CSS frames the original image without changing the source file; its alternative text is available in all three languages. Coaching years, languages, qualifications and personal quotations have not been confirmed and are not asserted in the biography.

The existing contact details, inquiry form, events and weekly schedule remain demo content; confirm real contact details and availability with Omar before publishing them as live information. The static form does not deliver messages.

## Languages

Every page has a Français / English / العربية selector. French is the default when there is no saved choice. A valid `?lang=fr`, `?lang=en` or `?lang=ar` overrides the saved preference, and internal page links retain it even when browser storage is unavailable. Arabic uses right-to-left layout; phone numbers, email addresses and time ranges keep their left-to-right direction.

Translations for page text, metadata, accessible labels, form placeholders, events and training labels live in `js/translations.js`. The English text in the HTML and `js/data.js` is the translation key: update the matching French and Arabic entries when changing English copy. Brand names, contact addresses, dates and source links retain their identity. User-entered form values are never translated or cleared. With JavaScript disabled, the existing English HTML remains readable and the inactive selector stays hidden.

`js/i18n.js` applies translations in place and emits `languagechange` so `js/app.js` can render the events and calendar in the selected language. The selector does not activate the existing demo contact form.

## HTTPS and search metadata

Production origin: `https://ofencing.vercel.app`. Update the absolute URLs in the four HTML canonical/Open Graph tags, `robots.txt` and `sitemap.xml` together if the production domain changes.

Vercel handles HTTP → HTTPS with a permanent 308 redirect at its edge (verified on the existing public origin). `vercel.json` adds one-year HSTS and `Content-Security-Policy: upgrade-insecure-requests`. It also redirects `/index.html` to `/` and the common typo `/robot.txt` to `/robots.txt`. These rules require a Vercel deployment; another host needs equivalent server configuration and a TLS certificate. No JavaScript-only HTTPS redirect is used, so local HTTP development remains available.

Each page has its own title and description, an HTTPS canonical URL, a robots meta tag and Open Graph/X text metadata. `js/i18n.js` translates the title, description and social text and updates the Open Graph locale when switching languages. Static HTML metadata is English; French is still the default for visitors with JavaScript. Crawlers that do not execute JavaScript receive the English metadata. Language query variants share the page’s canonical URL; the sitemap lists the four main pages, not separate server-rendered language versions. No unsupported `hreflang` claims are made.

`robots.txt` allows crawling and declares `sitemap.xml`. Neither a sitemap nor a robots directive guarantees indexing. The changes in `dev` take effect on the public website only when that revision is deployed. HSTS does not request subdomain inclusion or preload.

## Social preview image

`og.png` is the 1734 × 907 social-sharing card based on the approved coach portrait, with the requested text “Maître Omar Nahi”. All four pages reference its absolute HTTPS URL in Open Graph and Twitter metadata, including dimensions, MIME type and translated alternative text. Twitter uses `summary_large_image`. The card is served as a static file without requiring JavaScript. The preview card retains the requested “Maître Omar Nahi” caption; the site brand and page metadata use ONescrime.

Deploy this revision before checking public link previews. Sharing services may retain an older cached preview until they fetch the page again. If the production domain changes, update the image URLs along with the canonical URLs.

## Brand

ONescrime stands for Omar Nahi escrime. The header and footer use the O/N monogram; the matching favicon uses the site’s burgundy colour. Names and translated metadata use ONescrime in French, English and Arabic. The existing production URL remains `https://ofencing.vercel.app`.

The previous demo email address and Instagram handle have been replaced with translated “coming soon” labels. Add verified contact details when available; no new email address or social account has been assumed.
