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
$GLOBALS['test_meta'][2][CAMMINO_PROJECT_HIDE_IMAGE_META] = '1';

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
projects_expect( str_contains( $directory, 'project-card--no-image' ) && ! str_contains( $directory, 'project-card__media' ), 'A project configured without an image renders a text-only card.' );

$picker_options = cammino_get_project_picker_options();
projects_expect( 1 === count( $picker_options ) && 2 === $picker_options[0]['id'], 'The visual picker offers only published, public project posts.' );
projects_expect( 'Projekt Beta' === $picker_options[0]['title'], 'Picker options use the current project title.' );
projects_expect( str_contains( $picker_options[0]['html'], 'home-project-card--no-image' ), 'Picker previews honor the project image setting.' );

$home_projects = cammino_render_home_projects( array( 'ids' => array( 2 ) ) );
$home_query    = end( $GLOBALS['test_queries'] );
projects_expect( 3 === $home_query['posts_per_page'] && array( 2 ) === $home_query['post__in'], 'The homepage renderer requests only the selected IDs with a three-project limit.' );
projects_expect( str_contains( $home_projects, '<a class="home-project-card' ) && str_contains( $home_projects, 'Projekt Beta' ), 'A selected project renders as a fully linked homepage card.' );
projects_expect( str_contains( $home_projects, 'Krátky popis projektu.' ), 'The homepage project card displays its description.' );

$template = file_get_contents( NSTARTER_PATH . '/snapshot-templates/projects.php' );
projects_expect( ! preg_match( '/<header class="projects-heading">.*?<p>/s', $template ), 'The removed introductory header paragraph is not rendered.' );
projects_expect( str_contains( $template, 'class="projects-feature"' ), 'A permanent featured-project hero is rendered.' );
projects_expect( str_contains( $template, '<h1 id="featured-project-title">' ), 'The featured project provides the page heading.' );
projects_expect( str_contains( $template, 'class="projects-feature__copy"' ) && str_contains( $template, 'class="button button--coral"' ), 'The hero contains description copy and a CTA.' );
projects_expect( str_contains( $template, 'class="projects-feature__media"' ) && str_contains( $template, '<img ' ), 'The hero contains a replaceable image.' );
projects_expect( strpos( $template, 'class="projects-feature"' ) < strpos( $template, 'class="projects-listing section"' ), 'The featured hero remains above the category directory.' );

echo "Passed $checks project directory checks.\n";
