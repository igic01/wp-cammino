<?php
/** Minimal WordPress test doubles for the standalone post workflow regression suite. */
define( 'ABSPATH', __DIR__ );
define( 'NSTARTER_PATH', dirname( __DIR__ ) );
define( 'NSTARTER_URL', '' );
define( 'NSTARTER_VERSION', 'test' );
define( 'CAMMINO_DONATE_URL', '/donate/' );
class WP_Post {
	public $ID, $post_title, $post_type = 'post', $post_status = 'publish', $post_password = '', $post_content = '', $post_name = '', $post_author = 1;
	public function __construct( int $id, string $title ) { $this->ID = $id; $this->post_title = $title; $this->post_name = 'post-' . $id; }
}
class TestJsonResponse extends RuntimeException {
	public $payload, $status;
	public function __construct( $payload, $status ) { $this->payload = $payload; $this->status = $status; }
}
$GLOBALS['test_posts'] = array();
$GLOBALS['test_meta'] = array();
$GLOBALS['test_defaults'] = array();
$GLOBALS['test_options'] = array( 'date_format' => 'j. F Y' );
$GLOBALS['test_can_edit'] = true;
$GLOBALS['test_queries'] = array();
foreach ( array( 1 => 'Podujatie Alfa', 2 => 'Projekt Beta', 3 => 'Príbeh Gama', 4 => 'Pôvodný článok', 5 => 'Koncept', 6 => 'Súkromný projekt', 7 => 'Heslom chránený', 8 => 'Bez metadát' ) as $id => $title ) {
	$GLOBALS['test_posts'][ $id ] = new WP_Post( $id, $title );
}
$GLOBALS['test_posts'][5]->post_status = 'draft';
$GLOBALS['test_posts'][6]->post_status = 'private';
$GLOBALS['test_posts'][7]->post_password = 'secret';
foreach ( array( 1 => 'event', 2 => 'project', 3 => 'impact-story', 4 => 'article', 5 => 'project', 6 => 'project', 7 => 'project' ) as $id => $type ) {
	$GLOBALS['test_meta'][ $id ]['_cammino_post_placement'] = $type;
}
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $text ) { return esc_html( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function sanitize_key( $text ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $text ) ); }
function sanitize_text_field( $text ) { return trim( strip_tags( (string) $text ) ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_slash( $value ) { return addslashes( $value ); }
function wp_unslash( $value ) { return stripslashes( $value ); }
function current_user_can( ...$args ) { return $GLOBALS['test_can_edit']; }
function register_post_meta( $type, $key, $args ) { $GLOBALS['test_defaults'][ $key ] = $args['default'] ?? ''; }
function get_post( $id ) { return $id instanceof WP_Post ? $id : ( $GLOBALS['test_posts'][ $id ] ?? null ); }
function get_post_type( $id ) { return get_post( $id )->post_type ?? ''; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['test_meta'][ $id ][ $key ] ?? ( $GLOBALS['test_defaults'][ $key ] ?? '' ); }
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['test_meta'][ $id ][ $key ] = stripslashes( $value );
	if ( defined( 'POST_WORKFLOW_STATE_FILE' ) ) { file_put_contents( POST_WORKFLOW_STATE_FILE, json_encode( $GLOBALS['test_meta'] ) ); }
}
function delete_post_meta( $id, $key ) { unset( $GLOBALS['test_meta'][ $id ][ $key ] ); }
function get_post_field( $key, $id ) { return get_post( $id )->$key ?? ''; }
function clean_post_cache( $id ) {}
function get_option( $key ) { return $GLOBALS['test_options'][ $key ] ?? false; }
function update_option( $key, $value, $autoload = false ) { $GLOBALS['test_options'][ $key ] = $value; }
function wp_is_post_revision( $id ) { return false; }
function wp_verify_nonce( $nonce, $action ) { return 'test-nonce' === $nonce; }
function check_ajax_referer( $action, $field ) { if ( ! wp_verify_nonce( $_POST[$field] ?? '', $action ) ) { wp_send_json_error( array( 'message' => 'Bad nonce' ), 403 ); } }
function wp_send_json_error( $payload, $status = 400 ) { test_json_response( array( 'success' => false, 'data' => $payload ), $status ); }
function wp_send_json_success( $payload ) { test_json_response( array( 'success' => true, 'data' => $payload ), 200 ); }
function test_json_response( $payload, $status ) {
	if ( defined( 'POST_WORKFLOW_HTTP' ) ) { http_response_code( $status ); header( 'Content-Type: application/json' ); echo json_encode( $payload ); exit; }
	throw new TestJsonResponse( $payload, $status );
}
function test_meta_matches( int $id, array $query ): bool {
	$results = array();
	foreach ( $query as $key => $clause ) {
		if ( 'relation' === $key ) { continue; }
		if ( ! isset( $clause['key'] ) ) { $results[] = test_meta_matches( $id, $clause ); continue; }
		$exists = isset( $GLOBALS['test_meta'][$id][$clause['key']] );
		$value = $GLOBALS['test_meta'][$id][$clause['key']] ?? null;
		$results[] = match ( $clause['compare'] ?? '=' ) {
			'NOT EXISTS' => ! $exists,
			'IN' => $exists && in_array( $value, $clause['value'], true ),
			default => $exists && $value === $clause['value'],
		};
	}
	return ( $query['relation'] ?? 'AND' ) === 'OR' ? in_array( true, $results, true ) : ! in_array( false, $results, true );
}
function get_posts( $args ) {
	$GLOBALS['test_queries'][] = $args;
	$posts = array_values( array_filter( $GLOBALS['test_posts'], static function ( $post ) use ( $args ) {
		if ( $post->post_type !== ( $args['post_type'] ?? 'post' ) ) { return false; }
		if ( ( $args['post_status'] ?? 'publish' ) !== 'any' && $post->post_status !== ( $args['post_status'] ?? 'publish' ) ) { return false; }
		if ( isset( $args['has_password'] ) && ! $args['has_password'] && '' !== $post->post_password ) { return false; }
		if ( in_array( $post->ID, $args['post__not_in'] ?? array(), true ) ) { return false; }
		if ( ! empty( $args['post__in'] ) && ! in_array( $post->ID, $args['post__in'], true ) ) { return false; }
		if ( isset( $args['meta_query'] ) && ! test_meta_matches( $post->ID, $args['meta_query'] ) ) { return false; }
		if ( isset( $args['meta_key'] ) && get_post_meta( $post->ID, $args['meta_key'], true ) !== $args['meta_value'] ) { return false; }
		return empty( $args['s'] ) || str_contains( mb_strtolower( $post->post_title ), mb_strtolower( $args['s'] ) );
	} ) );
	if ( ( $args['orderby'] ?? '' ) === 'post__in' ) {
		usort( $posts, static fn( $a, $b ) => array_search( $a->ID, $args['post__in'], true ) <=> array_search( $b->ID, $args['post__in'], true ) );
	} else { usort( $posts, static fn( $a, $b ) => $b->ID <=> $a->ID ); }
	if ( ( $args['posts_per_page'] ?? 5 ) !== -1 ) { $posts = array_slice( $posts, 0, $args['posts_per_page'] ?? 5 ); }
	return ( $args['fields'] ?? '' ) === 'ids' ? array_map( static fn( $p ) => $p->ID, $posts ) : $posts;
}
function get_the_title( $post ) { return get_post( $post )->post_title ?? ''; }
function get_permalink( $post ) { return '/live/post-preview.php?id=' . get_post( $post )->ID; }
function get_the_date( $format = '', $post = null ) { return date( $format ?: 'j. F Y', 1788600000 ); }
function get_the_post_thumbnail_url( $id, $size ) { return '/assets/images/placeholder.webp'; }
function get_post_timestamp( $post ) { return 1788600000; }
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function strip_shortcodes( $text ) { return $text; }
function wp_timezone() { return new DateTimeZone( 'Europe/Bratislava' ); }
function wp_date( $format, $timestamp ) { return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() )->format( $format ); }
function wp_nonce_field( $action, $name ) { echo '<input type="hidden" name="' . esc_attr($name) . '" value="test-nonce">'; }
function selected( $value, $expected ) { if ( $value === $expected ) { echo 'selected'; } }
function parse_blocks( $content ) { return array( array( 'blockName' => null, 'innerHTML' => $content ) ); }
function render_block( $block ) { return $block['innerHTML']; }
require NSTARTER_PATH . '/inc/live-sections.php';
require NSTARTER_PATH . '/inc/posts.php';
require NSTARTER_PATH . '/inc/post-collections.php';
cammino_register_post_meta();
nstarter_register_live_section( 'cammino_post_collection', 'cammino_render_post_collection' );
