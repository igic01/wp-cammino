# Cammino Preview — Astra child theme

Cammino Preview is an Astra child theme that adds opt-in PHP source templates and editable HTML snapshots. Pages without an NStarter template continue through Astra's normal template hierarchy and assets. A Cammino snapshot is stored in an ACF field and displayed until it is regenerated from PHP.

## Safe preview installation

1. Keep the parent **Astra** theme installed in the `astra` theme directory.
2. Install this child theme ZIP and activate **Cammino Preview Child**.
3. Existing pages continue using Astra. The child stylesheet and Cammino assets are loaded only for pages explicitly assigned an NStarter snapshot template.
4. On first activation, existing Astra theme modifications (including menu locations and standard Customizer theme mods) are copied into the child-theme option. The original Astra settings are not changed.

The Cammino snapshot document uses `header-nstarter.php` and `footer-nstarter.php`. The child theme intentionally contains no default `header.php`, `footer.php`, `page.php`, or `index.php`, so it does not override those parent templates.

## Set up a page

1. Activate **Cammino Preview Child** and Advanced Custom Fields.
2. Create or edit a WordPress page.
3. In WordPress's normal page **Template** selector, choose an **NStarter — …** design and save.
4. Visit the page to see the selected PHP design immediately.
5. Use the **Open visual editor** button in the sidebar meta box to edit and save its rendered output.

The first editor load previews the PHP output. **Save** stores it in the `nstarter_snapshot_html` ACF field. **Regenerate** renders the PHP file again and replaces the saved snapshot completely.

If ACF is temporarily inactive, the theme uses private WordPress post meta as a fallback so the editor remains usable.

## Editor modes

- **Text:** enables `document.designMode = "on"` inside the page-preview iframe.
- **Media:** clicking an image or video opens a combined image/video Media Library. Either element can be replaced by the other type; videos provide Auto start, Muted, and Show controls options. The edit icon on a video reopens those settings without replacing its source.
- **Interaction:** disables editing and media interception so links, menus, popups and other frontend behavior can be tested.

Press `Ctrl+S` or `Cmd+S` to save from any mode.

## Add a PHP source template

Add a `.php` file to `snapshot-templates/`:

```php
<?php
/**
 * Snapshot Name: Product landing page
 */
?>
<section class="product-hero">
    <h1><?php echo esc_html( get_the_title( $nstarter_post_id ) ); ?></h1>
</section>
```

It automatically appears in WordPress's native page **Template** selector with an **NStarter —** prefix. The template receives:

- `$nstarter_post_id` — current page ID.
- `$nstarter_page` — current `WP_Post` object.

The included **Media Playground** keeps its CSS in the generated snapshot and demonstrates image-to-video and video-to-image replacement. You can organize future production templates differently without changing the snapshot workflow.

## Add an editable section variable

A variable section changes the current snapshot in the browser; it does not require its own ACF field or AJAX endpoint. The editor shows a purple pen in the section's top-right corner. Saving its popup changes the preview immediately, while the main editor **Save** persists the complete resulting HTML snapshot.

For a number that controls repeated items:

```php
<section<?php
nstarter_variable_section_attributes(
    'card_count',
    array(
        'label'   => __( 'Number of cards', 'nstarter' ),
        'type'    => 'number',
        'control' => 'repeat',
        'value'   => 3,
        'min'     => 0,
        'max'     => 12,
        'step'    => 1,
    )
);
?>>
    <div data-nstarter-variable-empty-state>There are currently no cards.</div>
    <div data-nstarter-variable-items>
        <article data-nstarter-variable-item>Existing editable card</article>
    </div>
    <template data-nstarter-variable-template>
        <article data-nstarter-variable-item>New card {{index}}</article>
    </template>
</section>
```

The section wrapper is never removed, even at `0`. Its empty state should be shown with a selector such as `[data-nstarter-variable-value="0"] [data-nstarter-variable-empty-state]`. Existing repeated items keep their text and media edits; removing items requires confirmation. New items come from the template, where `{{index}}` and `{{index_padded}}` are replaced automatically.

For a text value, use `'type' => 'text'` and `'control' => 'text'`, then mark one or more text nodes with `data-nstarter-variable-output`.

The included **Variable Card Playground** is a complete working example. Regenerating the page intentionally resets its variables and card edits to the PHP source defaults.

## Cammino static designs

The nine permanent source documents in `snapshot-sources/cammino/` are available as native snapshot designs with a **Cammino —** prefix. Their page assets live in `assets/cammino/`; CSS and JavaScript are loaded only when the matching design is selected. Relative asset URLs and `.html` links are resolved to theme assets and WordPress pages when the clean snapshot is rendered. Nothing in the snapshot implementation depends on the temporary prototype directory.

The repeated site shell lives in `snapshot-parts/cammino-header.php` and `snapshot-parts/cammino-footer.php`. The active navigation item, optional header modifier, footer anchor, contact email/address, and unique newsletter field ID are passed as PHP variables by `inc/cammino-snapshots.php`.

Repeated editorial collections are exposed through the visual editor's purple variable control:

| Design | Variable collections |
| --- | --- |
| Domov | Support cards, upcoming event rows |
| O nás | Values, contact people |
| Príbehy úspechov | Success stories |
| Novinky | Active events, article cards |
| Článok | Related article cards |
| Kontakt | Contact people |
| Možnosti podpory | Donation option cards |
| Detail podpory | Related donation cards |
| Podporte nás | No genuinely repeatable editorial section; form controls remain fixed for JavaScript behavior |

Each collection keeps its existing items editable, supports a count of `0`, and contains a reusable `<template>` for newly added items. Regenerating resets the snapshot to the current permanent source in `snapshot-sources/cammino/`.

## Add a live section

First register its runtime renderer from the theme, a child theme, or a plugin:

```php
add_action( 'nstarter_register_live_sections', function () {
    nstarter_register_live_section(
        'featured_products',
        function ( array $args, int $post_id ): string {
            ob_start();
            // Query WooCommerce, an API, or the database here.
            ?>
            <section class="products">Fresh content here</section>
            <?php
            return (string) ob_get_clean();
        }
    );
} );
```

Then place its marker in a source template:

```php
<?php nstarter_live_section( 'featured_products', array( 'limit' => 4 ) ); ?>
```

The editor displays the fresh runtime output with a visible **LIVE SECTION · LOCKED** boundary in Text and Media modes. Before saving, JavaScript removes the runtime output from the snapshot and retains only the marker. PHP expands that marker again on every page request.

## Main files

- `inc/cammino-snapshots.php` — Cammino page map, static importer, asset loading, link resolution and repeat-variable adapter.
- `snapshot-parts/cammino-header.php` / `cammino-footer.php` — shared Cammino site shell.
- `snapshot-sources/cammino/` — permanent source HTML used when a Cammino snapshot is first rendered or regenerated.
- `assets/cammino/` — permanent Cammino styles, scripts, fonts, images and logos.

- `snapshot-templates/media-playground.php` — image/video feature test template.
- `snapshot-templates/variable-card-playground.php` — variable card-count test template.
- `templates/visual-page.php` — internal wrapper that displays the saved snapshot.
- `inc/snapshots.php` — ACF storage and source-template discovery.
- `inc/live-sections.php` — live marker registry and runtime expansion.
- `inc/variable-sections.php` — variable-section markup helper.
- `inc/editor.php` — editor shell, permissions and AJAX endpoints.
- `assets/js/editor.js` — design mode, variable controls, media selection, saving and regeneration.
