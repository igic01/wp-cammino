<?php
/** Run with `php tests/projects-page-workflow.php`. No WordPress/database changes. */
function get_the_excerpt( $post ) { return 'Krátky popis projektu.'; }
function wp_trim_words( $text, $limit ) { return implode( ' ', array_slice( preg_split( '/\s+/', trim( $text ) ), 0, $limit ) ); }

require __DIR__ . '/post-fixtures.php';

$checks = 0;
function projects_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$GLOBALS['test_categories'][2] = array(
	(object) array( 'slug' => 'vzdelavanie', 'name' => 'Vzdelávanie' ),
	(object) array( 'slug' => 'komunita', 'name' => 'Komunita' ),
	(object) array( 'slug' => 'uncategorized', 'name' => 'Uncategorized' ),
);

$directory = cammino_render_all_projects();
$query     = end( $GLOBALS['test_queries'] );

projects_expect( -1 === $query['posts_per_page'], 'The project directory requests every project.' );
projects_expect( 'project' === $query['meta_query'][0]['value'], 'Only posts placed as projects are queried.' );
projects_expect( str_contains( $directory, 'data-project-filter="vzdelavanie"' ), 'A project category becomes a filter.' );
projects_expect( str_contains( $directory, 'data-project-filter="komunita"' ), 'Every meaningful project category becomes a filter.' );
projects_expect( str_contains( $directory, 'data-project-filter="uncategorized"' ), 'Every category used by a project is shown.' );
projects_expect( ! str_contains( $directory, 'data-project-filter="all"' ), 'The redesigned selector starts with categories rather than an all-projects filter.' );
projects_expect( str_contains( $directory, 'class="project-category-card"' ), 'Categories use the large selectable card design.' );
projects_expect( str_contains( $directory, 'data-project-categories="vzdelavanie komunita uncategorized"' ), 'Cards expose all their categories to the client filter.' );
projects_expect( str_contains( $directory, 'data-project-category-view' ), 'The initial panel state contains the category view.' );
projects_expect( str_contains( $directory, 'data-project-results' ), 'The same panel contains the replacement project view.' );
projects_expect( str_contains( $directory, 'data-project-selected-name' ), 'The selected category name is displayed above its projects.' );
projects_expect( str_contains( $directory, 'data-project-back' ), 'Visitors can return from projects to the category list.' );
projects_expect( ! str_contains( $directory, 'project-category-selector__heading' ), 'The removed category heading is not rendered.' );
projects_expect( str_contains( $directory, '<a class="project-card' ), 'The whole project card is a link.' );
projects_expect( str_contains( $directory, 'Projekt Beta' ), 'The project title is displayed.' );
projects_expect( str_contains( $directory, 'Krátky popis projektu.' ), 'The project description is displayed.' );

$template = file_get_contents( NSTARTER_PATH . '/snapshot-templates/projects.php' );
projects_expect( ! preg_match( '/<header class="projects-heading">.*?<p>/s', $template ), 'The removed introductory header paragraph is not rendered.' );

echo "Passed $checks project directory checks.\n";
