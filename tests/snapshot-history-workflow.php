<?php
/** Run with `php tests/snapshot-history-workflow.php`. No WordPress/database changes. */
define( 'ABSPATH', __DIR__ );
define( 'NSTARTER_PATH', dirname( __DIR__ ) );
$checks = 0;
$meta = array();
$source = array( 1 => 'home', 2 => 'home' );
$can_edit = true;
$nonce_valid = true;
$fail_write = false;
$race = null;
$author = 7;

function add_action( ...$args ) {}
function do_action( ...$args ) {}
function add_filter( ...$args ) {}
function __( $text, $domain = '' ) { return $text; }
function get_file_data( $file, $headers ) { return array( 'name' => basename( $file, '.php' ) ); }
function sanitize_file_name( $value ) { return preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ); }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_html__( $value, $domain = '' ) { return esc_attr( $value ); }
function get_page_template_slug( $id ) { return 'snapshot-templates/' . $GLOBALS['source'][ $id ] . '.php'; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['meta'][ $id ][ $key ] ?? ''; }
function metadata_exists( $type, $id, $key ) { return array_key_exists( $key, $GLOBALS['meta'][ $id ] ?? array() ); }
function wp_slash( $value ) { return addslashes( $value ); }
function wp_unslash( $value ) { return stripslashes( $value ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function update_post_meta( $id, $key, $value, $previous = '' ) {
	if ( $GLOBALS['fail_write'] ) { return false; }
	if ( $GLOBALS['race'] ) { $callback = $GLOBALS['race']; $GLOBALS['race'] = null; $callback( $id, $key ); }
	// WordPress unslashes the new value; its comparison value is already unslashed.
	if ( '' !== $previous && $previous !== get_post_meta( $id, $key, true ) ) { return false; }
	$GLOBALS['meta'][ $id ][ $key ] = wp_unslash( $value );
	return true;
}
function add_post_meta( $id, $key, $value, $unique = false ) {
	if ( $unique && metadata_exists( 'post', $id, $key ) ) { return false; }
	return update_post_meta( $id, $key, $value );
}
function clean_post_cache( $id ) {}
function wp_cache_delete( ...$args ) {}
function wp_update_post( $data ) { return $data['ID']; }
function wp_generate_uuid4() { static $id = 0; return 'version-' . ++$id; }
function get_current_user_id() { return $GLOBALS['author']; }
function get_userdata( $id ) { return $id ? (object) array( 'display_name' => 'Editor ' . $id ) : false; }
function get_post_type( $id ) { return 'page'; }
function get_post( $id ) { return (object) array( 'ID' => $id, 'post_type' => 'page' ); }
function get_permalink( $id ) { return '/page/' . $id; }
function add_query_arg( $key, $value, $url ) { return $url . '?' . $key . '=' . urlencode( $value ); }
function current_user_can( ...$args ) { return $GLOBALS['can_edit']; }
function absint( $value ) { return abs( (int) $value ); }
function check_ajax_referer( ...$args ) { if ( ! $GLOBALS['nonce_valid'] ) { wp_send_json_error( array(), 403 ); } }
class HistoryJsonResponse extends RuntimeException {
	public array $data;
	public int $status;
	public function __construct( array $data, int $status ) { $this->data = $data; $this->status = $status; }
}
function wp_send_json_error( $data, $status = 400 ) { throw new HistoryJsonResponse( $data, $status ); }
function wp_send_json_success( $data ) { throw new HistoryJsonResponse( $data, 200 ); }
require NSTARTER_PATH . '/inc/snapshots.php';
require NSTARTER_PATH . '/inc/history.php';
require NSTARTER_PATH . '/inc/html-merge.php';
require NSTARTER_PATH . '/inc/live-sections.php';
require NSTARTER_PATH . '/inc/editor.php';

function history_expect( bool $condition, string $message ): void {
	global $checks;
	++$checks;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function history_call( string $callback, array $extra = array() ): HistoryJsonResponse {
	$_POST = array_merge( array( 'post_id' => '1', 'source' => $GLOBALS['source'][1], 'snapshot_token' => nstarter_snapshot_token( 1 ) ), $extra );
	try { $callback(); } catch ( HistoryJsonResponse $response ) { return $response; }
	throw new RuntimeException( 'Expected a JSON response.' );
}

// Existing snapshots are kept as the first recoverable state.
$legacy = '<p>Client’s \"quoted\" content \\ path</p>';
update_post_meta( 1, NSTARTER_SNAPSHOT_META_KEY, wp_slash( $legacy ) );
update_post_meta( 1, '_nstarter_snapshot_source', 'home' );
history_expect( nstarter_get_snapshot_html( 1 ) === $legacy, 'Legacy raw snapshots remain available.' );
history_expect( count( nstarter_snapshot_history_list( 1 ) ) === 1, 'Legacy HTML is listed before the first new save.' );
history_expect( nstarter_update_snapshot_html( 1, '<p>First save</p>' ), 'First save succeeds.' );
$state = nstarter_get_snapshot_history( 1 );
history_expect( $state['previous'][0]['html'] === $legacy, 'The first save preserves the original HTML exactly.' );
$token = nstarter_snapshot_token( 1 );
history_expect( nstarter_update_snapshot_html( 1, '<p>First save</p>' ) && nstarter_snapshot_token( 1 ) === $token, 'Unchanged saves do not create versions or change the token.' );
for ( $i = 2; $i <= 14; ++$i ) { history_expect( nstarter_update_snapshot_html( 1, '<p>Save ' . $i . '</p>' ), 'Save ' . $i . ' succeeds with JSON quotes and slashes.' ); }
$state = nstarter_get_snapshot_history( 1 );
history_expect( count( $state['previous'] ) === 10 && count( nstarter_snapshot_history_list( 1 ) ) === 11, 'Ten previous saves are retained alongside the current save.' );
history_expect( $state['previous'][9]['html'] === '<p>Save 4</p>', 'The oldest saves are pruned.' );
history_expect( $state['current']['author'] === 7 && str_contains( $state['current']['saved_at'], 'UTC' ), 'Versions contain editor and timestamp metadata.' );
history_expect( ! isset( nstarter_snapshot_history_list( 1 )[0]['html'] ), 'Listing history does not send every HTML snapshot.' );

$source[1] = 'about-us';
history_expect( nstarter_get_snapshot_html( 1 ) === '', 'An unsaved design starts with its template defaults.' );
nstarter_update_snapshot_html( 1, '<p>About content</p>' );
history_expect( count( nstarter_get_snapshot_history( 1 )['previous'] ) === 0, 'Designs have independent histories.' );
$source[1] = 'home';
history_expect( nstarter_get_snapshot_html( 1 ) === '<p>Save 14</p>', 'Switching back to a design retrieves its saved content.' );
nstarter_update_snapshot_html( 2, '<p>Another page</p>' );
history_expect( nstarter_get_snapshot_html( 1 ) !== nstarter_get_snapshot_html( 2 ), 'Pages using the same design have independent content.' );

$state = nstarter_get_snapshot_history( 1 );
$restored = $state['previous'][2];
$response = history_call( 'nstarter_ajax_restore_snapshot_version', array( 'version_id' => $restored['id'] ) );
history_expect( $response->status === 200 && nstarter_get_snapshot_html( 1 ) === $restored['html'], 'Restoring commits the requested HTML.' );
history_expect( nstarter_get_snapshot_history( 1 )['previous'][0]['html'] === '<p>Save 14</p>', 'The replaced content remains recoverable after restore.' );
history_expect( nstarter_get_snapshot_history( 1 )['current']['id'] !== $restored['id'], 'Restore creates a new version identity.' );
$response = history_call( 'nstarter_ajax_restore_snapshot_version', array( 'version_id' => 'missing' ) );
history_expect( $response->status === 404, 'A missing/pruned version cannot be restored.' );
$response = history_call( 'nstarter_ajax_snapshot_history', array( 'snapshot_token' => 'stale' ) );
history_expect( $response->status === 409, 'Stale editor tabs are rejected.' );
$response = history_call( 'nstarter_ajax_snapshot_history', array( 'source' => 'about-us' ) );
history_expect( $response->status === 409, 'Requests for a previously selected design are rejected.' );
$can_edit = false;
history_expect( history_call( 'nstarter_ajax_restore_snapshot_version' )->status === 403, 'Restore requires permission to edit the page.' );
$can_edit = true;
$nonce_valid = false;
history_expect( history_call( 'nstarter_ajax_snapshot_history' )->status === 403, 'History requires a valid editor nonce.' );
$nonce_valid = true;

$before = nstarter_get_snapshot_html( 1 );
$fail_write = true;
history_expect( ! nstarter_update_snapshot_html( 1, '<p>Failed</p>' ) && nstarter_get_snapshot_html( 1 ) === $before, 'Failed history writes leave the saved HTML intact.' );
$fail_write = false;
$token = nstarter_snapshot_token( 1 );
nstarter_update_snapshot_html( 1, '<p>Concurrent edit</p>' );
history_expect( ! nstarter_update_snapshot_html( 1, '<p>Stale edit</p>', $token ) && nstarter_get_snapshot_html( 1 ) === '<p>Concurrent edit</p>', 'A write also checks its expected version after the endpoint context check.' );
$race = static function ( int $id, string $key ): void {
	$state = json_decode( get_post_meta( $id, $key ), true );
	$state['current']['html'] = '<p>Racing save</p>';
	$state['current']['id'] = 'racing-version';
	$GLOBALS['meta'][ $id ][ $key ] = json_encode( $state );
};
history_expect( ! nstarter_update_snapshot_html( 1, '<p>Losing save</p>' ) && nstarter_get_snapshot_html( 1 ) === '<p>Racing save</p>', 'Compare-and-swap preserves the winning concurrent save.' );

// Exercise the actual editor save, reset and restore endpoints.
$source[1] = 'contact2';
$html = '<h1 id="contact2-title">Client heading</h1><p id="removed">Recover this text</p>';
$response = history_call( 'nstarter_ajax_save_snapshot', array( 'html' => wp_slash( $html ) ) );
history_expect( $response->status === 200 && nstarter_get_snapshot_html( 1 ) === $html, 'The editor save endpoint commits complete HTML.' );
history_expect( $response->data['snapshotToken'] === nstarter_snapshot_token( 1 ), 'Save returns the token needed for subsequent edits.' );
$response = history_call( 'nstarter_ajax_regenerate_snapshot' );
history_expect( $response->status === 200 && str_contains( nstarter_get_snapshot_html( 1 ), 'contact2-main' ), 'Reset renders fresh source HTML.' );
$old_version = nstarter_get_snapshot_history( 1 )['previous'][0];
history_expect( $old_version['html'] === $html, 'Reset preserves the previous client content in History.' );
$response = history_call( 'nstarter_ajax_restore_snapshot_version', array( 'version_id' => $old_version['id'] ) );
$merged = nstarter_render_merged_page_html( 1 );
history_expect( $response->status === 200 && str_contains( $merged['html'], 'contact2-main' ) && str_contains( $merged['html'], '>Client heading</h1>' ), 'Restored content renders within the latest real template.' );
history_expect( nstarter_get_snapshot_html( 1 ) === $html && ! empty( $merged['conflicts'] ), 'Restore preserves original HTML and reports unmatched content in the editor.' );
$before = nstarter_get_snapshot_html( 1 );
$response = history_call( 'nstarter_ajax_save_snapshot', array( 'html' => 'Wrong', 'snapshot_token' => 'stale' ) );
history_expect( $response->status === 409 && nstarter_get_snapshot_html( 1 ) === $before, 'A stale editor save cannot overwrite current content.' );
$response = history_call( 'nstarter_ajax_regenerate_snapshot', array( 'snapshot_token' => 'stale' ) );
history_expect( $response->status === 409 && nstarter_get_snapshot_html( 1 ) === $before, 'A stale editor reset cannot overwrite current content.' );

$source[3] = 'about-us';
update_post_meta( 3, NSTARTER_SNAPSHOT_META_KEY, wp_slash( '<p>Legacy home client copy</p>' ) );
update_post_meta( 3, '_nstarter_snapshot_source', 'home' );
history_expect( nstarter_update_snapshot_html( 3, '<p>New about content</p>' ), 'The first save after a legacy design switch succeeds.' );
$source[3] = 'home';
history_expect( nstarter_get_snapshot_html( 3 ) === '<p>Legacy home client copy</p>', 'Saving a different design does not overwrite the previously unversioned legacy design.' );

echo "Passed $checks snapshot history checks.\n";
