# Dior Medical — Deep Code Study (verified against current HEAD)

**Repository:** `vc06031994-ai/dior-new`
**Branch analysed:** `arena/4c4c1ca1-dior-new` @ `442301b` ("LATEST update 6:39pm")
**Analysis date:** 2026-11-16
**Method:** full static read of all 109 tracked files (46,858 lines of PHP, ~2.3 MB of JS, 1.6 MB CSS). No PHP runtime was available in the sandbox (`php` not installed), so this is code-reading + targeted greps, not execution. JS was syntax-checked with `node --check` (all 16 hand-written bundles pass).

> This document supersedes `docs/DEEP-ANALYSIS.md`, which was written against the older commit `146fb69` and is now partly stale. Section 6 explicitly tracks **which of those earlier findings are fixed and which are still live**.

---

## 1. What this repo is

A single WordPress plugin that is really a full telehealth application:

| | |
|---|---|
| Plugin header | `Dior Medical - Patient Portal & Dashboard`, v17.2.20, author Vulture Concepts |
| Internal constant | `DIOR_PORTAL_VERSION` = `17.2.19` (**mismatch with header**) |
| DB schema version | `Dior_DB::DB_VERSION` = `4.0.5` (**third version number**) |
| Entry point | `dior-medical-dashboard.php` (5,854 lines) |
| Second root file | `dior-doctor-dashboard.php` (2,685 lines) — **not a plugin**, `require_once`d from the main file at line 5,721 |
| Tracked size | 6.8 MB (109 files) |

There is no `readme`, `LICENSE`, `composer.json`, `.gitignore`, CI config, or test suite. Git history is a single commit, so there is no way to see intent or regression history.

### Portals and shortcodes

| Shortcode | Where | Gate |
|---|---|---|
| `[dior_patient_dashboard]` | main file | logged-in (any role) |
| `[dior_doctor_dashboard]` | doctor file | `administrator` / `manage_options` / role `doctor` (`Dior_Doctor_Dashboard::can_access()`, line 122) |
| `[dior_login_form]`, `[dior_registration_form]`, `[dior_lost_password_form]`, `[dior_reset_password_form]` | main file | public |
| `[dior_book_visit_page]` | main file | public |

### Feature surface (all present in code)

Auth + onboarding (custom AJAX login/register/reset, 18+ gate), appointments (booking → status machine → history), telemedicine (Zoom S2S OAuth primary, Google Meet OAuth fallback, `tel:` for phone visits), clinical encounters (SOAP + vitals + ICD-10 + SHA-256 attestation), e-prescriptions, documents vault, invoices/payments, in-app notifications + 15 HTML email templates, audit log, admin analytics (ApexCharts + CSV), HIPAAtizer intake/questionnaire webhooks, DocBooker sync, demo-data seeder.

---

## 2. Architecture map

```
dior-medical-dashboard.php          5,854 L   plugin header, Dior_Patient_Portal_Data (data layer + HIPAAtizer
                                             importer), Dior_Medical_Auth (shortcodes + ~37 AJAX endpoints),
                                             enqueue_assets(), page auto-creation, DocBooker glue
dior-doctor-dashboard.php           2,685 L   Dior_Doctor_Dashboard (render + 24 AJAX endpoints),
                                             Dior_Doctor_Resolver (legacy account consolidation)
includes/  (16 files, ~7,000 L)              Dior_DB, Dior_Auth_Service, Dior_Appointment_Service,
                                             Dior_Encounter_Service, Dior_Notification_Service (1,024 L),
                                             Dior_Medical_Secure_Files, Dior_Audit_Service, Dior_Analytics,
                                             Dior_Medical_REST_API (dior/v2), Dior_Doctor_Dynamic,
                                             Dior_Questionnaire_Service, Dior_Zoom_Meetings,
                                             Dior_Google_Meet, Dior_Demo_Data
rest_api/  (5 files, ~1,530 L)               HIPAAtizer REST bridge + 3 standalone HTTP entry points
templates/ (64 files, 29,829 L)              patient-dashboard.php, doctor-dashboard.php, 15 patient tabs,
                                             30 doctor tabs, 12 email templates, modals
assets/    (22 files, 6.4 MB)                dior-custom.css (57,738 lines / 1.6 MB), 16 hand-written JS
                                             bundles, Bootstrap, ApexCharts, html2pdf
```

**Two god files.** `dior-medical-dashboard.php` mixes plugin bootstrap, data access, REST registration, 37 AJAX handlers and HTML rendering. `dior-doctor-dashboard.php` does the same for the provider side. Neither has a namespace; everything is a global static class.

**Duplicated logic between the two root files** (same name, different implementation/bug state): `enqueue_assets`, `ajax_upload_avatar`, `ajax_upload_signature`, `ajax_change_password`, `ajax_remove_avatar`, `ajax_save_profile`, `ajax_get_live_notifications`, `install_schema`. The two JS bundles `dior-doctor.js` and `dior-patient-dashboard.js` each define a global `diorShowMeetingJoinModal()` — both are enqueued on every page, so the later file silently wins.

---

## 3. Request / render flow

```
Browser ── admin-ajax.php?action=dior_* ──>  Dior_Medical_Auth (37 handlers)  ─┐
        └─ admin-ajax.php?action=dior_doctor_* ─> Dior_Doctor_Dashboard (24) ──┤
                                                Dior_Doctor_Dynamic (auth'd)  ─┴─> services ─> $wpdb tables
                                                                                          └─> wp_usermeta (dual write)
HIPAAtizer ── /wp-json/dior/v1/hipaatizer ─────> dior_handle_hipaatizer_data ──> save_hipaa_intake()
           ├─ /wp-json/dior/v1/hipaa-webhook ──> rest_hipaa_webhook ─────────────┘
           ├─ /wp-json/dior/v1/questionnaire ──> Dior_Questionnaire_Service
           └─ plugins/.../rest_api/hipaatizer_*.php (3 direct file entry points)
```

Rendering: shortcode → gather **all** data → `include` 14 patient tabs (or 26 doctor tabs) in **one** page load, toggled client-side by CSS. Every dashboard view therefore loads every query, every table and every modal at once.

---

## 4. Data model

11 tables from `Dior_DB::install_schema()`:

`dior_encounters`, `dior_documents`, `dior_appointments`, `dior_audit_logs`, `dior_appointment_history`, `dior_notifications`, `dior_prescriptions`, `dior_invoices`, `dior_payments`, `dior_reviews`, `dior_patients`

+4 tables from `Dior_Doctor_Dynamic::install_schema()` (hooked on `plugins_loaded`, so schema is installed by two independent code paths): `dior_telemedicine`, `dior_chat_messages`, `dior_doctor_settings`, and a second `dior_reviews` definition.

Schema quality is actually good: proper `BIGINT UNSIGNED` PKs, unique `*_uid` keys, ENUMs for status fields, `is_deleted` soft deletes, and sensible secondary indexes.

### The three data-model defects that cause most of the bugs below

1. **`patient_id` has two meanings.** In `dior_appointments`, `dior_encounters`, `dior_documents` it is a **WP user ID** (see `Dior_Appointment_Service::book_appointment()` at line 83, which validates and anti-IDOR-checks it as a user ID). In `dior_patients` the PK `id` is a separate row ID while `user_id` is the WP user. Handlers frequently treat a `dior_patients.id` value as if it were a user ID. `Dior_Doctor_Dynamic::patient()` papers over this by accepting "`user_id` OR `patient_uid`" in one query, which means a numeric `id` that happens to equal a different user's ID resolves to the wrong person.
2. **Dual-write to `wp_usermeta`.** Every entity is also serialized into user meta and read back from there in several places: `update_user_meta(..., 'dior_appointments', ...)` at `includes/class-dior-appointment-service.php:759`, `dior-medical-dashboard.php:5482`, `:5663`, `rest_api/hipaatizer_rest_api.php:391`, and `'dior_payments'` at `dior-medical-dashboard.php:4589`. Reads prefer meta in parts of the patient dashboard, relational tables in others. "Which copy is the truth?" has no answer, and `save_hipaa_intake()` synthesises a fake appointment row into the meta copy (`dior-medical-dashboard.php:5467`).
3. **No migration path between the two schemas.** `Dior_DB` bumps `dior_medical_db_version`; `Dior_Doctor_Dynamic` uses `dbDelta` on every `plugins_loaded`. There is no `register_activation_hook` and no `uninstall.php` anywhere in the repo.

---

## 5. What is genuinely good (keep this)

- **SQL hygiene is strong.** 136 `prepare()` call sites vs only 2 genuine unprepared `query()` sites (an `ALTER TABLE` in `dior-doctor-dashboard.php:1965` and `SHOW TABLES LIKE` with a `$wpdb->prefix`-built string). No request data was found concatenated into SQL.
- **PHP escaping is disciplined.** 1,195 escape/`intval` calls; zero raw `echo $var` in `templates/`; the only 91 unescaped echoes repo-wide are small local values in `doctor-tabs/all-patients.php` (still worth fixing — see §7.4).
- **A real service layer exists.** `Dior_Auth_Service`, `Dior_Appointment_Service`, `Dior_Encounter_Service`, `Dior_Audit_Service`, `Dior_Notification_Service` are clean seams to refactor behind.
- **The documents vault is good.** `includes/class-dior-secure-files.php`: extension allow-list, `finfo` magic-byte check, 25 MB cap, `move_uploaded_file`, `realpath()` prefix check against the vault dir, `.htaccess` deny + `index.php`, audit logging on both success and denial, and a proper anti-IDOR gate on the streaming download (`handle_file_stream_request()`, line 484-497).
- **Intake webhooks have moved in the right direction.** `rest_api/hipaatizer_direct_webhook.php` now **fails closed** when no secret is configured (line 66-74) and uses `hash_equals`.
- **Google Meet OAuth** uses a hashed one-time state transient; Zoom S2S OAuth keeps credentials server-side and never echoes them to the admin UI.
- **All 16 hand-written JS files pass `node --check`.**

---

## 6. Status of the previous analysis (`docs/DEEP-ANALYSIS.md`, commit `146fb69`)

Re-checked every P0/P1 item against current `442301b`:

| # | Earlier finding | Status now | Evidence |
|---|---|---|---|
| 6.1 | `verify_ajax_nonce()` fails open for any logged-in user | **STILL LIVE — critical** | `includes/class-dior-auth-service.php:135` `if (!$passed && is_user_logged_in()) { $passed = true; }`. All 47 call sites inherit this. |
| 6.2 | Unauthenticated PHI write endpoints | **PARTIALLY FIXED / partly worse** | `hipaatizer_rest_api.php:23,30` still `__return_true` and still only validates the token *if* a secret is set (line 449-460) → open when unconfigured. `dior-medical-dashboard.php:1016` `/hipaa-webhook` still `__return_true` with **no token check at all**. `save_hipaa_intake()` still trusts `user_id` from the body (`:5147-5152`). `rest_api/hipaatizer_webhook.php` and `hipaatizer_questionnaire_webhook.php` are direct file endpoints with **no authentication whatsoever** and `Access-Control-Allow-Origin: *`. |
| 6.3 | IDOR on `/dior/v1/patient/{id}` | **STILL LIVE — critical** | `rest_api/hipaatizer_rest_api.php:37` permission is `is_user_logged_in()`; the handler (`:520`) queries `dior_patients WHERE id = %d` and returns profile + `dior_hipaa_intake`. Any subscriber (self-registration is open, `:2845`) can enumerate `1..N`. |
| 6.4 | Anonymous booking can attach to an existing patient | **STILL LIVE** | `dior-medical-dashboard.php:3996-4001` looks up `get_user_by('email', …)` from the request body with no verification and writes to that user; booking then defaults `payment_status => 'Paid'` (`:4121`) via `book_appointment()` default (`class-dior-appointment-service.php:109`). |
| 6.5 | Fake "Payment processed via Stripe" | **STILL LIVE** | `dior-medical-dashboard.php:4568-4600`: flips the meta status to `Paid`, sends a receipt, returns `"Payment processed successfully via Stripe! Receipt issued."`. No gateway exists in the repo; the receipt template even says `Paid (Stripe 256-Bit SSL)` (`class-dior-notification-service.php:560`). |
| 6.6 | Fabricated clinical data in the live patient portal | **STILL LIVE — worse than described** | `templates/patient-dashboard.php:290` still includes `templates/patient-tabs/_design-static-data.php` unconditionally. It forces **10 fake payments** (`$payments = [...]`, no guard, line 21) and **10 fake insurance claims** for every patient; and it *appends* fake appointments to the real array (`for ($i = count($appointments); $i < 10; $i++) $appointments[] = …`, line 118) so a patient with 3 real visits sees 7 invented ones. It also injects a fake identity + a fabricated **penicillin allergy** when intake is empty (`:68-93`, `:51-64`) and loads an avatar from `images.unsplash.com`. On top of that, `templates/patient-tabs/payments.php:54` ships a **separate hardcoded `$billing_mock`** of 10 fake invoices (2016 dates) that is what actually renders in the Billing tab. |
| 6.7 | `ensure_default_doctor()` + `doctor@gmail.com` | **STILL LIVE** | `dior-doctor-dashboard.php:2402` `DEFAULT_DOCTOR_EMAIL = 'doctor@gmail.com'`, hooked on **every** `init` (`:50`), still rewrites an existing doctor's email (`:2437-2448`) and force-adds the role. |
| 6.8 | `dior/v2` broken and unsafe | **STILL LIVE** | `includes/class-dior-rest-api.php:385` still `WHERE user_id = %d AND appointment_date BETWEEN …` against `wp_postmeta`; `:501-513` prescriptions query postmeta; `:471 update_appointment()` still allows any logged-in user to write meta on any post ID; `check_auth_permission()` is still `is_user_logged_in()` (`:264`). |
| 6.9 | No rate limiting / 6-char passwords | **STILL LIVE** | zero `rate_limit`/`lockout`/`login_attempt` code; `dior-medical-dashboard.php:2819` and `:3045` still enforce only `strlen < 6`. |
| 6.10 | Nonce computed then discarded (login CSRF) | **STILL LIVE** | `$nonce_valid = wp_verify_nonce(...)` at `:2677`, `:2767`, `:2896`, `:2922` — assigned 4×, never read 4×. |
| 6.11 | Audit IP spoofable via `X-Forwarded-For` | **STILL LIVE** | `includes/class-dior-audit-service.php:74-78` takes the first `X-Forwarded-For` value unconditionally. |
| 6.12 | Vault blast radius | **PARTIALLY FIXED** | `.htaccess` + `index.php` + streaming gate present; still under `wp-content/uploads/` (no Nginx/IIS protection, no encryption at rest, no malware scan). Salt fallback `NONCE_SALT ?: 'dior_medical_secret'` remains (`:119`). |
| 6.13 | XSS via JS `innerHTML` | **WORSE by count** | 370 `innerHTML` assignments now (was 296); `escapeHtml()` is still only defined locally inside two functions (`dior-dashboard.js:3377`, `dior-patient-dashboard.js:2404`). |
| 6.14 | Hardcoded demo credentials `DiorDemo!2026` | **STILL LIVE** | `includes/class-dior-demo-data.php:51` (admin-tool gated, but a real working password in the repo). |
| 6.15 | CDN leaks, no readme/CI | **STILL LIVE** | Font Awesome from `cdnjs.cloudflare.com` and Bootstrap Icons from `cdn.jsdelivr.net` on every page (`:1044-1058`); no readme/CI/tests still. |
| 7 | Assets loaded on every page of the site | **STILL LIVE** | `add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets'])` at `:930` with **no page condition**; 21 enqueue calls, ~6.4 MB, plus `time()` used as the cache-busting version for two bundles (`:1169`, `:1189`) so they are re-downloaded on every single request. |

**Net:** of 7 previously-P0 items, **0 are fully fixed, 2 are partially improved** (webhook secret on one of four endpoints, vault hardening), 5 are unchanged. The main *regression* since the old doc is the volume of `innerHTML` and the newly found `payments.php` mock table.

---

## 7. New findings (not in the earlier document)

### 7.1 🔴 Open provider-record write through `ajax_edit_appointment`
`dior-doctor-dashboard.php:776-920`. The handler checks the nonce + `can_access()` (i.e. "am I a doctor?"), then accepts **`patient_id` straight from the request** and, at line 869-880, writes that user's `first_name`, `last_name`, `phone`, `gender`, `dob`, plus `dior_patients` fields and insurance meta. There is **no ownership check** (`patient_belongs_to_doctor` / `can_access_patient`) on this path, unlike `ajax_get_patient_detail` (`:2185`) and `ajax_save_soap_note` (which is also unowned — see 7.2). Any doctor account can therefore rewrite any patient's demographic record by ID, and can create appointments for arbitrary patients.

### 7.2 🟠 Doctor-side clinical writes are not scoped to the doctor's own patients
`ajax_save_soap_note` (`:2305-2340`) validates the patient ID but never verifies the patient belongs to the caller, so any `doctor` role user can write encounters/prescriptions against any user ID. `Dior_Doctor_Dynamic` does this correctly via `patient_belongs_to_doctor()` (line 137) — but that helper is only used in some of its own handlers (`ajax_patient_status`, `ajax_patient_delete`, `ajax_appointment_delete`) and is not applied to `ajax_get_patient_profile` (`:576`) or `ajax_chat_*` (`:736-737`).

### 7.3 🟠 Two admin-gated features auto-execute on public requests
- `ensure_pages_exist()` runs on `init` **and** `admin_init` (`dior-medical-dashboard.php:1021-1022`) and performs `wp_insert_post` side effects on ordinary page views.
- `Dior_Doctor_Resolver::ensure_default_doctor()` runs on `init` (`dior-doctor-dashboard.php:50`) — a user creation + `wp_new_user_notification()` on the first hit after the account is missing.

### 7.4 🟡 Unescaped PII echo in the doctor patient list
`templates/doctor-tabs/all-patients.php:136-156` echoes `$pid`, `$name`, `$puid`, `$phone`, `$blood`, `$address` raw (only `$address` in the `title=` attribute is passed through `htmlspecialchars`). These values originate from patient-editable profile fields, so a stored-XSS payload in a phone/address field would execute in a doctor's session — exactly the PHI-adjacent XSS path the earlier doc warned about, but in PHP rather than JS.

### 7.5 🟡 `patient_belongs_to_doctor()` is fail-open by design
`includes/class-dior-doctor-dynamic.php:140-143`:
```php
if ($user && array_intersect(['doctor','dior_doctor','administrator'], (array)$user->roles)) return true;
if (class_exists('Dior_Auth_Service') && Dior_Auth_Service::can_access_patient($pid, $did)) return true;
```
Two problems: it passes `$did` (a user ID) as the *user* argument to `can_access_patient`, and `can_access_patient()` returns `true` for **any** doctor, so the whole function collapses to "any doctor may access any patient". Combined with `doctor_can_manage_appointment()`'s "if only one doctor exists, they own everything" rule (`:44-46`), provider isolation does not exist in this codebase — that may be intentional for a single-provider clinic, but it is undocumented and untested.

### 7.6 🟡 `dior_patients` rows are created almost accidentally
Only one insert into `dior_patients` exists (`dior-medical-dashboard.php:4146`), and only inside the anonymous-booking DocBooker sync block, filled with placeholder values (`'phone' => '(555) 000-0000'`, `'dob' => '1990-01-01'`, `:4148-4153`). Registration (`:2845-2866`) **never** creates the row; `Dior_Doctor_Dynamic::patients()` synthesises rows from `get_users()` when the table is empty. So `dior_patients` is partly phantom data, and anything keyed on it (the `/patient/{id}` REST route, patient search) behaves inconsistently depending on whether the row exists.

### 7.7 🟡 Dead and never-included templates
`templates/doctor-tabs/consultation-room.php`, `form-test.php`, `notification-source.php`, `notifications-alerts.php` are tracked but **never included** by `templates/doctor-dashboard.php`; `templates/patient-tabs/consultation.php` is likewise never included. `notification-source.php` in particular is a full "reference panel" page with hardcoded fake patients (`Sarah Smith`, `John Doe`) and fake notification counts — dead weight that looks like live UI.

### 7.8 🟡 Two schema installers, two activation paths
`Dior_DB::check_and_update_schema()` runs on `plugins_loaded`; `Dior_Doctor_Dynamic::install_schema()` also runs on `plugins_loaded` (priority 20) and re-`dbDelta`s `dior_reviews` with a different definition. There is no uninstall routine, so removing the plugin leaves 15 tables of PHI behind.

### 7.9 🔵 Version drift
Header `17.2.20` vs `DIOR_PORTAL_VERSION` `17.2.19` vs `DB_VERSION` `4.0.5` vs the REST API comment "v2.8". Any code or ops check that compares these will be wrong.

### 7.10 🔵 Dead REST namespace
`dior/v2` (`includes/class-dior-rest-api.php`, 642 lines, 10 routes) is registered, broken (see §6.8) and duplicates the real service layer. Deleting it removes an attack surface at zero functional cost.

---

## 8. Performance

| Finding | Evidence | Effect |
|---|---|---|
| Assets global, not page-scoped | `dior-medical-dashboard.php:930` | ~6.4 MB (1.6 MB CSS + 888 KB html2pdf + 516 KB ApexCharts + Bootstrap + 16 JS files) on **every** page, including blog/checkout |
| `time()` as asset version | `:1169`, `:1189` | `dior-dashboard.js` (184 KB) and `dior-patient-dashboard.js` (196 KB) are re-downloaded on every request; no browser caching possible |
| No minification/build step | 16 hand-written JS files, 1.6 MB CSS | 57,738-line stylesheet with 24,988 `!important` declarations; no bundler, no tree-shaking |
| All tabs rendered per load | `templates/patient-dashboard.php:289-302` (14 includes), doctor dashboard (26) | hundreds of DOM sections and repeated per-tab queries on one page view |
| `get_all_documents()` per patient | `dior-doctor-dashboard.php:573-588` | admin dashboard does N+1 queries + N `get_patient_documents()` calls, uncapped |
| Hourly cron registered on front-end requests | `:999-1002` | `wp_schedule_event` on `init` for every visitor until scheduled |
| Init-time writes | `ensure_pages_exist`, `ensure_default_doctor` | `wp_insert_post` / `wp_insert_user` side effects on normal page views |

---

## 9. Compliance posture (HIPAA)

The code shows intent but not enforcement:

| Control | State |
|---|---|
| Unique user ID / roles | ✅ WP users + `doctor`/`patient` roles |
| Access control | ❌ fails open (§6.1, §6.3, §7.1, §7.2, §7.5) |
| Audit trail | 🟡 table + logging exists; spoofable IP, same DB credentials, no retention/rotation, no hash chain |
| Integrity | ❌ dual-write drift, fabricated rows, unauthenticated PHI writes |
| Authentication strength | ❌ no MFA, no rate limiting, 6-char minimum |
| Transmission | ❌ PHI in plain HTML email (`templates/emails/*`), third-party CDNs |
| Encryption at rest | ❌ plaintext files in `uploads/` |
| BAAs | ⚠️ Zoom / Google / jsDelivr / cdnjs / Gmail-backed doctor account — none documented |
| Retention, breach, backup, DR | ❌ absent from code |

---

## 10. Prioritised fix plan

### P0 — do not run with real patient data until these are done

1. **Delete the nonce bypass** in `Dior_Auth_Service::verify_ajax_nonce()` (`class-dior-auth-service.php:135`) and re-audit all 47 call sites; handlers that relied on it must send `X-WP-Nonce`.
2. **Remove `templates/patient-tabs/_design-static-data.php` from the render path** (`patient-dashboard.php:290`) and delete `$billing_mock` in `payments.php:54`. Replace with real data or an explicit, admin-only, per-site "demo mode" flag.
3. **Fail closed on every intake endpoint:** require the shared secret when unset on `/hipaatizer` and `/questionnaire`; add auth to `/hipaa-webhook` and to `rest_api/hipaatizer_webhook.php` / `hipaatizer_questionnaire_webhook.php` (or delete those two direct-file endpoints — they are redundant); stop trusting `user_id` in `save_hipaa_intake()`.
4. **Fix `/dior/v1/patient/{id}`** to `current_user_can('manage_options') || current_user_can('doctor') || $row['user_id'] === get_current_user_id()`.
5. **Replace `ajax_pay_invoice()`** with a real gateway, or relabel it "record payment (staff only)" and remove the Stripe receipt email.
6. **Kill `ensure_default_doctor()` auto-creation + email rewrite**; make it an explicit admin action with a clinic-owned address.
7. **Add the ownership check to `ajax_edit_appointment` / `ajax_save_soap_note`** (use `patient_belongs_to_doctor()`), and fix `patient_belongs_to_doctor()` so it actually scopes (§7.5).

### P1

8. Unify `patient_id` semantics (always WP user ID) and add a real `dior_patients` provisioning step at registration.
9. Delete `dior/v2` (`includes/class-dior-rest-api.php`) or reimplement it on the service layer.
10. Enforce nonces on login/register/lost-password (currently computed and discarded); add rate limiting, lockout, and min-10-character passwords.
11. Enqueue assets only on portal pages; remove `time()` versioning; split `dior-custom.css`; self-host Font Awesome / Bootstrap Icons.
12. Replace the dual-write: make SQL tables the only source and write a one-shot meta → table migration.

### P2

13. One `escapeHtml()` shared helper; audit the 370 `innerHTML` sites; escape `all-patients.php`.
14. Encrypt the vault at rest, add Nginx/IIS protection, malware-scan hook, drop the salt fallback.
15. Append-only audit export, retention policy, trusted-proxy-aware IP.
16. Split both god files (<800 lines each); delete the dead templates (§7.7); add `readme.md`, `uninstall.php`, CI (`php -l` + PHPCS WordPress standard), and Playwright smoke tests (login → book → prescribe → download).
17. Unify the three version constants (§7.9) and derive them from one source.

---

## 11. Questions that change the priorities

1. Is this deployed with real patients yet? (Determines whether §6.6 and §6.5 are emergencies.)
2. Single-provider clinic or multi-doctor? (Determines whether §7.5's clinic-wide access is a bug or by design.)
3. Is a real payment gateway planned, or is billing manual/offline?
4. Is the HIPAAtizer webhook secret actually configured in production? If not, §6.2/§6.3 are live right now.
5. Are third-party CDNs acceptable for the BAA story, or must everything be self-hosted?

---

*Static analysis by Arena coding agent. No PHP runtime in the sandbox — behaviour of the `dior/v2` SQL, email delivery and the OAuth round-trips is inferred from code, not executed. JS syntax verified with `node --check`.*
