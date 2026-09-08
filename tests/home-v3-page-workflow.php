<?php
/** Run with `php tests/home-v3-page-workflow.php`. No WordPress/database changes. */

$root      = dirname( __DIR__ );
$template  = file_get_contents( $root . '/snapshot-templates/home-v3.php' );
$home      = file_get_contents( $root . '/snapshot-templates/home.php' );
$styles    = file_get_contents( $root . '/assets/home-v3/home-v3.css' );
$home_css  = file_get_contents( $root . '/assets/css/pages/home.css' );
$theme     = file_get_contents( $root . '/functions.php' );
$checks    = 0;

function home_v3_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

home_v3_expect( str_contains( $template, 'Snapshot Name: Domov – farebné bloky' ), 'The third homepage is registered as a native snapshot.' );
home_v3_expect( str_contains( $template, "require __DIR__ . '/home.php'" ), 'Version three inherits the maintained homepage structure.' );
home_v3_expect( str_contains( $template, 'class="home-v3"' ), 'Version three has an isolated scope class.' );
home_v3_expect( ! str_contains( $home, 'home-v3' ) && ! str_contains( $home_css, 'home-v3' ), 'The original homepage remains unchanged.' );
home_v3_expect( str_contains( $styles, '--v3-red: #e83b39;' ) && str_contains( $styles, '--v3-orange: #f09910;' ) && str_contains( $styles, '--v3-blue: #0299b5;' ), 'The supplied palette is defined centrally and exactly.' );
home_v3_expect( ! str_contains( $styles, 'gradient(' ), 'The colour-block direction uses solid surfaces.' );
home_v3_expect( ! preg_match( '/\.home-v3 \.home-project-card:nth-child\([^\n]+\) \{\s*background-color:/', $styles ), 'Version three keeps every project card on the same surface color.' );
home_v3_expect( str_contains( $styles, '.home-v3 .community-cta {' ) && str_contains( $styles, 'background-color: #17343b;' ), 'Version three retains the deep-blue community CTA.' );
home_v3_expect( ! str_contains( $styles, 'border-radius:' ) && ! str_contains( $styles, 'clip-path:' ), 'The colour layer does not alter shapes.' );
home_v3_expect( ! str_contains( $styles, 'font-family:' ) && ! str_contains( $styles, 'font-size:' ) && ! str_contains( $styles, '--font-' ), 'The colour layer does not alter typography.' );
home_v3_expect( str_contains( $styles, '.home-v3 .story-blob' ) && str_contains( $styles, '.home-v3 .community-cta-shape' ) && str_contains( $styles, '.home-v3 .home-involvement__shape' ), 'All inherited decorative shapes are retained and recoloured.' );
home_v3_expect( str_contains( $styles, 'body.home-v3-page .site-header {' ) && str_contains( $styles, 'body.home-v3-page .site-footer {' ), 'The palette covers the shared header and footer.' );
home_v3_expect( preg_match( "/'home-v3'\\s*=>\\s*array\\(/", $theme ) && str_contains( $theme, '/assets/home-v3/home-v3.css' ), 'The theme loads the third design stylesheet.' );
home_v3_expect( str_contains( $theme, "'cammino-home-v3-base' => '/assets/css/pages/home.css'" ), 'Version three layers on the original homepage styles.' );
home_v3_expect( str_contains( $theme, "'home-v3'       => array( 'home-page', 'home-v3-page' )" ), 'Version three receives isolated body classes.' );

echo "Passed $checks home V3 page checks.\n";
