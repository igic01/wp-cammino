# Cammino WordPress theme

This repository contains a minimal child theme for [Astra](https://wpastra.com/).
It deliberately relies on Astra's normal template hierarchy, so existing Astra
and Elementor pages continue to work unchanged while new Cammino features are
built incrementally. The opt-in custom pages currently include the editable
**O nás**, **Kontakt**, **Príbehy úspechov**, and **Novinky** designs, plus a
shared single-post design for Cammino articles and events.

## Requirements

- WordPress 6.4 or newer
- PHP 8.0 or newer
- Astra installed as the parent theme in `wp-content/themes/astra`
- Elementor installed when existing Elementor pages require it
- Contact Form 7 installed and active for the Kontakt page

## Current structure

- `style.css` declares the Astra child theme and is the entry point for shared CSS.
- `functions.php` loads the child stylesheet on ordinary pages and isolated
  Cammino assets on custom visual pages.
- `snapshot-templates/about-us.php` is the clean PHP source for the O nás page.
- `snapshot-templates/contact.php` is the clean PHP source for the Kontakt page.
- `snapshot-templates/ss.php` is the variable-card source for Príbehy úspechov.
- `snapshot-templates/news.php` is the editable shell around live post listings.
- `snapshot-templates/donate.php` provides the editable donation-options page.
- `snapshot-templates/donate-us.php` provides the unrestricted-donation page.
- `snapshot-templates/donate-detail.php` provides the reusable cause-detail page.
- `inc/posts.php` derives Article/Event placement from the post slug and stores
  optional event details.
- `templates/single-post.php` renders both classified post types identically.
- `inc/` and `assets/js/editor.js` provide the copied visual snapshot editor.
- `assets/fonts/` contains the Fredoka and Varela Round families used by custom
  Cammino pages.
- `temp_static/` contains reference material and must remain untouched.
- `live/` is a local implementation reference and is ignored by Git/deployment.

## Create an editable Cammino page

1. Create or edit a WordPress page.
2. In the page **Template** selector, choose **Cammino — O nás**,
   **Cammino — Kontakt**, **Cammino — Príbehy úspechov**, or
   **Cammino — Novinky**, then save.
3. Use the **Cammino visual editor** meta box or the **Visual editor** admin-bar
   link to open the editor.
4. Edit text in Text mode, replace images or videos in Media mode, and press
   **Save**.

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

1. Create or edit a normal WordPress post and use its title, excerpt, featured
   image, categories, and block-editor content as usual. Every post automatically
   uses the shared Cammino single-post design. The same design is also available
   explicitly as **Cammino Article** in the post's Template selector. With the
   default template, only the post's own content is rendered. On an empty post,
   **Cammino Article** additionally renders the complete reference article body.
2. To place it under **Podujatia**, make its slug start with `event-` or
   `podujatie-`, for example `event-komunitny-den`. Every other slug places the
   post under **Novinky**.
3. For an event, optionally set its date/time, location, and status label in the
   **Cammino príspevok** box.
4. Publish the post. The slug changes only its listing placement; both types
   keep the same detail-page styling.

The newsletter card is currently a visual placeholder and intentionally reports
that no mailing-list integration is connected yet.

The donation forms are interactive design previews only. They do not collect,
transmit, or process payments until a payment provider is integrated separately.

Avoid adding `header.php`, `footer.php`, `page.php`, or other parent-template
overrides unless a future feature intentionally needs to replace Astra behavior.
