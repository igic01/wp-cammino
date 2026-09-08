<?php
/** Run with `php tests/contact2-page-workflow.php`. No WordPress/database changes. */

$root      = dirname( __DIR__ );
$template  = file_get_contents( $root . '/snapshot-templates/contact2.php' );
$styles    = file_get_contents( $root . '/assets/css/pages/contact2.css' );
$script    = file_get_contents( $root . '/assets/js/pages/contact2.js' );
$functions = file_get_contents( $root . '/functions.php' );
$checks    = 0;

function contact2_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

contact2_expect( str_contains( $template, 'Snapshot Name: Zapojte sa' ), 'The contact2 source is registered as the Zapojte sa snapshot.' );
contact2_expect( str_contains( $template, '<main id="main-content" class="contact2-main">' ), 'The template exposes the shared-shell main landmark.' );
contact2_expect( ! str_contains( $template, '<header' ) && ! str_contains( $template, '<footer' ), 'The shared wrapper remains responsible for the header and footer.' );
contact2_expect( str_contains( $template, '<h1 id="contact2-title">' ) && str_contains( $template, 'contact2-lead' ), 'Editable information appears beside the form.' );
contact2_expect( str_contains( $template, "nstarter_live_section( 'cammino_contact2_form' )" ), 'The snapshot stores a live form marker.' );
contact2_expect( str_contains( $functions, "'cammino_contact2_form'" ), 'The second contact live section is registered.' );
contact2_expect( str_contains( $functions, '[contact-form-7 id="5554de4" title="Zapojte sa"]' ), 'The requested Contact Form 7 shortcode is rendered.' );
contact2_expect( str_contains( $functions, "'contact2' => array(" ) && str_contains( $functions, '/assets/css/pages/contact2.css' ) && str_contains( $functions, '/assets/js/pages/contact2.js' ), 'Contact2 loads dedicated assets.' );
contact2_expect( str_contains( $functions, "array( 'contact', 'contact2' )" ), 'Contact Form 7 assets load on both contact templates.' );
contact2_expect( str_contains( $functions, "'contact2'      => array( 'contact2-page' )" ), 'Contact2 receives an isolated body class.' );
contact2_expect( str_contains( $styles, '.contact2-form-card .wpcf7 select' ), 'The involvement selector is styled.' );
contact2_expect( str_contains( $styles, '.contact2-form-card .wpcf7-checkbox' ) && str_contains( $styles, '.wpcf7-acceptance' ), 'Checkbox and consent controls are styled.' );
contact2_expect( str_contains( $styles, '.contact2-form-card .wpcf7-submit' ), 'The submit control uses the Cammino design.' );
contact2_expect( str_contains( $styles, '@media (max-width: 760px)' ), 'The two-column page has a mobile layout.' );
contact2_expect( str_contains( $script, 'IntersectionObserver' ) && str_contains( $script, 'textarea.style.height' ), 'Contact2 includes progressive reveals and textarea resizing.' );

echo "Passed $checks contact2 page checks.\n";
