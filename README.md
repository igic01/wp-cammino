# Cammino WordPress theme

This repository contains a minimal child theme for [Astra](https://wpastra.com/).
It deliberately relies on Astra's normal template hierarchy, so existing Astra
and Elementor pages continue to work unchanged while new Cammino features are
built incrementally. The opt-in custom pages currently include the editable
**O nás**, **Kontakt**, and **Príbehy úspechov** designs.

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
- `inc/` and `assets/js/editor.js` provide the copied visual snapshot editor.
- `assets/fonts/` contains the Fredoka and Varela Round families used by custom
  Cammino pages.
- `temp_static/` contains reference material and must remain untouched.
- `live/` is a local implementation reference and is ignored by Git/deployment.

## Create an editable Cammino page

1. Create or edit a WordPress page.
2. In the page **Template** selector, choose **Cammino — O nás**,
   **Cammino — Kontakt**, or **Cammino — Príbehy úspechov**, then save.
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

Avoid adding `header.php`, `footer.php`, `page.php`, or other parent-template
overrides unless a future feature intentionally needs to replace Astra behavior.
