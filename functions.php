<?php
/**
 * Cammino child theme bootstrap.
 *
 * Add site-wide theme hooks here as the project grows. Page-specific code
 * should live with the page feature that owns it.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'cammino_enqueue_child_styles', 15 );

/**
 * Load child-theme styles after Astra's main stylesheet.
 */
function cammino_enqueue_child_styles(): void {
	$theme = wp_get_theme();

	wp_enqueue_style(
		'cammino-child',
		get_stylesheet_uri(),
		array( 'astra-theme-css' ),
		(string) $theme->get( 'Version' )
	);
}
