# Tipster accounts: Stage 1

Stage 1 provides administrator account management and frontend authentication.
The dashboard currently has an empty state. Tip submission, upload/download UI,
status screens, and conversations will be implemented after this checkpoint.

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
- Admin accounts: `/wp-admin/admin.php?page=cammino-tipsters`
- Create account: `/wp-admin/admin.php?page=cammino-tipsters-new`

On installations with plain permalinks, use `/?cammino_tipsters=login` and
`/?cammino_tipsters=dashboard`. The admin list links to the correct login URL.
If pretty routes return 404 after installation, save **Settings -> Permalinks**
once and check the site's normal rewrite configuration.

Account and login screens use Slovak labels and the existing Cammino shell.
Private screens omit the shared external translation proxy controls.

## Account behavior

- Create an account with a unique username and an administrator-set password.
  Email is not required. An optional display name defaults to the username.
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

No upload UI exists in Stage 1. The deletion foundation uses this schema for the
next stage: `_cammino_tip_files` contains file records with a `path` relative to
`CAMMINO_TIPSTERS_STORAGE_PATH`. Message records use `_cammino_tip_id` to link to
their tip. Status is stored in `_cammino_tip_status`.

Before uploads are implemented, configure a directory outside the served web
root in `wp-config.php`, for example:

```php
define( 'CAMMINO_TIPSTERS_STORAGE_PATH', '/srv/cammino-private/tipsters' );
```

Use a Windows absolute directory on Windows hosting. The directory must exist;
Stage 1 does not create storage or accept uploads. Cleanup validates every file
path before removing anything, rejects paths escaping the configured directory,
and fails rather than deleting an unrecognized/outside file. Private files must
remain protected independently of the active theme.

Deletion removes live application records and files. It does not erase hosting
backups or unrelated infrastructure logs. Legal retention, backup schedules, and
whether message retention overrides account deletion remain questions in
`todo.md`; the implementation does not establish legal compliance.

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
```

The test installation must activate this theme and explicitly define both
`CAMMINO_TIPSTERS_TEST_INSTALLATION` as `true` and a writable
`CAMMINO_TIPSTERS_STORAGE_PATH` in its config. Never opt a real site into these
tests: they create and delete fixture accounts, tips, messages, and files. The
suite cleans up its own fixtures and does not require a particular admin password.

Stage 1 was checked with PHP 8.0.30 and an isolated WordPress 6.4.7 installation
using a temporary SQLite database. The database adapter is test infrastructure;
it is not a theme dependency. Verification included 85 account/workflow checks,
30 HTTP form/session checks, and the existing site-shell, post, and WooCommerce
catalogue workflows. The separate admin area was also verified with 14 HTTP
navigation checks covering list/search separation, submenus, role selectors, and
native edit/delete redirects. Desktop and mobile layouts were also inspected.

Production cache/proxy/plugin behavior and your own installation remain part of
the user review checkpoint. No production deployment was performed.
