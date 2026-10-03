<?php
/**
 * Integration checks against a DISPOSABLE WordPress installation.
 * Run: php tipsters/tests/accounts-workflow.php /path/to/test-wordpress/wp-load.php
 * Its wp-config.php must define CAMMINO_TIPSTERS_TEST_INSTALLATION as true and
 * CAMMINO_TIPSTERS_STORAGE_PATH as a private test directory. Never use real data.
 */
if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) || ! is_file( $argv[1] ) ) {
	fwrite( STDERR, "Pass wp-load.php from a disposable WordPress test installation.\n" );
	exit( 1 );
}
ob_start();
$_SERVER['HTTP_HOST'] = '127.0.0.1:8765';
$_SERVER['SERVER_NAME'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
// Isolate persistent throttle counters between test runs.
$_SERVER['REMOTE_ADDR'] = 'fd00::' . bin2hex( random_bytes( 2 ) ) . ':' . bin2hex( random_bytes( 2 ) );
require $argv[1];
if ( ! defined( 'CAMMINO_TIPSTERS_TEST_INSTALLATION' ) || true !== CAMMINO_TIPSTERS_TEST_INSTALLATION || ! defined( 'CAMMINO_TIPSTERS_STORAGE_PATH' ) ) {
	fwrite( STDERR, "The installation has not opted into destructive test fixtures.\n" );
	exit( 1 );
}
if ( ! function_exists( 'cammino_tipsters_create_account' ) ) {
	fwrite( STDERR, "Activate the Cammino theme before running the tests.\n" );
	exit( 1 );
}
add_filter( 'pre_wp_mail', '__return_true' );
$checks = 0;
$users = array();
$posts = array();
$files = array();
$failure = null;
$prefix = 'tipster_test_' . bin2hex( random_bytes( 4 ) );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
wp_set_current_user( $admin->ID );
function expect_tipsters( $condition, string $message ): void {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}
function fixture_tip( int $author, string $status = 'submitted' ): int {
	global $posts;
	$id = wp_insert_post( array( 'post_type' => CAMMINO_TIP_POST_TYPE, 'post_status' => 'private', 'post_author' => $author, 'post_title' => 'Private test tip' ) );
	$posts[] = $id;
	update_post_meta( $id, '_cammino_tip_status', $status );
	return $id;
}
function fixture_message( int $author, int $tip ): int {
	global $posts;
	$id = wp_insert_post( array( 'post_type' => CAMMINO_MESSAGE_POST_TYPE, 'post_status' => 'private', 'post_author' => $author, 'post_content' => 'Private test message' ) );
	$posts[] = $id;
	update_post_meta( $id, CAMMINO_MESSAGE_TIP_META, $tip );
	return $id;
}

try {
	expect_tipsters( cammino_tipsters_can_manage(), 'Administrator receives dedicated capability.' );
	$role = get_role( CAMMINO_TIPSTER_ROLE );
	expect_tipsters( $role->has_cap( 'read' ) && ! $role->has_cap( 'edit_posts' ) && ! $role->has_cap( 'upload_files' ), 'Tipster role has no editor/media capabilities.' );
	cammino_tipsters_initialize();
	expect_tipsters( CAMMINO_TIPSTERS_VERSION === get_option( 'cammino_tipsters_version' ), 'Setup is idempotent.' );
	foreach ( array( CAMMINO_TIP_POST_TYPE, CAMMINO_MESSAGE_POST_TYPE ) as $type ) {
		$model = get_post_type_object( $type );
		expect_tipsters( $model && ! $model->public && ! $model->publicly_queryable && ! $model->show_in_rest && ! $model->show_ui && ! $model->can_export, 'Private model has no public/native editing exposure: ' . $type );
	}
	expect_tipsters( is_wp_error( cammino_tipsters_create_account( 'invalid<username>', 'Valid-Password-123' ) ), 'Malformed username rejected.' );
	expect_tipsters( is_wp_error( cammino_tipsters_create_account( $prefix, 'short' ) ), 'Short password rejected.' );
	expect_tipsters( is_wp_error( cammino_tipsters_validate_password( ' Password-123456' ) ), 'Whitespace passwords are rejected instead of changed silently.' );
	$id = cammino_tipsters_create_account( $prefix, 'Valid-Password-123' );
	expect_tipsters( is_int( $id ) && $id > 0, 'Create account without email.' );
	$users[] = $id;
	$second = cammino_tipsters_create_account( $prefix . '_other', 'Valid-Password-456' );
	$users[] = $second;
	$user = get_userdata( $id );
	expect_tipsters( array( CAMMINO_TIPSTER_ROLE ) === $user->roles && '' === $user->user_email && $prefix === $user->display_name, 'Account role and username-only defaults.' );
	expect_tipsters( wp_check_password( 'Valid-Password-123', $user->user_pass, $id ) && 'Valid-Password-123' !== $user->user_pass, 'Password stored through WordPress hashing.' );
	expect_tipsters( is_wp_error( cammino_tipsters_create_account( $prefix, 'Valid-Password-123' ) ), 'Duplicate username rejected.' );
	expect_tipsters( ! is_wp_error( cammino_tipsters_update_account( $id, 'Test display name' ) ) && 'Test display name' === get_userdata( $id )->display_name, 'Admin updates display name without changing password.' );
	expect_tipsters( is_wp_error( cammino_tipsters_update_account( $id, '' ) ), 'Empty display name rejected.' );
	expect_tipsters( is_wp_error( cammino_tipsters_update_account( $admin->ID, 'Do not change' ) ), 'Management cannot target administrators.' );
	$mixed = wp_insert_user( array( 'user_login' => $prefix . '_mixed', 'user_pass' => 'Valid-Password-789', 'role' => CAMMINO_TIPSTER_ROLE ) );
	$users[] = $mixed;
	$multi_user = get_userdata( $mixed );
	$multi_user->add_role( 'administrator' );
	expect_tipsters( ! cammino_tipsters_managed_account( $mixed ) && is_wp_error( cammino_tipsters_delete_account( $mixed ) ), 'Mixed-role users protected from account deletion.' );
	$manager = WP_Session_Tokens::get_instance( $id );
	$manager->create( time() + HOUR_IN_SECONDS );
	expect_tipsters( count( $manager->get_all() ) === 1, 'Fixture has an active session.' );
	expect_tipsters( true === cammino_tipsters_set_enabled( $id, false ) && ! cammino_tipsters_account_enabled( $id ), 'Disable account.' );
	expect_tipsters( array() === $manager->get_all(), 'Disabling revokes sessions.' );
	expect_tipsters( is_wp_error( wp_authenticate( $prefix, 'Valid-Password-123' ) ), 'Disabled account cannot authenticate.' );
	expect_tipsters( 0 === cammino_tipsters_current_user( $id ), 'Existing authenticated disabled-user cookies are rejected.' );
	expect_tipsters( true === cammino_tipsters_set_enabled( $id, true ) && cammino_tipsters_account_enabled( $id ), 'Re-enable account.' );
	expect_tipsters( wp_authenticate( $prefix, 'Valid-Password-123' ) instanceof WP_User, 'Re-enabled account can authenticate.' );
	$manager->create( time() + HOUR_IN_SECONDS );
	expect_tipsters( ! is_wp_error( cammino_tipsters_update_account( $id, 'Updated name', 'Changed-Password-123' ) ), 'Administrator can change password.' );
	expect_tipsters( array() === $manager->get_all(), 'Admin password changes revoke sessions.' );
	expect_tipsters( ! wp_check_password( 'Valid-Password-123', get_userdata( $id )->user_pass, $id ) && wp_check_password( 'Changed-Password-123', get_userdata( $id )->user_pass, $id ), 'Old password rejected and new password valid.' );
	wp_set_current_user( $id );
	expect_tipsters( ! cammino_tipsters_can_manage() && ! current_user_can( 'edit_user', $id ), 'Tipster cannot manage accounts or edit own native profile.' );
	expect_tipsters( false === apply_filters( 'show_admin_bar', true ), 'Tipster toolbar hidden.' );
	expect_tipsters( cammino_tipsters_url() === apply_filters( 'login_redirect', admin_url(), '', get_userdata( $id ) ), 'Native login redirects tipsters to private dashboard.' );
	expect_tipsters( is_wp_error( cammino_tipsters_update_account( $id, 'Forged', 'Forged-Password-123' ) ), 'Tipster cannot call account update operation.' );
	expect_tipsters( is_wp_error( cammino_tipsters_create_account( $prefix . '_forged', 'Forged-Password-123' ) ), 'Tipster cannot create accounts.' );
	expect_tipsters( is_wp_error( cammino_tipsters_set_enabled( $second, false ) ) && is_wp_error( cammino_tipsters_delete_account( $second ) ), 'Tipster cannot disable/delete another account.' );
	$hash = get_userdata( $id )->user_pass;
	wp_update_user( array( 'ID' => $id, 'user_pass' => 'Forged-Password-123', 'display_name' => 'Forged', 'user_email' => 'forged@example.invalid' ) );
	expect_tipsters( $hash === get_userdata( $id )->user_pass && 'Updated name' === get_userdata( $id )->display_name && '' === get_userdata( $id )->user_email, 'Native plugin user-update route preserves protected fields.' );
	expect_tipsters( false === wp_is_password_reset_allowed_for_user( get_userdata( $id ) ), 'Native password recovery forbidden.' );
	expect_tipsters( is_wp_error( get_password_reset_key( get_userdata( $id ) ) ), 'No new password-reset keys for tipsters.' );
	expect_tipsters( ! wp_is_application_passwords_available_for_user( get_userdata( $id ) ), 'Application-password authentication unavailable to tipsters.' );
	$request = new WP_REST_Request( 'POST', '/wp/v2/users/' . $id );
	$request->set_param( 'password', 'Rest-Forged-Password-123' );
	expect_tipsters( rest_do_request( $request )->get_status() === 403, 'Native REST profile update forbidden.' );
	$errors = new WP_Error();
	do_action( 'woocommerce_save_account_details_errors', $errors, get_userdata( $id ) );
	expect_tipsters( $errors->has_errors(), 'WooCommerce account/password editing forbidden for tipsters.' );
	$throw_die = static function () { return static function ( $message ) { throw new RuntimeException( (string) $message ); }; };
	add_filter( 'wp_die_handler', $throw_die );
	$reset_blocked = false;
	try { reset_password( get_userdata( $id ), 'Reset-Forged-Password-123' ); } catch ( RuntimeException $error ) { $reset_blocked = true; }
	remove_filter( 'wp_die_handler', $throw_die );
	expect_tipsters( $reset_blocked && $hash === get_userdata( $id )->user_pass, 'Previously issued reset-key completion cannot change password.' );
	$tip = fixture_tip( $id, 'discussion' );
	$other_tip = fixture_tip( $second );
	$message = fixture_message( $id, $tip );
	$reply = fixture_message( (int) $admin->ID, $tip );
	$other_reply = fixture_message( (int) $admin->ID, $other_tip );
	expect_tipsters( cammino_tipsters_can_read_tip( $tip ) && cammino_tipsters_can_edit_tip( $tip ), 'Owner can read and edit discussion tip.' );
	expect_tipsters( ! cammino_tipsters_can_read_tip( $other_tip ) && ! cammino_tipsters_can_edit_tip( $other_tip ), 'Ownership prevents cross-account reads/writes.' );
	foreach ( array( 'submitted', 'approved', 'deleted_by_tipster' ) as $status ) {
		update_post_meta( $tip, '_cammino_tip_status', $status );
		expect_tipsters( ! cammino_tipsters_can_edit_tip( $tip ), 'Form locked in ' . $status );
	}
	expect_tipsters( ! cammino_tipsters_can_read_tip( $tip ), 'Deleted-by-tipster record hidden from owner.' );
	wp_set_current_user( $admin->ID );
	expect_tipsters( cammino_tipsters_can_read_tip( $tip ) && get_post( $message ) && get_post( $reply ), 'Admin retains deleted tip and conversation.' );
	cammino_tipsters_set_enabled( $id, false );
	expect_tipsters( get_post( $tip ) && get_post( $message ) && get_post( $reply ), 'Account disabling preserves tips and both parties messages.' );
	$dir = rtrim( CAMMINO_TIPSTERS_STORAGE_PATH, '/\\' ) . '/' . $prefix;
	mkdir( $dir );
	$file = $dir . '/document.pdf';
	file_put_contents( $file, 'Disposable file fixture' );
	$files[] = $file;
	update_post_meta( $tip, CAMMINO_TIP_FILES_META, array( array( 'path' => $prefix . '/document.pdf' ) ) );
	$records = cammino_tipsters_account_records( $id );
	expect_tipsters( 1 === count( $records['tips'] ) && 2 === count( $records['messages'] ) && 1 === count( $records['files'] ), 'Purge totals include soft-deleted tips and admin replies.' );
	expect_tipsters( is_wp_error( cammino_tipsters_private_file( '../outside.txt' ) ) && is_wp_error( cammino_tipsters_private_file( 'C:/outside.txt' ) ) && is_wp_error( cammino_tipsters_private_file( $prefix . '/document.pdf:stream' ) ), 'Traversal, absolute paths, and NTFS streams rejected.' );
	expect_tipsters( true === cammino_tipsters_delete_account( $id ), 'Full account purge succeeds.' );
	expect_tipsters( ! get_userdata( $id ) && ! get_post( $tip ) && ! get_post( $message ) && ! get_post( $reply ) && ! file_exists( $file ), 'Account, deleted tip, both messages, and physical file erased.' );
	expect_tipsters( get_userdata( $second ) && get_post( $other_tip ) && get_post( $other_reply ), 'Other accounts and their records preserved.' );
	$unsafe_tip = fixture_tip( $second );
	update_post_meta( $unsafe_tip, CAMMINO_TIP_FILES_META, array( array( 'path' => '../outside.txt' ) ) );
	expect_tipsters( is_wp_error( cammino_tipsters_delete_account( $second ) ) && get_userdata( $second ) && get_post( $unsafe_tip ), 'Unsafe file metadata stops purge without deleting account/records.' );
	expect_tipsters( 'deleting' === get_user_meta( $second, CAMMINO_TIPSTER_STATE_META, true ) && ! cammino_tipsters_account_enabled( $second ) && is_wp_error( cammino_tipsters_set_enabled( $second, true ) ), 'Interrupted purge remains blocked and cannot be re-enabled.' );
	delete_post_meta( $unsafe_tip, CAMMINO_TIP_FILES_META );
	expect_tipsters( true === cammino_tipsters_delete_account( $second ), 'Purge resumes safely after correcting storage metadata.' );
	$native = cammino_tipsters_create_account( $prefix . '_native', 'Native-Password-123' );
	$users[] = $native;
	$native_tip = fixture_tip( $native );
	$native_reply = fixture_message( (int) $admin->ID, $native_tip );
	expect_tipsters( wp_delete_user( $native ) && ! get_post( $native_tip ) && ! get_post( $native_reply ), 'Native Users deletion invokes complete private-record cleanup.' );
	$subscriber = wp_insert_user( array( 'user_login' => $prefix . '_subscriber', 'user_pass' => 'Subscriber-Password-123', 'role' => 'subscriber' ) );
	$users[] = $subscriber;
	wp_set_current_user( $subscriber );
	expect_tipsters( wp_is_password_reset_allowed_for_user( get_userdata( $subscriber ) ), 'Unrelated roles retain password recovery.' );
	expect_tipsters( admin_url() === apply_filters( 'login_redirect', admin_url(), '', get_userdata( $subscriber ) ), 'Unrelated native-login redirect preserved.' );
	$errors = new WP_Error();
	do_action( 'woocommerce_save_account_details_errors', $errors, get_userdata( $subscriber ) );
	expect_tipsters( ! $errors->has_errors(), 'Unrelated WooCommerce profile editing unaffected.' );
	wp_set_current_user( $admin->ID );
	$login_id = cammino_tipsters_create_account( $prefix . '_login', 'Login-Password-123' );
	$users[] = $login_id;
	wp_set_current_user( 0 );
	$_POST = array( 'username' => $prefix . '_login', 'password' => 'Login-Password-123', '_wpnonce' => wp_create_nonce( 'cammino_tipster_login' ), 'cammino_login_token' => str_repeat( 'a', 64 ) );
	$_COOKIE[ 'cammino_tipsters_login_' . COOKIEHASH ] = str_repeat( 'b', 64 );
	expect_tipsters( is_wp_error( cammino_tipsters_handle_login() ), 'Cross-browser login-CSRF token rejected.' );
	$_COOKIE[ 'cammino_tipsters_login_' . COOKIEHASH ] = str_repeat( 'a', 64 );
	expect_tipsters( cammino_tipsters_handle_login() instanceof WP_User && get_current_user_id() === $login_id, 'Frontend login accepts enabled tipster with valid CSRF token.' );
	wp_set_current_user( 0 );
	$_POST['username'] = $prefix . '_subscriber';
	$_POST['password'] = 'Subscriber-Password-123';
	$_POST['_wpnonce'] = wp_create_nonce( 'cammino_tipster_login' );
	expect_tipsters( is_wp_error( cammino_tipsters_handle_login() ) && 0 === get_current_user_id(), 'Frontend login cannot establish an unrelated account session, even with valid credentials.' );
	for ( $attempt = 0; $attempt < 8; ++$attempt ) {
		cammino_tipsters_login_failed( $prefix . '_login' );
	}
	expect_tipsters( cammino_tipsters_login_limited( $prefix . '_login' ) && is_wp_error( wp_authenticate( $prefix . '_login', 'Login-Password-123' ) ), 'Native login cannot bypass portal tipster rate limit.' );
	expect_tipsters( wp_authenticate( $prefix . '_subscriber', 'Subscriber-Password-123' ) instanceof WP_User, 'Tipster throttling does not block unrelated native account authentication.' );
	$GLOBALS['wp_query']->set( 'cammino_tipsters', 'dashboard' );
	expect_tipsters( cammino_use_site_shell( '/other-template.php' ) === NSTARTER_PATH . '/tipsters/templates/portal.php', 'Final theme router uses live portal template.' );
	expect_tipsters( false === apply_filters( 'redirect_canonical', 'http://redirect.example/' ), 'Canonical redirect does not break virtual portal routes.' );
	expect_tipsters( ! empty( apply_filters( 'wp_robots', array() )['noindex'] ), 'Private portal pages not indexed.' );
	$GLOBALS['wp_query']->set( 'cammino_tipsters', 'invalid' );
	expect_tipsters( '' === cammino_tipsters_route(), 'Invalid portal route ignored.' );
	$permalinks = get_option( 'permalink_structure' );
	update_option( 'permalink_structure', '' );
	expect_tipsters( false !== strpos( cammino_tipsters_url( 'login' ), 'cammino_tipsters=login' ), 'Plain-permalink login URL supported.' );
	update_option( 'permalink_structure', $permalinks );
} catch ( Throwable $error ) {
	$failure = $error;
} finally {
	wp_set_current_user( $admin->ID );
	foreach ( $posts as $post_id ) { if ( get_post( $post_id ) ) { delete_post_meta( $post_id, CAMMINO_TIP_FILES_META ); wp_delete_post( $post_id, true ); } }
	foreach ( $users as $user_id ) {
		$user = get_userdata( $user_id );
		if ( $user ) {
			if ( cammino_tipsters_is_tipster( $user ) ) { $user->set_role( CAMMINO_TIPSTER_ROLE ); }
			wp_delete_user( $user_id );
		}
	}
	foreach ( $files as $file ) { if ( file_exists( $file ) ) { unlink( $file ); } }
	if ( isset( $dir ) && is_dir( $dir ) ) { rmdir( $dir ); }
	foreach ( array( '', '_other', '_mixed', '_native', '_subscriber', '_login' ) as $suffix ) {
		foreach ( cammino_tipsters_login_keys( $prefix . $suffix ) as $key ) { delete_transient( $key ); }
	}
	$_POST = array();
}
$output = ob_get_clean();
if ( '' !== $output ) { fwrite( STDERR, $output ); }
if ( $failure ) { fwrite( STDERR, "FAIL: " . $failure->getMessage() . "\n" ); exit( 1 ); }
echo "Passed $checks WordPress tipster account/workflow checks.\n";
