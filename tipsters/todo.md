# Tipsters feature: implementation plan

Status: Stage 1 implemented and locally verified. Waiting for your review at
checkpoint 1 before Stage 2. Tip submission and conversations are not yet built.

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
- Disabling an account blocks access and preserves all its records. Permanently
  deleting an account deletes its tips, files, conversations, and related logs,
  including tips previously marked deleted by tipster.
- Administrators can review tips, change their status, and communicate with the
  submitting tipster in a separate conversation on each tip.
- Tipsters log in on the website and have their own private dashboard. They
  cannot access the WordPress admin interface.
- One tipster can submit multiple tips and see only their own tips.
- A tip contains a title, short description, long description, and uploaded files
  such as PDFs, images, and Word documents.
- The review statuses are `submitted`, `discussion`, and `approved`. There is
  also a `deleted_by_tipster` status, displayed as **Deleted by tipster**.
- A tipster can delete their own tip. This is a status change, not permanent
  deletion: the administrator can still see the tip, files, and conversation.
- Tipsters cannot change a submitted or approved form. They can change the form
  when its status is discussion. This includes changes to its files.
- Communication is enabled after an administrator reviews/accepts the tip; the
  exact relationship between that action and the statuses needs confirmation.
- Every message is saved persistently with its tip, sender, and timestamp.
  Neither administrators nor tipsters can edit or individually delete messages.
  Full account deletion currently includes its conversations; the possible
  conflict with a legal retention obligation is an open question below.

## 2. Questions to settle at the first checkpoint

The proposals here are assumptions for reviewing the plan, not confirmed rules.
Please answer or change them before the affected stage is implemented.

| Question | Proposed behavior |
| --- | --- |
| Does “approved by admin” mean the admin first accepts the tip for discussion, or that messages can only start in the final approved status? | Admin moves a reviewed tip to discussion to open communication and form edits. Approved is the final acceptance and locks the form. Communication remains available in approved. |
| Should admins be able to reopen approved tips, move discussion back to submitted, and approve a tip directly from submitted? | Allow submitted -> discussion, discussion -> approved, and submitted -> approved. Allow approved -> discussion as an explicit reopen action. Other backwards transitions need your decision. |
| When a tipster saves edits during discussion, should the tip return to submitted? | Keep it in discussion until the admin changes it. |
| Which form fields are required, and do descriptions need formatted text? | Title and both descriptions required; files optional. Descriptions are plain text with line breaks. Set length limits before implementing validation. |
| Which file formats, sizes, and counts are needed? | Start with PDF, JPG/JPEG, PNG, WEBP, DOC, and DOCX; maximum 5 files per tip and 10 MB per file, subject to hosting limits. Confirm whether spreadsheets or other formats are needed. |
| Do messages need attachments or email notifications? | Saving messages and preventing individual edits/deletions are confirmed. Attachments and notifications are still undecided. Proposed first version: text messages on page load/refresh, no attachments or notifications. Username/password is sufficient for an account; if notifications are requested, decide whether to add an email field and who receives admin notifications. |
| Should admins be able to edit tip content or permanently delete individual tips? | Tipster deletion is confirmed and preserves the tip for admins. Proposed first version: no admin content override or individual permanent tip deletion; full account deletion still removes all associated records. |
| Can a tipster delete a tip in every review status, and what should they see afterwards? Can an admin restore it? | Proposed: allow deletion from submitted, discussion, and approved after confirmation; hide it from the tipster dashboard and block their direct tip/file/conversation access. Admin retains read access to everything. No new messages or restoration on a deleted tip initially. Confirm these details. |
| How should legal message retention interact with deleting an account and its entire history? | Your product rules require saved, uneditable messages and full account deletion. Proposed technical scope: no individual message deletion, but a confirmed account deletion purges its conversations. We have not established a legal retention obligation or period. Confirm any applicable retention policy before using permanent deletion with real records, including whether backups must retain or erase data on a schedule. |
| Which language, page URLs, and site-menu entry should we use? | Match the existing Cammino styling and Slovak frontend. Suggested URLs: `/tipsters/login/`, `/tipsters/`, `/tipsters/new/`, and `/tipsters/tip/{id}/`. Confirm the visible name and where the login link belongs. |

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

| Status | View own tip/files | Edit title/descriptions/files | Tipster/admin messages | Who changes status? |
| --- | --- | --- | --- | --- |
| submitted | Yes | Locked | Closed until admin review | Administrator |
| discussion | Yes | Tipster can edit | Open | Administrator |
| approved | Yes | Locked | Open; form stays locked | Administrator |
| deleted_by_tipster | Proposed: hidden from owner; admin retains access | Locked | Proposed: closed; admin retains message history | Owner deletes; restoration undecided |

Proposed normal workflow:

1. Tipster submits a new form; server assigns submitted and locks it.
2. Admin reviews it and moves it to discussion; messaging and tipster edits open.
3. Tipster saves requested changes and replies; status remains discussion.
4. Admin moves it to approved; the form and file changes lock again.
5. Admin may explicitly reopen it as discussion if agreed at checkpoint 0.

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
storage, 69 real WordPress account/workflow checks and 30 HTTP form/session
checks passed. Existing site-shell, post, WooCommerce catalogue, and page-spacing
checks passed. Desktop and an emulated 390px mobile viewport were inspected;
mobile content fits the viewport and starts below the shared header. Native
reset/profile/REST paths and the WooCommerce account-update guard were checked;
the complete WooCommerce plugin flow and host cache/proxy configuration still
need review on your installation.

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
- [ ] Collect your feedback and wait before Stage 2.

### Stage 2 — Submit multiple tips and upload private files

- [ ] Build the new-tip form with the agreed required fields and limits.
- [ ] Validate/sanitize fields server-side and escape them when rendering.
  Preserve entered text and show useful field errors after an invalid request.
- [ ] Implement multiple uploads with an explicit format allowlist, filename and
  content/MIME checks, count/size limits, safe filenames, and no executable files.
- [ ] Verify protected storage and authorized downloads, including attempts to
  fetch files directly without logging in. Do not expose public upload URLs.
- [ ] Save successful submissions with the logged-in owner and submitted status.
  Prevent repeated form submissions from creating duplicates. Clean up temporary
  files and partial records when a submission fails.
- [ ] Expand the dashboard to list only the owner's tips with title, status,
  submitted/updated dates, and links. Include pagination and a new-tip action.
- [ ] Add a private tip detail page with submitted fields and downloads. In this
  stage submitted tips are read-only and communication is closed.
- [ ] Verify multiple tips from one account and isolation between two tipsters,
  including altered tip/file IDs and public discovery endpoints.

**STOP / REVIEW 2 — Submission and dashboard**

You submit two tips, try valid/invalid uploads and form errors, and open each tip
from your dashboard. Using a second account, verify that the first account's tips
and files cannot be accessed. Check the form layout and file limits.

- [ ] Demonstrate submission, private downloads, and dashboard behavior.
- [ ] Collect your feedback and wait before Stage 3.

### Stage 3 — Admin review, statuses, and conditional editing

- [ ] Add **Tipsters -> Tips** in wp-admin, with owner/title/date columns,
  status filtering, search, pagination, and a tip detail screen.
- [ ] Let admins read all tip fields and download the attached files.
- [ ] Add the agreed status actions with clear explanations and a status history.
- [ ] Centralize allowed transitions and enforce administrator authorization.
- [ ] Add a confirmed **Delete tip** action for the owner. Store
  deleted_by_tipster instead of erasing the tip, uploads, or conversation.
  Apply the agreed rules for owner visibility, messaging, and restoration.
- [ ] Keep deleted tips visible in the admin list/detail with a clear
  **Deleted by tipster** label, deletion time/actor, and status filter.
- [ ] Enable tipster editing only during discussion, including adding/removing
  files under the same upload limits. Follow the agreed file-retention policy
  and delete physical files only when no retained record references them.
- [ ] Recheck permissions and current status at save time for every field and
  file operation. Protect against stale forms and concurrent admin status
  changes so an outdated save cannot overwrite an approval or newer edit.
- [ ] Make submitted/approved views visibly locked and explain why. Saving during
  discussion follows the agreed status rule, rather than trusting form inputs.
- [ ] Verify the transition matrix, malformed status requests, attempts to edit
  locked tips directly, an approval while the tipster has an edit form open,
  deletion by a different tipster, and stale requests after tip/account deletion
  or account disabling. Verify permanent account deletion removes these tips
  and their files, while deleting a single tip preserves them for admins.

**STOP / REVIEW 3 — Status workflow and form locking**

You move a tip from submitted to discussion, edit it as the tipster, then approve
it and confirm editing is locked again. Try direct approval and reopening if
included. Check whether this matches your intended review process.
Delete a tip as its owner and confirm the admin still sees it and its files under
**Deleted by tipster**. Confirm disabling preserves it and deleting its account
removes it and its files completely.

- [ ] Demonstrate status changes, history, and editing/file restrictions.
- [ ] Collect your feedback and wait before Stage 4.

### Stage 4 — Per-tip communication

- [ ] Add a conversation to the admin tip screen and frontend tip detail page.
- [ ] Show messages in chronological order with sender and timestamp, with
  pagination/loading for long conversations.
- [ ] Let the admin and the owning tipster post messages only in the agreed
  statuses. Apply the same rules on the server and show closed-state guidance.
- [ ] Validate/escape message text, reject empty/oversized messages, and prevent
  accidental duplicate sends on retries or page refresh.
- [ ] Keep each conversation linked to exactly one tip. Enforce ownership when
  reading or posting, including manually altered requests.
- [ ] Persist every accepted message before reporting success. Preserve sender,
  tip linkage, text, and timestamp; expose no individual message edit/delete
  controls and reject those operations through standard editors and APIs.
- [ ] Verify tipster deletion and account disabling retain the conversation for
  admins. Full account deletion removes every message on the account's tips,
  including admin-authored replies, subject to the settled retention policy.
- [ ] If notifications were agreed, add them for the selected events, with no
  sensitive tip/file contents in email, authenticated links, and useful handling
  of delivery failures. Message saving must not depend on email succeeding.
- [ ] Verify persisted messages after logout/reload, isolation across tips/users,
  denied message edits/deletions for both parties, and communication behavior
  before/after every supported status transition and account-state change.

**STOP / REVIEW 4 — Communication**

You exchange messages as admin and tipster on two separate tips. Check when
messaging opens, its behavior after approval/reopening, sender labels, layout,
and any agreed notifications.
Confirm messages survive tipster deletion and account disabling and cannot be
edited or individually deleted by either party. Verify the agreed account-purge
behavior using disposable test conversations.

- [ ] Demonstrate complete conversations and status-dependent behavior.
- [ ] Collect your feedback and wait before Stage 5.

### Stage 5 — Full verification and handover

- [ ] Finish responsive styling, keyboard navigation, labels, error messages,
  and the agreed menu/login entry using the existing Cammino design.
- [ ] Add meaningful automated tests for permissions, ownership, transitions,
  locked writes, stale saves, upload rejection, message persistence/isolation,
  blocked message edits/deletions, admin-only password changes, account
  disabling/purging, and preserved tipster-deleted tips, following
  the repository's PHP workflow-test style.
- [ ] Run PHP syntax checks and relevant existing theme workflow tests after
  bootstrap/router changes. Verify the real WordPress paths on the agreed test
  installation; standalone fixtures alone cannot prove upload/auth behavior.
- [ ] Run end-to-end checks with an administrator, two tipsters, an unrelated
  account, and an anonymous visitor, including blocked tipster password recovery,
  admin password changes, disabling/re-enabling, tipster deletion, and full
  account deletion with populated records and files.
- [ ] Verify no private data leaks through public endpoints, attachment URLs,
  page caching, the visual editor, or direct file access. Check private file
  protection with the theme inactive and after reactivation.
- [ ] Verify idempotent setup on an existing site, role permissions, final
  template routing, and no regressions to WooCommerce or existing page designs.
- [ ] Document page setup, file-storage configuration, notification settings if
  included, deletion/retention behavior, backups, and upgrade/rollback steps in
  `tipsters/README.md`. Feature rollback must preserve stored submissions.
- [ ] Prepare a final acceptance checklist and report passed checks and any
  environment-dependent checks still outstanding.

**STOP / REVIEW 5 — Final acceptance**

You run the full workflow: create an account -> login -> submit multiple tips ->
admin review -> discussion and edits -> messages -> approval and locked form.
Also check tipster deletion with admin visibility, account disabling with all
records retained, administrator-only password changes, uneditable messages, and
permanent account deletion under the settled retention policy.

- [ ] Resolve any feedback and complete the final acceptance checklist.
- [ ] Obtain your acceptance before any production rollout. Production deployment
  is a separate action from implementing and testing this feature.

## 6. Scope boundaries for the first version

There is no public self-registration or tipster password change/reset. Unless
changed during review, there is no live chat/polling, message attachment support,
tip publication, administrator content override, or advanced reporting.
Account CRUD with disabling and full deletion, administrator-controlled
passwords, multiple private tips, private files, administrator review statuses,
tipster deletion with admin retention, persistent messages without individual
editing/deletion, and enforced form locking are all part of the first version.
