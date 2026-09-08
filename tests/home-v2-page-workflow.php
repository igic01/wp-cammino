<?php
/** Run with `php tests/home-v2-page-workflow.php`. No WordPress/database changes. */

$root      = dirname( __DIR__ );
$template  = file_get_contents( $root . '/snapshot-templates/home-v2.php' );
$home      = file_get_contents( $root . '/snapshot-templates/home.php' );
$styles    = file_get_contents( $root . '/assets/home-v2/home-v2.css' );
$home_css  = file_get_contents( $root . '/assets/css/pages/home.css' );
$theme     = file_get_contents( $root . '/functions.php' );
$checks    = 0;

function home_v2_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

home_v2_expect( str_contains( $template, 'Snapshot Name: Domov – gradienty a zvýraznenia' ), 'The alternative homepage is registered as a native snapshot.' );
home_v2_expect( str_contains( $template, "require __DIR__ . '/home.php'" ), 'Version two inherits the complete maintained homepage structure.' );
home_v2_expect( str_contains( $template, 'class="home-v2"' ), 'The alternative design has an isolated scope class.' );
home_v2_expect( ! str_contains( $home, 'home-v2' ) && ! str_contains( $home_css, 'home-v2' ), 'The original homepage source and stylesheet remain unchanged.' );
home_v2_expect( 1 === substr_count( $styles, 'gradient(' ), 'The design contains exactly one gradient.' );
home_v2_expect( str_contains( $styles, '.home-v2 .hero {' ) && str_contains( $styles, 'background-image: linear-gradient(110deg, #fff5ed 0%, #fff5ed 13%, #efc7ca 33%, #a28ebf 49%, #5898cc 69%, #5898cc 100%);' ), 'The four-color gradient is applied to the hero only.' );
home_v2_expect( str_contains( $styles, '--v2-canvas: #fffaf4;' ) && str_contains( $styles, 'background: var(--v2-canvas) !important;' ), 'The shared body artwork is explicitly replaced by a warm solid background.' );
home_v2_expect( str_contains( $styles, '--v2-red: #e83b39;' ) && str_contains( $styles, '--v2-orange: #f09910;' ) && str_contains( $styles, '--v2-blue: #0299b5;' ), 'The supplied three-colour palette is defined centrally and exactly.' );
home_v2_expect( str_contains( $styles, '--v2-red-soft: #fbd8d7;' ) && str_contains( $styles, '--v2-orange-soft: #fce7c5;' ) && str_contains( $styles, '--v2-blue-soft: #d2eef3;' ), 'Quiet tints of the supplied palette support section backgrounds.' );
home_v2_expect( str_contains( $styles, '.home-v2 .about {' ) && str_contains( $styles, '.home-v2 .events {' ) && str_contains( $styles, '.home-v2 .story-section {' ), 'The redesign covers the complete homepage rather than the hero alone.' );
home_v2_expect( ! preg_match( '/\.home-v2 \.home-project-card:nth-child\([^\n]+\) \{\s*background-color:/', $styles ), 'Version two keeps every project card on the same surface color.' );
home_v2_expect( str_contains( $styles, '.home-v2 .community-cta {' ) && str_contains( $styles, 'background-color: #17343b;' ), 'Version two retains the deep-blue community CTA.' );
home_v2_expect( str_contains( $styles, 'body.home-v2-page .site-header.is-scrolled {' ) && str_contains( $styles, 'body.home-v2-page .site-footer {' ), 'The new art direction includes both header and footer.' );
home_v2_expect( str_contains( $styles, '.home-v2 .event-image::after {' ) && str_contains( $styles, 'background: rgba(23, 52, 59, 0.08);' ), 'The inherited event image gradient is replaced with a solid tint.' );
home_v2_expect( str_contains( $styles, '.site-nav > a:not(.button, .language-link)::after' ) && str_contains( $styles, 'background-color: var(--v2-red);' ), 'Header link underlines use the red accent color.' );
home_v2_expect( str_contains( $styles, '.home-v2 .hero-copy h1 em {' ) && str_contains( $styles, 'color: var(--v2-red);' ), 'Emphasized hero text receives the red accent color.' );
home_v2_expect( ! str_contains( $styles, 'border-radius:' ) && ! str_contains( $styles, 'clip-path:' ), 'The alternative color layer does not change existing element shapes.' );
home_v2_expect( ! str_contains( $styles, 'font-family:' ) && ! str_contains( $styles, 'font-size:' ) && ! str_contains( $styles, '--font-' ), 'The alternative color layer does not change typography.' );
home_v2_expect( str_contains( $styles, '.home-v2 .story-blob' ) && str_contains( $styles, '.home-v2 .community-cta-shape' ) && ! str_contains( $styles, 'display: none;' ), 'The original decorative shapes remain present and are only recolored.' );
home_v2_expect( str_contains( $styles, 'body.home-v2-page .site-header {' ) && str_contains( $styles, 'body.home-v2-page .site-footer {' ), 'The alternative palette extends to the page header and footer.' );
home_v2_expect( preg_match( "/'home-v2'\\s*=>\\s*array\\(/", $theme ) && str_contains( $theme, '/assets/home-v2/home-v2.css' ), 'The theme loads the second design from its dedicated asset folder.' );
home_v2_expect( str_contains( $theme, "'cammino-home-v2-base' => '/assets/css/pages/home.css'" ), 'Version two layers on the original homepage styles.' );
home_v2_expect( str_contains( $theme, "'home-v2'       => array( 'home-page', 'home-v2-page' )" ), 'The second homepage receives isolated body classes.' );

echo "Passed $checks home V2 page checks.\n";
