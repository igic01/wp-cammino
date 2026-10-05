<?php
/** Stage 5 rejection and isolated tip purge checks; disposable installation only. */
if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) || ! is_file( $argv[1] ) ) { exit( 1 ); }
ob_start(); $_SERVER['HTTP_HOST'] = '127.0.0.1:8765'; $_SERVER['REQUEST_METHOD'] = 'GET'; require $argv[1];
if ( ! defined( 'CAMMINO_TIPSTERS_TEST_INSTALLATION' ) || true !== CAMMINO_TIPSTERS_TEST_INSTALLATION ) { exit( 1 ); }
require_once ABSPATH . 'wp-admin/includes/user.php'; add_filter( 'pre_wp_mail', '__return_true' );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]; $users = $paths = array(); $checks = 0; $failure = null;
function final_expect( bool $ok, string $label ): void { global $checks; ++$checks; if ( ! $ok ) { throw new RuntimeException( $label ); } }
function final_version( int $id ): string { return cammino_tipsters_tip_record( $id )['version']; }
try {
	wp_set_current_user( $admin->ID );
	$owner = cammino_tipsters_create_account( 'final_test_' . bin2hex( random_bytes( 5 ) ), 'Abcd123!' ); $users[] = $owner;
	wp_set_current_user( $owner ); $input = array( 'title' => 'Final purge test', 'short_description' => 'Short', 'long_description' => 'Long', 'file_link' => 'https://example.com/shared' );
	$id = cammino_tipsters_create_tip( $input, array(), cammino_tipsters_submission_token() );
	$other = cammino_tipsters_create_tip( array_merge( $input, array( 'title' => 'Preserve another tip' ) ), array(), cammino_tipsters_submission_token() );
	final_expect( is_wp_error( cammino_tipsters_admin_delete_tip( $id, final_version( $id ), $input['title'], true ) ), 'Owner cannot permanently delete a tip.' );
	wp_set_current_user( $admin->ID ); cammino_tipsters_change_status( $id, 'discussion', final_version( $id ) );
	$reply = cammino_tipsters_send_message( $id, 'Admin reply retained through rejection', cammino_tipsters_message_token( $id ) );
	$before = final_version( $id );
	final_expect( true === cammino_tipsters_change_status( $id, 'rejected', $before ), 'Admin can reject discussion.' );
	final_expect( 'Zamietnutý' === cammino_tipsters_status_labels()['rejected'], 'Rejected has its Slovak label.' );
	final_expect( ! cammino_tipsters_can_message( $id ) && is_wp_error( cammino_tipsters_send_message( $id, 'Admin locked', cammino_tipsters_message_token( $id ) ) ), 'Rejected conversation is closed to admin.' );
	wp_set_current_user( $owner );
	final_expect( cammino_tipsters_can_read_tip( $id ) && ! cammino_tipsters_can_edit_tip( $id ) && ! cammino_tipsters_can_message( $id ), 'Rejected owner can read but cannot edit/chat.' );
	final_expect( is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), $before ) ) && is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), final_version( $id ) ) ), 'Rejected rejects stale and freshly forged edit forms.' );
	final_expect( 1 === (int) cammino_tipsters_messages( $id )->found_posts && in_array( $id, wp_list_pluck( cammino_tipsters_own_tips()->posts, 'ID' ), true ), 'Rejected tip remains listed with readable history.' );
	final_expect( is_wp_error( cammino_tipsters_send_message( $id, 'Owner locked', cammino_tipsters_message_token( $id ) ) ), 'Rejected owner write rejected by service.' );
	wp_set_current_user( $admin->ID );
	final_expect( is_wp_error( cammino_tipsters_change_status( $id, 'discussion', final_version( $id ) ) ), 'Rejected reopen requires confirmation.' );
	final_expect( true === cammino_tipsters_change_status( $id, 'discussion', final_version( $id ), true ) && cammino_tipsters_can_message( $id ), 'Confirmed reopen restores communication.' );
	$version = final_version( $id );
	final_expect( is_wp_error( cammino_tipsters_admin_delete_tip( $id, $version, $input['title'] ) ) && is_wp_error( cammino_tipsters_admin_delete_tip( $id, $version, 'Wrong title', true ) ) && get_post( $reply ), 'No purge without checkbox and matching title.' );
	final_expect( is_wp_error( cammino_tipsters_admin_delete_tip( $id, $before, $input['title'], true ) ), 'Stale deletion form cannot purge a newer tip.' );
	$lock = cammino_tipsters_write_lock( $owner );
	final_expect( is_wp_error( cammino_tipsters_admin_delete_tip( $id, $version, $input['title'], true ) ), 'Purge serializes with account writes.' );
	cammino_tipsters_release_lock( $lock ); unset( $lock );
	// Disposable fixtures simulate retained and interrupted Stage 2 uploads.
	$root = trailingslashit( CAMMINO_TIPSTERS_STORAGE_PATH ); $record = cammino_tipsters_tip_record( $id );
	foreach ( array( 'files', 'retained_files', 'pending' ) as $kind ) {
		$name = 'final-' . bin2hex( random_bytes( 8 ) ) . '.pdf'; $paths[] = $root . $name; file_put_contents( $root . $name, '%PDF disposable' );
		$file = array( 'id' => bin2hex( random_bytes( 16 ) ), 'path' => $name, 'name' => $kind . '.pdf', 'size' => 15, 'mime' => 'application/pdf' );
		if ( 'pending' === $kind ) { update_post_meta( $id, CAMMINO_TIP_PENDING_FILES_META, array( $file ) ); } else { $record[ $kind ] = array( $file ); }
	}
	update_post_meta( $id, CAMMINO_TIP_WORKFLOW_META, $record );
	// Block final row deletion to verify resumability after earlier cleanup succeeds.
	$fail = static function ( $result, $post ) use ( $id ) { return (int) $post->ID === $id ? false : $result; };
	add_filter( 'pre_delete_post', $fail, 99, 2 );
	try { $result = cammino_tipsters_admin_delete_tip( $id, $version, $input['title'], true ); }
	finally { remove_filter( 'pre_delete_post', $fail, 99 ); }
	final_expect( is_wp_error( $result ) && get_post_meta( $id, '_cammino_tip_purging', true ) && get_post( $id ), 'Interrupted purge keeps a retryable locked tip.' );
	wp_set_current_user( $owner );
	final_expect( ! cammino_tipsters_can_read_tip( $id ) && ! cammino_tipsters_can_message( $id ) && ! in_array( $id, wp_list_pluck( cammino_tipsters_own_tips()->posts, 'ID' ), true ), 'Interrupted purge revokes only this tip access.' );
	final_expect( cammino_tipsters_can_read_tip( $other ) && cammino_tipsters_account_enabled( $owner ), 'Account and other tip remain active during interrupted purge.' );
	wp_set_current_user( $admin->ID );
	final_expect( is_wp_error( cammino_tipsters_change_status( $id, 'approved', $version ) ), 'Interrupted purge cannot accept further status changes.' );
	final_expect( true === cammino_tipsters_admin_delete_tip( $id, $version, $input['title'], true ) && ! get_post( $id ) && ! get_post( $reply ), 'Retry finishes tip/message purge.' );
	final_expect( ! array_filter( $paths, 'file_exists' ) && get_post( $other ) && get_userdata( $owner ), 'All retained/pending files removed; unrelated records preserved.' );
	// A malformed legacy path must block cleanup without erasing the conversation.
	$unsafe = wp_insert_post( array( 'post_type' => CAMMINO_TIP_POST_TYPE, 'post_status' => 'private', 'post_author' => $owner, 'post_title' => 'Unsafe fixture' ) );
	update_post_meta( $unsafe, '_cammino_tip_status', 'discussion' ); update_post_meta( $unsafe, CAMMINO_TIP_FILES_META, array( array( 'path' => '../outside.pdf' ) ) );
	$unsafe_reply = cammino_tipsters_send_message( $unsafe, 'Preserve on validation failure', cammino_tipsters_message_token( $unsafe ) );
	final_expect( is_wp_error( cammino_tipsters_admin_delete_tip( $unsafe, final_version( $unsafe ), 'Unsafe fixture', true ) ) && get_post( $unsafe_reply ), 'Unsafe file path blocks deletion before erasing messages.' );
	delete_post_meta( $unsafe, CAMMINO_TIP_FILES_META );
	final_expect( true === cammino_tipsters_admin_delete_tip( $unsafe, final_version( $unsafe ), 'Unsafe fixture', true ), 'Corrected cleanup can be retried.' );
} catch ( Throwable $error ) { $failure = $error; }
finally {
	if ( isset( $lock ) ) { cammino_tipsters_release_lock( $lock ); }
	wp_set_current_user( $admin->ID ); foreach ( $users as $user ) { cammino_tipsters_delete_account( $user ); }
	foreach ( $paths as $path ) { if ( is_file( $path ) ) { unlink( $path ); } }
}
$output = ob_get_clean(); if ( $output ) { fwrite( STDERR, $output ); }
if ( $failure ) { fwrite( STDERR, 'FAIL: ' . $failure->getMessage() . "\n" ); exit( 1 ); }
echo "Passed $checks final rejection/tip-purge WordPress checks.\n";
