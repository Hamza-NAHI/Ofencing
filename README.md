# ONescrime — fencing coach website

A responsive website for Moroccan épée fencer and coach Omar Nahi. The public pages use HTML/CSS/vanilla JavaScript; PHP/PDO and MySQL power events, training, photo albums and contact messages on the same shared host.

## Pages
- `index.html` — welcoming homepage, fencing introduction, coach preview, upcoming events, weekly training calendar
- `coach.html` — coach biography, selected competition results and training goals
- `events.html` — upcoming events list
- `contact.html` — API-backed inquiry form / contact information
- `gallery.html`, `gallery.css`, `js/gallery.js` — album gallery, responsive thumbnails and accessible photo viewer
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
- Photo albums: `/admin/gallery/`; metadata in MySQL, optimized images and thumbnails in `uploads/gallery/`.
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

Production origin: `https://ofencing.vercel.app`. Update the absolute URLs in the five HTML canonical/Open Graph tags, `robots.txt` and `sitemap.xml` together if the production domain changes.

Vercel handles HTTP → HTTPS with a permanent 308 redirect at its edge (verified on the existing public origin). `vercel.json` adds one-year HSTS and `Content-Security-Policy: upgrade-insecure-requests`. It also redirects `/index.html` to `/` and the common typo `/robot.txt` to `/robots.txt`. These rules require a Vercel deployment; another host needs equivalent server configuration and a TLS certificate. No JavaScript-only HTTPS redirect is used, so local HTTP development remains available.

Each page has its own title and description, an HTTPS canonical URL, a robots meta tag and Open Graph/X text metadata. `js/i18n.js` translates the title, description and social text and updates the Open Graph locale when switching languages. Static HTML metadata is English; French is still the default for visitors with JavaScript. Crawlers that do not execute JavaScript receive the English metadata. Language query variants share the page’s canonical URL; the sitemap lists the five main pages, not separate server-rendered language versions. No unsupported `hreflang` claims are made.

`robots.txt` allows crawling and declares `sitemap.xml`. Neither a sitemap nor a robots directive guarantees indexing. The changes in `dev` take effect on the public website only when that revision is deployed. HSTS does not request subdomain inclusion or preload.

## Social preview image

`og.png` is the 1734 × 907 social-sharing card based on the approved coach portrait, with the requested text “Maître Omar Nahi”. All five pages reference its absolute HTTPS URL in Open Graph and Twitter metadata, including dimensions, MIME type and translated alternative text. Twitter uses `summary_large_image`. The card is served as a static file without requiring JavaScript. The preview card retains the requested “Maître Omar Nahi” caption; the site brand and page metadata use ONescrime.

Deploy this revision before checking public link previews. Sharing services may retain an older cached preview until they fetch the page again. If the production domain changes, update the image URLs along with the canonical URLs.

## Brand

ONescrime stands for Omar Nahi escrime. The header and footer use the O/N monogram; the matching favicon uses the site’s burgundy colour. Names and translated metadata use ONescrime in French, English and Arabic. The existing production URL remains `https://ofencing.vercel.app`.

The previous demo email address and Instagram handle have been replaced with translated “coming soon” labels. Add verified contact details when available; no new email address or social account has been assumed.

## PHP / MySQL deployment

### 1. Requirements

- PHP **8.0+**, PDO, `pdo_mysql`, GD and Fileinfo; EXIF is recommended for phone/camera orientation. Gallery details below; use a maintained PHP 8 release on production hosting.
- MySQL **8+** or MariaDB **10.4+** (including XAMPP). `utf8mb4` supports French and Arabic.
- Apache 2.4 with `.htaccess` support (`AllowOverride All`), `mod_rewrite` and preferably `mod_headers`.
- PHP sessions and a writable system temporary directory (used for small locked rate-limit files outside the website).
- HTTPS for production; local HTTP on `localhost` works. No npm, Node backend, build, Composer, daemon or framework is needed in production.

### 2. Exact XAMPP setup (Windows)

1. Obtain the latest **`dev`** branch of `Hamza-NAHI/Ofencing`. Do not switch production or merge other branches for this test.
2. Extract/copy the **contents** of the project into `C:\xampp\htdocs\ofencing\`. You should have `C:\xampp\htdocs\ofencing\index.html`, not an extra nested repository folder. Include the `.htaccess` files.
3. Open XAMPP Control Panel and start **Apache** and **MySQL**. If either cannot start, resolve the port conflict shown by XAMPP before testing the website.
4. Open `http://localhost/phpmyadmin/`. Create a database named **`ofencing`**, using `utf8mb4_unicode_ci`.
5. Select that database → **Import** → choose `C:\xampp\htdocs\ofencing\database\schema.sql` → **Go**. Import into an empty database **once**. It creates six tables and development examples; it is not an upgrade/reset script. Re-importing intentionally fails rather than overwriting existing data.
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
| Gallery | `http://localhost/ofencing/gallery.html` |
| Gallery Admin | `http://localhost/ofencing/admin/gallery/` |
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
4. PHP returns only published events dated today or later, using `APP_TIMEZONE`. Past events remain manageable in Admin, but are no longer sent to public browsers.

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
2. For a new, empty database, import `database/schema.sql` once through the host's phpMyAdmin. For an existing installation from the previous backend commit, back it up and import only `database/gallery.sql` once; do not re-import the full schema.
3. Upload the public pages/assets, JavaScript, CSS, `404.php`, `api/`, `admin/`, `config/`, `uploads/.htaccess`, an empty `uploads/gallery/` for a new installation, and all other relevant `.htaccess` files into `public_html/` (or your chosen subfolder). Do **not** upload `.git`, tests, SQL exports or local test artifacts. The SQL file is only needed for import, not to serve the website.
4. Prefer a PHP configuration array outside `public_html`, for example `/home/account/private/ofencing.php`, and set the server environment variable `OFENCING_CONFIG_FILE` to its absolute path. Where the host cannot configure this, use the Apache-protected, Git-ignored `config/local.php`. Use actual hosting credentials, not local XAMPP credentials. The dedicated production database user needs SELECT/INSERT/UPDATE/DELETE on this database; use a separate maintenance account for schema import. Never use MySQL root in production.
5. Enable the TLS certificate and **HTTPS redirect in the hosting panel**, then set `APP_REQUIRE_HTTPS` to `true` in production configuration. PHP rejects non-HTTPS requests in this mode; the hosting redirect also protects static pages. Only then use setup and Admin. Session cookies become Secure when the server reports HTTPS. With a reverse proxy, configure trusted server-side HTTPS reporting; arbitrary client forwarding headers are ignored. Leave this option `false` for HTTP localhost/XAMPP.
6. Run the one-time admin setup, erase its secret, and confirm login at `https://YOUR-DOMAIN/admin/` (include your subfolder if applicable).
7. Remove/unpublish example events and inactive/unconfirmed schedule entries, and enter verified availability. Confirm the placeholder WhatsApp/contact information with Omar.
8. Update the existing absolute canonical/Open Graph/image URLs, `robots.txt` and `sitemap.xml` together for the real domain. Metadata was preserved in this change, so it still refers to the existing Vercel origin until you replace it.
9. Repeat the manual flow above over HTTPS, including denied configuration URLs and nested 404s. If a host disallows `Options` in `.htaccess`, ask it to apply equivalent settings or remove that directive only after verifying directory listing is disabled. The 404 rewrite requires `mod_rewrite`.

`vercel.json` and the existing static 404 are retained for the previous static deployment. **Vercel static hosting cannot run this PHP/MySQL architecture.** No Vercel function is added. This commit does not migrate/deploy the production domain.

### 6. API contract

All API responses are JSON with `Content-Type: application/json; charset=utf-8` and `Cache-Control: no-store`. Success: `{"success":true,"data":...}` (contact instead returns `message`). Failure: `{"success":false,"error":"..."}`.

| Endpoint | Method | Access / behavior |
| --- | --- | --- |
| `api/events.php` | GET | Published upcoming events ordered by `event_date`, then ID. Only date/title/description/location/type are returned. |
| `api/training.php` | GET | Active slots ordered by `display_order`, weekday, start time, ID. |
| `api/gallery.php` | GET | Published albums with photo counts and cover URLs. |
| `api/gallery.php?album=ID` | GET | Published album metadata and ordered photos; draft/missing albums return 404. |
| `api/events.php` | POST | Authenticated create/update/delete with CSRF. |
| `api/training.php` | POST | Authenticated create/update/delete with CSRF. |
| `api/messages.php` | POST | Public contact submission. Stores `name`, `contact`, optional `level` and `message`; returns 201. |
| `api/messages.php` | GET | Always 405. Read/toggle/delete messages through authenticated Admin only. |

**POST actions** avoid PUT/DELETE restrictions on shared hosting. Supply `action=create` (default), `action=update`, or `action=delete`. For update/delete supply a positive integer `id` in the body or query. Update sends all editable fields, not a partial patch. Submit JSON (`application/json`), URL-encoded data, or FormData. Unsupported methods return 405 and an `Allow` header.

Admin API writes require the login session cookie and `csrf_token` in the body or `X-CSRF-Token` header; the token is available in Admin form hidden fields. No token or secret is embedded in public JavaScript. Missing checkbox flags mean `0`; use `1` for published/active. Training weekdays are ISO numbers: Monday `1` to Sunday `7`; times are `HH:MM`; order is `0`–`9999`.

Public events/training no longer include record IDs, timestamps, publication flags or internal ordering values. Training returns only `day_of_week`, `start_time`, `end_time`, `type` and `location`. Authenticated event/training write responses return only the affected ID. Contact accepts an optional empty `website` honeypot; it is never stored. Unknown submitted fields never become database columns or trusted server metadata.

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

A small filesystem rate limiter limits contact requests to **5 per 10 minutes per IP**, login attempts to **10 per 15 minutes per IP** (cleared on successful login), and setup attempts to **5 per 15 minutes per IP**. Gallery uploads allow **100 submitted images and 120 requests per 10 minutes per administrator**, across sessions; rejected images consume the image budget too. It uses server-reported IPs, private storage and atomic file locks in the PHP temporary directory, with bounded state size. It does not replace hosting-level anti-abuse controls; users behind the same IP share the allowance. No third-party CAPTCHA or email dependency is introduced.

All ordinary Admin/API bodies are capped at **32 KiB**; the upload route is capped at **20 MiB** per request, in addition to PHP limits and the 15 MiB per-image cap. Apache also enforces ingress limits, including before PHP processes multipart bodies. Password-hash replacement or administrator deletion revokes existing authenticated sessions on their next request. Existing sessions from before this update require a fresh login.

The enforced CSP permits local scripts only, without `unsafe-inline` or `unsafe-eval`; Google Fonts CSS (`fonts.googleapis.com`) and fonts (`fonts.gstatic.com`) are the only external subresource allowances. It blocks framing, objects and injected base URLs. PHP and Apache send `nosniff`, a strict referrer policy, disabled unused browser permissions and frame protection. Failed logins, CSRF rejection, upload validation failures, rate limits and unexpected errors produce bounded server-side security events without passwords, cookies, session IDs, message content or SQL. Keep logs private and rotate them.

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

Browser verification also covered real form submission and Admin-created data, FR/EN/AR including RTL, mobile navigation and layouts, long administrator-entered titles, escaped HTML, empty/unavailable states, preserved form input after failures, disabled sending buttons and the 404 adapter. Those backend changes retain the existing public design. Gallery-specific styles are isolated in `gallery.css`.

See [docs/security-audit.md](docs/security-audit.md) for the complete 45-area review, actual findings, fixes, test evidence and remaining deployment requirements. `tests/security_integration.py` adds adversarial HTTP tests using the same local-only environment variables as the gallery test below and requires Pillow only for development fixtures. It deliberately exhausts local login/upload limits; use a disposable installation and wait for limits to expire between runs. This security update requires **no database migration** and adds no production dependency.

## Gallery

### Install or upgrade the database

- **New installation:** import `database/schema.sql` once into an empty database. It includes `gallery_albums` and `gallery_photos` alongside the four existing tables. No sample photos or default Admin password are inserted.
- **Existing installation:** back up the database, select it in phpMyAdmin and import **`database/gallery.sql` once** before using the updated Admin dashboard. It only creates the two new tables and their indexes/foreign key; existing accounts, events, training and messages are preserved. Do not import both SQL files or recreate your database. A repeated migration intentionally reports existing tables.
- `gallery_albums` stores title, description, unique stable slug, optional date/location, publication flag, display order, cover ID and timestamps. `gallery_photos` stores album ID, generated filenames, original basename (metadata only), caption, alt text, encoded sizes/dimensions and display order. The album foreign key cascades photo rows; cover selection is checked against the same album in PHP. Always delete through Admin so the associated files are removed too.

### XAMPP and shared-host configuration

In XAMPP Control Panel → Apache → **Config → PHP (php.ini)**, enable `extension=gd` and `extension=fileinfo`; enable `extension=exif` for automatic JPEG orientation. Enable each extension only once. Some Windows builds require `mbstring` before EXIF: follow the comments in the supplied XAMPP `php.ini`. Restart Apache after changing PHP extensions or limits. PDO MySQL and the original database/session configuration remain required.

To check GD from the XAMPP terminal, run `C:\xampp\php\php.exe -r "var_export(extension_loaded('gd'));"` (`true` means loaded). `C:\xampp\php\php.exe -r "print_r(gd_info());"` lists JPEG/PNG/WebP capabilities. PHP CLI and Apache can use different configuration files, so also open an Admin album → **Limites de ce serveur** to verify the PHP instance actually serving uploads.

Practical settings for typical camera batches:

```ini
file_uploads = On
upload_max_filesize = 15M
post_max_size = 20M
memory_limit = 512M
max_execution_time = 60
max_input_time = 120
max_file_uploads = 20
```

The application caps **each source at 15 MiB, 40 megapixels and 12,000 px per side**, even if PHP allows more. It estimates decoded memory before calling GD and rejects an image if the configured memory limit is insufficient. A 256M host accepts many ordinary photos; larger high-resolution photos can require 512M or pre-resizing. Do not raise limits beyond your host's allowance. The Admin album page shows the effective PHP limits and GD/WebP/JPEG/EXIF capabilities. `max_input_time` covers receiving an upload; `max_execution_time` covers each processing request. A hosting proxy may impose additional request limits.

For shared hosting/cPanel, enable **GD, Fileinfo and EXIF** in the PHP extension selector and set permitted limits through the hosting panel (or supported `php.ini`/`.user.ini` mechanism). WebP encoding is detected automatically. If GD cannot encode WebP, the application writes JPEG instead; if no suitable GD encoder exists, it refuses the upload with a clear error. No unprocessed file is retained as a fallback.

Keep `uploads/.htaccess` and create `uploads/gallery/` if your upload tool omits empty folders. The PHP web account needs write/delete access to `uploads/gallery/` and its album directories. On typical Linux hosting, start with directories **0755**, files **0644**, and ownership matching the account running PHP. Have the host correct ownership/group access if necessary; do not use blanket `0777`. XAMPP Windows needs the equivalent write permission for the Apache account. Runtime photos and album access files are ignored by Git. Deployments must **preserve existing `uploads/gallery/` contents**; back up and restore that directory together with the database.

Apache **2.4 must honor the provided `.htaccess` rules**, including authorization, handler and directory-listing directives (`AllowOverride All` is the simplest local setting). A draft album gets a generated `Require all denied` access file; publishing changes it to `Require all granted`. Public photos are static optimized files, not PHP downloads. Authenticated Admin previews use `admin/gallery/image.php` so drafts remain manageable. Do not delete or manually edit generated album `.htaccess` files. PHP's development server, static hosts and Nginx do not enforce these files; use XAMPP/Apache for the complete security behavior. An alternative web server needs equivalent server-side access rules before deployment.

### Admin workflow

1. Sign in at `/admin/` → **Galerie** → **Nouvel album**. Enter title, description, optional date/location and order. New albums start as drafts. Lower display order comes first; ties show the newest album first.
2. Open the album and select **1–50 JPEG, PNG or WebP photos at once**. Click **Ajouter les photos**. The browser sends one photo per request, shows progress and continues after an individual failure. This permits a 50-photo selection without raising PHP's usual `max_file_uploads=20` or fitting the whole batch inside `post_max_size`.
3. Keep the page open until it finishes, then click **Actualiser les photos de l’album**. Successful photos are retained; failed files remain selected where the browser supports it. An interrupted/uncertain request asks you to check the album before retrying, because a retry can add a duplicate. No automatic retry occurs.
4. Save optional captions and accessible descriptions, move photos with **Monter / Descendre**, and choose any photo as the cover. With no chosen cover, the first ordered photo is used. Deleting the chosen cover falls back to the first remaining photo.
5. Publish through the album form or album list. Unpublishing removes it from the API/public gallery and blocks direct static image requests. Previously downloaded/copied images cannot be recalled.
6. Photo and album deletion each require a confirmation page and a CSRF-protected POST. Deleting a photo removes both generated files; deleting an album removes all photo rows, files and its empty directory. Files are temporarily moved into a private folder so a database failure can restore them; any incomplete cleanup is logged and shown to the administrator.

Without JavaScript, the multipart upload form still works, but a batch must fit both `max_file_uploads` and the **total** `post_max_size`. Larger selections should use the normal JavaScript upload queue. The public gallery requires JavaScript and shows a readable notice when it is disabled.

### Real image processing and storage

`config/gallery-images.php` validates the PHP upload, actual file size, allowed extension, Fileinfo MIME and image dimensions. The browser-provided MIME type is not trusted. PHP GD decodes and re-encodes the image; files are never accepted by merely changing their extension. Path components/control characters are removed from the original filename, which is kept only as private metadata. Generated disk names use 128 random bits; album folders use stable numeric IDs, so renaming an album does not break URLs.

| Generated file | Longest edge | WebP quality | JPEG fallback quality |
| --- | --- | --- | --- |
| Viewer image | At most 1920 px | 82 | 85 |
| Grid/cover thumbnail | At most 640 px | 76 | 78 |

Aspect ratios are preserved and small photos are never enlarged. JPEG EXIF orientations 1–8 are corrected when EXIF is enabled; re-encoding removes source EXIF/GPS metadata. WebP preserves transparency; JPEG fallback flattens transparent pixels onto white. Source PNGs are encoded to WebP or JPEG, not stored unchanged. HEIC/HEIF, GIF, SVG, AVIF and animated-image workflows are not supported; convert these to a supported static photo first. Lossy encoding and resizing reduce typical large photo sizes, but an already tiny source can produce a slightly larger file.

For each successful upload, only these two files remain:

```text
uploads/gallery/album-12/9b03f480a1044090ba4fabcc0f21e817.webp
uploads/gallery/album-12/9b03f480a1044090ba4fabcc0f21e817-thumb.webp
```

The PHP temporary original is deleted after processing; it is never moved to public storage. DB insertion failures clean up generated images. Only generated `.webp`/`.jpg` filenames are served under `uploads/`; executable and other extensions are denied, script handlers are removed, directory listing is disabled and responses include `nosniff`. Image caching requires revalidation so later unpublishing is honored on subsequent requests when the included headers are active.

### Public gallery and API

`gallery.html` first renders published album covers/counts; `gallery.html?album=12` shows that album's title, description, date, location, count and ordered grid. Grids use lazy-loaded **thumbnails only**. An optimized larger image loads on opening the native-dialog viewer. It supports previous/next, Escape, arrow keys, touch swipes, focus trapping/restoration and labelled buttons. Layouts use four/three/two photo columns for desktop/tablet/mobile. The surrounding site design, logo and FR/EN/AR behavior are retained, including RTL. Administrator-entered album text/captions are not machine-translated. Empty albums, missing images, unavailable API and missing/unpublished albums have readable states.

- `GET api/gallery.php` returns `{ "success": true, "data": [ ...albums ] }`, ordered by display order then newest first. Each album has `id` (needed for its public route), `title`, `location`, `photo_count`, and `cover` (`thumbnail_url`/`alt_text`, or `null`).
- `GET api/gallery.php?album=12` returns `{ "success": true, "data": { "album": {...}, "photos": [...] } }`. Album detail additionally includes `description` and `event_date`. A photo contains only `caption`, `alt_text`, `thumbnail_url` and `image_url`. No photo IDs, slugs, stored dimensions, internal flags, server filesystem paths, original filenames or unpublished records are returned, including to logged-in visitors using this public endpoint. Draft/missing IDs return 404; malformed IDs return 422; non-GET methods return 405.
- Admin writes remain under authenticated `admin/gallery/*.php`, with prepared SQL and CSRF protection. `upload.php?id=12` accepts multipart `photos[]`; `Accept: application/json` selects the upload queue response. It returns 201 for complete success, 200 for mixed success, or 422 when no photo succeeds, with per-file results. Missing session/CSRF return 401/403 in JSON mode. PHP request-size overflow returns 413 when the request reaches PHP.

### Test locally

After importing the appropriate SQL file and configuring PHP/permissions:

1. Open `http://localhost/ofencing/admin/gallery/`, create a draft, and upload several large camera JPEGs plus PNG/WebP photos in one selection. Include one invalid `.jpg` containing plain text; valid photos must still succeed. Repeat with 50 small valid images.
2. Confirm two generated files per accepted photo under `C:\xampp\htdocs\ofencing\uploads\gallery\album-ID\`, no camera originals, viewer edge ≤1920 px and thumbnail edge ≤640 px. Test a small photo (no enlargement) and a portrait JPEG with EXIF rotation.
3. While drafted, `http://localhost/ofencing/api/gallery.php?album=ID` must return 404; a known direct image URL must return 403 through Apache. Publish and open `http://localhost/ofencing/gallery.html`; the album appears and its photos open in the viewer.
4. Change cover, captions, alt text and order. Check keyboard controls, mobile swipes, Français/English/العربية, responsive layouts, and the absence of raw HTML execution in entered titles.
5. Delete the chosen cover: both files disappear and cover fallback works. Delete the album: its rows and all files disappear. Verify the original events, training, contact and Admin login/logout flows still work.

`tests/gallery_integration.py` is an optional **development-only** HTTP/disk test using Python and Pillow (`python -m pip install Pillow`). Production requires neither. Use an isolated local database and test administrator, set `OFENCING_TEST_BASE_URL`, `OFENCING_TEST_USERNAME`, `OFENCING_TEST_PASSWORD`, `OFENCING_TEST_ALLOW_WRITES=yes` and `OFENCING_TEST_APACHE=yes`, then run `python tests/gallery_integration.py`. Optionally set `OFENCING_TEST_REPO` to this local checkout to also verify deletion on disk. The script refuses non-local hosts, creates prefixed albums, and cleans up only its own data. Use PHP GD with WebP/EXIF, `upload_max_filesize=15M` or higher, `post_max_size=20M` or higher and `memory_limit=512M` for its 24-megapixel test fixture.

Validation used real PHP 8.3/GD, MariaDB 10.11 and Apache 2.4 at the domain root and `/ofencing/`. It covered actual multipart compression/thumbnail generation, partial failures, draft metadata and static-file privacy, CSRF/authentication, formats/size/pixel guards, metadata/cover/order changes, complete file deletion and existing backend regression tests. Separate checks covered the additive migration, all eight EXIF orientations, actual JPEG fallback, missing GD and a 64M memory limit. A 6000×4000 test JPEG shrank from 12,595,976 bytes to 930,514 bytes including its thumbnail. Windows XAMPP and the final shared-host account still need the local/hosting checks above.

Chromium browser verification against live PHP/MySQL also covered album creation, mixed JPEG/PNG/WebP uploads with partial failure, a single selection of **50 successfully stored photos**, captions/alt text, thumbnails-only initial requests, larger-image loading, keyboard/focus controls, mobile touch swipes, FR/EN/AR and RTL. It checked 320–1440 px layouts, empty/missing/unavailable states, broken images and Gallery links on all five public pages. Test photos and accounts are not part of the repository.

### Limits

There is no pagination, drag-and-drop sorting, client-side image editing, automatic duplicate detection, resumable queue, cloud storage, CDN, video support or background processing. Many large albums can make Admin/public pages heavier despite lazy loading; split collections into manageable albums. Image orientation correction depends on EXIF availability; conversion depends on the installed GD codecs and host CPU/memory/disk quotas. Original camera files must be archived separately if needed later. The deployment remains standard PHP/MySQL/Apache; no Node server, build system or external gallery service is added.
