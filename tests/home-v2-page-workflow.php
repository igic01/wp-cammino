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
home_v2_expect( substr_count( $styles, 'linear-gradient(' ) >= 20, 'The color concept uses gradients throughout the page.' );
home_v2_expect( str_contains( $styles, '--v2-spectrum-light: #fff5ed;' ) && str_contains( $styles, '--v2-spectrum-peach: #efc7ca;' ) && str_contains( $styles, '--v2-spectrum-violet: #a28ebf;' ) && str_contains( $styles, '--v2-spectrum-blue: #5898cc;' ), 'The reference light, blush, violet, and blue spectrum is defined as the central palette.' );
home_v2_expect( str_contains( $styles, '--v2-spectrum: linear-gradient(110deg, var(--v2-spectrum-light) 0%, var(--v2-spectrum-peach) 30%, var(--v2-spectrum-violet) 62%, var(--v2-spectrum-blue) 100%);' ), 'The core background follows a full four-color transition.' );
home_v2_expect( str_contains( $styles, '.site-nav > a:not(.button, .language-link)::after' ) && str_contains( $styles, 'background: #15163f;' ), 'Header link underlines use a single solid color.' );
home_v2_expect( str_contains( $styles, '.home-v2 .hero-copy h1 em {' ) && str_contains( $styles, 'color: #d83259;' ), 'Emphasized heading text receives an accent text color.' );
home_v2_expect( ! str_contains( $styles, 'linear-gradient(transparent 60%' ) && ! str_contains( $styles, 'linear-gradient(transparent 62%' ), 'Emphasized text does not use colored background highlights.' );
home_v2_expect( ! str_contains( $styles, 'border-radius:' ) && ! str_contains( $styles, 'clip-path:' ), 'The alternative color layer does not change existing element shapes.' );
home_v2_expect( str_contains( $styles, '.home-v2 .story-blob' ) && str_contains( $styles, '.home-v2 .community-cta-shape' ), 'Large floating circle decorations are disabled.' );
home_v2_expect( str_contains( $styles, '@keyframes home-v2-gradient-drift' ) && str_contains( $styles, '@media (prefers-reduced-motion: reduce)' ), 'Gradient motion includes an accessible reduced-motion fallback.' );
home_v2_expect( str_contains( $styles, 'body.home-v2-page .site-header {' ) && str_contains( $styles, 'body.home-v2-page .site-footer {' ), 'The alternative palette extends to the page header and footer.' );
home_v2_expect( preg_match( "/'home-v2'\\s*=>\\s*array\\(/", $theme ) && str_contains( $theme, '/assets/home-v2/home-v2.css' ), 'The theme loads the second design from its dedicated asset folder.' );
home_v2_expect( str_contains( $theme, "'cammino-home-v2-base' => '/assets/css/pages/home.css'" ), 'Version two layers on the original homepage styles.' );
home_v2_expect( str_contains( $theme, "'home-v2'       => array( 'home-page', 'home-v2-page' )" ), 'The second homepage receives isolated body classes.' );

echo "Passed $checks home V2 page checks.\n";
