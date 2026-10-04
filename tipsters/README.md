# Tipsters: accounts and private submissions

Stages 1 and 2 provide administrator account management, frontend authentication,
multiple private tip submissions, attachments, a paginated owner dashboard, and
read-only tip details. Stage 2 is ready for your review. Admin status screens,
discussion editing, tipster deletion, and conversations belong to later stages.

## Open the screens

With the updated Cammino theme active, administrators have a separate **Tipsteri**
menu in wp-admin, with **Všetci tipsteri** and **Pridať tipstera** pages. Account
details, password changes, disabling, and deletion stay in that area. Tipsters
are excluded from the general Users list/search and its role selectors; its
counts and pagination reflect the remaining users. Existing tipster edit/delete
links redirect to the dedicated screens. The account form layout is unchanged.
Roles and route rules initialize automatically on the first request,
including updates to an already-active theme. No WordPress pages need to be
created, and these account screens do not use the visual snapshot editor.

- Frontend login: `/tipsters/login/`
- Private dashboard: `/tipsters/`
- New tip: `/tipsters/new/`
- Private detail: `/tipsters/tip/{id}/`
- Admin accounts: `/wp-admin/admin.php?page=cammino-tipsters`
- Create account: `/wp-admin/admin.php?page=cammino-tipsters-new`

On installations with plain permalinks, use `/?cammino_tipsters=login` and
`/?cammino_tipsters=dashboard`. New submissions use `/?cammino_tipsters=new`;
details use `/?cammino_tipsters=tip&cammino_tip_id={id}`. Download links are
generated automatically and also work with plain permalinks.
The admin list links to the correct login URL.
If pretty routes return 404 after installation, save **Settings -> Permalinks**
once and check the site's normal rewrite configuration.

Account and login screens use Slovak labels and the existing Cammino shell.
Private screens omit the shared external translation proxy controls.

## Account behavior

- Create an account with a unique username and an administrator-set password.
  Tipster accounts have no email field. An optional display name defaults to the username.
- Usernames are fixed after creation, consistent with the account edit screen.
  Administrators can change display names and passwords.
- Passwords require at least 8 characters and cannot have leading/trailing
  spaces. They are hashed by WordPress, never displayed after saving, and never
  included in feature notices, logs, or URLs. Deliver the credentials to the
  tipster yourself; account creation sends no notification email.
- A blank password field when editing preserves the existing password. Changing
  a password invalidates existing sessions.
- Tipsters cannot register, edit their native profile, reset/change passwords,
  or create application passwords. The native WordPress reset completion,
  REST profile update, and WooCommerce account update paths are also restricted.
  Unrelated users keep their existing recovery/profile behavior.
- Tipsters entering wp-admin are redirected to the frontend dashboard. They have
  no WordPress editing/upload capabilities and no frontend admin toolbar.
- Disabling an account revokes its sessions and blocks login and private access,
  preserving its records. Re-enabling permits a fresh login with its password.
- Deleting an account requires a dedicated confirmation screen, the exact
  username, a confirmation checkbox, and a valid nonce. It removes the account's
  tips (including `deleted_by_tipster`), all messages on those tips including
  admin replies, attached private files, and associated post/user metadata.
  Native tipster deletion links redirect to this confirmation screen. The
  WordPress account-deletion hook still ensures private-record cleanup for
  supported programmatic deletion.
- If cleanup fails, the account stays blocked with **Mazanie nedokončené** and
  can be deleted again after correcting the reported issue. It cannot be
  re-enabled while cleanup is incomplete. Other users' records are preserved.
- Feature account actions reject administrators and accounts carrying additional
  roles. Mixed-role accounts remain visible in the general Users screen so their
  other roles can be managed; they are excluded from the dedicated tipster list.
  This version supports full account deletion on single-site WordPress;
  multisite account deletion needs a separate network-aware policy.

Native account restrictions run while the Cammino theme/module is active.
Trusted plugins that bypass WordPress authorization or directly write the
database must be reviewed separately; this module is not a database firewall.

## Stage 1 review checklist

Use disposable accounts. Test the administrator and tipster in separate browser
sessions (for example, a normal window and an incognito window). An administrator
session deliberately receives 403 on the tipster-only pages.

1. Open **Tipsteri**, create a username/password account, and find it through
   search. Confirm its display name defaults to the username if left blank.
   Check that it appears in **Tipsteri -> Všetci tipsteri** and is absent from the
   general **Users** list/search. Native user creation must not offer the Tipster
   role; use **Tipsteri -> Pridať tipstera** for those accounts.
2. In a separate session, open the login URL and sign in. Check the welcome
   message and empty **Moje tipy** section, then sign out and back in.
3. As tipster, open `/wp-admin/` and `/wp-admin/profile.php`. Both should redirect
   to the dashboard. No admin toolbar or password-change controls should appear.
4. As admin, change the display name and password. The old session should lose
   access; the old password should fail and the new password should work.
5. Disable the account. Its open session should lose access and fresh login
   should fail. Re-enable it and verify a fresh login works again.
6. Try the native WordPress forgotten-password form for the tipster: recovery
   must be blocked. Check that recovery still works for an unrelated account.
7. Open the account's deletion screen. An incorrect confirmation username must
   preserve it; correct confirmation removes it from the account list/search.
8. Check the frontend layout and forms on desktop/mobile and report changes
   before Stage 2 starts.

## Private storage and retention

`_cammino_tip_files` contains file records with an opaque `id`, a random `path`
relative to `CAMMINO_TIPSTERS_STORAGE_PATH`, sanitized original `name`, detected
`mime`, and byte `size`. Files are not WordPress media attachments and have no
public upload URL. Downloads require an enabled owning tipster or an authorized
administrator, and are served as attachments with private/no-store headers.
Message records use `_cammino_tip_id` to link to their tip. Business status is
stored in `_cammino_tip_status`, separately from the private post status.

Before accepting uploads, configure an existing writable directory outside the
served web root in `wp-config.php`, for example:

```php
define( 'CAMMINO_TIPSTERS_STORAGE_PATH', '/srv/cammino-private/tipsters' );
```

Use a Windows absolute directory on Windows hosting, for example
`C:/cammino-private/tipsters`, when the actual served root is elsewhere. Grant
the PHP/web-server account read/write access; use directory permissions 0700
on systems supporting Unix permissions. The module does not create the root.
Upload and download handlers reject storage within ABSPATH, WP_CONTENT_DIR, or
the server's DOCUMENT_ROOT. Verify your web-server aliases, CDN, and static-file
configuration do not expose this directory; PHP cannot discover every alias.
Private files must remain protected independently of the active theme.
Submissions without attachments work without storage configuration.

Uploads require PHP `fileinfo`; DOCX validation additionally needs `ZipArchive`.
Set `upload_max_filesize` to at least 10M and `post_max_size` comfortably above
50M (for example 64M) to support five 10 MB attachments in one request. Also
configure proxy/server request-body limits and `max_file_uploads` to allow five
files. A lower WordPress/hosting file limit is respected and shown on the form.
Exceeding the total request limit can leave PHP with an empty POST; the form then
reports that the request expired or exceeded the hosting limit.

Cleanup validates every path before removing anything, rejects paths escaping
the configured directory, and fails rather than deleting an unrecognized file.

Deletion removes live application records and files. It does not erase hosting
backups or unrelated infrastructure logs. Legal retention, backup schedules, and
whether message retention overrides account deletion remain questions in
`todo.md`; the implementation does not establish legal compliance.

## Stage 2 behavior and review checklist

Text fields are required and plain text: title up to 200 characters, short
description up to 1,000, and long description up to 20,000. Files are optional:
PDF, JPG/JPEG, PNG, WEBP, DOC, DOCX, up to five per tip, up to 10 MB each subject
to the host limit. These are the plan's initial file limits for this checkpoint.
Browser-supplied MIME and size are not trusted. Validation checks extension and
content; DOCX packages are checked for Word content, macro/executable entries,
and excessive expanded size. Allowed document formats are not a malware scan.

The server fixes ownership to the logged-in tipster and status to `submitted`.
The dashboard lists only that owner's completed, non-deleted tips, ten per page,
with title, status, submitted/updated times, and detail links. Tip pages render
escaped text with line breaks and private attachment links. No editing or
conversation controls are enabled in this stage. No email is requested or sent.

A signed form identifier prevents repeat submissions from creating duplicate
records/files. Successful POSTs redirect to the saved tip. Invalid requests keep
the entered text and display errors; browsers require files to be selected again.
An account write lock prevents submission from racing disabling/deletion.
Unexpected failures remove partial records/files where possible; interrupted
or failed cleanup records stay hidden as `building` and remain included in full
account cleanup. An interrupted lock can be reclaimed after 30 minutes; forms
expire after one day. A new form creates a new tip, even with identical text.

Use two disposable tipster accounts and a separate administrator session:

1. Configure private storage above, log in as the first tipster, and select
   **Nový tip** on `/tipsters/`.
2. Submit a tip with a PDF and image, then submit another without attachments.
   Both should appear on **Moje tipy** as **Odoslaný**. Open each and check its
   descriptions, line breaks, dates, and attachment downloads.
3. Try empty fields, a renamed text file as PDF, an executable, six files, and
   an oversized file. No tip should be created; entered text should remain.
4. Refresh the saved detail page and retry the same original form: no duplicate
   tip should appear. A newly opened form can create another tip.
5. In the second tipster session, verify its dashboard is separate. Paste the
   first tipster's tip and download URLs: access must be denied. Try downloading
   while signed out, and altering either the tip ID or file ID.
6. As admin, disable/re-enable the first account and verify access is revoked
   and restored while records/files remain. For a disposable account, use the
   dedicated deletion confirmation and check all its attachments are removed.
7. Check the form, list, and detail layouts on desktop and phone. Confirm the
   text/file limits before we begin Stage 3.

Admin tip listing and review/status controls arrive in Stage 3. Administrators
already have permission to use attachment download URLs, including when the
owning account is disabled.

## Hosting and login protection

Use HTTPS for real credentials. Private routes send `no-store`/private response
headers and noindex directives. Configure page-cache/CDN exclusions for both
`/tipsters/` and the `cammino_tipsters` query parameter, including the login page.
A cache serving before WordPress boots cannot be controlled by theme PHP alone.

Login forms use a browser-specific CSRF cookie plus a WordPress nonce. Account
actions and logout require nonces. A failed-login limit covers frontend and
native tipster authentication: 8 failures per username/IP or 30 failures per IP.
Counters expire after 15 minutes without another failed attempt; further failed
attempts extend that cooldown. Keys are hashed; passwords are not logged. Login
uses WordPress's `wp_signon()` password and session handling; these attempt limits
are added by the tipster module. They cover tipsters, not ordinary administrator
logins. IP-based limits do not prevent attackers from rotating IP addresses.
Existing site-level security plugins may apply stricter limits. Proxy deployments
should configure the web server's trusted client IP handling; the feature does
not trust arbitrary forwarded-IP headers.

## Verification

An integration suite uses actual WordPress APIs and a disposable test database:

```text
php tipsters/tests/accounts-workflow.php /path/to/disposable/wordpress/wp-load.php
php tipsters/tests/submissions-workflow.php /path/to/disposable/wordpress/wp-load.php
python tipsters/tests/submissions-http.py --php /path/to/php --wp-load /path/to/disposable/wordpress/wp-load.php --url http://127.0.0.1:8765
```

The test installation must activate this theme and explicitly define both
`CAMMINO_TIPSTERS_TEST_INSTALLATION` as `true` and a writable
`CAMMINO_TIPSTERS_STORAGE_PATH` in its config. Never opt a real site into these
tests: they create and delete fixture accounts, tips, messages, and files. The
suite cleans up its own fixtures and does not require a particular admin password.
The HTTP suite needs the disposable web server running and uses the guarded
`http-fixtures.php` CLI helper to create/clean temporary accounts. Give that
server at least 12M upload_max_filesize, 64M post_max_size, and max_file_uploads
of 10 to exercise application size/count rejection rather than silent PHP
truncation. The responsive CDP script can additionally inspect a headless Chrome
session using credentials generated by that same helper; clean fixtures after it.

Stage 1 was checked with PHP 8.0.30 and an isolated WordPress 6.4.7 installation
using a temporary SQLite database. The database adapter is test infrastructure;
it is not a theme dependency. Verification included 85 account/workflow checks,
30 HTTP form/session checks, and the existing site-shell, post, and WooCommerce
catalogue workflows. The separate admin area was also verified with 14 HTTP
navigation checks covering list/search separation, submenus, role selectors, and
native edit/delete redirects. Desktop and mobile layouts were also inspected.

Stage 2 passed 64 real WordPress submission/upload checks and 55 HTTP checks,
including real PDF/PNG/DOCX uploads, duplicate prevention, tampered IDs/nonces,
anonymous/other-owner download denial, public endpoint isolation, disabling, and
physical file deletion. Failed-save rollback and stale-cache lock acquisition
were also checked. Six Chrome desktop/mobile layout checks and an actual browser
form submission passed, with screenshots inspected. The original 85 account
checks and the site-shell/post/page-spacing/catalogue workflows also pass.

Production cache/proxy/plugin behavior and your own installation remain part of
the user review checkpoint. No production deployment was performed.
