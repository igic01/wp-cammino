# Tipsters feature: implementation plan

Status: Stages 1-5 implemented. Stage 5 is ready for your review at checkpoint 5.
Stage 5 adds rejected tips, confirmed administrator tip deletion, compact chat,
password visibility and a footer login link. Stage 4 replaced new uploads with
an optional shared-file URL, as requested.
Earlier upload checkpoints below describe the historical implementation.

We will implement this in stages. At every **STOP / REVIEW** checkpoint, I will
describe what is ready, give you exact steps to try it, and wait for your feedback
before starting the next stage. Fixes requested at a checkpoint belong to that
stage. Checkboxes track implementation progress; your acceptance is recorded
separately at each review checkpoint.

## 1. Requirements from your request

- Administrators have a dedicated WordPress admin area to create, list, view,
  update, disable/re-enable, and permanently delete tipster accounts.
- Administrators create accounts with a unique username and an admin-set
  password. Only administrators can change tipster passwords. Tipsters cannot
  register themselves, change their password, or use password recovery/reset.
  The minimum password length is 8 characters for creation and admin changes.
  Tipster accounts have no email field; Stage 2 adds no email requirement or notifications.
- Disabling an account blocks access and preserves all its records. Permanently
  deleting an account deletes its tips, files, conversations, and related logs,
  including tips previously marked deleted by tipster.
- Administrators can review tips, change their status, and communicate with the
  submitting tipster in a separate conversation on each tip.
- Tipsters log in on the website and have their own private dashboard. They
  cannot access the WordPress admin interface.
- One tipster can submit multiple tips and see only their own tips.
- A tip contains a title, short description, long description, and an optional
  shared-file/folder URL (Google Drive or another provider). Older private uploads
  remain preserved; new file uploads/removals are rejected.
- The review statuses are `submitted`, `discussion`, `approved`, and `rejected`
  (Slovak: **Zamietnutý**). Rejection locks editing and communication. There is
  also a `deleted_by_tipster` status, displayed as **Deleted by tipster**.
- A tipster can delete their own tip. This is a status change, not permanent
  deletion: the administrator can still see the tip, files, and conversation.
- Administrators can permanently delete an individual tip with confirmation;
  this removes its conversation, history and private legacy files, while keeping
  the account and other tips. External-provider files are unaffected.
- Tipsters cannot change a submitted, approved or rejected form. They can change the form
  when its status is discussion. This includes changes to its shared-file link.
- Communication is enabled after an administrator reviews/accepts the tip; the
  implemented initial policy opens messages in discussion and keeps them open in approved.
- Every message is saved persistently with its tip, sender, and timestamp.
  Neither administrators nor tipsters can edit or individually delete messages.
  Confirmed admin tip deletion and full account deletion include conversations; the possible
  conflict with a legal retention obligation is an open question below.

## 2. Workflow decisions and remaining questions

The table records the implemented workflow and remaining product/hosting
questions. Later user instructions take precedence over the original proposals.

| Question | Current behavior / open question |
| --- | --- |
| Does “approved by admin” mean the admin first accepts the tip for discussion, or that messages can only start in the final approved status? | Admin moves a reviewed tip to discussion to open communication and form edits. Approved is the final acceptance and locks the form. Communication remains available in approved. |
| Should admins be able to reopen approved tips, move discussion back to submitted, and approve a tip directly from submitted? | Allow submitted -> discussion, discussion -> approved, and submitted -> approved. Allow approved -> discussion as an explicit reopen action. Other backwards transitions need your decision. |
| When a tipster saves edits during discussion, should the tip return to submitted? | Keep it in discussion until the admin changes it. |
| Which form fields are required, and do descriptions need formatted text? | Title and both descriptions required; shared link optional. Descriptions are plain text with line breaks. Set length limits before implementing validation. |
| How should external file access work? | One optional HTTP/HTTPS link, up to 2,048 bytes; use a shared folder for multiple files. Tipster grants the administrator access through the provider. WordPress does not verify provider permissions or delete external files. Existing uploads remain private and downloadable. |
| Do messages need attachments or email notifications? | Saving messages and preventing individual edits/deletions are confirmed. Attachments and notifications are still undecided. Current version: automatic text-message updates and sending, no attachments or notifications. Username/password is sufficient for an account; if notifications are requested, decide whether to add an email field and who receives admin notifications. |
| Should admins be able to edit tip content or permanently delete individual tips? | Stage 5: confirmed permanent administrator tip deletion is requested and implemented, including its conversation/history/legacy files. Tipster deletion remains a retained status change. No admin content override. |
| Can a tipster delete a tip in every review status, and what should they see afterwards? Can an admin restore it? | Implemented: allow deletion from submitted, discussion, approved and rejected after confirmation; hide it from the tipster dashboard and block their direct tip/file/conversation access. Admin retains read access to everything. No new messages or restoration on a deleted tip initially. Confirm these details. |
| How should legal message retention interact with permanent administrator tip/account deletion? | Your product rules require saved, uneditable messages and full account deletion. Proposed technical scope: no individual message deletion, but confirmed administrator tip/account deletion purges the affected conversations. We have not established a legal retention obligation or period. Confirm any applicable retention policy before using permanent deletion with real records, including whether backups must retain or erase data on a schedule. |
| Which language, page URLs, and site-menu entry should we use? | Match the existing Cammino styling and Slovak frontend. Suggested URLs: `/tipsters/login/`, `/tipsters/`, `/tipsters/new/`, and `/tipsters/tip/{id}/`. Stage 5 adds the requested footer login link and password visibility toggle. |

**STOP / REVIEW 0 — Plan and workflow**

- [ ] Review the confirmed rules above and resolve the remaining workflow,
  deleted-tip behavior, retention, and file questions.
- [ ] Agree on the first version's scope, language, and navigation.
- [x] Prepare an isolated local WordPress installation and private test-file
  directory. Actual staging/production hosting and cache settings remain to be
  checked on your installation.
- [x] Receive your instruction to implement Stage 1. Remaining questions apply
  to later stages or production retention/hosting decisions.

## 3. Proposed structure and storage

Repository observations: `functions.php` loads feature modules, the theme uses a
shared Cammino header/footer, and `inc/site-shell.php` makes the final template
selection. There is also a visual snapshot editor and optional WooCommerce
integration. `tipsters/todo.md` was empty when this plan was written.

The proposed implementation is a self-contained theme module under `tipsters/`,
loaded through `functions.php`, following the existing module layout:

```text
tipsters/
  todo.md                 This plan and review progress
  bootstrap.php           Module setup and versioned initialization
  permissions.php         Roles, ownership checks, and access rules
  accounts.php            Admin account operations and frontend authentication
  tips.php                Tip storage, validation, status transitions, and history
  uploads.php             Private storage and authorized downloads
  messages.php            Per-tip conversations
  admin.php               Admin menus, account screens, and tip screens
  frontend.php            Page registration/routing and request handling
  templates/              Login, dashboard, and tip forms
  assets/                 Feature CSS and optional progressive enhancements
  tests/                  Permission and workflow tests
  README.md               Setup and administrator instructions
```

- Use native WordPress users and password/session handling with a dedicated
  `cammino_tipster` role. Give administrators specific feature capabilities;
  tipsters do not receive dashboard, post-editing, or media-library capabilities.
- Require username and admin-set password for account creation. Display name can
  default to the username; do not require an email address unless later agreed.
  Store passwords through WordPress hashing, never in plaintext or logs.
- Track account enabled/disabled state separately from tip statuses. Disabling
  or deleting an account revokes its sessions; all private handlers also check
  the account state rather than relying only on an existing login cookie.
- Enforce administrator-only password changes for tipsters on the server,
  including native WordPress reset/profile routes and any enabled WooCommerce
  or API routes. Keep password recovery working for unrelated account roles.
- Store tips as a non-public custom post type, owned by the submitting user.
  Store the business statuses in protected metadata, separate from
  WordPress publication status. Approved does not make a tip public.
- Tipster deletion stores deleted_by_tipster, previous status, actor, and time;
  it does not trash the post or erase its files/messages. Preserve admin access
  and offer a deleted-tip filter in the admin list.
- Store messages as a separate non-public custom post type linked to a tip,
  including sender, body, and server-generated timestamp. These database records
  are the persistent conversation log; ordinary application/server logs are not
  the conversation store. Keep them out of public comments and standard editors,
  and reject individual message update/delete operations on every exposed route.
- Account deletion is a separate, explicitly confirmed purge of the account's
  tips, files, all messages on those tips (including admin replies), and related
  history/log records. It includes deleted_by_tipster tips. Make interrupted
  purges resumable, revoke access before cleanup, and report completion only once
  both database records and physical files have been removed. Backup retention
  is a separate operational policy to settle above.
- Record status changes with actor, time, and previous/new status. Record content
  updates so an admin can see when the tipster last changed the submission.
- Store files outside publicly served directories, with metadata linking each
  file to its tip. Downloads go through an authenticated permission check.
  If storage must be inside the web root, direct access must be blocked and
  verified on the actual server before accepting real uploads.
- Build dynamic frontend pages within the shared Cammino layout. Update the
  final template router so it selects these templates correctly. Account data,
  messages, and forms must never become saved visual-editor snapshots.
- Use one server-side permission/status layer for every write and download.
  Hiding an edit button is not sufficient to lock a form.
- Keep tips/messages out of public search, archives, feeds, sitemaps, REST
  responses, and attachment pages. Exclude private pages/downloads from caches.
- Initialize roles/settings idempotently for both new installs and existing
  installations receiving a theme update. Avoid overwriting existing roles or
  account data, and avoid creating duplicate pages.

This proposal keeps implementation in the current theme. Database records must
survive theme updates/switches, although these screens and permission handlers
only run while the module is loaded. Private files must remain protected even
when the theme is inactive. If functionality must continue under another theme,
move the business logic to a companion plugin before implementing the foundation.

## 4. Status and permission rules to confirm

This table follows the proposed answer to the communication question above.

| Status | View own tip/links/legacy files | Edit title/descriptions/link | Tipster/admin messages | Who changes status? |
| --- | --- | --- | --- | --- |
| submitted | Yes | Locked | Closed until admin review | Administrator |
| discussion | Yes | Tipster can edit | Open | Administrator |
| approved | Yes | Locked | Open; form stays locked | Administrator |
| rejected (Zamietnutý) | Yes | Locked | Closed; existing history remains readable | Administrator; confirmed reopen to discussion |
| deleted_by_tipster | Proposed: hidden from owner; admin retains access | Locked | Proposed: closed; admin retains message history | Owner deletes; restoration undecided |

Proposed normal workflow:

1. Tipster submits a new form; server assigns submitted and locks it.
2. Admin reviews it and moves it to discussion; messaging and tipster edits open.
3. Tipster saves requested changes and replies; status remains discussion.
4. Admin moves it to approved; the form and shared-link changes lock again.
5. Admin may reject from submitted, discussion or approved, locking both form
   and communication. Approved/rejected tips can reopen as discussion with
   explicit confirmation. Admin may permanently delete any tip with confirmation.

An admin may also approve directly from submitted if that transition is agreed.
Tipsters cannot change review statuses or choose a different owner, even through
a manually altered request. Their only status action is deleting their own tip
through a dedicated, validated action. An approved tip stays private to its owner
and admins; a deleted tip remains available to admins.

Account state is independent of this workflow: disabling preserves every tip,
file, and message while blocking the account; account deletion permanently
removes all associated records. A tipster deleting one tip never triggers that
account-level purge.

## 5. Step-by-step implementation

### Stage 1 — Foundation, accounts, and frontend login

- [x] Add the module bootstrap, private tip/message models, dedicated role, and
  administrator capabilities using the agreed storage design.
- [x] Add shared authorization helpers: administrator actions, tipster actions,
  ownership checks, and private-route access.
- [x] Add a **Tipsters** wp-admin menu with list/search, account detail, create,
  edit/password-change, disable/re-enable, and permanent delete actions.
  Validate unique username and password requirements, and protect role
  assignments so these screens cannot modify unrelated administrators/users.
- [x] Keep account management in the separate **Tipsteri** admin area, with
  list/create subpages and dedicated account details/actions. Exclude tipsters
  from the general Users list/search, counts, and role selectors. Route native
  tipster edit/delete links into this area. Preserve the existing account forms
  and leave unrelated/mixed-role users manageable through the normal screens.
- [x] Implement disabling/re-enabling independently of deletion. Revoke sessions
  when disabling and block login, reads, writes, messages, and downloads for
  disabled accounts, including requests using existing cookies.
- [x] Implement full account deletion with an explicit warning showing the
  affected tip/file/message counts, confirmation, session invalidation, and
  cleanup of all associated records/files. Wire cleanup into supported native
  account deletion paths as well. Populated-record cleanup was verified using
  private tip/message/file fixtures; verify again with the actual upload and
  conversation handlers when those stages are implemented.
- [x] Add frontend login and logout using WordPress authentication. Admin sets
  the initial password and can replace it later; password changes revoke old
  sessions. Do not build tipster password setup/recovery/profile pages.
  Reject tipster password changes/reset requests on native, plugin, and API
  routes, including previously issued reset links. Use generic failure messages, safe
  redirects, and login-attempt protection compatible with the installation.
- [x] Add an initial private dashboard with a welcome message and empty state.
- [x] Redirect tipsters away from wp-admin and hide their admin toolbar. Keep
  required authenticated form/AJAX handlers working with permission checks.
- [x] Verify login redirects, logout, admin password changes, blocked tipster
  password changes/resets, disabling/re-enabling, session expiration, direct
  wp-admin access, and access by anonymous users and unrelated logged-in roles.
  Check compatibility with existing administrator and WooCommerce accounts,
  including their existing password-recovery flows.

Local verification: PHP 8.0.30, isolated WordPress 6.4.7 with temporary SQLite
storage, 85 real WordPress account/workflow checks and 30 HTTP form/session
checks passed. Existing site-shell, post, WooCommerce catalogue, and page-spacing
checks passed. Desktop and an emulated 390px mobile viewport were inspected;
mobile content fits the viewport and starts below the shared header. Native
reset/profile/REST paths and the WooCommerce account-update guard were checked;
the complete WooCommerce plugin flow and host cache/proxy configuration still
need review on your installation.
The dedicated admin navigation also passed 14 HTTP checks for menus, account
creation/details, Users list/search isolation, role selectors, and old native
tipster edit/delete URLs.

Review instructions: [tipsters/README.md](README.md#stage-1-review-checklist).
Admin menu label: **Tipsteri**. Frontend routes: `/tipsters/login/` and
`/tipsters/` (query-string fallback documented in README). No pages need creating.
Use separate administrator and tipster browser sessions. No production site was
modified or deployed during local verification.

**STOP / REVIEW 1 — Account management and login**

You try creating, finding, editing, disabling/re-enabling, and deleting a test
tipster. Sign in through the website and change its password as admin. Confirm
the tipster cannot change/reset that password, disabled accounts cannot access
the site features, and the tipster cannot enter wp-admin.

- [x] Implement and verify the review workflow on an isolated local installation;
  provide the exact review checklist in `tipsters/README.md`.
- [ ] You check the account/login/dashboard workflow on your installation.
- [x] Receive your instruction to proceed to Stage 2. Further account feedback
  can still be addressed without assuming every hosting check is complete.

### Stage 2 — Submit multiple tips and upload private files

- [x] Build the new-tip form with required title (200 characters), short
  description (1,000), and long description (20,000). Plain text; files optional.
- [x] Validate/sanitize fields server-side and escape them when rendering.
  Preserve entered text and show useful field errors after an invalid request.
- [x] Implement multiple uploads with an explicit format allowlist, filename and
  content/MIME checks, count/size limits, safe filenames, and no executable files.
- [x] Verify protected storage and authorized downloads, including attempts to
  fetch files directly without logging in. Do not expose public upload URLs.
- [x] Save successful submissions with the logged-in owner and submitted status.
  Prevent repeated form submissions from creating duplicates. Clean up temporary
  files and partial records when a submission fails.
- [x] Expand the dashboard to list only the owner's tips with title, status,
  submitted/updated dates, and links. Include pagination and a new-tip action.
- [x] Add a private tip detail page with submitted fields and downloads. In this
  stage submitted tips are read-only and communication is closed.
- [x] Verify multiple tips from one account and isolation between two tipsters,
  including altered tip/file IDs and public discovery endpoints.

Implementation uses the initial proposed PDF/JPG/JPEG/PNG/WEBP/DOC/DOCX allowlist,
five files, 10 MB per file subject to hosting limits. File limits remain open to
your checkpoint feedback. Private storage must be configured outside the served
web root before attaching files; text-only submissions do not need it.

Local verification: 64 WordPress submission/upload checks and 55 HTTP checks
passed, plus the existing 85 account checks. HTTP verification includes real
multipart PDF/PNG/DOCX uploads, byte-exact downloads, owner/anonymous isolation,
form nonces and idempotency, invalid content/count/size, public discovery routes,
disabled account access, and full deletion of physical uploads. Failed-save
rollback and lock acquisition with a stale cache were tested. Six Chrome
desktop/mobile layout checks and a browser form submission passed; screenshots
were inspected. Site-shell/post/page-spacing/catalogue regression checks passed.
Actual host aliases/cache/proxy configuration still needs review on your installation.

Review instructions: [tipsters/README.md](README.md#stage-2-behavior-and-review-checklist).
New routes: `/tipsters/new/` and `/tipsters/tip/{id}/`; both also support plain
permalink query URLs. No WordPress pages need creating. Stage 3 is not started.

**STOP / REVIEW 2 — Submission and dashboard**

You submit two tips, try valid/invalid uploads and form errors, and open each tip
from your dashboard. Using a second account, verify that the first account's tips
and files cannot be accessed. Check the form layout and file limits.

- [x] Implement and verify submission, private downloads, and dashboard behavior;
  provide the exact review checklist in `tipsters/README.md`.
- [x] Receive your instruction to proceed to Stage 3, with tips in a separate
  wp-admin tab. Further Stage 2 feedback can still be addressed.

### Stage 3 — Admin review, statuses, and conditional editing

- [x] Add a separate **Tipy** top-level wp-admin menu next to **Tipsteri**, with owner/title/date columns,
  status filtering, search, pagination, and a tip detail screen.
- [x] Let admins read all tip fields and download the attached files.
- [x] Add status actions with clear explanations and actor/timestamp history.
- [x] Centralize allowed transitions and enforce administrator authorization.
- [x] Add a confirmed **Delete tip** action for the owner. Store
  deleted_by_tipster instead of erasing the tip, uploads, or conversation.
  Apply the agreed rules for owner visibility, messaging, and restoration.
- [x] Keep deleted tips visible in the admin list/detail with a clear
  **Deleted by tipster** label, deletion time/actor, and status filter.
- [x] Enable tipster editing only during discussion, including adding/removing
  files under the same upload limits. Follow the agreed file-retention policy
  and delete physical files only when no retained record references them.
- [x] Recheck permissions and current status at save time for every field and
  file operation. Protect against stale forms and concurrent admin status
  changes so an outdated save cannot overwrite an approval or newer edit.
- [x] Make submitted/approved views visibly locked and explain why. Saving during
  discussion follows the agreed status rule, rather than trusting form inputs.
- [x] Verify the transition matrix, malformed status requests, attempts to edit
  locked tips directly, an approval while the tipster has an edit form open,
  deletion by a different tipster, and stale requests after tip/account deletion
  or account disabling. Verify permanent account deletion removes these tips
  and their files, while deleting a single tip preserves them for admins.

Implemented the plan's initial transitions: submitted → discussion/approved,
discussion → approved, approved → discussion with an explicit reopen checkbox.
Edits stay in discussion. Owner deletion is allowed in all three statuses,
hides the tip/files from the owner, and preserves everything for admins. No
restoration or further status changes on deleted tips are exposed.
Removed attachments remain private for admin review until full account deletion;
please confirm this initial retention choice at the checkpoint.

Local verification: 70 WordPress review checks and 42 HTTP review checks passed,
including stale forms across approval/reopening, failed-save rollback, versioned
history, replacement/five-active-file limits, owner isolation, disabled accounts,
soft-deleted tips, and full purge of current/retained/pending files and replies.
Existing 85 account, 64 submission, and 55 HTTP submission checks passed, as did
site-shell/post/page-spacing/catalogue regressions. Twelve Chrome desktop/mobile
layout checks plus real browser submission, admin review, and editing passed;
screenshots were inspected. Your actual host/cache/plugin settings remain a
review item. No production deployment was performed.

Open **wp-admin → Tipy**, or `/wp-admin/admin.php?page=cammino-tips`.
Existing Stage 2 tips appear immediately. Full review instructions:
[tipsters/README.md](README.md#stage-3-behavior-and-review-checklist).

**STOP / REVIEW 3 — Status workflow and form locking**

You move a tip from submitted to discussion, edit it as the tipster, then approve
it and confirm editing is locked again. Try direct approval and reopening if
included. Check whether this matches your intended review process.
Delete a tip as its owner and confirm the admin still sees it and its files under
**Deleted by tipster**. Confirm disabling preserves it and deleting its account
removes it and its files completely.

- [x] Implement and verify status changes, history, editing/file restrictions,
  and admin retention; provide the exact review checklist.
- [x] Receive your instruction to proceed to Stage 4, replacing uploads with links.

### Stage 4 — Per-tip communication

- [x] Add a conversation to the admin tip screen and frontend tip detail page.
- [x] Update both conversations automatically through authenticated incremental
  AJAX polling (5-15 seconds while visible; pause hidden/offline; reconnect
  immediately; retry failures with backoff). Send without page reload, preserve
  drafts, deduplicate replies, bound batches/live DOM, and reflect composer access.
- [x] Verify automatic updates with two isolated browser sessions, plus HTTP
  authorization/session/account-state checks and bounded backlog queries.

- [x] Show messages in chronological order with sender and timestamp, with
  pagination/loading for long conversations.
- [x] Let the admin and the owning tipster post messages only in the agreed
  statuses. Apply the same rules on the server and show closed-state guidance.
- [x] Validate/escape message text, reject empty/oversized messages, and prevent
  accidental duplicate sends on retries or page refresh.
- [x] Keep each conversation linked to exactly one tip. Enforce ownership when
  reading or posting, including manually altered requests.
- [x] Persist every accepted message before reporting success. Preserve sender,
  tip linkage, text, and timestamp; expose no individual message edit/delete
  controls and reject those operations through standard editors and APIs.
- [x] Verify tipster deletion and account disabling retain the conversation for
  admins. Full account deletion removes every message on the account's tips,
  including admin-authored replies, subject to the settled retention policy.
- [x] Keep this first version text-only, with no message attachments or email
  notifications; tipster email remains removed.
- [x] Verify persisted messages after logout/reload, isolation across tips/users,
  denied message edits/deletions for both parties, and communication behavior
  before/after every supported status transition and account-state change.

- [x] Replace uploads with one optional, validated shared-file/folder URL; preserve
  existing attachments and their private download/purge rules. Links can change
  only during discussion, with the same stale-form/version checks as text.
- [x] Implement the initial status policy: messaging in discussion and approved;
  submitted/deleted/disabled accounts closed. Admin history remains readable.

Local verification: 45 WordPress conversation/link checks and 36 HTTP checks
passed. Existing 85 account, 64 submission, 70 review WordPress checks and the
updated 55 submission/link/legacy-download and 41 review HTTP checks passed.
Sixteen Chrome desktop/mobile checks, browser submission/review/editing/replies,
and existing theme regression checks passed; screenshots were inspected.
Your host/cache/plugin settings remain a review item.

Review instructions: [tipsters/README.md](README.md#stage-4-behavior-and-review-checklist).
No pages, API keys or new storage configuration are needed for shared links.
External file access/deletion is controlled by the storage provider.

**STOP / REVIEW 4 — Communication**

You exchange messages as admin and tipster on two separate tips. Check when
messaging opens, its behavior after approval/reopening, sender labels, layout,
and any agreed notifications.
Confirm messages survive tipster deletion and account disabling and cannot be
edited or individually deleted by either party. Verify the agreed account-purge
behavior using disposable test conversations.

- [x] Implement and locally verify complete conversations and status-dependent behavior.
- [x] Receive your instruction to proceed to Stage 5 with the five requested additions.

### Stage 5 — Full verification and handover

- [x] Add **Zamietnutý**; keep existing messages readable while blocking edits
  and new messages. Allow confirmed admin reopening to discussion.
- [x] Add confirmed permanent administrator tip deletion, scoped to that tip,
  including retryable cleanup if files/messages/post deletion fail.
- [x] Replace growing message lists with responsive, keyboard-accessible scroll
  panels, own/other bubbles, latest history by default and older-page navigation.
  Preserve reading/draft position on incoming messages; show a new-message button.
- [x] Add accessible show/hide-password control and shared-footer login link.
- [x] Apply checkpoint feedback: center login, move contrasting logout to the
  top right, and add a dedicated dashboard button to open each tip.
- [x] Limit new messages to 600 characters on both the page and server; on
  desktop Enter sends and Shift+Enter inserts a newline. Mobile Enter stays
  a newline. Preserve existing longer messages.
- [x] Place textarea and Send beside each other for admin and tipster, including
  mobile. Keep incoming-message/status notifications inside the message panel
  as overlays, without moving the composer.

- [x] Finish responsive styling, keyboard navigation, labels, error messages,
  and the agreed menu/login entry using the existing Cammino design.
- [x] Add meaningful automated tests for permissions, ownership, transitions,
  locked writes, stale saves, upload rejection, message persistence/isolation,
  blocked message edits/deletions, admin-only password changes, account
  disabling/purging, and preserved tipster-deleted tips, following
  the repository's PHP workflow-test style.
- [x] Run PHP syntax checks and relevant existing theme workflow tests after
  bootstrap/router changes. Verify the real WordPress paths on the agreed test
  installation; standalone fixtures alone cannot prove upload/auth behavior.
- [x] Run end-to-end checks with an administrator, two tipsters, an unrelated
  account, and an anonymous visitor, including blocked tipster password recovery,
  admin password changes, disabling/re-enabling, tipster deletion, and full
  account deletion with populated records and files.
- [x] Verify private endpoint/download isolation, no-store responses and
  exclusion from public/editor routes on the disposable installation.
- [x] Switch the disposable installation to Twenty Twenty-Four and back;
  verify private-file isolation, preserved records and restored downloads.
- [ ] Verify production cache/CDN/plugin behavior and storage aliases, including
  direct-file protection when the theme is inactive and after reactivation.
  Local tests cannot establish protection on your host.
- [x] Verify idempotent setup on an existing site, role permissions, final
  template routing, and no regressions to WooCommerce or existing page designs.
- [x] Document page setup, file-storage configuration, notification settings if
  included, deletion/retention behavior, backups, and upgrade/rollback steps in
  `tipsters/README.md`. Feature rollback must preserve stored submissions.
- [x] Prepare a final acceptance checklist and report passed checks and any
  environment-dependent checks still outstanding.

Local verification: 303 WordPress, 200 HTTP, 42 Chrome and 135 relevant theme
regression checks passed, plus PHP/JavaScript syntax checks. The browser checks
cover long conversations, automatic incoming messages without losing drafts or
reading position, mobile layouts, password visibility, rejection and confirmed
admin deletion. Screenshots were inspected. The theme-switch check is local;
production caches, aliases and plugins still require your host review.

Checkpoint refinements: 49 message WordPress, 36 message HTTP, 24 live AJAX
and 38 browser UX checks passed, together with the 7 delta, 21 rejection/purge
and 41 relevant theme checks. Desktop/mobile screenshots were inspected.

Review instructions: [Stage 5 acceptance checklist](README.md#stage-5-behavior-and-final-review-checklist).

**STOP / REVIEW 5 — Final acceptance**

You run the full workflow: create an account -> login -> submit multiple tips ->
admin review -> discussion and edits -> messages -> approval and locked form.
Also check tipster deletion with admin visibility, account disabling with all
records retained, administrator-only password changes, uneditable messages, and
rejection (form/chat locked), confirmed reopening, permanent admin tip deletion,
and permanent account deletion under the settled retention policy.

- [ ] Resolve any feedback and complete the final acceptance checklist.
- [ ] Obtain your acceptance before any production rollout. Production deployment
  is a separate action from implementing and testing this feature.

## 6. Scope boundaries for the first version

There is no public self-registration or tipster password change/reset. Unless
changed during review, there is no message attachment support,
tip publication, administrator content override, or advanced reporting.
Account CRUD with disabling and full deletion, administrator-controlled
passwords, multiple private tips, shared-file links and preserved legacy uploads, administrator review statuses,
tipster deletion with admin retention, confirmed administrator tip deletion,
rejected tips, persistent messages without individual
editing/deletion, and enforced form locking are all part of the first version.
