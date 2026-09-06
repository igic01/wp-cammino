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
		$this->posts = array_slice( $all, ( $args['paged'] - 1 ) * $args['posts_per_page'], $args['posts_per_page'] );
	}
}

$GLOBALS['test_event_directory_posts'] = array();
for ( $id = 100; $id < 135; ++$id ) {
	$post = new WP_Post( $id, 'Podujatie ' . $id );
	$GLOBALS['test_posts'][ $id ] = $post;
	$GLOBALS['test_meta'][ $id ][ CAMMINO_POST_PLACEMENT_META ] = 'event';
	$GLOBALS['test_meta'][ $id ][ CAMMINO_EVENT_DATE_META ] = '2026-10-15T16:00';
	$GLOBALS['test_meta'][ $id ][ CAMMINO_EVENT_LOCATION_META ] = 'Bratislava';
	$GLOBALS['test_event_directory_posts'][] = $post;
}

$_GET = array();
$first_page = cammino_render_all_events( array(), 4 );
events_expect( 30 === $GLOBALS['test_event_query_args']['posts_per_page'], 'Directory query is limited to 30 events' );
events_expect( 30 === substr_count( $first_page, 'class="event-directory-card"' ), 'First page renders at most 30 events' );
events_expect( str_contains( $first_page, 'class="events-pager"' ), 'Directory renders a pager when more than 30 events exist' );
events_expect( ! str_contains( $first_page, '<img' ), 'Event directory cards do not depend on photos' );

$_GET = array( 'events_page' => '2' );
$second_page = cammino_render_all_events( array(), 4 );
events_expect( 5 === substr_count( $second_page, 'class="event-directory-card"' ), 'Second page renders the remaining events' );

$_GET = array( 'event_date' => '2026-10-15' );
cammino_render_all_events( array(), 4 );
$date_clause = $GLOBALS['test_event_query_args']['meta_query']['event_date'];
events_expect( 'BETWEEN' === $date_clause['compare'], 'Calendar selection activates a bounded date query' );
events_expect( $date_clause['value'] === array( '2026-10-15T00:00', '2026-10-15T23:59' ), 'Calendar query covers only the selected day' );

echo "Passed $checks event directory checks.\n";
