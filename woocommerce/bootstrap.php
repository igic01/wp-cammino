<?php
/** Cammino product catalogue integration. @package Cammino */

defined( 'ABSPATH' ) || exit;

/** The shop is an archive, so read its assigned page template explicitly. */
function cammino_is_product_catalogue(): bool {
	$page_id = function_exists( 'is_shop' ) && is_shop() ? wc_get_page_id( 'shop' ) : get_queried_object_id();
	return ( is_page() || ( function_exists( 'is_shop' ) && is_shop() ) )
		&& 'woocommerce/page-products.php' === get_page_template_slug( $page_id );
}

add_filter( 'template_include', static function ( string $template ): string {
	return cammino_is_product_catalogue() ? NSTARTER_PATH . '/woocommerce/page-products.php' : $template;
}, 100 );

add_filter( 'body_class', static function ( array $classes ): array {
	if ( cammino_is_product_catalogue() ) {
		$classes[] = 'cammino-visual-page';
		$classes[] = 'cammino-product-catalogue';
	}
	return $classes;
} );

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( ! cammino_is_product_catalogue() ) {
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
	cammino_enqueue_design_assets( 'cammino-products', '/woocommerce/assets/products.css', '' );
}, 1001 );

add_action( 'woocommerce_after_shop_loop_item_title', static function (): void {
	if ( ! cammino_is_product_catalogue() ) {
		return;
	}
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$description = wp_trim_words( strip_shortcodes( wp_strip_all_tags( $product->get_short_description() ) ), 30 );
	if ( '' !== $description ) {
		echo '<p class="cammino-product-description">' . esc_html( $description ) . '</p>';
	}
}, 7 );
