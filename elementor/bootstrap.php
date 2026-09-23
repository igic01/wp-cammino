<?php
/**
 * Elementor proof-of-concept bootstrap for the Cammino homepage.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Register assets shared by every Cammino Home Elementor widget. */
function cammino_elementor_register_assets(): void {
	wp_register_style(
		'cammino-elementor-font-awesome',
		'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.2.0/css/all.min.css',
		array(),
		'7.2.0'
	);
	wp_register_style(
		'cammino-elementor-base',
		NSTARTER_URL . '/assets/css/cammino-base.css',
		array( 'cammino-elementor-font-awesome' ),
		(string) filemtime( NSTARTER_PATH . '/assets/css/cammino-base.css' )
	);
	wp_register_style(
		'cammino-elementor-home',
		NSTARTER_URL . '/assets/css/pages/home.css',
		array( 'cammino-elementor-base' ),
		(string) filemtime( NSTARTER_PATH . '/assets/css/pages/home.css' )
	);
	wp_register_style(
		'cammino-elementor-home-bridge',
		NSTARTER_URL . '/elementor/elementor-home.css',
		array( 'cammino-elementor-home' ),
		(string) filemtime( NSTARTER_PATH . '/elementor/elementor-home.css' )
	);
	wp_register_script(
		'cammino-elementor-shell',
		NSTARTER_URL . '/assets/js/cammino-shell.js',
		array(),
		NSTARTER_VERSION,
		true
	);
	wp_register_script(
		'cammino-elementor-home',
		NSTARTER_URL . '/assets/js/pages/home.js',
		array( 'cammino-elementor-shell' ),
		(string) filemtime( NSTARTER_PATH . '/assets/js/pages/home.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'cammino_elementor_register_assets', 5 );
add_action( 'elementor/editor/before_enqueue_scripts', 'cammino_elementor_register_assets', 5 );

/** Add a dedicated group to the Elementor element panel. */
function cammino_elementor_register_category( $elements_manager ): void {
	$elements_manager->add_category(
		'cammino-home',
		array(
			'title' => __( 'Cammino Home', 'cammino' ),
			'icon'  => 'eicon-home-heart',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'cammino_elementor_register_category' );

/** Add the design-scope classes only to pages containing a Cammino Home widget. */
function cammino_elementor_body_classes( array $classes ): array {
	$post_id = get_queried_object_id();
	if ( $post_id > 0 ) {
		$data = (string) get_post_meta( $post_id, '_elementor_data', true );
		if ( str_contains( $data, 'cammino-home-' ) || str_contains( $data, 'cammino-native-hero' ) ) {
			$classes[] = 'cammino-visual-page';
			$classes[] = 'cammino-elementor-home-page';
			$classes[] = 'home-page';
		}
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'cammino_elementor_body_classes' );

/** Load and register the homepage section widgets after Elementor is available. */
function cammino_elementor_register_widgets( $widgets_manager ): void {
	$files = array(
		'class-home-widget.php',
		'class-hero.php',
		'class-about.php',
		'class-projects.php',
		'class-events.php',
		'class-story.php',
		'class-main-project.php',
		'class-involvement.php',
		'class-donate.php',
		'class-partners.php',
		'class-newsletter.php',
	);

	foreach ( $files as $file ) {
		require_once NSTARTER_PATH . '/elementor/elements/' . $file;
	}

	$classes = array(
		'Cammino_Elementor_About',
		'Cammino_Elementor_Projects',
		'Cammino_Elementor_Events',
		'Cammino_Elementor_Story',
		'Cammino_Elementor_Main_Project',
		'Cammino_Elementor_Involvement',
		'Cammino_Elementor_Donate',
		'Cammino_Elementor_Partners',
		'Cammino_Elementor_Newsletter',
	);

	foreach ( $classes as $class ) {
		$widgets_manager->register( new $class() );
	}
}
add_action( 'elementor/widgets/register', 'cammino_elementor_register_widgets' );

/** Load Home design assets when a page uses only the native Hero section. */
function cammino_elementor_enqueue_native_assets(): void {
	$post_id = get_queried_object_id();
	if ( $post_id < 1 ) {
		return;
	}
	$data = (string) get_post_meta( $post_id, '_elementor_data', true );
	if ( str_contains( $data, 'cammino-native-hero' ) ) {
		wp_enqueue_style( 'cammino-elementor-home-bridge' );
		wp_enqueue_script( 'cammino-elementor-home' );
	}
}
add_action( 'wp_enqueue_scripts', 'cammino_elementor_enqueue_native_assets', 20 );
