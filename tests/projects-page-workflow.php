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
projects_expect( ! str_contains( $directory, 'data-project-filter="uncategorized"' ), 'Uncategorized is not shown as a filter.' );
projects_expect( str_contains( $directory, 'data-project-categories="vzdelavanie komunita"' ), 'Cards expose all their categories to the client filter.' );
projects_expect( str_contains( $directory, '<a class="project-card' ), 'The whole project card is a link.' );
projects_expect( str_contains( $directory, 'Projekt Beta' ), 'The project title is displayed.' );
projects_expect( str_contains( $directory, 'Krátky popis projektu.' ), 'The project description is displayed.' );

echo "Passed $checks project directory checks.\n";
