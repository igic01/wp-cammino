<?php
/** Run with `php tests/main-project-page-workflow.php`. No WordPress/database changes. */

$root     = dirname( __DIR__ );
$template = file_get_contents( $root . '/snapshot-templates/main-project.php' );
$styles   = file_get_contents( $root . '/assets/css/pages/main-project.css' );
$script   = file_get_contents( $root . '/assets/js/pages/main-project.js' );
$theme    = file_get_contents( $root . '/functions.php' );
$checks   = 0;

function main_project_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

main_project_expect( str_contains( $template, 'Snapshot Name: Hlavný projekt' ), 'The main-project source is registered as a native snapshot.' );
main_project_expect( str_contains( $template, '<h1 id="main-project-title">Darujme <em>úsmev</em></h1>' ), 'The page introduces the supplied project.' );
main_project_expect( ! str_contains( strtolower( $template ), 'eyebrow' ), 'The page does not render eyebrow elements.' );
main_project_expect( 1 === substr_count( $template, '1500+' ) && 1 === substr_count( $template, '300+' ), 'Supplied impact totals are not repeated.' );
main_project_expect( 2 === substr_count( $template, 'data-main-project-counter' ), 'Only the two standalone impact totals are marked for count-up animation.' );
main_project_expect( str_contains( $template, '<strong>6 domovov</strong>' ), 'The Ukraine home count remains static.' );
main_project_expect( str_contains( $template, 'Identifikácia rodín a detí' ), 'The first project step is present.' );
main_project_expect( str_contains( $template, 'Zapojenie dobrovoľníkov a partnerov' ), 'The second project step is present.' );
main_project_expect( str_contains( $template, 'Distribúcia pomoci' ), 'The third project step is present.' );
main_project_expect( str_contains( $template, "'main_project_stories'" ), 'Stories expose a stable variable-section identifier.' );
main_project_expect( str_contains( $template, "'control' => 'repeat'" ), 'Stories use the repeat control.' );
main_project_expect( str_contains( $template, "'max'     => 20" ), 'The story collection supports many entries.' );
main_project_expect( str_contains( $template, 'data-nstarter-variable-items' ) && str_contains( $template, 'data-nstarter-variable-template' ), 'Stories include the repeat container and template.' );
main_project_expect( preg_match( '/<article class="main-project-story" data-nstarter-variable-item>\s*<h3>.*?<\/h3>\s*<p>.*?<\/p>\s*<\/article>/s', $template ), 'Each generated story contains a title and description.' );
main_project_expect( str_contains( $styles, 'body:not(.nstarter-editor-preview) .main-project-stories[data-nstarter-variable-value="0"]' ), 'An empty story collection stays hidden outside the editor.' );
main_project_expect( strrpos( $template, '<section' ) === strpos( $template, '<section class="section donate"' ), 'The donation call to action is the final section.' );
main_project_expect( str_contains( $template, '>Chcem pomôcť <' ), 'The reused donation call to action keeps the requested label.' );
main_project_expect( str_contains( $theme, "'main-project' => array(" ) && str_contains( $theme, '/assets/css/pages/main-project.css' ) && str_contains( $theme, '/assets/js/pages/main-project.js' ), 'The theme loads the dedicated main-project assets.' );
main_project_expect( str_contains( $script, 'IntersectionObserver' ), 'The page animation is progressive and viewport-aware.' );
main_project_expect( str_contains( $script, 'animateCounter' ) && str_contains( $script, 'editorPreview' ), 'Impact counters animate publicly without mutating editable preview text.' );

echo "Passed $checks main project page checks.\n";
