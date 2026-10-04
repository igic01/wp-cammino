# Tipsters: accounts and private submissions

Stages 1-4 provide account management, frontend login, private submissions,
shared-file links, administrator review/status history, discussion-only editing,
tipster deletion with admin retention, and persistent per-tip conversations.
Stage 4 is ready for your review; Stage 5 has not started.

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
New messages link to their tip through `post_parent`; `_cammino_tip_id` is a legacy mirror. Sender, username snapshot, body, signed retry identifier and server time are stored in the same private post row. Business status is
stored in `_cammino_tip_status`, separately from the private post status.

New submissions use an optional shared-file URL, so they require no private-file
storage configuration. Use a Google Drive folder (or another provider) to share
multiple documents through one link. The owner controls the provider's sharing
permissions; WordPress stores the URL and does not upload, download, embed, or
change permissions on external files. Full account deletion removes the saved
link, but does not delete files held by an external provider.

Existing uploads remain available through the authenticated download handler.
Keep their original storage location and configure it in `wp-config.php`:

```php
define( 'CAMMINO_TIPSTERS_STORAGE_PATH', '/srv/cammino-private/tipsters' );
```

On Windows use an absolute path outside the served root, for example
`C:/cammino-private/tipsters`. PHP needs read/write access for downloads and
account purges. The module does not create the root. Verify server aliases,
CDNs and static-file rules do not expose this directory. Files must remain
protected even when the theme is inactive. Active and previously retained
attachments stay preserved; the new form cannot add/remove uploaded files.
Existing interrupted upload journals are still included in full account purges.

Cleanup validates every path before removing anything, rejects paths escaping
the configured directory, and fails rather than deleting an unrecognized file.

Deletion removes live application records and files. It does not erase hosting
backups or unrelated infrastructure logs. Legal retention, backup schedules, and
whether message retention overrides account deletion remain questions in
`todo.md`; the implementation does not establish legal compliance.

## Stage 2 behavior and review checklist

Title (200 characters), short description (1,000), and long description (20,000)
are required plain text. Stage 4 replaces uploads with one optional **Odkaz na
subory** field: an HTTP/HTTPS URL up to 2,048 bytes. Use a shared folder for
multiple files. Unsafe schemes, credentials, whitespace/control characters and
malformed URLs are rejected. Invalid forms preserve entered values.

Ownership and submitted status are assigned by the server. Signed form tokens
prevent duplicate submissions. The dashboard lists only the enabled owner's
non-deleted tips, ten per page. An account lock serializes submission, editing,
status changes, messaging, disabling and deletion. Forms expire after one day;
interrupted locks can be reclaimed after 30 minutes.

To check submissions, create two tips with different shared links. Verify their
content and links, invalid-field errors, retry protection, and separate
accounts' dashboards. Other tipsters must not access the first owner's tip URL.
Older attachment URLs retain their existing authorization checks.

## Stage 3 behavior and review checklist

Open **wp-admin -> Tipy** to find all completed tips, twenty per page, with
search, owner/status filters and a private detail screen. Admins can read fields,
open the shared link, download legacy attachments and inspect change history.

Allowed transitions: submitted -> discussion/approved, discussion -> approved,
and approved -> discussion with explicit reopen confirmation. Owners can change
text and the shared link only in discussion; saving keeps discussion status.
Submitted/approved forms are locked on both the page and server. Version checks
reject stale forms, including forms left open through approval/reopening.

Owner deletion requires confirmation, records deleted_by_tipster and hides the
tip and its conversation/legacy downloads from that owner. Admins retain read
access. No individual permanent tip deletion or restoration is exposed. Account
disabling preserves records and revokes access; full account deletion purges
all tips, conversations (including admin replies), private legacy files, history
and temporary message drafts. External provider files are not removed.

The `_cammino_tip_workflow` aggregate holds current fields/status, version,
legacy files, deletion details and history together. Old tips work immediately;
existing aggregates remain readable without GET migrations. The shared-link
field joins the aggregate on an edit. Index mirrors support search/filtering.

To check review, open discussion, edit text/link, approve, and try saving an old
form. Reopen with confirmation and verify fresh edits work. Delete a tip as its
owner and find it under the admin's deleted-tip filter. Verify disabling retains
it, and use a disposable account to check permanent deletion.

## Stage 4 behavior and review checklist

Each frontend tip detail and **Tipy** admin detail contains **Komunikacia**.
Messaging opens in discussion and stays open in approved, independently of the
locked form. Submitted tips, owner-deleted tips, disabled accounts and accounts
being purged cannot receive new messages. Admins retain readable history for
disabled/deleted tips; their owners lose access as appropriate.

Messages are plain text, 1-5,000 characters, ordered oldest first with username,
role and site-local time. Twenty messages appear per page; successful sends
redirect to the latest page. Refresh to see new replies. No message attachments,
email notifications, individual editing or individual deletion are provided.
No tipster email is needed.

Messages persist as private database records. Standard editors/REST are disabled,
native edit/delete capabilities are denied, and ordinary core update/trash/delete
operations cannot alter saved message fields. Accepted messages are verified
before success; nonce checks and signed sender/tip-bound tokens prevent forged
requests and duplicate retries. The same account lock protects posting against
status/account changes and purges. Trusted code with direct database access is
outside these application protections.

Admin validation errors preserve at most 5,000 draft characters in a transient
bound to that admin and tip for five minutes, consumed on the next detail view.
The draft is tracked for removal during account purging. These drafts are not
accepted conversation messages. Conversation records are the persistent log;
this does not establish a legal retention obligation or tamper-proof audit store.
The retention/backups question remains in `todo.md`.

Use separate admin and tipster sessions and disposable accounts:

1. Submit a tip using a shared-file or folder link. Confirm no upload input is
   shown; check the link and its permissions from the administrator's session.
2. Open the tip under **Tipy**. Submitted must have no message form. Select
   **Otvorit diskusiu**, then exchange messages from both sessions.
3. Reload and log out/in: both replies, usernames, roles and timestamps remain.
   Create a second tip and verify its conversation is separate. Try empty and
   oversized messages and retry the same POST: no duplicate should appear.
4. Approve the first tip. Text/link edits lock; both parties can still reply.
   Reopening restores editing. There are no message edit/delete controls.
5. Disable its account: admin history remains, posting closes and the owner
   loses access. Re-enable and log in again. Delete the tip as owner: admin keeps
   its read-only conversation under the deleted-tip filter.
6. Permanently delete the disposable account with confirmation. Its tips,
   messages, history and legacy uploads disappear; unrelated accounts remain.
   Files on Google Drive or other providers stay under the provider's control.
7. Check desktop/mobile forms, long messages, links and conversation pagination.

**Stop at review checkpoint 4. Stage 5 waits for your feedback.**

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
php tipsters/tests/messages-workflow.php /path/to/disposable/wordpress/wp-load.php
python tipsters/tests/submissions-http.py --php /path/to/php --wp-load /path/to/disposable/wordpress/wp-load.php --url http://127.0.0.1:8765
python tipsters/tests/review-http.py --php /path/to/php --wp-load /path/to/disposable/wordpress/wp-load.php --url http://127.0.0.1:8765
python tipsters/tests/messages-http.py --php /path/to/php --wp-load /path/to/disposable/wordpress/wp-load.php --url http://127.0.0.1:8765
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

Stage 4 passed 45 real WordPress conversation/link checks and 36 HTTP checks:
message persistence and failed-save cleanup, sender/tip token binding,
permission/status/account changes, immutable messages, pagination and full
purging (including admin replies and temporary drafts). Sixteen Chrome
desktop/mobile layout checks plus browser submission, review, editing and replies
passed; screenshots were inspected. The 85 account, 64 submission and 70 review
WordPress checks also pass, along with the updated 55 submission/link/legacy
HTTP checks and 41 review HTTP checks. Existing site-shell/post/page-spacing/
catalogue regression checks passed. Earlier upload test results above describe
Stages 2-3; the current HTTP suites test links and preserved legacy downloads.

For browser review, start headless Chrome with a debugging port and use the
guarded fixture helper to create a temporary credentials JSON, then run
`node tipsters/tests/responsive-browser.mjs fixtures.json /path/to/screenshots http://127.0.0.1:8765 9225 review`.
Clean the fixture prefix with `http-fixtures.php ... cleanup {prefix}` afterwards.

Production cache/proxy/plugin behavior and your own installation remain part of
the user review checkpoint. No production deployment was performed.
