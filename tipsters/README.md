# Tipsters: accounts and private submissions

Stages 1–3 provide administrator account management, frontend authentication,
private tip submissions/files, a paginated dashboard, admin review and status
history, discussion-only editing, and tipster deletion with admin retention.
Stage 3 is ready for your review. Conversations belong to Stage 4.

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

A separate **Tipy** menu next to **Tipsteri** lists submissions from every tipster,
including existing Stage 2 tips and tips deleted by their owner. It has status
filters, search, owner filters, pagination, and private detail/status screens.
Account details link to that account's tips.

- Frontend login: `/tipsters/login/`
- Private dashboard: `/tipsters/`
- New tip: `/tipsters/new/`
- Private detail: `/tipsters/tip/{id}/`
- Admin accounts: `/wp-admin/admin.php?page=cammino-tipsters`
- Create account: `/wp-admin/admin.php?page=cammino-tipsters-new`
- Admin tips: `/wp-admin/admin.php?page=cammino-tips`
- Admin tip detail: `/wp-admin/admin.php?page=cammino-tips&tip_id={id}`

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
escaped text with line breaks and private attachment links. At the Stage 2
checkpoint tips were read-only. Stage 3 adds discussion editing and confirmed
deletion as described below; conversations are still pending. No email is
requested or sent.

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

Admin listing and review controls are now available under **Tipy**. Administrators
can use attachment download URLs even when the owning account is disabled.

## Stage 3 behavior and review checklist

The separate **Tipy** admin page lists all completed submissions, twenty per
page. Open a title to read the tip, download its files, change its status, and
view changes with the actor and server timestamp. Filters include **Odstránený
tipsterom**; owner links filter the list, and account details link to their tips.

Allowed changes are submitted → discussion or approved, discussion → approved,
and approved → discussion after an explicit reopen checkbox. No other backward
changes or restoration of deleted tips are available in this version.
Admins can review disabled owners' tips, but cannot edit tip content or change
records while an account purge is underway.

Owners see an edit form only during discussion. They can update all text fields,
add files, or mark existing files for removal, with the same validation and
maximum five active attachments. Saving preserves discussion status. Submitted
and approved forms are locked on both the page and server. Every mutation needs
the current version; stale admin/owner forms are rejected, including forms left
open through approval and reopening.

Owner deletion requires a checkbox and is allowed in submitted, discussion, and
approved. It records `deleted_by_tipster`, previous status, actor, and time. The
tip disappears from the owner's dashboard; their direct tip/file access is
denied. Admins retain the tip, active and removed files, and existing messages.
This is independent of full account deletion, which still removes everything.

Attachments removed during discussion are archived for administrators and
physically retained until full account deletion. They no longer count toward
the five active files and cannot be downloaded by the owner. This is the initial
retention choice for this checkpoint; confirm it matches your workflow.

The `_cammino_tip_workflow` metadata aggregate stores current fields/status,
version, active/retained files, deletion details, and change history together.
Old Stage 2 tips work immediately and acquire that aggregate on their first
successful mutation; reads do not bulk-migrate or rewrite submissions. WordPress
post fields and status/file metadata remain search/index mirrors; server access
and templates use the aggregate. Pending upload paths are journaled under
`_cammino_tip_pending_files` before transfer, so account purges also include
interrupted uploads. A failed normal save restores mirrors and removes newly
uploaded files; an interrupted journal stays private and purgeable.

Use a disposable tip with attachments and separate admin/tipster browser sessions:

1. Open **wp-admin → Tipy**. Find the tip you already submitted and open its
   title. Check the fields, owner, dates, and file downloads; try search and
   status/owner filters.
2. Select **Otvoriť diskusiu**. Reload the tipster's tip detail. Change the text,
   remove an existing file, add another, and select **Uložiť zmeny**. The tip
   remains **Diskusia**; admin history shows the update and file changes.
3. Verify the removed file is unavailable to the tipster but still downloadable
   under the admin's **Prílohy odstránené z formulára** section.
4. Leave a discussion edit form open. As admin select **Schváliť tip**, then try
   saving that old form. It must be rejected. Reload to see the locked form.
5. Confirm **Znovu otvoriť diskusiu** to allow fresh edits. A form saved before
   approval/reopening must remain stale. Test direct approval on another
   submitted tip if useful.
6. As owner, confirm **Odstrániť tip**. It should disappear and direct URLs
   should fail. In **Tipy**, filter **Odstránený tipsterom** and verify the tip,
   deletion details, history, and both current/removed attachments remain.
7. Disable the account and verify admin records remain. On a disposable account,
   confirm full account deletion removes tips, all current/retained/pending
   uploads, existing messages, and history. Other accounts must remain intact.

Stop at review checkpoint 3. Messaging is still scheduled for Stage 4.

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
php tipsters/tests/review-workflow.php /path/to/disposable/wordpress/wp-load.php
python tipsters/tests/submissions-http.py --php /path/to/php --wp-load /path/to/disposable/wordpress/wp-load.php --url http://127.0.0.1:8765
python tipsters/tests/review-http.py --php /path/to/php --wp-load /path/to/disposable/wordpress/wp-load.php --url http://127.0.0.1:8765
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

Stage 3 passed 70 real WordPress review checks and 42 HTTP review checks covering
the transition matrix, old submissions, permissions, stale admin/owner forms,
failed-save rollback, search/filter/pagination, attachment replacement/limits,
retained downloads, disabling, soft deletion, and full physical account cleanup.
Twelve Chrome desktop/mobile layout checks plus browser submission, admin
review, and discussion editing passed; screenshots were inspected. The original
85 account and 64 submission checks and existing theme regressions also pass.

For browser review, start headless Chrome with a debugging port and use the
guarded fixture helper to create a temporary credentials JSON, then run
`node tipsters/tests/responsive-browser.mjs fixtures.json /path/to/screenshots http://127.0.0.1:8765 9225 review`.
Clean the fixture prefix with `http-fixtures.php ... cleanup {prefix}` afterwards.

Production cache/proxy/plugin behavior and your own installation remain part of
the user review checkpoint. No production deployment was performed.
