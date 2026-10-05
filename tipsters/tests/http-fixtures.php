<?php
/** CLI fixture helper for submissions-http.py; never expose this file as an endpoint. */
if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) || ! is_file( $argv[1] ) ) { exit( 1 ); }
ob_start(); $_SERVER['HTTP_HOST'] = '127.0.0.1:8765'; $_SERVER['REQUEST_METHOD'] = 'GET';
require $argv[1];
if ( ! defined( 'CAMMINO_TIPSTERS_TEST_INSTALLATION' ) || true !== CAMMINO_TIPSTERS_TEST_INSTALLATION || ! defined( 'CAMMINO_TIPSTERS_STORAGE_PATH' ) ) { fwrite( STDERR, "Disposable test installation required.\n" ); exit( 1 ); }
require_once ABSPATH . 'wp-admin/includes/user.php';
add_filter( 'pre_wp_mail', '__return_true' );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC' ) )[0];
wp_set_current_user( $admin->ID );
$result = array();
$mode = $argv[2] ?? '';
if ( 'setup' === $mode ) {
	$prefix = 'stage2_http_' . bin2hex( random_bytes( 6 ) );
	$password = bin2hex( random_bytes( 12 ) );
	$result = array( 'prefix' => $prefix, 'password' => $password, 'users' => array(), 'storage_root' => CAMMINO_TIPSTERS_STORAGE_PATH );
	foreach ( array( 'one', 'two' ) as $suffix ) {
		$name = $prefix . '_' . $suffix;
		$id = cammino_tipsters_create_account( $name, $password );
		if ( is_wp_error( $id ) ) { fwrite( STDERR, $id->get_error_message() ); exit( 1 ); }
		$result['users'][ $suffix ] = array( 'username' => $name, 'id' => $id );
	}
	$name = $prefix . '_admin';
	$id = wp_insert_user( array( 'user_login' => $name, 'user_pass' => $password, 'role' => 'administrator' ) );
	$result['users']['admin'] = array( 'username' => $name, 'id' => $id );
} elseif ( preg_match( '/^stage2_http_[a-f0-9]{12}$/', $argv[3] ?? '' ) ) {
	$prefix = $argv[3];
	foreach ( array( 'one', 'two', 'admin' ) as $suffix ) {
		$user = get_user_by( 'login', $prefix . '_' . $suffix );
		if ( ! $user ) { continue; }
		if ( 'cleanup' === $mode ) {
			if ( 'admin' === $suffix ) { wp_delete_user( $user->ID ); } else { cammino_tipsters_delete_account( $user->ID ); }
		} elseif ( 'chat-history' === $mode && 'one' === $suffix ) {
			$records = cammino_tipsters_account_records( (int) $user->ID );
			$tip = (int) ( $records['tips'][0] ?? 0 );
			$fixture_admin = get_user_by( 'login', $prefix . '_admin' );
			if ( ! $tip || ! $fixture_admin ) { exit( 1 ); }
			wp_set_current_user( $fixture_admin->ID );
			for ( $i = 1; $i <= 45; ++$i ) {
				$message = cammino_tipsters_send_message( $tip, 'Historical message ' . $i . "\n" . str_repeat( 'Readable conversation content. ', 5 ), cammino_tipsters_message_token( $tip ) );
				if ( is_wp_error( $message ) ) { fwrite( STDERR, $message->get_error_message() ); exit( 1 ); }
			}
			$result = array( 'tip_id' => $tip, 'messages' => count( cammino_tipsters_tip_message_ids( $tip ) ) );
			wp_set_current_user( $admin->ID );
		} elseif ( 'legacy' === $mode && 'one' === $suffix ) {
			$records = cammino_tipsters_account_records( (int) $user->ID );
			$tip = (int) ( $records['tips'][0] ?? 0 );
			if ( ! $tip ) { exit( 1 ); }
			$body = '%PDF-1.4 disposable legacy upload';
			$path = $prefix . '-legacy.pdf';
			file_put_contents( trailingslashit( CAMMINO_TIPSTERS_STORAGE_PATH ) . $path, $body );
			$file = array( 'id' => bin2hex( random_bytes( 16 ) ), 'path' => $path, 'name' => 'legacy.pdf', 'size' => strlen( $body ), 'mime' => 'application/pdf' );
			update_post_meta( $tip, CAMMINO_TIP_FILES_META, array( $file ) );
			$result = array( 'url' => cammino_tipsters_url( 'download', $tip, $file['id'] ), 'body' => $body );
		} elseif ( 'snapshot' === $mode && 'admin' !== $suffix ) {
			$records = cammino_tipsters_account_records( (int) $user->ID );
			$result[ $suffix ] = array( 'tips' => count( $records['tips'] ), 'messages' => count( $records['messages'] ), 'files' => array() );
			foreach ( $records['files'] as $file ) {
				$file['exists'] = is_file( cammino_tipsters_private_file( $file['path'] ) );
				$result[ $suffix ]['files'][] = $file;
			}
		}
	}
} else { exit( 1 ); }
ob_end_clean(); echo wp_json_encode( $result );
