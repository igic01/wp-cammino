# Cammino WordPress theme

This repository contains a minimal child theme for [Astra](https://wpastra.com/).
It deliberately relies on Astra's normal template hierarchy, so existing Astra
and Elementor pages continue to work unchanged while new Cammino features are
built incrementally. The opt-in custom pages currently include the editable
**Domov**, **O nás**, **Naše aktivity**, **Darujme úsmev**, **Kontakt**, **Príbehy úspechov**, **Novinky**, **Všetky podujatia**, **Projekty**, and donation designs, plus a
shared single-post design for events, projects, and impact stories.

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
- `snapshot-templates/events.php` is the compact directory of upcoming events,
  with the shared Cammino header and client-side event-type filtering.
- `snapshot-templates/projects.php` is the project directory without a hero. It
  lists post categories first and filters linked project cards on the page.
- `snapshot-templates/donate.php` provides the editable donation-options page.
- `snapshot-templates/donate-us.php` provides the unrestricted-donation page.
- `snapshot-templates/donate-detail.php` provides the reusable cause-detail page.
- `inc/posts.php` stores the selected event/project/impact-story type and optional
  details, preserves legacy articles, and provides type-aware starter content.
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

For the event directory, assign **Cammino — Všetky podujatia** to a page. It
uses the shared header and a compact, photo-free card grid focused on event
title, type, date, time, and location. Because the collection stays below ten
events, visitors filter it by event type without date search or pagination.

For the project directory, assign **Cammino — Projekty** to a page. Published
posts whose Cammino type is **Projekt** appear automatically. WordPress post
categories create the filter buttons; each card shows its title, excerpt (or a
shortened body), and the featured image only when one is set.

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
   shows this type in its own column. Event posts are automatically assigned to
   the dedicated WordPress **Podujatia** category and removed from that category
   if their type changes.
3. Fill the optional fields for that type: event date/time, location, and an
   optional free-text event type used by the directory filters;
   project period, countries/location and status; or impact result and reporting
   period. Only populated fields for the current type appear publicly.
4. Open **Cammino visual editor**. Use the purple variable control attached to
   the **Event details** element to update its title, date/time, location,
   optional event type, and cover-photo visibility; those details appear
   prominently below the title.
   Use the three buttons directly below the body to add a heading,
   paragraph, or placeholder image. Edit text in place; click an image to
   replace it, and use the small controls below a block to move or remove it.
5. Publish the post. Events appear in the event listing; projects appear in the
   project directory and news listing; impact stories and legacy articles appear
   in the main news listing.

Titles, excerpts and type-specific facts can be edited in WordPress. Post titles
and the event date/location are also editable in the visual editor. Body sections,
links and images are edited there as well. New empty posts start with an empty
body and the inline add controls. Changing a type never regenerates a saved body
or overwrites existing content. **Reset content** remains an explicit reset to
the current WordPress content or an empty body.

## Article body builder

All three post types use the same dedicated content-builder element below the
cover image. Its three large inline buttons add unlimited headings, paragraphs,
and placeholder images. Text is editable in the preview; clicking an image opens
the WordPress media library. The small buttons below each block change its order
or remove it. The standard floating editor panel remains available for modes,
Save, View, and reset. Existing saved content is preserved, and legacy article
classifications remain unchanged until explicitly reclassified.

## Post workflow checks

Run `php tests/post-workflow.php` and `php tests/events-page-workflow.php` for
standalone regression checks using WordPress test doubles. They cover migration,
event-category assignment, visual event details, photo visibility, compact event
cards and type filtering, type changes, saved-content preservation, the
inline builder, and removal of legacy Related Posts markup. These checks do not
require or modify a WordPress database.

The newsletter card is currently a visual placeholder and intentionally reports
that no mailing-list integration is connected yet.

The original donation forms are interactive design previews only. They do not
collect, transmit, or process payments.

Avoid adding `page.php` or other parent-template overrides unless a future
feature intentionally needs to replace Astra behavior. The custom Cammino header
and footer are rendered only inside the isolated visual-page and post wrappers,
so ordinary Astra and Elementor pages continue to use Astra.
