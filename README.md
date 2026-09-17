# ONescrime — fencing coach website

A responsive website for Moroccan épée fencer and coach Omar Nahi. The public pages use HTML/CSS/vanilla JavaScript; PHP/PDO and MySQL power events, training and contact messages on the same shared host.

## Pages
- `index.html` — welcoming homepage, fencing introduction, coach preview, upcoming events, weekly training calendar
- `coach.html` — coach biography, selected competition results and training goals
- `events.html` — upcoming events list
- `contact.html` — API-backed inquiry form / contact information
- `styles2.css` — all visual styles
- `js/api.js` — shared, subdirectory-aware API client
- `js/contact.js` — real contact form submission
- `api/`, `admin/`, `config/`, `database/` — PHP/MySQL backend and private administration
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

Use XAMPP (Apache + MySQL) and follow **PHP / MySQL deployment** below. Opening an HTML file directly, `python -m http.server`, and static-only hosting cannot execute the PHP API. The surrounding pages remain readable when the API is unavailable and show a translated availability message.

## Where to edit content now

- Events and weekly training: `/admin/`, stored only in MySQL. `js/data.js` has been removed.
- Contact inquiries: submitted to MySQL and visible only in `/admin/messages/`.
- Coach biography and general website copy: the existing HTML pages and translation dictionaries.
- No CMS, translation editor, Google Calendar synchronization or JavaScript backend is required.

## Omar Nahi content

Coach copy is in `index.html`, `coach.html` and `contact.html`. Research sources and the limits of each claim are recorded in [docs/coach-sources.md](docs/coach-sources.md).

The user-supplied coach portrait is stored in `assets/omar-nahi-coach.jpeg` and used on the homepage and coach profile. CSS frames the original image without changing the source file; its alternative text is available in all three languages. Coaching years, languages, qualifications and personal quotations have not been confirmed and are not asserted in the biography.

The inquiry form now saves real messages in MySQL. The seeded events, seeded weekly schedule and placeholder contact details still need confirmation with Omar before public deployment. Receiving a message does not book or confirm a session. No email is sent automatically.

## Languages

Every page has a Français / English / العربية selector. French is the default when there is no saved choice. A valid `?lang=fr`, `?lang=en` or `?lang=ar` overrides the saved preference, and internal page links retain it even when browser storage is unavailable. Arabic uses right-to-left layout; phone numbers, email addresses and time ranges keep their left-to-right direction.

Translations for page text, metadata, accessible labels, form placeholders and API status messages live in `js/translations.js`. The English UI text is the translation key: update the matching French and Arabic entries when changing it. Event titles/descriptions, locations and training types remain in the language entered by the administrator. Dates and weekdays are formatted in the selected language. Brand names, contact addresses, dates and source links retain their identity. User-entered form values are never translated or cleared. With JavaScript disabled, the existing English HTML remains readable and the inactive selector stays hidden.

`js/i18n.js` applies translations in place and emits `languagechange` so `js/app.js` can render the events and calendar in the selected language. Contact success/error messages also follow the selected language. Changing languages preserves form input. The new private Admin UI is in French.

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

## PHP / MySQL deployment

### 1. Requirements

- PHP **8.0+**, PDO and the `pdo_mysql` extension; use a maintained PHP 8 release on production hosting.
- MySQL **8+** or MariaDB **10.4+** (including XAMPP). `utf8mb4` supports French and Arabic.
- Apache 2.4 with `.htaccess` support (`AllowOverride All`), `mod_rewrite` and preferably `mod_headers`.
- PHP sessions and a writable system temporary directory (used for small locked rate-limit files outside the website).
- HTTPS for production; local HTTP on `localhost` works. No npm, Node backend, build, Composer, daemon or framework is needed in production.

### 2. Exact XAMPP setup (Windows)

1. Obtain the latest **`dev`** branch of `Hamza-NAHI/Ofencing`. Do not switch production or merge other branches for this test.
2. Extract/copy the **contents** of the project into `C:\xampp\htdocs\ofencing\`. You should have `C:\xampp\htdocs\ofencing\index.html`, not an extra nested repository folder. Include the `.htaccess` files.
3. Open XAMPP Control Panel and start **Apache** and **MySQL**. If either cannot start, resolve the port conflict shown by XAMPP before testing the website.
4. Open `http://localhost/phpmyadmin/`. Create a database named **`ofencing`**, using `utf8mb4_unicode_ci`.
5. Select that database → **Import** → choose `C:\xampp\htdocs\ofencing\database\schema.sql` → **Go**. Import into an empty database **once**. It creates four tables and development examples; it is not an upgrade/reset script. Re-importing intentionally fails rather than overwriting existing data.
6. Copy `config/local.example.php` to **`config/local.php`**. Edit it to contain the following local values:

   ```php
   <?php
   return [
       'DB_HOST' => '127.0.0.1',
       'DB_PORT' => '3306',
       'DB_NAME' => 'ofencing',
       'DB_USER' => 'root',
       'DB_PASS' => '', // Default LOCAL XAMPP only; use your actual password if changed.
       'APP_TIMEZONE' => 'Africa/Casablanca',
       'APP_BASE_PATH' => null,
       'SETUP_TOKEN' => '', // Set a temporary random secret in the next step.
   ];
   ```

   `config/database.php` loads these credentials and configures PDO. Keeping secrets in the ignored `local.php` avoids committing them; environment variables with the same names override local values. Never use a blank/root account on public hosting.
7. Create the first administrator using the one-time setup below.
8. Open **`http://localhost/ofencing/`**. Do not double-click the HTML file.

If Apache uses port 8080, use `http://localhost:8080/...` everywhere. If your folder has another name, use that name in the URLs. Paths are detected automatically, including admin redirects, cookie paths and the API client. `APP_BASE_PATH` can explicitly be `/ofencing` or `''` if your host uses unusual aliases. For a custom frontend path, set `window.API_BASE = '/ofencing/api/'` before `js/api.js`.

### 3. Create the first Admin user

No default account or password is seeded. `admin/setup.php` is disabled until explicitly configured.

1. Generate a random setup secret of at least 32 characters using a password manager, or this one-time PowerShell command:

   ```powershell
   C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

2. Paste the generated secret into `SETUP_TOKEN` in `config/local.php`.
3. Open **`http://localhost/ofencing/admin/setup.php`**. Enter the secret, choose a username (3–64 letters/digits/periods/hyphens/underscores) and your own password (at least 12 characters, at most 72 UTF-8 bytes). Confirm it and submit.
4. The server hashes the password using `password_hash()`; no plaintext password is written to the database. Setup automatically refuses further access once an admin exists, including concurrent attempts.
5. Immediately set `SETUP_TOKEN` back to `''`, then sign in at **`http://localhost/ofencing/admin/login.php`**.

On public hosting, do setup only over HTTPS. The setup secret is sent by POST, never in the URL. If you lose the admin password, use trusted server access/phpMyAdmin to replace `password_hash` with a hash generated by PHP; there is no public password-reset flow. Do not delete real data or re-import the schema to reset a password.

### 4. Local URLs and complete manual test

| Purpose | Local URL |
| --- | --- |
| Homepage / training | `http://localhost/ofencing/` |
| Events | `http://localhost/ofencing/events.html` |
| Contact | `http://localhost/ofencing/contact.html` |
| Admin login / dashboard | `http://localhost/ofencing/admin/` |
| Events Admin | `http://localhost/ofencing/admin/events/` |
| Training Admin | `http://localhost/ofencing/admin/training/` |
| Message inbox | `http://localhost/ofencing/admin/messages/` |

**Contact → API → MySQL → Admin**

1. Open `contact.html`. Enter a name, a real-format email or phone, a level and a test message. Name/contact are required; level/message may be empty through the API. Valid phone numbers contain 7–15 digits with optional leading `+`, spaces, parentheses, dots or hyphens.
2. Submit. The button disables while sending; success appears only after the API confirms the insert. Successful submission resets the form.
3. Sign in to Admin → Messages. The inquiry appears at the top with **Non lu**. Open it, mark it read, then unread. Test the unread/read filters. Delete it through the confirmation page.
4. Invalid email/phone must display an error and preserve your input. Stopping MySQL must display an availability error, never fake success. Restart MySQL afterwards.
5. Public `GET /api/messages.php` returns **405**, even if logged in. There is no public message listing.

**Admin → MySQL → API → Events**

1. Admin → Événements → Nouvel événement. Choose a future date, enter title/location/type and publish it.
2. Reload the homepage and `events.html`: it appears, ordered by date. The homepage shows the first three upcoming events. If three earlier events already exist, the new one appears on the full events page.
3. Edit it, unpublish it, verify it disappears from the API/site, republish if desired, then delete it with confirmation.
4. Published past events remain in `GET /api/events.php` and Admin, while the public sections headed “Upcoming” display today/future dates only. “Today” comes from the server in `APP_TIMEZONE`.

**Admin → MySQL → API → Training**

1. Admin → Entraînements → Nouveau créneau. Choose day, start/end time, type, optional location, order and **Actif**.
2. Reload the homepage. The new slot appears in display order, then weekday/start-time order for ties.
3. Edit it, deactivate it, verify it disappears publicly, then delete with confirmation. The end time must follow the start time on the same day.

**Authentication / translations / hosting**

1. Log out. Direct access to Admin pages must redirect to login. POST writes to event/training APIs without a session must return **401**; with a session but no valid CSRF token they return **403**.
2. Login regenerates the session ID. Inactivity for 30 minutes or a session older than 12 hours requires a new login. Logout is a CSRF-protected POST and destroys the cookie/session.
3. Check Français / English / العربية on the homepage, events and contact; Arabic keeps RTL layout and LTR time ranges. Entered content is not automatically translated.
4. Visit `/ofencing/a/missing/page`: the branded 404 must have HTTP **404**, working assets/navigation and all three languages. `404.php` reuses the existing `404.html` without redesigning it.
5. Direct requests for `/ofencing/config/local.php`, `/ofencing/database/schema.sql`, hidden repository files and Admin helpers must be forbidden. These protections depend on Apache honoring the supplied `.htaccess` files.

### 5. Shared hosting / cPanel deployment

1. Enable PHP with PDO MySQL, create a MySQL database and a dedicated database user in the hosting panel, and associate that user with the database. Hosts often prefix the database/user names with your account name.
2. Import `database/schema.sql` once through the host's phpMyAdmin. Take a database backup before any later manual migrations.
3. Upload the public pages/assets, JavaScript, CSS, `404.php`, `api/`, `admin/`, `config/` and all relevant `.htaccess` files into `public_html/` (or your chosen subfolder). Do **not** upload `.git`, tests, SQL exports or local test artifacts. The SQL file is only needed for import, not to serve the website.
4. Create `config/local.php` using the actual database hostname, port, database, user and password from your host. Do not upload local XAMPP credentials. The production web account needs SELECT/INSERT/UPDATE/DELETE on this database; schema import may use a separate account.
5. Enable the TLS certificate and **HTTPS redirect in the hosting panel**. Only then use the setup page and Admin. The application automatically marks session cookies Secure when PHP receives HTTPS. With a reverse proxy, configure the host to report HTTPS correctly; do not trust arbitrary client forwarding headers.
6. Run the one-time admin setup, erase its secret, and confirm login at `https://YOUR-DOMAIN/admin/` (include your subfolder if applicable).
7. Remove/unpublish example events and inactive/unconfirmed schedule entries, and enter verified availability. Confirm the placeholder WhatsApp/contact information with Omar.
8. Update the existing absolute canonical/Open Graph/image URLs, `robots.txt` and `sitemap.xml` together for the real domain. Metadata was preserved in this change, so it still refers to the existing Vercel origin until you replace it.
9. Repeat the manual flow above over HTTPS, including denied configuration URLs and nested 404s. If a host disallows `Options` in `.htaccess`, ask it to apply equivalent settings or remove that directive only after verifying directory listing is disabled. The 404 rewrite requires `mod_rewrite`.

`vercel.json` and the existing static 404 are retained for the previous static deployment. **Vercel static hosting cannot run this PHP/MySQL architecture.** No Vercel function is added. This commit does not migrate/deploy the production domain.

### 6. API contract

All API responses are JSON with `Content-Type: application/json; charset=utf-8` and `Cache-Control: no-store`. Success: `{"success":true,"data":...}` (contact instead returns `message`). Failure: `{"success":false,"error":"..."}`.

| Endpoint | Method | Access / behavior |
| --- | --- | --- |
| `api/events.php` | GET | Published events ordered by `event_date`, then ID; includes server `today` (`YYYY-MM-DD`). |
| `api/training.php` | GET | Active slots ordered by `display_order`, weekday, start time, ID. |
| `api/events.php` | POST | Authenticated create/update/delete with CSRF. |
| `api/training.php` | POST | Authenticated create/update/delete with CSRF. |
| `api/messages.php` | POST | Public contact submission. Stores `name`, `contact`, optional `level` and `message`; returns 201. |
| `api/messages.php` | GET | Always 405. Read/toggle/delete messages through authenticated Admin only. |

**POST actions** avoid PUT/DELETE restrictions on shared hosting. Supply `action=create` (default), `action=update`, or `action=delete`. For update/delete supply a positive integer `id` in the body or query. Update sends all editable fields, not a partial patch. Submit JSON (`application/json`), URL-encoded data, or FormData. Unsupported methods return 405 and an `Allow` header.

Admin API writes require the login session cookie and `csrf_token` in the body or `X-CSRF-Token` header; the token is available in Admin form hidden fields. No token or secret is embedded in public JavaScript. Missing checkbox flags mean `0`; use `1` for published/active. Training weekdays are ISO numbers: Monday `1` to Sunday `7`; times are `HH:MM`; order is `0`–`9999`.

Validation errors return 422, malformed JSON 400, unsupported body types 415, oversized API payloads 413 (32 KiB maximum), missing records 404, unauthenticated writes 401, invalid CSRF 403, rate limits 429 with `Retry-After`, database/unexpected failures 503. Database details and stack traces are never returned to clients. Content is treated as plain text and escaped on both public and Admin screens.

### 7. Production security checklist

- [ ] HTTPS enforced and session cookie marked Secure/HttpOnly/SameSite=Lax; Apache honors `.htaccess`.
- [ ] Dedicated database account and strong unique admin password; `config/local.php` is ignored by Git and denied by HTTP.
- [ ] `SETUP_TOKEN` removed after first account; no default admin/password present.
- [ ] Configuration, database, tests, helper files and hidden files denied or not uploaded; directory listing disabled.
- [ ] PHP `display_errors=Off` and `log_errors=On` also set in the hosting panel (including startup/parse errors); logs not publicly served.
- [ ] Production database backups and a contact-message retention/deletion policy established; verify the actual contact/privacy text before launch.
- [ ] Development seed events/schedule removed or confirmed; site domain/SEO URLs and contact details updated.
- [ ] Tested login/logout, CSRF refusal, publish/unpublish, contact delivery, message privacy and nested 404 after upload.

All database operations use PDO prepared statements. SQL identifiers in the small shared CRUD helpers are allowlisted, and integer pagination offsets are calculated from validated values. Passwords use `password_hash` / `password_verify`; session IDs rotate on login. Public content and Admin output are HTML-escaped. Every Admin mutation (including logout/read status) requires CSRF; GET requests never perform writes.

A small filesystem rate limiter limits contact requests to **5 per 10 minutes per IP**, login attempts to **10 per 15 minutes per IP**, and setup attempts to **5 per 15 minutes per IP**. It uses server-reported IPs and atomic file locks in the PHP temporary directory. It does not replace hosting-level anti-abuse controls; users behind the same IP share the allowance. No third-party CAPTCHA or email dependency is introduced.

### 8. Limitations and maintenance

- Receiving an inquiry only saves it in Admin. Email/SMS notifications, email sending/replies, online payment and automatic booking are not implemented.
- There is one simple admin role, a French Admin UI, no public account creation and no password-reset email. The public website remains FR/EN/AR.
- Coach/static page edits still happen in HTML and translations; dynamic content keeps the language entered by the coach.
- Training slots repeat weekly and cannot cross midnight. There is no Google Calendar dependency on `dev`.
- No AJAX live refresh: reload the public page after an Admin edit. Events are never silently replaced with static fallback data.
- A network timeout after an insert can leave delivery uncertain. The form says it could not confirm receipt, preserves input and allows retry; retries can create duplicate inquiries. There is no automatic retry or fake success.
- Database timestamps are UTC; event dates and training hours are local to `APP_TIMEZONE` (Morocco by default). Past seeded events may no longer appear in upcoming sections.
- The site needs PHP/MySQL and Apache configuration after upload. Production credentials/domain/TLS cannot be inferred from this repository.

### 9. Automated verification

`tests/integration.py` is an optional development check using only Python's standard library. It makes real HTTP requests to PHP and a real MySQL/MariaDB database. Run it **only against a disposable local installation**: it creates and deletes prefixed event, training and message records, and deliberately exhausts the local contact rate limit for ten minutes.

Set `OFENCING_TEST_BASE_URL` (for example `http://localhost/ofencing/`), `OFENCING_TEST_USERNAME`, `OFENCING_TEST_PASSWORD` and `OFENCING_TEST_ALLOW_WRITES=yes`, then run `python tests/integration.py`. Set `OFENCING_TEST_APACHE=yes` to also verify private-file protection and a nested 404. Optional `OFENCING_TEST_SETUP_TOKEN` exercises first-account setup on a freshly imported, empty-admin database. Never use production credentials or a database containing real customer inquiries.

The implementation was checked with PHP 8.3, MariaDB 10.11 and Apache 2.4, at both the domain root and `/ofencing/`: public reads, real contact inserts, all Admin/API CRUD operations, publication/activation filtering, sorting, escaped Unicode content, malformed/oversized input, invalid IDs, authentication, CSRF, logout, throttling, safe 503 JSON on database failure, denied private files and the branded nested 404. Windows XAMPP and the final hosting account still require the deployment checks above.

Browser verification also covered real form submission and Admin-created data, FR/EN/AR including RTL, mobile navigation and layouts, long administrator-entered titles, escaped HTML, empty/unavailable states, preserved form input after failures, disabled sending buttons and the 404 adapter. The only public CSS additions keep long dynamic text inside the existing layout and indicate a pending submission.
