<?php
/** Stage 4 integration checks against an opted-in disposable WordPress installation. */
if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) || ! is_file( $argv[1] ) ) { exit( 1 ); }
ob_start(); $_SERVER['HTTP_HOST'] = '127.0.0.1:8765'; $_SERVER['REQUEST_METHOD'] = 'GET'; require $argv[1];
if ( ! defined( 'CAMMINO_TIPSTERS_TEST_INSTALLATION' ) || true !== CAMMINO_TIPSTERS_TEST_INSTALLATION ) { exit( 1 ); }
require_once ABSPATH . 'wp-admin/includes/user.php'; add_filter( 'pre_wp_mail', '__return_true' );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
$prefix = 'messages_test_' . bin2hex( random_bytes( 5 ) ); $users = array(); $checks = 0; $failure = null;
function expect_message( bool $ok, string $label ): void { global $checks; ++$checks; if ( ! $ok ) { throw new RuntimeException( $label ); } }
function message_version( int $id ): string { return cammino_tipsters_tip_record( $id )['version']; }
try {
	wp_set_current_user( $admin->ID );
	$one = cammino_tipsters_create_account( $prefix, 'Abcd123!' ); $users[] = $one;
	$two = cammino_tipsters_create_account( $prefix . '_two', 'Abcd123!' ); $users[] = $two;
	$input = array( 'title' => 'Private message test', 'short_description' => 'Short', 'long_description' => 'Long', 'file_link' => 'https://drive.google.com/drive/folders/test?usp=sharing&key=abc' );
	wp_set_current_user( $one ); $id = cammino_tipsters_create_tip( $input, array(), cammino_tipsters_submission_token() );
	expect_message( is_int( $id ) && cammino_tipsters_tip_record( $id )['file_link'] === $input['file_link'], 'Shared link persists with its query string.' );
	foreach ( array( 'javascript:alert(1)', 'data:text/html,test', 'ftp://example.com/test', 'https://user:pass@example.com/', 'https://example.com/"bad', 'https://example.com/a b', 'https://example.com/\path', 'https:///broken', array(), 'https://example.com/' . str_repeat( 'a', 2048 ) ) as $bad ) {
		expect_message( is_wp_error( cammino_tipsters_validate_tip( array_merge( $input, array( 'file_link' => $bad ) ) ) ), 'Unsafe or malformed link rejected.' );
	}
	expect_message( ! is_wp_error( cammino_tipsters_validate_tip( array_merge( $input, array( 'file_link' => '' ) ) ) ), 'Link is optional.' );
	expect_message( is_wp_error( cammino_tipsters_send_message( $id, 'Submitted blocked', cammino_tipsters_message_token( $id ) ) ), 'Submitted owner messages closed.' );
	wp_set_current_user( $admin->ID );
	expect_message( is_wp_error( cammino_tipsters_send_message( $id, 'Admin submitted blocked', cammino_tipsters_message_token( $id ) ) ), 'Submitted admin messages closed.' );
	cammino_tipsters_change_status( $id, 'discussion', message_version( $id ) );
	$admin_token = cammino_tipsters_message_token( $id );
	$reply = cammino_tipsters_send_message( $id, "Admin reply\nLine two \\ path", $admin_token );
	expect_message( is_int( $reply ) && get_post( $reply )->post_content === "Admin reply\nLine two \\ path" && (int) get_post( $reply )->post_parent === $id && (int) get_post( $reply )->post_author === (int) $admin->ID, 'Admin message stores body, linkage, sender and time.' );
	expect_message( $reply === cammino_tipsters_send_message( $id, 'Retry different text', $admin_token ) && 1 === (int) cammino_tipsters_messages( $id )->found_posts, 'Retry cannot duplicate or change a saved message.' );
	wp_set_current_user( $one );
	expect_message( is_wp_error( cammino_tipsters_send_message( $id, 'Forged sender', $admin_token ) ), 'Message token cannot cross senders.' );
	$owner_token = cammino_tipsters_message_token( $id );
	$owner_reply = cammino_tipsters_send_message( $id, 'Owner reply <b>plain</b>', $owner_token );
	expect_message( is_int( $owner_reply ) && 'Owner reply plain' === get_post( $owner_reply )->post_content, 'Owner reply saved as plain text.' );
	$corrupt = static function ( $data ) { if ( CAMMINO_MESSAGE_POST_TYPE === $data['post_type'] ) { $data['post_content'] = 'Corrupted write'; } return $data; };
	add_filter( 'wp_insert_post_data', $corrupt, 99 );
	try { $failed = cammino_tipsters_send_message( $id, 'Must persist exactly', cammino_tipsters_message_token( $id ) ); }
	finally { remove_filter( 'wp_insert_post_data', $corrupt, 99 ); }
	expect_message( is_wp_error( $failed ) && 2 === (int) cammino_tipsters_messages( $id )->found_posts, 'Failed message verification reports error and cleans its incomplete record.' );
	$filters_before = array( has_filter( 'title_save_pre', 'wp_filter_kses' ), has_filter( 'content_save_pre', 'wp_filter_post_kses' ), has_filter( 'excerpt_save_pre', 'wp_filter_post_kses' ) );
	$amp_tip = cammino_tipsters_create_tip( array_merge( $input, array( 'title' => 'Title & plain', 'short_description' => 'Short & plain', 'long_description' => 'Long & plain' ) ), array(), cammino_tipsters_submission_token() );
	expect_message( is_int( $amp_tip ) && 'Title & plain' === get_post( $amp_tip )->post_title && 'Short & plain' === get_post( $amp_tip )->post_excerpt && 'Long & plain' === get_post( $amp_tip )->post_content, 'Plain submission preserves literal ampersands.' );
	expect_message( $filters_before === array( has_filter( 'title_save_pre', 'wp_filter_kses' ), has_filter( 'content_save_pre', 'wp_filter_post_kses' ), has_filter( 'excerpt_save_pre', 'wp_filter_post_kses' ) ), 'Core HTML filters restored after private plain-text write.' );
	expect_message( is_wp_error( cammino_tipsters_send_message( $id, '  ', cammino_tipsters_message_token( $id ) ) ) && is_wp_error( cammino_tipsters_send_message( $id, str_repeat( 'x', 601 ), cammino_tipsters_message_token( $id ) ) ), 'Empty and 601-character messages rejected.' );
	wp_set_current_user( $admin->ID ); cammino_tipsters_change_status( $amp_tip, 'discussion', message_version( $amp_tip ) );
	foreach ( array( $one, $admin->ID ) as $actor ) {
		wp_set_current_user( $actor );
		$boundary = str_repeat( 'é', 600 );
		$boundary_id = cammino_tipsters_send_message( $amp_tip, $boundary, cammino_tipsters_message_token( $amp_tip ) );
		expect_message( is_int( $boundary_id ) && get_post( $boundary_id )->post_content === $boundary, 'Both parties can persist exactly 600 Unicode characters.' );
		expect_message( is_wp_error( cammino_tipsters_send_message( $amp_tip, $boundary . 'é', cammino_tipsters_message_token( $amp_tip ) ) ), 'Both parties reject 601 Unicode characters.' );
	}
	wp_set_current_user( $one );
	$another = cammino_tipsters_create_tip( $input, array(), cammino_tipsters_submission_token() );
	wp_set_current_user( $admin->ID ); cammino_tipsters_change_status( $another, 'discussion', message_version( $another ) );
	wp_set_current_user( $one );
	expect_message( is_wp_error( cammino_tipsters_send_message( $another, 'Cross tip', $owner_token ) ) && ! cammino_tipsters_messages( $another )->posts, 'Tip-bound tokens and conversation isolation.' );
	$changed = array_merge( $input, array( 'file_link' => 'https://example.com/shared-folder' ) );
	expect_message( true === cammino_tipsters_edit_tip( $id, $changed, array(), array(), message_version( $id ) ) && cammino_tipsters_tip_record( $id )['file_link'] === $changed['file_link'], 'Link editable during discussion.' );
	$old_version = message_version( $id );
	expect_message( is_wp_error( cammino_tipsters_edit_tip( $id, $input, array( 'name' => array( 'test.pdf' ), 'error' => array( UPLOAD_ERR_OK ), 'tmp_name' => array( __FILE__ ), 'size' => array( 10 ), 'type' => array( 'application/pdf' ) ), array(), $old_version ) ) && $old_version === message_version( $id ), 'Crafted upload cannot update a tip.' );
	wp_set_current_user( $two );
	expect_message( ! cammino_tipsters_messages( $id )->posts && is_wp_error( cammino_tipsters_send_message( $id, 'Other owner', cammino_tipsters_message_token( $id ) ) ), 'Other account cannot read or send.' );
	wp_set_current_user( 0 ); expect_message( ! cammino_tipsters_messages( $id )->posts && ! cammino_tipsters_can_message( $id ), 'Anonymous conversation access denied.' );
	wp_set_current_user( $admin->ID ); cammino_tipsters_change_status( $id, 'approved', message_version( $id ) );
	wp_set_current_user( $one );
	expect_message( ! cammino_tipsters_can_edit_tip( $id ) && is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), message_version( $id ) ) ) && is_int( cammino_tipsters_send_message( $id, 'Approved still open', cammino_tipsters_message_token( $id ) ) ), 'Approved locks form while preserving messaging.' );
	foreach ( array( $one, $admin->ID ) as $actor ) {
		wp_set_current_user( $actor );
		wp_update_post( array( 'ID' => $owner_reply, 'post_content' => 'Tamper', 'post_parent' => $another, 'post_author' => $two, 'post_status' => 'publish', 'post_title' => 'Spoof' ) );
		$saved = get_post( $owner_reply );
		expect_message( 'Owner reply plain' === $saved->post_content && (int) $saved->post_parent === $id && (int) $saved->post_author === $one && 'private' === $saved->post_status && $saved->post_title === $prefix, 'Core update cannot mutate message fields.' );
		expect_message( false === wp_trash_post( $owner_reply ) && false === wp_delete_post( $owner_reply, true ) && get_post( $owner_reply ), 'Individual trash/delete blocked for both parties.' );
		expect_message( ! current_user_can( 'edit_post', $owner_reply ) && ! current_user_can( 'delete_post', $owner_reply ), 'Native message editor capabilities denied.' );
		expect_message( false === update_post_meta( $owner_reply, CAMMINO_MESSAGE_TIP_META, $another ) && false === delete_post_meta( $owner_reply, CAMMINO_MESSAGE_TIP_META ) && (int) get_post_meta( $owner_reply, CAMMINO_MESSAGE_TIP_META, true ) === $id, 'Saved metadata linkage cannot be updated or deleted.' );
	}
	wp_set_current_user( $admin->ID );
	$lock = cammino_tipsters_write_lock( $one );
	expect_message( is_wp_error( cammino_tipsters_send_message( $id, 'Race', cammino_tipsters_message_token( $id ) ) ), 'Posting serializes with account and status operations.' );
	cammino_tipsters_release_lock( $lock ); unset( $lock );
	cammino_tipsters_set_enabled( $one, false );
	expect_message( 3 === (int) cammino_tipsters_messages( $id )->found_posts && ! cammino_tipsters_can_message( $id ), 'Disabled owner preserves admin history and closes posting.' );
	wp_set_current_user( $one ); expect_message( ! cammino_tipsters_messages( $id )->posts, 'Disabled owner cannot read history.' );
	wp_set_current_user( $admin->ID ); cammino_tipsters_set_enabled( $one, true );
	cammino_tipsters_change_status( $id, 'discussion', message_version( $id ), true );
	wp_set_current_user( $one );
	for ( $i = 0; $i < 21; ++$i ) { cammino_tipsters_send_message( $id, 'Page message ' . $i, cammino_tipsters_message_token( $id ) ); }
	$first = cammino_tipsters_messages( $id ); $second = cammino_tipsters_messages( $id, 2 );
	expect_message( 20 === count( $first->posts ) && 4 === count( $second->posts ) && ! array_intersect( wp_list_pluck( $first->posts, 'ID' ), wp_list_pluck( $second->posts, 'ID' ) ) && $reply === (int) $first->posts[0]->ID, 'Chronological pagination preserves every message once.' );
	cammino_tipsters_delete_tip( $id, message_version( $id ), true );
	expect_message( ! cammino_tipsters_messages( $id )->posts && ! cammino_tipsters_can_message( $id ), 'Owner-deleted tip closes owner conversation access.' );
	wp_set_current_user( $admin->ID );
	expect_message( 24 === (int) cammino_tipsters_messages( $id )->found_posts && ! cammino_tipsters_can_message( $id ), 'Deleted-tip history remains readable by admin without posting.' );
	// Purge must still find admin messages if the optional legacy metadata mirror is missing.
	global $wpdb;
	$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $reply, 'meta_key' => CAMMINO_MESSAGE_TIP_META ) ); wp_cache_delete( $reply, 'post_meta' );
	expect_message( in_array( $reply, cammino_tipsters_account_records( $one )['messages'], true ), 'Purge uses authoritative parent linkage.' );
	$draft_key = 'cammino_message_draft_' . $admin->ID . '_' . $id;
	add_post_meta( $id, '_cammino_message_draft_user', $admin->ID ); set_transient( $draft_key, array( 'body' => 'Rejected draft' ), 300 );
	expect_message( true === cammino_tipsters_delete_account( $one ) && ! get_post( $reply ) && ! get_post( $owner_reply ) && ! get_post( $id ), 'Account purge removes both senders and soft-deleted tips.' );
	expect_message( false === get_transient( $draft_key ), 'Account purge removes temporary admin message drafts.' );
} catch ( Throwable $error ) { $failure = $error; }
finally {
	if ( isset( $lock ) && is_array( $lock ) ) { cammino_tipsters_release_lock( $lock ); }
	wp_set_current_user( $admin->ID ); foreach ( $users as $uid ) { if ( get_userdata( $uid ) ) { cammino_tipsters_delete_account( $uid ); } }
}
$output = ob_get_clean(); if ( $output ) { fwrite( STDERR, $output ); }
if ( $failure ) { fwrite( STDERR, 'FAIL: ' . $failure->getMessage() . "\n" ); exit( 1 ); }
echo "Passed $checks WordPress conversation/link checks.\n";
