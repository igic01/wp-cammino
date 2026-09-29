<?php
/** Standalone theme setup and final front-end template routing. @package Cammino */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'cammino_setup_standalone_theme', 100 );
function cammino_setup_standalone_theme(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}

// Existing installations store "astra" as the parent in WordPress options.
// Re-select the SAME active stylesheet once its installed theme has no parent.
// Run after rendering: changing the template directory during Astra's setup
// could break its current request. The next request boots Cammino alone.
// Theme mods stay under the same key.
add_action( 'shutdown', 'cammino_migrate_standalone_theme', PHP_INT_MAX );
function cammino_migrate_standalone_theme(): void {
	if ( 'astra' !== get_template() ) {
		return;
	}
	wp_clean_themes_cache( false );
	if ( ! wp_get_theme()->parent() ) {
		switch_theme( get_stylesheet() );
	}
}

// Register at the end of init so plugin/template selectors are already present.
add_action( 'init', static function (): void {
	add_filter( 'template_include', 'cammino_use_site_shell', PHP_INT_MAX );
}, PHP_INT_MAX );

function cammino_use_site_shell( string $template ): string {
	if ( is_feed() || is_embed() || is_admin() ) {
		return $template;
	}
	if ( cammino_is_single_product() ) {
		return NSTARTER_PATH . '/woocommerce/single-product.php';
	}
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		return NSTARTER_PATH . '/woocommerce/archive-products.php';
	}
	if ( cammino_is_product_catalogue() ) {
		return NSTARTER_PATH . '/woocommerce/page-products.php';
	}
	if ( is_page() && nstarter_is_visual_page( get_queried_object_id() ) ) {
		return NSTARTER_PATH . '/templates/visual-page.php';
	}
	if ( cammino_is_managed_post_request() ) {
		return NSTARTER_PATH . '/templates/single-post.php';
	}
	if ( is_singular() ) {
		return NSTARTER_PATH . '/page.php';
	}
	return NSTARTER_PATH . '/index.php';
}

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( nstarter_is_editor_request() || nstarter_is_visual_page( get_queried_object_id() ) || cammino_is_managed_post_request()
		|| cammino_is_single_product() || cammino_is_product_catalogue() ) {
		return;
	}
	cammino_enqueue_design_assets( 'cammino-default-page', '/assets/css/pages/default-page.css', '' );
}, 1002 );

add_filter( 'body_class', static function ( array $classes ): array {
	$classes = array_values( array_filter( $classes, static fn( $class ) => ! str_starts_with( $class, 'ast-' ) && ! str_starts_with( $class, 'astra-' ) ) );
	$classes[] = 'cammino-visual-page';
	if ( ! nstarter_is_visual_page( get_queried_object_id() ) && ! cammino_is_managed_post_request()
		&& ! cammino_is_single_product() && ! cammino_is_product_catalogue() ) {
		$classes[] = 'cammino-default-page';
	}
	return array_unique( $classes );
}, PHP_INT_MAX );

// Dequeueing alone does not prevent another plugin's dependency re-adding Astra.
// A harmless empty registration satisfies those dependencies without its assets.
add_action( 'wp_enqueue_scripts', 'cammino_disable_astra_assets', PHP_INT_MAX );
add_action( 'wp_print_styles', 'cammino_disable_astra_assets', PHP_INT_MAX );
add_action( 'wp_print_footer_scripts', 'cammino_disable_astra_assets', 0 );
function cammino_disable_astra_assets(): void {
	global $wp_styles, $wp_scripts;
	foreach ( array( 'style' => $wp_styles, 'script' => $wp_scripts ) as $type => $registry ) {
		foreach ( is_object( $registry ) ? (array) $registry->registered : array() as $handle => $asset ) {
			$source = (string) $asset->src;
			if ( ! str_starts_with( $handle, 'astra-' ) && ! str_contains( $source, '/themes/astra/' ) ) {
				continue;
			}
			$asset->src = false;
			$asset->deps = array();
			$asset->extra = array();
			if ( 'style' === $type ) {
				wp_dequeue_style( $handle );
			} else {
				wp_dequeue_script( $handle );
			}
		}
	}
}
