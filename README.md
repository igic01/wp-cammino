# Cammino WordPress theme

This repository contains a minimal child theme for [Astra](https://wpastra.com/).
It deliberately relies on Astra's normal template hierarchy, so existing Astra
and Elementor pages continue to work unchanged while new Cammino features are
built incrementally. The first opt-in custom page is the editable **O nás**
design.

## Requirements

- WordPress 6.4 or newer
- PHP 8.0 or newer
- Astra installed as the parent theme in `wp-content/themes/astra`
- Elementor installed when existing Elementor pages require it

## Current structure

- `style.css` declares the Astra child theme and is the entry point for shared CSS.
- `functions.php` loads the child stylesheet on ordinary pages and isolated
  Cammino assets on custom visual pages.
- `snapshot-templates/about-us.php` is the clean PHP source for the O nás page.
- `inc/` and `assets/js/editor.js` provide the copied visual snapshot editor.
- `assets/fonts/` contains the Fredoka and Varela Round families used by custom
  Cammino pages.
- `temp_static/` contains reference material and must remain untouched.
- `live/` is a local implementation reference and is ignored by Git/deployment.

## Create the editable O nás page

1. Create or edit a WordPress page.
2. In the page **Template** selector, choose **Cammino — O nás** and save.
3. Use the **Cammino visual editor** meta box or the **Visual editor** admin-bar
   link to open the editor.
4. Edit text in Text mode, replace images or videos in Media mode, and press
   **Save**.

The saved HTML is stored in ACF when ACF is active, with private post meta as a
fallback. **Regenerate page** resets the editable snapshot from
`snapshot-templates/about-us.php`.

Avoid adding `header.php`, `footer.php`, `page.php`, or other parent-template
overrides unless a future feature intentionally needs to replace Astra behavior.
