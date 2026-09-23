# Cammino Elementor Home proof of concept

This directory recreates the sections in `snapshot-templates/home.php` for
Elementor. The Hero is currently the native-Elementor experiment: it uses only
Elementor Free containers and core widgets. The other nine sections remain
custom widgets while the approach is evaluated. The existing snapshot editor
and its templates remain unchanged.

## Try the complete page

1. Activate this theme on a staging/test site that has Elementor installed.
2. In WordPress, open **Templates > Saved Templates** and choose **Import Templates**.
3. Import `elementor/templates/home-elementor.json`.
4. Create or open a page with Elementor, insert **Cammino - Home Elementor** from
   **My Templates**, and publish it using the **Elementor Full Width** page layout.

The ten individual sections also appear in the Elementor element panel under
**Cammino Home**, except for the native Hero. Insert the Hero through the
complete page template, or import `elementor/templates/hero-elementor.json` as
a standalone template. In the native Hero, its containers, heading, paragraph,
two buttons, main image, two note logos, two notes, and decorative path are all
independently selectable in Elementor's Structure panel.

The remaining custom widgets support inline editing for their main text;
detailed content, links, media, repeaters, projects, and events are available in
the widget panel.

The bundled native image URLs assume that the uploaded theme directory remains
named `wp-cammino`. If WordPress installs it under another directory name,
replace the Hero image and the two note logos once in Elementor.

The newsletter remains a visual placeholder, matching the current Home page.
Project and event selectors use the existing Cammino post metadata and show only
published posts of the appropriate type.
