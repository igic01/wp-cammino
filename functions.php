<?php
/**
 * NStarter theme bootstrap.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NSTARTER_VERSION', '1.1.0' );
define( 'NSTARTER_PATH', get_template_directory() );
define( 'NSTARTER_URL', get_template_directory_uri() );

require_once NSTARTER_PATH . '/inc/snapshots.php';
require_once NSTARTER_PATH . '/inc/live-sections.php';
require_once NSTARTER_PATH . '/inc/variable-sections.php';
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
 * Load the small amount of global theme CSS.
 */
function nstarter_enqueue_assets(): void {
	wp_enqueue_style( 'nstarter', get_stylesheet_uri(), array(), NSTARTER_VERSION );
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
