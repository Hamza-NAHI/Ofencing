# Ofencing security review

Date: 2026-09-21. Repository: `Hamza-NAHI/Ofencing`. Branch: **dev only**.
Reviewed baseline: `7191dd08c2d479a0c114f12f21e3b24d2f1f0c21` and the fixes accompanying this report. No production requests, production database changes, deployment, other-branch modifications or merges were performed.

## Executive assessment

The existing PHP architecture already enforced authentication, CSRF, prepared SQL, plain-text output escaping and substantial image validation. No authentication bypass, SQL injection, executable upload, exploitable XSS, public contact-message disclosure or committed live credential was confirmed. This is not a claim that none can exist.

The review identified **0 CRITICAL, 0 HIGH, 5 MEDIUM and 7 LOW findings**, plus the INFORMATIONAL deployment dependency below. The medium/low findings were fixed without adding production dependencies or changing the PHP/PDO/MySQL/GD/Apache architecture. Several are defense-in-depth weaknesses, not demonstrated end-to-end compromises; their prerequisites and residual risks are stated explicitly. Native PHP sessions remain the authority. Public design and tested application flows are preserved.

Deployment is **conditional on Apache enforcing the supplied rules, correct PHP configuration, HTTPS, private writable storage and a least-privilege database account**. Windows XAMPP and the actual hosting account were not available for direct testing. This audit does not certify either environment or provide an absolute security guarantee.

## Inventory reviewed before changes

All tracked application sources, configuration, SQL, documentation and tests on the baseline dev branch were inspected. Binary images were inventoried as static assets, not audited for vulnerabilities in third-party decoders.

| Area | Inventory / responsibility |
| --- | --- |
| Public pages | `index.html`, `coach.html`, `events.html`, `contact.html`, `gallery.html`, `404.html`; `404.php` adapter |
| Public UI | `js/api.js`, `js/app.js`, `js/contact.js`, `js/gallery.js`, `js/i18n.js`, `js/translations.js`, `js/404-translations.js`; `styles.css`, `styles2.css`, `gallery.css`, `404.css` |
| API | `api/_common.php`, `api/events.php`, `api/training.php`, `api/messages.php`, `api/gallery.php` |
| Admin infrastructure | `admin/_common.php`, `admin/_content.php`, `admin/index.php`, `admin/login.php`, `admin/logout.php`, `admin/setup.php`, `admin/admin.css` |
| Event/training management | Each of `admin/events/` and `admin/training/`: `index.php`, `create.php`, `edit.php`, `delete.php` |
| Contact administration | `admin/messages/index.php`, `view.php`, `toggle-read.php`, `delete.php` |
| Gallery administration | `admin/gallery/_common.php`, `_form.php`, `index.php`, `album.php`, `create.php`, `edit.php`, `publish.php`, `upload.php`, `upload.js`, `image.php`, `save-photo.php`, `set-cover.php`, `reorder.php`, `delete-photo.php`, `delete-album.php` |
| Authentication / data | `config/auth.php`, `bootstrap.php`, `database.php`, `validation.php`, `gallery.php`, `gallery-images.php`, placeholder-only `local.example.php`; ignored runtime `local.php` |
| Database | `database/schema.sql`, additive `database/gallery.sql`: admin users, events, training, contact messages, albums, photos |
| Uploads | `uploads/.htaccess`, `uploads/gallery/.gitkeep`; generated per-album access rules, two random-name encoded files per image, private deletion staging |
| Access configuration | Root, `admin/`, `admin/gallery/`, `api/`, `config/`, `database/`, `tests/`, `uploads/` `.htaccess`; `.gitignore`; legacy static-only `vercel.json` |
| Other public artifacts | `assets/` SVG/JPEG assets, `og.png`, `robots.txt`, `sitemap.xml` |
| Documentation / verification | `README.md`, `docs/coach-sources.md`, `tests/integration.py`, `tests/gallery_integration.py`, `tests/404.cjs` |

New security helper, regression tests, documentation access rule and this report are reviewed additions, not assumed baseline components. There are no payment, checkout or pricing features, no external authentication service and no third-party client JavaScript library. Google Fonts is the only external subresource dependency identified.

## Findings and applied fixes

### M01 — Password replacement did not revoke existing sessions — MEDIUM

- **Affected:** `config/auth.php`, `admin/login.php`.
- **Problem:** Authenticated requests verified that the administrator still existed, but not whether the password hash had changed since login.
- **Attack scenario:** Someone possessing an already authenticated session could retain access after the owner replaced a compromised password hash.
- **Impact:** Access could survive password recovery until session expiry/logout.
- **Fix applied:** Store a server-only fingerprint of the credential hash in the session; compare it on every privileged request using `hash_equals`. Hash changes, account deletion and pre-update sessions require fresh login. Rehash-on-login updates the stored fingerprint correctly.
- **Remaining risk:** This does not detect a stolen session before password replacement. TLS, cookie protections, endpoint authorization and session expiry remain necessary. Rehashing also invalidates the account's other sessions intentionally.

### M02 — Gallery access could open before publication committed — MEDIUM

- **Affected:** `config/gallery.php`: album update/publication.
- **Problem:** The previous publication sequence wrote an Apache grant before the database transaction committed.
- **Attack scenario:** A failed publication or interrupted worker could leave a still-draft album's known static image URLs readable.
- **Impact:** Disclosure of draft photos if their URLs were known; not an unauthenticated way to initiate publishing.
- **Fix applied:** Commit publication first, then reacquire the album row lock, read the committed state and synchronize access. Unpublishing closes filesystem access before changing the database. A delayed, failing SQL update was injected locally and the access file remained denied throughout.
- **Remaining risk:** Process/disk failure after commit may leave a published album inaccessible, rather than expose a draft. Retry publication after resolving the failure. Previously downloaded public images cannot be recalled. Restoring old database/filesystem backups inconsistently can still break access-state agreement.

### M03 — Gallery failure cleanup ran after releasing the database lock — MEDIUM

- **Affected:** `config/gallery.php`, `config/gallery-images.php`.
- **Problem:** Deletion restoration / failed-upload cleanup could occur after transaction rollback released the album lock.
- **Attack scenario:** Another administrator operation on the same album could interleave with restoration and observe or alter partially restored state.
- **Impact:** File/database inconsistency, missing files or orphan files under concurrent failure conditions. No independent authorization bypass was found.
- **Fix applied:** `gallery_write` accepts a failure callback and restores/removes staged files before rollback releases the row lock. Recovery failures are logged; private staged files remain available for operator recovery. Normal success still commits before permanent cleanup.
- **Remaining risk:** MySQL and a filesystem do not share an atomic transaction. Abrupt termination, an ambiguous database commit, disk errors or failed restoration require manual reconciliation/backups. The tests verify the ordering and injected failures, not every possible process-crash schedule.

### M04 — Image processing lacked a server-side upload rate budget — MEDIUM

- **Affected:** `admin/gallery/upload.php`, `config/bootstrap.php`.
- **Problem:** Per-file limits existed, but an authenticated account could repeatedly invoke expensive GD processing without a time-window quota.
- **Attack scenario:** An abused or compromised administrator account repeatedly submits valid/invalid images, bypassing the JavaScript queue.
- **Impact:** CPU, temporary storage or disk exhaustion on shared hosting.
- **Fix applied:** Before image decoding, enforce 120 upload requests and 100 submitted images per 10 minutes per administrator. Reject empty/overlarge batches; failed images count too. Account-keyed state survives new sessions. Existing size/dimension/memory checks remain authoritative.
- **Remaining risk:** Not a storage quota or distributed DoS solution. The web server receives bodies before PHP authorization; Apache/PHP ingress caps and host resource controls remain necessary. Legitimate large sessions must wait when throttled.

### M05 — Application request-body limits were incomplete — MEDIUM

- **Affected:** `api/_common.php`, `admin/_common.php`, new `config/security.php`, root/Admin/API access rules.
- **Problem:** The small API form-body check depended on `Content-Length`; ordinary Admin forms lacked the same small application limit. PHP's broader host-level limits still applied, so this was not an unlimited-body claim.
- **Attack scenario:** Send an oversized URL-encoded body without a declared length, or large ordinary Admin input, to consume more parsing/validation resources than intended.
- **Impact:** Excess request processing/memory relative to the intended small-field contract.
- **Fix applied:** Bounded raw reads for non-multipart writes, strict declared-length validation, a 32 KiB ordinary-body cap, 20 MiB upload-request cap and matching Apache ingress limits. Multipart requires a declared length when it reaches the application.
- **Remaining risk:** PHP parses form/multipart data before application code; Apache and PHP limits must be active. A proxy can reject requests first with its own non-JSON error. Real chunked oversized form requests were rejected by both Apache and PHP independently.

### L01 — Content policy and browser-feature restrictions were missing — LOW

- **Affected:** Root `.htaccess`, PHP responses via `config/security.php`.
- **Problem:** No practical enforced CSP/Permissions-Policy covered the existing UI. This was missing defense in depth, not an observed XSS exploit.
- **Attack scenario:** A future injection could have fewer browser-side restrictions on script execution or outbound connections.
- **Impact:** Increased consequences if another injection flaw is introduced.
- **Fix applied:** Enforce local scripts/connections/images, no inline/eval scripts, no objects/base overrides/framing; permit only the two Google Fonts resource hosts needed by the design. Add unused-feature restrictions and consistent nosniff/referrer/frame headers.
- **Remaining risk:** CSP does not replace escaping or authorization. Future legitimate external resources require deliberate policy review. Remote font availability was not exercised; browser tests blocked those external requests while checking the rest of the policy.

### L02 — Accidental backup and documentation exposure was insufficiently guarded — LOW

- **Affected:** Root `.htaccess`, new `docs/.htaccess`, `.gitignore`, `uploads/.htaccess`.
- **Problem:** The baseline denied several sensitive files, but not common additional backup/archive/key suffixes; internal documentation had no directory-specific deny rule. No actual sensitive backup was found.
- **Attack scenario:** An operator later leaves `config.php.old`, an archive or a private key under the document root.
- **Impact:** Conditional disclosure of the accidentally deployed file; not evidence that credentials were already public.
- **Fix applied:** Extend case-insensitive HTTP denial for backup/archive/key/temp suffixes, deny documentation, extend ignore patterns, and remove script MIME associations in uploads as well as handlers. Existing strict generated-image filename rules remain.
- **Remaining risk:** A denylist cannot enumerate every future filename or server alias. Keep private files outside the document root and do not deploy Git, tests, SQL, backups or logs. `.gitignore` is not an HTTP security control.

### L03 — Public responses included unnecessary metadata and past events — LOW

- **Affected:** `api/_common.php`, `api/gallery.php`, `js/api.js`, `js/app.js`.
- **Problem:** Public responses included unused event/training IDs, training order, server date, gallery photo IDs/dimensions/slugs and extra cover data. Published past events were sent then hidden by JavaScript.
- **Attack scenario:** A caller bypasses UI filtering and obtains historical published records or unnecessary metadata.
- **Impact:** Excess disclosure relative to the current UI's needs. These IDs were not secrets and no IDOR followed merely from their presence; unpublished records were already filtered.
- **Fix applied:** Explicit public-field projections; upcoming-event filtering in PHP using the application timezone; minimal write acknowledgments; smaller list/cover/photo responses. Keep album IDs because the public route needs them.
- **Remaining risk:** Public titles, captions and image URLs necessarily cross the network. Published content can be copied. Admin endpoints still receive the IDs/metadata required for authorized management.

### L04 — Identifier validation accepted some JSON type coercions — LOW

- **Affected:** `config/bootstrap.php::valid_id`.
- **Problem:** Scalar conversion could turn `true` or a whole-valued float into a valid numeric identifier.
- **Attack scenario:** A client submits a boolean/float where the contract expects a positive integer.
- **Impact:** Ambiguous input handling; existing authentication/CSRF/object checks prevented this from becoming an authorization bypass.
- **Fix applied:** Accept only PHP integers or canonical decimal strings, then apply positive/range checks. Reject booleans, floats, arrays and objects.
- **Remaining risk:** Numeric IDs remain enumerable by design; resource authorization, not obscurity, controls access.

### L05 — Setup minimum password length counted bytes, not characters — LOW

- **Affected:** `admin/setup.php`, `admin/login.php`.
- **Problem:** A password with six two-byte characters could satisfy the advertised 12-character minimum.
- **Attack scenario:** During authorized one-time setup, a shorter-than-advertised multibyte password is accepted.
- **Impact:** Weaker minimum policy than intended, not password disclosure.
- **Fix applied:** Validate UTF-8, count at least 12 Unicode code points, retain the 72-byte upper bound for PHP's current default bcrypt compatibility, reject NUL, use `PASSWORD_DEFAULT` and `password_verify` without custom password cryptography.
- **Remaining risk:** Character count is not an entropy guarantee. Existing passwords are not silently changed; owners should choose a strong unique passphrase. No arbitrary composition rules were added.

### L06 — Rate-limit state had no explicit application-size bound — LOW

- **Affected:** `config/bootstrap.php::rate_limit`.
- **Problem:** Expiry pruning existed, but live bucket count and file read size were not bounded.
- **Attack scenario:** Many distinct source IPs within a window grow limiter state and increase processing costs.
- **Impact:** Resource pressure on a small shared-host application.
- **Fix applied:** Private 0700/0600 storage, symlink refusal, locked reads/writes, validated state, 1 MiB read cap, 4096 live-bucket cap, exact-write checks and fail-closed behavior. Successful login clears its temporary failed-attempt budget.
- **Remaining risk:** Shared-IP users share limits. Distributed sources can exhaust the bounded pool and temporarily deny new attempts. A corrupted/unwritable limiter fails closed and requires private-storage repair. No cross-host distributed rate limiting is claimed.

### L07 — Important security rejections lacked consistent safe logging — LOW

- **Affected:** New `config/security.php`; auth/login/validation/upload/gallery/bootstrap call sites.
- **Problem:** Existing unexpected-error logs did not provide consistent events for rejected authentication, CSRF or upload validation.
- **Attack scenario:** Repeated suspicious requests are harder to distinguish and investigate.
- **Impact:** Reduced operational visibility, not a direct access bypass.
- **Fix applied:** Fixed event names and bounded allowlisted diagnostic context for login failures, rejected CSRF, honeypot submissions, upload failures, rate limits, revoked sessions, recovery issues and unexpected errors. No plaintext passwords, tokens, cookies, full session identifiers, SQL or contact content are intentionally logged.
- **Remaining risk:** Hosting access/PHP logs have their own policies. Restrict access, rotate/retain logs appropriately and monitor disk use; logging is not alerting or incident response. Diagnostics intentionally omit exception text that might contain secrets.

### I01 — Static upload privacy depends on the actual web server — INFORMATIONAL

- **Affected:** Apache access rules, generated album rules, retained legacy `vercel.json`, deployment instructions.
- **Problem:** Static upload files depend on Apache honoring the rules; PHP cannot enforce access when a different server serves those files directly.
- **Attack scenario:** Deploy the tree to a static server, PHP development server or unconfigured Nginx that ignores `.htaccess`; a known draft image URL may be served.
- **Impact:** Unsafe deployment despite correct PHP endpoint checks. Such a production deployment was not observed or tested.
- **Fix applied:** Document the supported Apache/shared-host/XAMPP deployment, verify real Apache draft denial and script denial, retain the intended architecture. Optional private configuration path and explicit PHP production-HTTPS guard were added.
- **Remaining risk:** The operator must verify the actual host. Do not use the legacy Vercel static configuration to deploy the PHP application, or treat the PHP built-in server as a complete security-equivalent host.

## Forty-five-area review matrix

| # | Area | Reviewed result / control |
| --- | --- | --- |
| 1 | Authentication | All non-public Admin routes share PHP auth; public exceptions only login/setup. Privileged APIs authenticate independently. Hash verification and post-login ID rotation already present; credential revocation added. |
| 2 | Sessions | Strict/cookie-only sessions, HttpOnly, SameSite=Lax, HTTPS Secure, explicit URL-ID disabling, checked session start, 30-minute idle/12-hour absolute expiry. |
| 3 | Authorization | Server-side single full-administrator role; forged role/is_admin fields confer no rights. All admins intentionally manage all records. |
| 4 | CSRF | Session-bound 32-byte random token, `hash_equals`, rotated at login; all create/update/delete/publish/upload/order/cover/message-status/logout mutations checked. |
| 5 | SQL injection | Every input-bearing query reviewed; PDO native prepared statements. Dynamic table names limited to events/training; columns come from fixed server-returned field maps, never submitted keys. Pagination/order fragments are server-validated. |
| 6 | XSS | PHP `h()` uses ENT_QUOTES/ENT_SUBSTITUTE UTF-8; public text uses safe text/escaping, constrained local image URLs. Contact text is escaped in Admin. No exploitable sink confirmed. |
| 7 | Contact | POST only, trimmed bounded fields, email/phone/enum validation, prepared insert, server-generated unread/timestamps; honeypot and request caps added. |
| 8 | Rate limiting | Contact 5/600s/IP, login 10/900s/IP, setup 5/900s/IP; upload 100 images +120 requests/600s/admin. No JavaScript throttle is trusted. |
| 9 | Brute force | Temporary IP budget, generic login error, dummy verification for unknown usernames, no permanent account lockout. Not protection against every distributed attack. |
| 10 | Database configuration | Credentials only PHP/server environment. Native PDO, no browser configuration. Optional absolute private config path; protected local fallback. |
| 11 | Errors | Generic public 503 JSON/HTML, no returned SQL/stack/path; bounded server diagnostics. Hosting must disable startup/parse-error display as well. |
| 12 | Methods | Public read routes GET, contact POST, content writes authenticated POST actions; unsupported methods 405/Allow. Admin GET shows forms; POST performs changes. |
| 13 | JSON | Bounded raw read, JSON_THROW_ON_ERROR, depth limit, object root, explicit field validators; unexpected keys ignored, never assigned to arbitrary columns. |
| 14 | Validation | Positive integer IDs, real dates, same-day valid time ranges, bounded UTF-8 strings, enum/flag/range checks; stricter ID types and password character count added. |
| 15 | Mass assignment | Explicit input-to-field maps; no loop over arbitrary posted keys builds SQL. Server timestamps/identity/file metadata are not client writable. |
| 16 | IDOR | Draft/missing gallery albums return 404 even to signed-in callers of the public API; photo operations check album membership. Events/training public filters enforced. Messages Admin-only. |
| 17 | Upload content | Actual upload status/bytes, Fileinfo and getimagesize agree with supported JPEG/PNG/WebP; decode/re-encode with GD. Client MIME never authoritative. |
| 18 | Filenames | Generated 128-bit random names; original basename private metadata only, never used as a destination. |
| 19 | Extensions | Stored extension selected by server encoder; strict generated `.webp`/`.jpg` names. Executable/SVG/etc sources refused. |
| 20 | Upload directory | Apache filename allowlist, handler/type removal, execution denial and nosniff; real PHP canary denied. Draft directories denied. |
| 21 | Image processing | Server chooses MIME/extension/storage/resize/quality/thumbnail; no browser image processing authority. EXIF re-encoding strips metadata. |
| 22 | Upload limits | 15 MiB/source, 40 megapixels, 12000 px/side, memory estimate, 1–50 files/request, 20 MiB request plus PHP/Apache caps and new account budget. |
| 23 | Traversal | All file read/delete/include/write sites reviewed. Fixed includes, trusted generated gallery paths, strict DB filename validation and symlink refusal. No user-selected filesystem path. |
| 24 | File deletion | Validated IDs load metadata, validate album/name/path, stage privately, mutate under transaction/row lock; client path fields ignored. |
| 25 | Headers | PHP + Apache nosniff, strict-origin-when-cross-origin, Permissions-Policy, CSP and DENY framing; no X-Powered-By from PHP. |
| 26 | CSP | Enforced policy works with local scripts and Google Fonts; no unsafe-inline/eval. Tested real UI and a blocked inline-script canary. |
| 27 | HTTPS | Secure cookies follow trusted server HTTPS only. Production option rejects HTTP PHP requests; host must redirect static/public traffic. Local HTTP remains enabled by default. No application mixed-content subresource dependency identified. |
| 28 | CORS | No wildcard or cross-origin API access headers. Same-origin frontend API base guard is convenience/privacy, not authorization. |
| 29 | API data | Explicit minimum public fields; published/upcoming/active filters; messages GET always 405. No password hashes, config, paths, session state or private originals in public JSON. |
| 30 | Caching | Admin/PHP/API no-store. Uploaded images require revalidation, draft access checked by Apache; already downloaded files cannot be revoked. |
| 31 | DB privileges | Dedicated runtime user with SELECT/INSERT/UPDATE/DELETE recommended; schema import/migrations by maintenance account. Test-only root not a production setting. |
| 32 | Password policy | At least 12 Unicode characters and at most 72 bytes during setup; modern PHP default hashing, rehash on login; no custom password crypto. |
| 33 | Secrets | Current tracked files and 154 distinct relevant blobs reachable from dev history scanned for credential/key/token patterns; no live secret candidate confirmed. Placeholder config and a public dummy hash are not real credentials. |
| 34 | Navigation | Direct unauthenticated requests to all 26 protected Admin routes denied/redirected, irrespective of buttons or links. |
| 35 | Tampering | Raw HTTP tests forge role/status/IDs/timestamps/file path/size/dimensions, omit tokens, use unsupported methods and cross-album references. Server remains authoritative. |
| 36 | Source of truth | PHP/DB owns timestamps, admin/session identity, password hash, filename/path, dimensions/MIME/encoded sizes, order changes, counts, public filtering and publication authorization. |
| 37 | Transactions | Gallery row locking, private staging and cleanup reviewed; failure callbacks now execute before rollback unlocks. Injected insert/delete failures checked. |
| 38 | Races | Per-album SQL row lock serializes upload/reorder/cover/delete; publish re-reads state under lock after commit; setup uses named DB lock. Not a formal exhaustive concurrency proof. |
| 39 | Public directory | Sensitive directories/dotfiles denied; docs rule added. Prefer config outside public_html, exclude source/test/backup artifacts from deployment. |
| 40 | Backups | No dangerous tracked backup archive found; extension deny coverage expanded and harmless backup canaries tested. |
| 41 | Indexes | Options -Indexes and directory-specific denial; actual HTTP directory probes performed. Host must permit equivalent settings. |
| 42 | Admin URL | `/admin/` intentionally remains discoverable; security comes from PHP checks, not an obscure URL. |
| 43 | Logging | Safe bounded security events added; no authentication secrets/contact content deliberately logged. Private host log rotation is manual. |
| 44 | Personal data | Contact data only stored in MySQL and shown to authenticated Admin with escaping; read state/deletion CSRF-protected. No email/third-party transmission introduced. Retention policy is operational. |
| 45 | Dependencies | Production stays native PHP/PDO/GD/vanilla JS. Python/Pillow/Chromium used only locally for verification; no new frontend library or backend service. Update host PHP/GD/MySQL/Apache through its maintenance process. |

## Browser/server trust boundary and network contracts

| Client input or UI behavior | Authoritative server behavior |
| --- | --- |
| User credentials, session cookie | PHP verifies hash and stored session state, checks credential fingerprint/expiry and rotates session identity. |
| ID, title, date, enum, publication request | PHP validates allowed fields/types; authentication and CSRF authorize mutation; DB selects actual record. A flag from the browser is an admin's requested change, not proof of permission. |
| Photo bytes, claimed MIME/name/size/dimensions | PHP measures, inspects, decodes, limits, re-encodes, names and stores. Forged derived metadata is unused. |
| Photo/album IDs, move direction, cover request | PHP checks membership, locks album, calculates ordering and cover fallback. No client file path is accepted. |
| Contact input, forged unread/timestamps | PHP validates only allowed contact fields; DB/server supplies default unread status and timestamps. |
| Previously client-filtered upcoming dates | PHP selects upcoming public events using APP_TIMEZONE. The browser only renders the returned order and takes the first three for the homepage layout. |
| Upload progress, selected-file count/size, translations, dates for display, viewer index/swipes | UI-only hints and presentation. None grants permissions or determines file acceptance/stored metadata; the server repeats every relevant acceptance check. |

No pricing/payment calculation existed to migrate. Compression, ordering, photo counts, timestamps, password decisions and authorization were already server-side and were verified rather than falsely described as newly moved. The actual moved rule is upcoming-event selection; new upload quotas/body limits are additional server controls.

Public events return date/title/description/location/type. Training returns weekday/start/end/type/location. Gallery list returns album route ID/title/location/count/cover thumbnail+alt; detail adds description/date and caption/alt/thumbnail/image URLs. Messages return a confirmation, never the message list or stored row. Authenticated content writes acknowledge only ID. Same-origin HTTP requests/responses remain necessary; nothing in this audit claims zero network traffic.

## Verification performed

Local disposable data only: PHP 8.3 with PDO MySQL/Fileinfo/GD/EXIF, MariaDB 10.11, Apache 2.4, Chromium. Both domain-root and `/ofencing/` Apache deployments were exercised. No production data was touched.

| Verification | Evidence / result |
| --- | --- |
| Existing integration suite | `tests/integration.py` passed real events/training CRUD, contact/inbox read/unread/delete, publication/active filters, escaping, validation, auth/CSRF/logout, throttling, private files and 404 at root and subdirectory. |
| Existing gallery suite | `tests/gallery_integration.py` passed at root and subdirectory: real JPEG/PNG/WebP processing, compression, thumbnail dimensions, drafts/static privacy, size/pixel guards, partial success, captions/order/cover, photo/album deletion. |
| New security suite | `tests/security_integration.py` passed at root and subdirectory. Enumerates 26 protected Admin routes; missing/bad CSRF matrix; unauthenticated/forged-role mutations; SQL-like/XSS/traversal strings; negative/boolean/float/missing/unknown IDs; invalid JSON/depth/dates/times/enums; oversized strings/bodies; fake MIME/formats; cross-album denial; mass-assignment/metadata tampering; minimal public keys; honeypot; headers/no-store/no-CORS; backup/script canaries; login and account-wide upload throttling. |
| Live browser regression | Real login, album creation, mixed upload failures and one 50-photo selection; compression-backed gallery display; captions and literal XSS text; viewer keyboard/focus/touch; FR/EN/AR and RTL; 320–1440 px layouts; all public page navigation; empty/404/503/broken-image states; real contact→Admin and event/training edit/delete flows. No legitimate CSP violation or JavaScript error observed. Inline canary blocked by enforcing CSP; browser web security was not disabled. |
| Session/TLS tests | Real local TLS cookie Secure/HttpOnly/SameSite=Lax; HTTP localhost cookies remain usable. Changed credential hash, deleted administrator, idle expiry and absolute expiry all rejected old sessions. Spoofed Host/X-Forwarded-Proto did not bypass production HTTPS guard. |
| Raw chunked requests | 32 KiB-plus URL-encoded requests using Transfer-Encoding: chunked were refused with 413 by Apache and separately by the PHP bounded-reader path. |
| Password setup boundary | Separate empty disposable DB: six multibyte characters rejected; twelve accepted; setup subsequently 404. |
| Controlled failure injection | A delayed SQL publication failure never opened the draft access file. A trigger rejecting photo INSERT left no new generated files or row. A thumbnail symlink caused deletion refusal without touching its target; the earlier staged image and metadata were restored. A callback assertion verified an active transaction during cleanup and none after rollback. |
| Source / history checks | PHP lint, JavaScript syntax checks, whitespace/diff checks; all input-bearing SQL and filesystem sites reviewed; no observed live secret in targeted current/dev-history scan. |

### Re-running the committed tests

Use an isolated local database, temporary administrator and Apache installation. Never point these scripts at real inquiries or production credentials. Set `OFENCING_TEST_BASE_URL`, `OFENCING_TEST_USERNAME`, `OFENCING_TEST_PASSWORD`, `OFENCING_TEST_ALLOW_WRITES=yes`, and `OFENCING_TEST_APACHE=yes`. The gallery/security scripts require development-only Pillow. Set `OFENCING_TEST_REPO` for the gallery disk assertions. Run separately:

```sh
python tests/integration.py
python tests/gallery_integration.py
python tests/security_integration.py
```

The scripts deliberately exhaust some local rate budgets. Wait for windows to expire between suites, or restart with fresh private limiter storage only in the isolated test environment; do not disable/reset production controls. Fixtures are prefixed and cleaned up; security canaries require a writable disposable checkout. TLS, session-age manipulation, SQL failure injection and full browser checks were additional local harness checks, not bundled as production-accessible diagnostic endpoints. There is no `phpinfo` or debug endpoint to deploy.

## Required operator actions and residual limitations

1. **Production TLS:** enable the certificate and canonical HTTPS redirect in the hosting panel; set `APP_REQUIRE_HTTPS=true` only after HTTPS works. PHP's guard rejects HTTP, it does not redirect static HTML. A trusted proxy must set the real HTTPS server state. Do not use client Host/forwarding headers as a localhost exemption. Consider host-managed HSTS only after validating the domain/subdomains; none is blindly enabled here.
2. **Apache:** verify `.htaccess`, authorization/headers/rewrite/handler directives and `Options -Indexes` actually take effect. Check a draft image URL (403), a published image (200), private files (403), upload script canary (403), and normal pages. Never deploy this backend using the retained static Vercel configuration. A different server needs equivalent rules before serving uploads.
3. **Configuration and DB:** prefer `/home/account/private/ofencing.php` via server-set `OFENCING_CONFIG_FILE`; fallback `config/local.php` must remain HTTP-denied and ignored by Git. Production runtime user gets only SELECT/INSERT/UPDATE/DELETE for this DB. Import schema separately; **this audit requires no migration**. Do not use root or commit credentials. Remove SETUP_TOKEN after setup.
4. **PHP/storage:** enable PDO MySQL, Fileinfo and GD; EXIF for orientation. Recommended starting limits: upload_max_filesize=15M, post_max_size=20M, memory_limit=512M, max_file_uploads=20, subject to host quotas. Ordinary bodies are 32 KiB, uploads 20 MiB. Set display_errors=Off/log_errors=On in hosting configuration as well; keep sessions, limiter files and logs private/writable, with rotation and disk monitoring. No blanket 0777 permissions.
5. **XAMPP:** use Apache/PHP/MySQL, import/configure the existing schema, enable extensions and restart Apache. Keep APP_REQUIRE_HTTPS=false for `http://localhost/ofencing/`; Secure cookies are automatically off on HTTP. Confirm AllowOverride/equivalent access rules. Linux Apache root/subdirectory behavior was tested; Windows ACL/rename/configuration behavior still needs the README manual checks.
6. **Deploy/back up consistently:** preserve existing generated uploads and album access files. Back up DB and uploads together. Investigate private `.trash-*` recovery warnings, missing files and publication-access mismatches after storage/process failures. Do not expose SQL/docs/tests/Git/archives/logs through public_html.
7. **Credentials/history:** no real secret was identified, so no specific leaked credential is asserted. Scans are heuristic and limited to reachable dev history, not unrelated branches, hosting settings or inaccessible deleted objects. If any real secret was ever committed elsewhere, **rotate it**; deleting the current file does not remove the compromised secret from history/copies.
8. **Operational limits:** one full-admin role, no MFA, no distributed rate service, no gallery storage quota/pagination, no exhaustive race/load/fuzz proof. Host-level DoS protection and updates remain necessary. Original photos are not archived; preserve originals separately if needed. Contact retention/deletion and privacy wording need owner decisions. Already published/downloaded information cannot be recalled.
9. **Compatibility:** public events now exclude past records and unused response fields were intentionally removed. The repository frontend/tests were updated; any unlisted external API consumer must adopt the minimized contract. Existing sessions require login again. Google Fonts still makes external font requests in normal use; no contact data is sent to that service.

## Files changed by this security work

Created: `config/security.php`, `docs/.htaccess`, `docs/security-audit.md`, `tests/security_integration.py`.

Modified: `.gitignore`, `.htaccess`, `README.md`; `admin/.htaccess`, `admin/_common.php`, `admin/login.php`, `admin/setup.php`, `admin/gallery/.htaccess`, `admin/gallery/upload.php`; `api/.htaccess`, `api/_common.php`, `api/gallery.php`; `config/auth.php`, `config/bootstrap.php`, `config/gallery.php`, `config/gallery-images.php`, `config/local.example.php`, `config/validation.php`; `contact.html`, `styles2.css`, `js/api.js`, `js/app.js`; `tests/integration.py`, `tests/gallery_integration.py`; `uploads/.htaccess`.

No production credentials, test accounts/photos, binary runtimes, database dumps or test-only server harnesses belong in the commit. No schema or architecture replacement was introduced.

## Implementation references

Primary documentation used to check configuration behavior: [PHP session security configuration](https://www.php.net/manual/en/session.security.ini.php), [PHP upload pitfalls](https://www.php.net/manual/en/features.file-upload.common-pitfalls.php), [Apache response headers](https://httpd.apache.org/docs/2.4/mod/mod_headers.html), [Content Security Policy reference](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy). Findings above come from this repository and local tests, not from assuming every checklist item was vulnerable.
