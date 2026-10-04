<?php
/** Bounded incremental query checks; disposable opted-in WordPress installation only. */
if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) || ! is_file( $argv[1] ) ) { exit( 1 ); }
ob_start(); $_SERVER['HTTP_HOST'] = '127.0.0.1:8765'; $_SERVER['REQUEST_METHOD'] = 'GET'; require $argv[1];
if ( ! defined( 'CAMMINO_TIPSTERS_TEST_INSTALLATION' ) || true !== CAMMINO_TIPSTERS_TEST_INSTALLATION ) { exit( 1 ); }
require_once ABSPATH . 'wp-admin/includes/user.php'; add_filter( 'pre_wp_mail', '__return_true' );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]; $users = array(); $checks = 0; $failure = null;
function expect_live( bool $ok, string $message ): void { global $checks; ++$checks; if ( ! $ok ) { throw new RuntimeException( $message ); } }
try {
	wp_set_current_user( $admin->ID );
	$owner = cammino_tipsters_create_account( 'live_test_' . bin2hex( random_bytes( 5 ) ), 'Abcd123!' ); $users[] = $owner;
	wp_set_current_user( $owner );
	$id = cammino_tipsters_create_tip( array( 'title' => 'Live backlog', 'short_description' => 'Short', 'long_description' => 'Long' ), array(), cammino_tipsters_submission_token() );
	wp_set_current_user( $admin->ID ); cammino_tipsters_change_status( $id, 'discussion', cammino_tipsters_tip_record( $id )['version'] );
	$ids = array();
	for ( $i = 0; $i < 55; ++$i ) { $ids[] = cammino_tipsters_send_message( $id, 'Backlog ' . $i, cammino_tipsters_message_token( $id ) ); }
	$first = cammino_tipsters_live_message_data( $id, 0 );
	expect_live( count( $first['messages'] ) === 50 && $first['more'] && $first['cursor'] === $ids[49], 'First backlog response is bounded to fifty messages.' );
	$second = cammino_tipsters_live_message_data( $id, $first['cursor'] );
	expect_live( count( $second['messages'] ) === 5 && ! $second['more'] && $second['cursor'] === $ids[54], 'Remaining backlog delivered without skipping IDs.' );
	expect_live( array_merge( array_column( $first['messages'], 'id' ), array_column( $second['messages'], 'id' ) ) === $ids, 'Cursor order delivers each message exactly once.' );
	$empty = cammino_tipsters_live_message_data( $id, $second['cursor'] );
	expect_live( ! $empty['messages'] && $empty['cursor'] === $ids[54] && ! $empty['more'], 'Idle response has no historical message payload.' );
	expect_live( cammino_tipsters_message_cursor( $id ) === $ids[54], 'Initial watermark includes all messages beyond the rendered history page.' );
	wp_set_current_user( $owner );
	expect_live( 5 === count( cammino_tipsters_live_message_data( $id, $ids[49] )['messages'] ), 'Owning tipster can fetch admin-authored deltas.' );
	wp_set_current_user( 0 ); expect_live( ! cammino_tipsters_live_message_rows( $id ) && 0 === cammino_tipsters_message_cursor( $id ), 'Anonymous query and watermark are denied.' );
} catch ( Throwable $error ) { $failure = $error; }
finally { wp_set_current_user( $admin->ID ); foreach ( $users as $user ) { cammino_tipsters_delete_account( $user ); } }
$output = ob_get_clean(); if ( $output ) { fwrite( STDERR, $output ); }
if ( $failure ) { fwrite( STDERR, 'FAIL: ' . $failure->getMessage() . "\n" ); exit( 1 ); }
echo "Passed $checks incremental conversation WordPress checks.\n";
