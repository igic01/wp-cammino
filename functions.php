<?php
/**
 * NStarter theme bootstrap.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NSTARTER_VERSION', '1.2.0' );
define( 'NSTARTER_PATH', get_stylesheet_directory() );
define( 'NSTARTER_URL', get_stylesheet_directory_uri() );

require_once NSTARTER_PATH . '/inc/snapshots.php';
require_once NSTARTER_PATH . '/inc/live-sections.php';
require_once NSTARTER_PATH . '/inc/variable-sections.php';
require_once NSTARTER_PATH . '/inc/cammino-snapshots.php';
require_once NSTARTER_PATH . '/inc/editor.php';

add_action( 'after_setup_theme', 'nstarter_setup' );

/**
 * Configure the basic theme features.
 */
function nstarter_setup(): void {
	load_theme_textdomain( 'nstarter', NSTARTER_PATH . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
}

add_action( 'wp_enqueue_scripts', 'nstarter_enqueue_assets' );

/**
 * Load snapshot/editor CSS only on pages using an NStarter design.
 */
function nstarter_enqueue_assets(): void {
	if ( ! is_page() || ! nstarter_is_visual_page( get_queried_object_id() ) ) {
		return;
	}

	wp_enqueue_style( 'nstarter', get_stylesheet_uri(), array(), NSTARTER_VERSION );
}

add_action( 'wp_enqueue_scripts', 'nstarter_isolate_visual_page_assets', 999 );

/**
 * Keep Astra's frontend CSS and JavaScript away from the intentionally bare
 * snapshot document. Ordinary Astra pages are never touched by this function.
 */
function nstarter_isolate_visual_page_assets(): void {
	if ( ! is_page() || ! nstarter_is_visual_page( get_queried_object_id() ) ) {
		return;
	}

	global $wp_styles, $wp_scripts;

	$parent_url = trailingslashit( get_template_directory_uri() );
	$styles     = is_object( $wp_styles ) ? (array) $wp_styles->queue : array();
	$scripts    = is_object( $wp_scripts ) ? (array) $wp_scripts->queue : array();

	foreach ( $styles as $handle ) {
		$source = isset( $wp_styles->registered[ $handle ] ) ? (string) $wp_styles->registered[ $handle ]->src : '';
		if ( str_starts_with( $handle, 'astra-' ) || ( '' !== $source && str_contains( $source, $parent_url ) ) ) {
			wp_dequeue_style( $handle );
		}
	}

	foreach ( $scripts as $handle ) {
		$source = isset( $wp_scripts->registered[ $handle ] ) ? (string) $wp_scripts->registered[ $handle ]->src : '';
		if ( str_starts_with( $handle, 'astra-' ) || ( '' !== $source && str_contains( $source, $parent_url ) ) ) {
			wp_dequeue_script( $handle );
		}
	}
}

add_action( 'after_switch_theme', 'nstarter_inherit_astra_theme_mods' );

/**
 * Copy the current Astra Customizer/menu settings on first child activation.
 * Existing child-theme values win, and the parent option remains unchanged.
 */
function nstarter_inherit_astra_theme_mods(): void {
	if ( get_option( 'nstarter_astra_mods_inherited', false ) ) {
		return;
	}

	$parent_mods = get_option( 'theme_mods_astra', array() );
	$child_key   = 'theme_mods_' . get_stylesheet();
	$child_mods  = get_option( $child_key, array() );

	if ( is_array( $parent_mods ) && is_array( $child_mods ) ) {
		update_option( $child_key, array_merge( $parent_mods, $child_mods ) );
	}

	update_option( 'nstarter_astra_mods_inherited', NSTARTER_VERSION );
}

add_filter( 'body_class', 'nstarter_body_classes' );

/**
 * Add editor-preview state to the iframe document.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function nstarter_body_classes( array $classes ): array {
	if ( nstarter_is_preview_request() ) {
		$classes[] = 'nstarter-editor-preview';
	}

	return $classes;
}
