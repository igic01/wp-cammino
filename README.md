# Cammino WordPress theme

This repository contains a minimal child theme for [Astra](https://wpastra.com/).
It deliberately relies on Astra's normal template hierarchy, so existing Astra
and Elementor pages continue to work unchanged while new Cammino features are
built incrementally. The opt-in custom pages currently include the editable
**Domov**, **O nás**, **Naše aktivity**, **Darujme úsmev**, **Kontakt**, **Príbehy úspechov**, **Novinky**, and
donation designs, plus a shared single-post design for events, projects, and
impact stories.

## Requirements

- WordPress 6.4 or newer
- PHP 8.0 or newer
- Astra installed as the parent theme in `wp-content/themes/astra`
- Elementor installed when existing Elementor pages require it
- Contact Form 7 installed and active for the Kontakt page

## Current structure

- `style.css` declares the Astra child theme and is the entry point for shared CSS.
- `functions.php` loads the child stylesheet on ordinary pages and isolated
  Cammino assets on custom visual pages. It registers the shared Cammino header
  and footer as locked live sections on visual pages and uses the same renderers
  outside the editable content of managed posts.
- `snapshot-templates/home.php` is the editable Cammino homepage source.
- `snapshot-templates/about-us.php` is the clean PHP source for the O nás page.
- `snapshot-templates/activities.php` provides the Naše aktivity page with four
  activity areas, scroll reveals, and an animated illustration using the shared
  design tokens. Choose **Cammino — Naše aktivity** for `/nase-aktivity/`.
- `snapshot-templates/darujme-usmev.php` provides the Darujme úsmev campaign
  page. Choose **Cammino — Darujme úsmev** for `/darujme-usmev/`.
- `snapshot-templates/contact.php` is the clean PHP source for the Kontakt page.
- `snapshot-templates/ss.php` is the variable-card source for Príbehy úspechov.
- `snapshot-templates/news.php` is the editable shell around live post listings.
- `snapshot-templates/donate.php` provides the editable donation-options page.
- `snapshot-templates/donate-us.php` provides the unrestricted-donation page.
- `snapshot-templates/donate-detail.php` provides the reusable cause-detail page.
- `inc/posts.php` stores the selected event/project/impact-story type and optional
  details, preserves legacy articles, and provides type-aware starter content.
- `inc/post-collections.php` renders configurable live collections of other posts
  and provides authenticated search and preview for the visual editor.
- `templates/single-post.php` renders all three types using the shared article
  layout with type-specific labels, accents, and factual summaries.
- `inc/` and `assets/js/editor.js` provide the copied visual snapshot editor.
- `assets/fonts/` contains the Fredoka and Varela Round families used by custom
  Cammino pages.
- `temp_static/` contains reference material and must remain untouched.
- `live/` is a local implementation reference and is ignored by Git/deployment.

## Create an editable Cammino page

1. Create or edit a WordPress page.
2. In the page **Template** selector, choose the required **Cammino — ...**
   design, such as **Cammino — Domov**, **Cammino — O nás**, **Cammino —
   Novinky**, or one of the donation templates, then save.
3. Use the **Cammino visual editor** meta box or the **Visual editor** admin-bar
   link to open the editor.
4. Edit text in Text mode, replace images or videos in Media mode, and press
   **Save**.

For the homepage, assign **Cammino — Domov** to a page and then select that page
under **Settings → Reading → Your homepage displays**.

The saved HTML is stored in ACF when ACF is active, with private post meta as a
fallback. **Regenerate page** resets the editable snapshot from
the selected file in `snapshot-templates/`.

The Kontakt template renders Contact Form 7 form `d43ca6f` at request time.
Its surrounding copy remains editable, while the live form itself is locked in
the visual editor so a snapshot save cannot replace or stale its shortcode.

The Naše aktivity template uses the usual Text, Media, and Link editing modes.
Its two image placeholders can be replaced in Media mode; add appropriate alt
text when adding real photos. Motion is disabled in the editor and respects
reduced-motion preferences. Project news, stories, and contact links resolve to
their assigned templates; Darujme úsmev uses the same project URL as the homepage.
The impact section uses qualitative outcomes until verified figures and reporting
periods are available.

The Darujme úsmev template includes the project introduction, qualitative impact,
three-step process, story introduction, three ways to help, gallery and partner
acknowledgement, and a closing invitation. Its gift illustration and scroll
animations respect reduced motion and remain still in the editor. Text, links,
photos and captions use the normal visual editing modes.

Three repeat controls manage verified result cards (0–4, initially 0), gallery
photos (0–9, initially 3 placeholders), and confirmed partner logos (0–12,
initially 0). After adding a result card, replace its dash, description and period
with verified information. After adding a partner, replace the placeholder logo
and name. Use consented photos and supply appropriate alt text in Media mode.
The source document's draft totals, project age, overseas recipients and example
family story require confirmation; they are not presented as facts in the initial
snapshot. The story section uses general introductory copy until a verified story
is available. Support links lead to the existing Kontakt template to arrange
volunteering, partnership or a project donation; set a verified project payment URL
in Link mode when available.

The Príbehy úspechov template uses nested visual-editor variables. The outer
control sets the number of story cards. Every card has its own 0–4 photo control
and a text control that writes the destination of its **Celý príbeh** link.
Card copy and images remain editable through the normal Text and Media modes.

## Publish an event, project, or impact story

1. Create or edit a normal WordPress post and set its title, excerpt, featured
   image, and categories. Every post automatically uses the shared Cammino
   article design. These remain normal WordPress posts with a type dropdown,
   preserving their URLs and compatibility with the existing visual editor.
2. In **Cammino príspevok → Typ príspevku**, choose **Podujatie** (event),
   **Projekt** (project), or **Príbeh pomoci** (impact story). The posts list also
   shows this type in its own column.
3. Fill the optional fields for that type: event date/time, location and status;
   project period, countries/location and status; or impact result and reporting
   period. Only populated fields for the current type appear publicly.
4. Open **Cammino visual editor**. Use **Obsah príspevku** to add, remove, and
   reorder titles, paragraphs, and images. Edit words in Text mode, replace an
   image in Media mode, and then press **Save**.
5. Publish the post. Events appear in the existing event listing; projects,
   impact stories and legacy articles appear in the main news listing.

Titles, excerpts and type-specific facts are edited in WordPress. Body sections,
links and images are edited in the visual editor. New empty posts receive starter
headings appropriate to their type. Changing a type never regenerates a saved
body or overwrites existing content. **Regenerate page** remains an explicit reset
to the WordPress content/starter body.

## Add a section of other posts

1. Open **Obsah príspevku → + Ďalšie príspevky**.
2. Set the section heading, then choose **Najnovšie príspevky** to show 1–6 cards
   from all types or one type. Alternatively choose **Vybrané príspevky**, search
   by title, and pick up to six published posts in the desired order. Removing and
   selecting a post again changes its position. The type selector filters search
   results; manual selections can span several types.
3. Press **Použiť**, arrange the section using the builder arrows, and press the
   main **Save** button. Add several sections if needed. **Nastaviť** reopens an
   existing section's settings; **×** removes it.

The saved body stores collection settings, not copies of the linked posts. Cards
read current titles, images, types, and URLs on each render. Draft, private and
password-protected posts, and the current post itself, are excluded. Empty
collections are hidden publicly and explained in the editor.

Existing visual bodies gain these controls without regeneration. The old fixed
“Read next” section becomes an editable default collection. Once removed and
saved, it stays removed. Legacy **Článok** records retain their content and type
until explicitly reclassified; only the three new types are selectable. A one-time
migration preserves the old classification of records without stored type metadata.

## Post workflow checks

Run `php tests/post-workflow.php` for standalone regression checks using WordPress
test doubles. They cover migration, type changes, saved-content preservation,
collection ordering, live updates, visibility, configuration validation, and
authenticated endpoints. These checks do not require or modify a WordPress database.

The newsletter card is currently a visual placeholder and intentionally reports
that no mailing-list integration is connected yet.

The original donation forms are interactive design previews only. They do not
collect, transmit, or process payments.

Avoid adding `page.php` or other parent-template overrides unless a future
feature intentionally needs to replace Astra behavior. The custom Cammino header
and footer are rendered only inside the isolated visual-page and post wrappers,
so ordinary Astra and Elementor pages continue to use Astra.
