<?php
/** Cammino product catalogue integration. @package Cammino */

defined( 'ABSPATH' ) || exit;

// Register explicitly, like the snapshot designs, so the selector does not
// depend on WordPress's cached discovery of PHP template file headers.
add_filter( 'theme_page_templates', static function ( array $templates ): array {
	$templates['woocommerce/page-products.php'] = __( 'Cammino — WooCommerce products', 'cammino' );
	return $templates;
}, 30 );

/** The shop is an archive, so read its assigned page template explicitly. */
function cammino_is_product_catalogue(): bool {
	$page_id = function_exists( 'is_shop' ) && is_shop() ? wc_get_page_id( 'shop' ) : get_queried_object_id();
	return ( is_page() || ( function_exists( 'is_shop' ) && is_shop() ) )
		&& 'woocommerce/page-products.php' === get_page_template_slug( $page_id );
}

function cammino_is_single_product(): bool {
	return function_exists( 'is_product' ) && is_product();
}

// WooCommerce and page builders can select their templates late in the request.
// Choose the shared Cammino shell after those selectors have run.
add_filter( 'template_include', static function ( string $template ): string {
	if ( cammino_is_single_product() ) {
		return NSTARTER_PATH . '/woocommerce/single-product.php';
	}
	return cammino_is_product_catalogue() ? NSTARTER_PATH . '/woocommerce/page-products.php' : $template;
}, PHP_INT_MAX );

add_action( 'woocommerce_before_shop_loop', static function (): void {
	if ( cammino_is_product_catalogue() ) {
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	}
}, 1 );

add_filter( 'body_class', static function ( array $classes ): array {
	if ( cammino_is_product_catalogue() || cammino_is_single_product() ) {
		$classes[] = 'cammino-visual-page';
		$classes[] = cammino_is_single_product() ? 'cammino-single-product' : 'cammino-product-catalogue';
	}
	return $classes;
} );

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( ! cammino_is_product_catalogue() && ! cammino_is_single_product() ) {
		return;
	}
	// Use the same standalone document and shared shell as the donate page.
	global $wp_styles, $wp_scripts;
	$parent_url = trailingslashit( get_template_directory_uri() );
	foreach ( array( 'style' => $wp_styles, 'script' => $wp_scripts ) as $type => $registry ) {
		foreach ( is_object( $registry ) ? (array) $registry->queue : array() as $handle ) {
			$source = isset( $registry->registered[ $handle ] ) ? (string) $registry->registered[ $handle ]->src : '';
			if ( str_starts_with( $handle, 'astra-' ) || ( '' !== $source && str_contains( $source, $parent_url ) ) ) {
				if ( 'style' === $type ) {
					wp_dequeue_style( $handle );
				} else {
					wp_dequeue_script( $handle );
				}
			}
		}
	}
	wp_dequeue_style( 'cammino-child' );
	if ( cammino_is_single_product() ) {
		cammino_enqueue_design_assets( 'cammino-single-product', '/woocommerce/assets/single-product.css', '', array( 'cammino-product-cards' => '/woocommerce/assets/products.css' ) );
	} else {
		cammino_enqueue_design_assets( 'cammino-products', '/woocommerce/assets/products.css', '' );
	}
}, 1001 );

// Both the Shop loop and product shortcode use this template-part filter.
// Keep Astra's product markup on other pages; render our own catalogue cards.
add_filter( 'wc_get_template_part', static function ( string $template, string $slug, string $name ): string {
	if ( 'content' === $slug && 'product' === $name && ( cammino_is_product_catalogue() || cammino_is_single_product() ) ) {
		return NSTARTER_PATH . '/woocommerce/parts/product-card.php';
	}
	return $template;
}, 100, 3 );
