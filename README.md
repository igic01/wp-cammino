# Cammino WordPress theme

This repository contains a minimal child theme for [Astra](https://wpastra.com/).
It deliberately relies on Astra's normal template hierarchy, so existing Astra
and Elementor pages continue to work unchanged while new Cammino features are
built incrementally. The opt-in custom pages currently include the editable
**Domov**, **O nás**, **Kontakt**, **Príbehy úspechov**, **Novinky**, and
donation designs, plus a shared single-post design for articles and events.

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
- `snapshot-templates/contact.php` is the clean PHP source for the Kontakt page.
- `snapshot-templates/ss.php` is the variable-card source for Príbehy úspechov.
- `snapshot-templates/news.php` is the editable shell around live post listings.
- `snapshot-templates/donate.php` provides the editable donation-options page.
- `snapshot-templates/donate-us.php` provides the unrestricted-donation page.
- `snapshot-templates/donate-detail.php` provides the reusable cause-detail page.
- `inc/posts.php` reads Article/Event placement from explicit post metadata and stores
  optional event details.
- `templates/single-post.php` renders both classified post types identically.
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

The Príbehy úspechov template uses nested visual-editor variables. The outer
control sets the number of story cards. Every card has its own 0–4 photo control
and a text control that writes the destination of its **Celý príbeh** link.
Card copy and images remain editable through the normal Text and Media modes.

## Publish an article or event

1. Create or edit a normal WordPress post and set its title, excerpt, featured
   image, and categories. Every post automatically uses the shared Cammino
   Article/Event design.
2. In the **Cammino príspevok** box, choose **Článok** or **Podujatie** under
   **Typ príspevku**. The selection controls its listing placement independently
   of the post slug.
3. For an event, optionally set its date/time, location, and status label in the
   same box.
4. Open **Cammino visual editor**. Use **Article content** to add, remove, and
   reorder titles, paragraphs, and images. Edit words in Text mode, replace an
   image in Media mode, and then press **Save**.
4. Publish the post. Both types use the same reorderable content section and
   detail-page styling.

The newsletter card is currently a visual placeholder and intentionally reports
that no mailing-list integration is connected yet.

The original donation forms are interactive design previews only. They do not
collect, transmit, or process payments.

Avoid adding `page.php` or other parent-template overrides unless a future
feature intentionally needs to replace Astra behavior. The custom Cammino header
and footer are rendered only inside the isolated visual-page and post wrappers,
so ordinary Astra and Elementor pages continue to use Astra.
