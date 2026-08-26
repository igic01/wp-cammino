# Cammino WordPress theme

This repository contains a minimal child theme for [Astra](https://wpastra.com/).
It deliberately relies on Astra's normal template hierarchy, so existing Astra
and Elementor pages continue to work unchanged while new Cammino features are
built incrementally.

## Requirements

- WordPress 6.4 or newer
- PHP 8.0 or newer
- Astra installed as the parent theme in `wp-content/themes/astra`
- Elementor installed when existing Elementor pages require it

## Current structure

- `style.css` declares the Astra child theme and is the entry point for shared CSS.
- `functions.php` loads the child stylesheet after Astra's stylesheet.
- `temp_static/` contains reference material and must remain untouched.

Avoid adding `header.php`, `footer.php`, `page.php`, or other parent-template
overrides unless a future feature intentionally needs to replace Astra behavior.
