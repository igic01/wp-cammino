# Cammino WordPress theme

This repository contains a minimal child theme for [Astra](https://wpastra.com/).
It deliberately relies on Astra's normal template hierarchy, so existing Astra
and Elementor pages continue to work unchanged while new Cammino features are
built incrementally. The opt-in custom pages currently include the editable
**Domov**, **O nás**, **Naše aktivity**, **Kontakt**, **Zapojte sa**, **Všetky podujatia**, **Projekty**, **Hlavný projekt**, and donation designs, plus a
shared single-post design for events, projects, and impact stories.

## Requirements

- WordPress 6.4 or newer
- PHP 8.0 or newer
- PHP DOM extension (used to merge saved HTML into updated page layouts)
- Astra installed as the parent theme in `wp-content/themes/astra`
- Elementor installed when existing Elementor pages require it
- Contact Form 7 installed and active for the Kontakt and Zapojte sa pages

## Current structure

- `style.css` declares the Astra child theme and is the entry point for shared CSS.
- `functions.php` loads the child stylesheet on ordinary pages and isolated
  Cammino assets on custom visual pages. It registers the shared Cammino header
  and footer as locked live sections on visual pages and uses the same renderers
  outside the editable content of managed posts.
- `snapshot-templates/home.php` is the editable Cammino homepage source.
  Its project picker displays up to three published Project posts, and its final
  section displays every color logo from `assets/partners/` in a responsive
  partner grid.
- `snapshot-templates/about-us.php` is the clean PHP source for the O nás page.
- `snapshot-templates/activities.php` provides the Naše aktivity page with four
  activity areas, scroll reveals, and an animated illustration using the shared
  design tokens. Choose **Cammino — Naše aktivity** for `/nase-aktivity/`.
- `snapshot-templates/contact.php` is the clean PHP source for the Kontakt page.
- `snapshot-templates/contact2.php` provides the two-column **Zapojte sa** page
  backed by Contact Form 7 form `b4ce2b6`.
- `snapshot-templates/events.php` is the compact directory of upcoming events,
  with the shared Cammino header and client-side event-type filtering.
- `snapshot-templates/projects.php` opens with a permanent featured-project hero,
  then lists post categories and replaces them with linked project cards after a selection.
- `snapshot-templates/main-project.php` is the editable Darujme úsmev project page.
  It includes a repeatable story collection and reuses the homepage donation call to action.
- `snapshot-templates/donate.php` provides the editable donation-options page.
- `snapshot-templates/donate-us.php` provides the unrestricted-donation page.
- `snapshot-templates/donate-detail.php` provides the reusable cause-detail page.
- `inc/posts.php` stores the selected event/project/impact-story type and optional
  details, preserves legacy articles, and provides type-aware starter content.
- `templates/single-post.php` renders events, projects, and existing legacy impact
  stories using the shared article layout.
- `inc/` and `assets/js/editor.js` provide the copied visual snapshot editor.
- `assets/fonts/` contains the Fredoka and Varela Round families used by custom
  Cammino pages.
- `temp_static/` contains reference material and must remain untouched.
- `live/` is a local implementation reference and is ignored by Git/deployment.

## Create an editable Cammino page

1. Create or edit a WordPress page.
2. In the page **Template** selector, choose the required **Cammino — ...**
   design, such as **Cammino — Domov**, **Cammino — O nás**, **Cammino —
   Projekty**, or one of the donation templates, then save.
3. Use the **Cammino visual editor** meta box or the **Visual editor** admin-bar
   link to open the editor.
4. Edit text in Text mode, replace images or videos in Media mode, and press
   **Save**.

For the homepage, assign **Cammino — Domov** to a page and then select that page
under **Settings → Reading → Your homepage displays**.

For the event directory, assign **Cammino — Všetky podujatia** to a page. It
uses the shared header and a compact, photo-free card grid focused on event
title, type, date, time, and location. Because the collection stays below ten
events, visitors filter it by event type without date search or pagination.

For the project directory, assign **Cammino — Projekty** to a page. Published
posts whose Cammino type is **Projekt** appear automatically. WordPress post
categories create the filter buttons; each card shows its title, excerpt (or a
shortened body), and the featured image only when one is set.

On the homepage, use the **Vybrané projekty** variable control to choose zero to
three published Project posts. The live cards keep their titles, descriptions,
links, categories, and optional images synchronized with the source posts.

Page layouts always render from the latest file in `snapshot-templates/`.
Saved HTML supplies the text, links, media, section order and section-variable
values. No title/paragraph field model is required. Private post meta stores the
current HTML and up to ten previous saves independently for each page and selected
design. The current HTML is also mirrored to the legacy snapshot meta and ACF
field when ACF is active; the design-specific history is authoritative.

Use **History** to select a save by date and author and restore it.
Restoring loads an unsaved draft into the editor using the latest layout. The
published page and history stay unchanged until you press **Save**. Saving the
draft keeps the replaced content in history; reloading the editor discards an
unsaved restore. Unchanged saves do not consume a history slot. Switching away
from a design and back retrieves its content and history. **Reset to defaults** inside **History**
replaces content with fresh template output and keeps the previous save in history.

The merger identifies elements by stable `id` attributes and existing variable,
live-section and `aria-labelledby` markers. Unique classes can help match nodes
inside a matched section. Anonymous siblings use their positions only when their
type counts agree; ambiguous or removed content produces an editor warning.
Check the page carefully before saving when a merge warning appears.
The previous raw HTML remains available within the ten-save retention limit.

For reliable updates, retain IDs when moving or redesigning editable elements.
Anonymous siblings that exchange positions without any other identifying change
cannot be distinguished automatically. Saved content always wins, including old
defaults the client never edited. New elements retain the new template defaults.
Tags, classes, wrappers, decorations, scripts and other presentation attributes
come from the latest template. Supported inline text formatting and client media
type replacements are preserved. Repeatable collections retain their items and
order using the current item markup. Forms and listings still render live.
Purge full-page caches after deploying template updates so the fresh layout is served.

The history feature applies to Cammino page designs. Normal posts retain their
existing separate article-body storage and reset workflow. Concurrent page edits
are protected by version tokens; reload a stale editor before saving.

To try the features, create a draft page and select **Cammino — Feature test —
HTML merge and history** as its template. Edit its heading, paragraphs, link
destination and image, adjust the number of test cards, and save several times.
Open **History**, select an earlier save and choose **Restore**, then press
**Save** to commit it. The replaced save remains available. Twelve distinct saves also let you verify the limit of
ten previous saves alongside the current one.

The test template now defaults to layout two. If you saved content with layout
one, purge page caches and reload the editor to see the update. To repeat the
test, change the default `: 2` to `: 1` on the `$cammino_test_layout` line in
`snapshot-templates/feature-test.php`, save your edits with layout one, then
change it back to `: 2`. Layout two changes the heading tag, introduces
wrappers, changes the visual layout and adds a section. Existing edited content,
including both paragraphs without IDs or classes, should remain. Restore an
earlier save again to verify it uses layout two. Use **Reset to defaults** only
when testing a deliberate reset of the content.

The Kontakt and Zapojte sa templates render Contact Form 7 forms `d43ca6f` and
`b4ce2b6` at request time. Their surrounding copy remains editable, while each
live form is locked in the visual editor so a snapshot save cannot replace or
stale its shortcode.

The Naše aktivity template uses the usual Text, Media, and Link editing modes.
Its two image placeholders can be replaced in Media mode; add appropriate alt
text when adding real photos. Motion is disabled in the editor and respects
reduced-motion preferences. Project, story, and contact links resolve to
their assigned destinations; Darujme úsmev resolves to the page assigned the
**Cammino — Hlavný projekt** design.
The impact section uses qualitative outcomes until verified figures and reporting
periods are available.

## Publish an event or project

1. Create or edit a normal WordPress post and set its title, excerpt, featured
   image, and categories. Every post automatically uses the shared Cammino
   article design. These remain normal WordPress posts with a type dropdown,
   preserving their URLs and compatibility with the existing visual editor.
2. In **Cammino príspevok → Typ príspevku**, choose **Podujatie** (event) or
   **Projekt** (project). The removed impact-story choice remains supported only
   on posts that already used it. The posts list also shows the type in its own
   column. Event posts are automatically assigned to
   the dedicated WordPress **Podujatia** category and removed from that category
   if their type changes.
3. Fill the fields for that type: event date/time, location, optional event type,
   category, and photo visibility; for a project, enter its category and choose
   whether its image should be shown. A new category name is created automatically.
4. Open **Cammino visual editor**. Use the purple variable control attached to
   **Event details** or **Project details**. Both edit title, category, and image
   visibility; events additionally edit date/time, location, and event type.
   Use the four buttons directly below the body to add a heading, paragraph,
   placeholder image, or impact-story callout. Edit text in place; click an
   image to replace it, and use the small controls below a block to move or
   remove it. To add a link, select words in a paragraph and choose **Link
   selected text**. Select linked words and choose **Remove link** to undo it.
5. Publish the post. Events appear in the event listing and projects appear in
   the project directory. Impact stories keep their individual post pages.

Titles, excerpts and type-specific facts can be edited in WordPress. Post titles
and the event date/location are also editable in the visual editor. Body sections,
links and images are edited there as well. New empty posts start with an empty
body and the inline add controls. Changing a type never regenerates a saved body
or overwrites existing content. **Reset content** remains an explicit reset to
the current WordPress content or an empty body.

## Article body builder

Managed posts use the same dedicated content-builder element below the
cover image. Its four inline buttons add unlimited headings, paragraphs,
placeholder images, and compact impact-story calls to action. Every paragraph
has safe controls for turning selected words into a link, editing an existing
selected link, or removing links without replacing the paragraph. Text is
editable in the preview; clicking an image opens the WordPress media library.
The small buttons below each block change its order or remove it. The standard floating editor panel remains available for modes,
Save, View, and reset. Existing saved content is preserved, and legacy article
classifications remain unchanged until explicitly reclassified.

## Post workflow checks

Run `php tests/post-workflow.php`, `php tests/events-page-workflow.php`,
`php tests/projects-page-workflow.php`, `php tests/main-project-page-workflow.php`,
`php tests/contact2-page-workflow.php`, and `php tests/home-page-workflow.php` for
standalone regression checks using WordPress test doubles. They cover migration,
event-category assignment, visual event details, photo visibility, compact event
cards and type filtering, type changes, saved-content preservation, the
inline builder, and removal of legacy Related Posts markup. These checks do not
require or modify a WordPress database.

Run `php tests/html-merge-workflow.php`,
`php tests/template-merge-workflow.php` and
`php tests/snapshot-history-workflow.php` for HTML merge and save-history checks.
These cover layout changes, anonymous matching conflicts, media, repeatable
items, all existing page designs, legacy saves, retention, restore, reset,
permissions and concurrent saves without modifying a WordPress database.
Run `node tests/editor-history-workflow.mjs` for the Chrome UI check. Set
`BROWSER_BIN` if Chrome/Chromium is not installed at one of the detected paths.

The newsletter card is currently a visual placeholder and intentionally reports
that no mailing-list integration is connected yet.

The original donation forms are interactive design previews only. They do not
collect, transmit, or process payments.

Avoid adding `page.php` or other parent-template overrides unless a future
feature intentionally needs to replace Astra behavior. The custom Cammino header
and footer are rendered only inside the isolated visual-page and post wrappers,
so ordinary Astra and Elementor pages continue to use Astra.
