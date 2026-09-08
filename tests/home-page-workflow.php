<?php
/** Run with `php tests/home-page-workflow.php`. No WordPress/database changes. */

$root     = dirname( __DIR__ );
$template = file_get_contents( $root . '/snapshot-templates/home.php' );
$styles   = file_get_contents( $root . '/assets/css/pages/home.css' );
$script   = file_get_contents( $root . '/assets/js/pages/home.js' );
$checks   = 0;

function home_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$community_start = strpos( $template, '<section class="section community-cta"' );
$community_end   = false !== $community_start ? strpos( $template, '</section>', $community_start ) : false;
$community       = false !== $community_start && false !== $community_end
	? substr( $template, $community_start, $community_end - $community_start )
	: '';

home_expect( ! str_contains( $template, 'story-section smile-impact' ), 'The former standalone Darujme úsmev story section is removed.' );
home_expect( ! str_contains( $template, 'smile-impact-title' ), 'The removed section heading is not retained.' );
home_expect( 1 === substr_count( $template, 'class="smile-impact-stats"' ), 'The impact statistics render only once.' );
home_expect( str_contains( $community, 'class="smile-impact-stats"' ), 'Impact statistics are inside the community CTA.' );
home_expect( str_contains( $community, "'darujme_usmev_deti'" ) && str_contains( $community, "'darujme_usmev_rodiny'" ), 'Both editable counters moved together.' );
home_expect( strpos( $community, 'class="smile-impact-stats"' ) < strpos( $community, '>Viac o projekte <' ), 'The statistics appear above the project button.' );
home_expect( 2 === substr_count( $community, 'data-impact-counter' ), 'Both public counter animations remain connected.' );
home_expect( 2 === substr_count( $community, 'data-nstarter-variable-output' ), 'Both visual-editor values remain connected.' );
home_expect( ! str_contains( $styles, '.story-section.smile-impact' ) && ! str_contains( $styles, '.smile-impact .story-' ), 'Styles for the removed standalone section are cleaned up.' );
home_expect( str_contains( $styles, '.community-cta .smile-impact-stat:hover' ), 'The relocated cards have CTA-specific interaction styling.' );
home_expect( str_contains( $script, '[data-impact-counter]' ), 'The homepage counter script still discovers the relocated values.' );

echo "Passed $checks home page checks.\n";
