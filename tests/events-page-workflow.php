<?php
/** Run with `php tests/events-page-workflow.php`. No WordPress/database changes. */
require __DIR__ . '/post-fixtures.php';

$checks = 0;
function events_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function _n( $single, $plural, $number, $domain = '' ) { return 1 === (int) $number ? $single : $plural; }
function home_url( $path = '/' ) { return $path; }
function add_query_arg( $args, $url ) { return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . http_build_query( $args ); }
function wp_reset_postdata() {}

class WP_Query {
	public $posts = array();
	public $found_posts = 0;
	public $max_num_pages = 0;

	public function __construct( array $args ) {
		$GLOBALS['test_event_query_args'] = $args;
		$all = $GLOBALS['test_event_directory_posts'];
		$this->found_posts = count( $all );
		$this->max_num_pages = (int) ceil( $this->found_posts / $args['posts_per_page'] );
		$this->posts = array_slice( $all, ( ( $args['paged'] ?? 1 ) - 1 ) * $args['posts_per_page'], $args['posts_per_page'] );
	}
}

$GLOBALS['test_event_directory_posts'] = array();
$event_types = array(
	(object) array( 'slug' => 'workshop', 'name' => 'Workshopy' ),
	(object) array( 'slug' => 'webinar', 'name' => 'Webináre' ),
	(object) array( 'slug' => 'komunita', 'name' => 'Komunita' ),
);
for ( $id = 100; $id < 108; ++$id ) {
	$post = new WP_Post( $id, 'Podujatie ' . $id );
	$GLOBALS['test_posts'][ $id ] = $post;
	$GLOBALS['test_meta'][ $id ][ CAMMINO_POST_PLACEMENT_META ] = 'event';
	$GLOBALS['test_meta'][ $id ][ CAMMINO_EVENT_DATE_META ] = '2026-10-15T16:00';
	$GLOBALS['test_meta'][ $id ][ CAMMINO_EVENT_LOCATION_META ] = 'Bratislava';
	$GLOBALS['test_categories'][ $id ] = array( $event_types[ ( $id - 100 ) % count( $event_types ) ] );
	$GLOBALS['test_event_directory_posts'][] = $post;
}
$GLOBALS['test_categories'][100] = array(
	(object) array( 'slug' => 'uncategorized', 'name' => 'Uncategorized' ),
	$event_types[0],
);

$_GET = array();
$directory = cammino_render_all_events( array(), 4 );
events_expect( 10 === $GLOBALS['test_event_query_args']['posts_per_page'], 'Directory query has a safe ten-event ceiling' );
events_expect( 8 === substr_count( $directory, 'data-event-card' ), 'Directory renders every event in the compact set' );
events_expect( 4 === substr_count( $directory, 'data-event-filter=' ), 'Directory renders all and three event-type filters' );
events_expect( ! str_contains( $directory, 'type="date"' ), 'Directory does not render a date search' );
events_expect( ! str_contains( $directory, 'events-pager' ), 'Small event collections do not use pagination' );
events_expect( 24 === substr_count( $directory, '<time' ), 'Every event renders its date tile, full date, and time' );
events_expect( 8 === substr_count( $directory, 'fa-location-dot' ), 'Every event renders a location' );
events_expect( str_contains( $directory, 'data-event-type="workshop"' ), 'Event categories supply the type-filter value' );
events_expect( ! str_contains( $directory, 'Uncategorized' ), 'The default WordPress category is not exposed as an event type' );
events_expect( ! str_contains( $directory, 'data-reveal' ), 'Event content is visible without reveal JavaScript' );

echo "Passed $checks event directory checks.\n";
