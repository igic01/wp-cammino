# Cammino Elementor Home proof of concept

This directory adds ten independent Elementor widgets matching the sections in
`snapshot-templates/home.php`. The existing snapshot editor and its templates
remain unchanged.

## Try the complete page

1. Activate this theme on a staging/test site that has Elementor installed.
2. In WordPress, open **Templates > Saved Templates** and choose **Import Templates**.
3. Import `elementor/templates/home-elementor.json`.
4. Create or open a page with Elementor, insert **Cammino - Home Elementor** from
   **My Templates**, and publish it using the **Elementor Full Width** page layout.

The ten individual sections also appear in the Elementor element panel under
**Cammino Home**. Their main text supports Elementor inline editing; detailed
content, links, media, repeaters, projects, and events are available in the
widget panel.

The newsletter remains a visual placeholder, matching the current Home page.
Project and event selectors use the existing Cammino post metadata and show only
published posts of the appropriate type.

