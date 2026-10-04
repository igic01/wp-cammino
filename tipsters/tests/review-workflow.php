<?php
/** Stage 3 integration checks. Run only against an opted-in disposable installation. */
if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) || ! is_file( $argv[1] ) ) { exit( 1 ); }
ob_start(); $_SERVER['HTTP_HOST'] = '127.0.0.1:8765'; $_SERVER['REQUEST_METHOD'] = 'GET'; require $argv[1];
if ( ! defined( 'CAMMINO_TIPSTERS_TEST_INSTALLATION' ) || true !== CAMMINO_TIPSTERS_TEST_INSTALLATION || ! defined( 'CAMMINO_TIPSTERS_STORAGE_PATH' ) ) { fwrite( STDERR, "Disposable test installation required.\n" ); exit( 1 ); }
require_once ABSPATH . 'wp-admin/includes/user.php'; add_filter( 'pre_wp_mail', '__return_true' );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
$prefix = 'review_test_' . bin2hex( random_bytes( 5 ) ); $users = $paths = array(); $checks = 0; $failure = null;
function expect_review( bool $ok, string $message ): void { global $checks; ++$checks; if ( ! $ok ) { throw new RuntimeException( $message ); } }
function review_version( int $id ): string { return cammino_tipsters_tip_record( $id )['version']; }
function review_tip( int $owner, string $status = 'submitted' ): int {
	$id = wp_insert_post( array( 'post_type' => CAMMINO_TIP_POST_TYPE, 'post_status' => 'private', 'post_author' => $owner, 'post_title' => 'Legacy private tip', 'post_excerpt' => 'Short', 'post_content' => 'Long' ) );
	update_post_meta( $id, '_cammino_tip_status', $status ); return $id;
}
try {
	wp_set_current_user( $admin->ID );
	$one = cammino_tipsters_create_account( $prefix, 'Abcd123!' ); $users[] = $one;
	$two = cammino_tipsters_create_account( $prefix . '_two', 'Abcd123!' ); $users[] = $two;
	$input = array( 'title' => 'Updated title', 'short_description' => "Short\nSecond line", 'long_description' => 'Long \\ path and "quotes".' );
	$statuses = array_keys( cammino_tipsters_status_labels() );
	foreach ( $statuses as $from ) {
		foreach ( array_merge( $statuses, array( 'building', 'invalid' ) ) as $to ) {
			$id = review_tip( $one, $from );
			$result = cammino_tipsters_change_status( $id, $to, review_version( $id ), true );
			$allowed = in_array( $to, cammino_tipsters_tip_transitions( $from ), true );
			expect_review( $allowed ? true === $result && $to === cammino_tipsters_tip_status( $id ) : is_wp_error( $result ) && $from === cammino_tipsters_tip_status( $id ), 'Transition matrix: ' . $from . ' -> ' . $to );
		}
	}
	$id = review_tip( $one ); $other = review_tip( $two ); $initial = review_version( $id );
	expect_review( ! metadata_exists( 'post', $id, CAMMINO_TIP_WORKFLOW_META ) && cammino_tipsters_tip_ready( $id ), 'Legacy Stage 2 tips work without GET migration.' );
	wp_set_current_user( $one );
	expect_review( is_wp_error( cammino_tipsters_change_status( $id, 'discussion', $initial ) ), 'Tipster cannot change review status.' );
	expect_review( is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), $initial ) ), 'Submitted tip rejects edits.' );
	expect_review( is_wp_error( cammino_tipsters_delete_tip( $id, $initial ) ), 'Deletion requires explicit confirmation.' );
	wp_set_current_user( $two );
	expect_review( is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), $initial ) ) && is_wp_error( cammino_tipsters_delete_tip( $id, $initial, true ) ), 'Other owner cannot edit or delete.' );
	wp_set_current_user( 0 ); expect_review( is_wp_error( cammino_tipsters_change_status( $id, 'discussion', $initial ) ) && is_wp_error( cammino_tipsters_delete_tip( $id, $initial, true ) ), 'Anonymous actions denied.' );
	wp_set_current_user( $admin->ID );
	expect_review( true === cammino_tipsters_change_status( $id, 'discussion', $initial ), 'Admin opens discussion.' );
	$discussion = review_version( $id ); $record = cammino_tipsters_tip_record( $id );
	expect_review( $discussion !== $initial && 1 === count( $record['history'] ) && (int) $admin->ID === $record['history'][0]['actor'] && 'submitted' === $record['history'][0]['from'], 'Version and actor/status history saved together.' );
	expect_review( is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), $discussion ) ), 'Admin cannot override tip content.' );
	wp_set_current_user( $one );
	expect_review( is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), $initial ) ), 'Stale pre-discussion form rejected.' );
	expect_review( true === cammino_tipsters_edit_tip( $id, $input, array(), array(), $discussion ), 'Owner edits during discussion.' );
	$edited = review_version( $id ); $record = cammino_tipsters_tip_record( $id );
	expect_review( 'discussion' === $record['status'] && $input['long_description'] === $record['long_description'] && $input['long_description'] === get_post( $id )->post_content, 'Editing preserves discussion, quotes, and backslashes.' );
	expect_review( 2 === count( $record['history'] ) && 'edited' === $record['history'][1]['type'] && $one === $record['history'][1]['actor'], 'Content update history includes actor and time.' );
	expect_review( is_wp_error( cammino_tipsters_edit_tip( $id, array_merge( $input, array( 'title' => 'Stale overwrite' ) ), array(), array(), $discussion ) ), 'Second tab cannot overwrite a newer edit.' );
	expect_review( true === cammino_tipsters_edit_tip( $id, $input, array(), array(), $edited ) && $edited === review_version( $id ), 'Unchanged save does not duplicate history.' );
	wp_set_current_user( $admin->ID );
	expect_review( is_wp_error( cammino_tipsters_change_status( $id, 'approved', $initial ) ) && 'discussion' === cammino_tipsters_tip_status( $id ), 'Stale administrator form cannot change a newer revision.' );
	$fail_commit = static function ( $check, $post_id, $key, $value ) { return CAMMINO_TIP_WORKFLOW_META === $key ? false : $check; };
	add_filter( 'update_post_metadata', $fail_commit, 10, 4 );
	try { expect_review( is_wp_error( cammino_tipsters_change_status( $id, 'approved', $edited ) ), 'Failed aggregate save is reported.' ); }
	finally { remove_filter( 'update_post_metadata', $fail_commit, 10 ); }
	expect_review( 'discussion' === cammino_tipsters_tip_status( $id ) && 'discussion' === get_post_meta( $id, '_cammino_tip_status', true ) && $edited === review_version( $id ) && 2 === count( cammino_tipsters_tip_record( $id )['history'] ), 'Failed status change rolls back mirrors and retains authoritative history.' );
	expect_review( true === cammino_tipsters_change_status( $id, 'approved', $edited ), 'Admin approves updated tip.' );
	$approved = review_version( $id ); wp_set_current_user( $one );
	expect_review( ! cammino_tipsters_can_edit_tip( $id ) && is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), $edited ) ) && is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), $approved ) ), 'Approval blocks old and freshly forged edit forms.' );
	wp_set_current_user( $admin->ID );
	expect_review( is_wp_error( cammino_tipsters_change_status( $id, 'discussion', $approved ) ) && 'approved' === cammino_tipsters_tip_status( $id ), 'Reopening requires explicit confirmation.' );
	expect_review( true === cammino_tipsters_change_status( $id, 'discussion', $approved, true ), 'Confirmed reopen works.' );
	wp_set_current_user( $one ); expect_review( is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), $edited ) ), 'Approval/reopen cycle invalidates earlier discussion forms.' );
	$root = cammino_tipsters_storage_root(); $file_id = bin2hex( random_bytes( 16 ) ); $relative = $prefix . '.pdf'; $paths[] = $root . $relative;
	file_put_contents( $root . $relative, '%PDF-1.4 disposable' );
	$file_tip = review_tip( $one, 'discussion' );
	$file = array( 'id' => $file_id, 'path' => $relative, 'name' => 'old-report.pdf', 'size' => filesize( $root . $relative ), 'mime' => 'application/pdf' );
	update_post_meta( $file_tip, CAMMINO_TIP_FILES_META, array( $file ) );
	expect_review( true === cammino_tipsters_edit_tip( $file_tip, $input, array(), array( $file_id ), review_version( $file_tip ) ), 'Owner can remove an active attachment during discussion.' );
	$record = cammino_tipsters_tip_record( $file_tip );
	expect_review( ! $record['files'] && 1 === count( $record['retained_files'] ) && file_exists( $root . $relative ) && is_wp_error( cammino_tipsters_tip_file( $file_tip, $file_id ) ), 'Removed attachment remains on disk and is inaccessible to owner.' );
	expect_review( is_wp_error( cammino_tipsters_edit_tip( $file_tip, $input, array(), array( bin2hex( random_bytes( 16 ) ) ), review_version( $file_tip ) ) ), 'Foreign removal ID rejected.' );
	wp_set_current_user( $admin->ID ); expect_review( ! is_wp_error( cammino_tipsters_tip_file( $file_tip, $file_id ) ), 'Admin can download retained attachment.' );
	$lock = cammino_tipsters_write_lock( $one );
	expect_review( is_wp_error( cammino_tipsters_change_status( $id, 'approved', review_version( $id ) ) ), 'Admin change cannot race an account operation.' );
	cammino_tipsters_release_lock( $lock );
	cammino_tipsters_set_enabled( $one, false ); wp_set_current_user( $one );
	expect_review( is_wp_error( cammino_tipsters_edit_tip( $id, $input, array(), array(), review_version( $id ) ) ) && is_wp_error( cammino_tipsters_delete_tip( $id, review_version( $id ), true ) ), 'Disabled account cannot edit or delete.' );
	wp_set_current_user( $admin->ID ); expect_review( cammino_tipsters_can_read_tip( $id ) && true === cammino_tipsters_change_status( $id, 'approved', review_version( $id ) ), 'Admin can review a disabled owner tip.' );
	cammino_tipsters_set_enabled( $one, true );
	foreach ( array( 'submitted', 'discussion', 'approved' ) as $status ) {
		$deleted_tip = review_tip( $one, $status ); $version = review_version( $deleted_tip ); wp_set_current_user( $one );
		expect_review( true === cammino_tipsters_delete_tip( $deleted_tip, $version, true ), 'Owner deletion allowed in ' . $status );
		expect_review( ! cammino_tipsters_can_read_tip( $deleted_tip ) && get_post( $deleted_tip ), 'Deleted tip hidden from owner but retained.' );
		wp_set_current_user( $admin->ID ); $record = cammino_tipsters_tip_record( $deleted_tip );
		expect_review( cammino_tipsters_can_read_tip( $deleted_tip ) && $status === $record['deleted']['previous_status'] && $one === $record['deleted']['actor'] && ! cammino_tipsters_tip_transitions( $record['status'] ), 'Admin retains deletion actor, time, and previous state.' );
	}
	expect_review( cammino_tipsters_admin_tips_query( 'deleted_by_tipster', '', $one )->found_posts >= 3, 'Admin deleted filter includes retained tips.' );
	expect_review( cammino_tipsters_admin_tips_query( '', 'Updated title', $one )->found_posts > 0, 'Admin search and owner filter find edited text.' );
	$first_page = cammino_tipsters_admin_tips_query( '', '', $one, 1 ); $second_page = cammino_tipsters_admin_tips_query( '', '', $one, 2 );
	expect_review( 20 === count( $first_page->posts ) && count( $second_page->posts ) > 0 && ! array_intersect( wp_list_pluck( $first_page->posts, 'ID' ), wp_list_pluck( $second_page->posts, 'ID' ) ), 'Administrator pagination separates pages without losing or repeating records.' );
	wp_set_current_user( $two ); expect_review( ! cammino_tipsters_admin_tips_query()->posts, 'Tipster cannot query administrator list.' );
	wp_set_current_user( $admin->ID );
	$pending_path = $prefix . '-pending.pdf'; file_put_contents( $root . $pending_path, 'Interrupted upload' ); $paths[] = $root . $pending_path;
	update_post_meta( $file_tip, CAMMINO_TIP_PENDING_FILES_META, array( array( 'path' => $pending_path ) ) );
	$message = wp_insert_post( array( 'post_type' => CAMMINO_MESSAGE_POST_TYPE, 'post_status' => 'private', 'post_author' => $admin->ID, 'post_content' => 'Retained reply fixture' ) ); update_post_meta( $message, CAMMINO_MESSAGE_TIP_META, $file_tip );
	expect_review( 2 === count( cammino_tipsters_account_records( $one )['files'] ), 'Purge includes retained and interrupted file paths once each.' );
	expect_review( true === cammino_tipsters_delete_account( $one ) && ! get_post( $file_tip ) && ! get_post( $message ) && ! file_exists( $root . $relative ) && ! file_exists( $root . $pending_path ), 'Account purge removes workflow records, retained/pending files, and admin replies.' );
	expect_review( get_post( $other ) && get_userdata( $two ), 'Other account preserved.' );
} catch ( Throwable $error ) { $failure = $error; }
finally {
	if ( isset( $lock ) && is_array( $lock ) ) { cammino_tipsters_release_lock( $lock ); }
	wp_set_current_user( $admin->ID ); foreach ( $users as $uid ) { if ( get_userdata( $uid ) ) { cammino_tipsters_delete_account( $uid ); } }
	foreach ( $paths as $path ) { if ( is_file( $path ) ) { unlink( $path ); } }
}
$output = ob_get_clean(); if ( $output ) { fwrite( STDERR, $output ); }
if ( $failure ) { fwrite( STDERR, 'FAIL: ' . $failure->getMessage() . "\n" ); exit( 1 ); }
echo "Passed $checks WordPress tip review checks.\n";
