<?php
/**
 * Cammino child theme bootstrap.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NSTARTER_VERSION', '1.2.2' );
define( 'NSTARTER_PATH', get_stylesheet_directory() );
define( 'NSTARTER_URL', get_stylesheet_directory_uri() );

require_once NSTARTER_PATH . '/inc/snapshots.php';
require_once NSTARTER_PATH . '/inc/live-sections.php';
require_once NSTARTER_PATH . '/inc/variable-sections.php';
require_once NSTARTER_PATH . '/inc/editor.php';

add_action( 'init', 'cammino_register_live_sections' );

/**
 * Register dynamic content that must never be stored in an editable snapshot.
 */
function cammino_register_live_sections(): void {
	nstarter_register_live_section(
		'cammino_contact_form',
		static function (): string {
			if ( ! shortcode_exists( 'contact-form-7' ) ) {
				return '<p class="cammino-live-section-error">' . esc_html__( 'Contact Form 7 is required to display this form.', 'cammino' ) . '</p>';
			}

			return (string) do_shortcode( '[contact-form-7 id="d43ca6f" title="Kontaktný formulár 1"]' );
		}
	);
}

add_action( 'wp_enqueue_scripts', 'cammino_enqueue_child_styles', 15 );

/**
 * Load shared child-theme CSS on ordinary Astra pages.
 */
function cammino_enqueue_child_styles(): void {
	if ( is_page() && nstarter_is_visual_page( get_queried_object_id() ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_style(
		'cammino-child',
		get_stylesheet_uri(),
		array( 'astra-theme-css' ),
		(string) $theme->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'cammino_enqueue_visual_page_assets', 1000 );

/**
 * Load the reusable Cammino foundation and the selected page's own assets.
 */
function cammino_enqueue_visual_page_assets(): void {
	if ( ! is_page() || nstarter_is_editor_request() ) {
		return;
	}

	$slug  = nstarter_get_native_source_template_slug( get_queried_object_id() );
	$pages = array(
		'about-us' => array(
			'handle' => 'cammino-about-us',
			'style'  => '/assets/css/pages/about-us.css',
			'script' => '/assets/js/pages/about-us.js',
		),
		'contact'  => array(
			'handle' => 'cammino-contact',
			'style'  => '/assets/css/pages/contact.css',
			'script' => '/assets/js/pages/contact.js',
		),
		'ss'       => array(
			'handle' => 'cammino-success-stories',
			'style'  => '/assets/css/pages/ss.css',
			'script' => '/assets/js/pages/ss.js',
		),
	);

	if ( ! isset( $pages[ $slug ] ) ) {
		return;
	}

	$page = $pages[ $slug ];

	if ( 'contact' === $slug ) {
		if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
			wpcf7_enqueue_styles();
		}

		if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
			wpcf7_enqueue_scripts();
		}
	}

	wp_enqueue_style(
		'cammino-font-awesome',
		'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.2.0/css/all.min.css',
		array(),
		'7.2.0'
	);

	wp_enqueue_style(
		'cammino-base',
		NSTARTER_URL . '/assets/css/cammino-base.css',
		array( 'cammino-font-awesome' ),
		NSTARTER_VERSION
	);

	wp_enqueue_style(
		$page['handle'],
		NSTARTER_URL . $page['style'],
		array( 'cammino-base' ),
		NSTARTER_VERSION
	);

	wp_enqueue_script(
		$page['handle'],
		NSTARTER_URL . $page['script'],
		array(),
		NSTARTER_VERSION,
		true
	);

	if ( nstarter_is_preview_request() ) {
		wp_enqueue_style(
			'cammino-editor-preview',
			NSTARTER_URL . '/assets/css/editor-preview.css',
			array( $page['handle'] ),
			NSTARTER_VERSION
		);
	}
}

add_action( 'wp_enqueue_scripts', 'cammino_isolate_visual_page_assets', 999 );

/**
 * Prevent Astra's presentation layer from leaking into opt-in Cammino pages.
 */
function cammino_isolate_visual_page_assets(): void {
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

add_filter( 'body_class', 'cammino_visual_page_body_classes' );

/**
 * Add page and editor state classes to the custom document.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function cammino_visual_page_body_classes( array $classes ): array {
	if ( is_page() ) {
		$slug         = nstarter_get_native_source_template_slug( get_queried_object_id() );
		$page_classes = array(
			'about-us' => 'about-page',
			'contact'  => 'contact-page',
			'ss'       => 'stories-page',
		);

		if ( isset( $page_classes[ $slug ] ) ) {
			$classes[] = 'cammino-visual-page';
			$classes[] = $page_classes[ $slug ];
		}
	}

	if ( nstarter_is_preview_request() ) {
		$classes[] = 'nstarter-editor-preview';
	}

	return $classes;
}
