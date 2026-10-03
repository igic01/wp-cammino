<?php
/** Live, uncached frontend authentication and private dashboard. @package Cammino */
defined( 'ABSPATH' ) || exit;

add_filter( 'query_vars', static function ( $vars ) {
	$vars[] = 'cammino_tipsters';
	return $vars;
} );

add_action( 'init', static function (): void {
	add_rewrite_rule( '^tipsters/login/?$', 'index.php?cammino_tipsters=login', 'top' );
	add_rewrite_rule( '^tipsters/?$', 'index.php?cammino_tipsters=dashboard', 'top' );
}, 20 );

// Flush only after all theme/plugin rewrite rules have been registered.
add_action( 'wp_loaded', static function (): void {
	if ( CAMMINO_TIPSTERS_VERSION !== get_option( 'cammino_tipsters_routes_version' ) ) {
		flush_rewrite_rules( false );
		update_option( 'cammino_tipsters_routes_version', CAMMINO_TIPSTERS_VERSION, false );
	}
} );

function cammino_tipsters_route(): string {
	$route = get_query_var( 'cammino_tipsters', '' );
	return in_array( $route, array( 'login', 'dashboard' ), true ) ? $route : '';
}

function cammino_tipsters_url( string $route = 'dashboard' ): string {
	if ( ! get_option( 'permalink_structure' ) ) {
		return add_query_arg( 'cammino_tipsters', 'login' === $route ? 'login' : 'dashboard', home_url( '/' ) );
	}
	return home_url( 'login' === $route ? '/tipsters/login/' : '/tipsters/' );
}

add_action( 'parse_request', static function ( $wp ): void {
	if ( ! empty( $wp->query_vars['cammino_tipsters'] ) && ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
} );

add_filter( 'redirect_canonical', static function ( $redirect ) {
	return cammino_tipsters_route() ? false : $redirect;
} );
add_filter( 'wp_robots', static function ( $robots ) {
	if ( cammino_tipsters_route() ) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'] );
	}
	return $robots;
} );
add_filter( 'document_title_parts', static function ( $title ) {
	if ( cammino_tipsters_route() ) {
		$title['title'] = 'login' === cammino_tipsters_route() ? __( 'Prihlásenie tipstera', 'cammino' ) : __( 'Môj účet tipstera', 'cammino' );
	}
	return $title;
} );

/** Scalar form inputs only; passwords must not be sanitized or trimmed. */
function cammino_tipsters_input( string $key ): string {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
}

/** No proxy headers: accepting arbitrary X-Forwarded-For bypasses throttling. */
function cammino_tipsters_login_keys( string $username ): array {
	$target = get_user_by( 'login', $username );
	if ( ! $target && is_email( $username ) ) {
		$target = get_user_by( 'email', $username );
	}
	if ( $target ) {
		$username = $target->user_login;
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
	return array(
		'cammino_login_ip_' . hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ),
		'cammino_login_user_' . hash_hmac( 'sha256', $ip . ':' . strtolower( $username ), wp_salt( 'auth' ) ),
	);
}

function cammino_tipsters_login_limited( string $username ): bool {
	$keys = cammino_tipsters_login_keys( $username );
	return (int) get_transient( $keys[0] ) >= 30 || (int) get_transient( $keys[1] ) >= 8;
}

function cammino_tipsters_login_failed( string $username ): void {
	foreach ( cammino_tipsters_login_keys( $username ) as $key ) {
		set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
	}
}

// Core username/email authenticators may replace earlier authenticate errors.
// This hook runs after user lookup, before password verification, on both paths.
add_filter( 'wp_authenticate_user', static function ( $user, $password ) {
	if ( cammino_tipsters_is_tipster( $user ) && ( ! cammino_tipsters_account_enabled( (int) $user->ID ) || cammino_tipsters_login_limited( $user->user_login ) ) ) {
		return new WP_Error( 'cammino_login_failed', __( 'Prihlásenie sa nepodarilo. Skúste to neskôr alebo kontaktujte administrátora.', 'cammino' ) );
	}
	return $user;
}, PHP_INT_MAX, 2 );

add_action( 'wp_login_failed', static function ( $username ): void {
	$target = get_user_by( 'login', (string) $username );
	if ( ! $target && is_email( $username ) ) {
		$target = get_user_by( 'email', $username );
	}
	if ( ! empty( $GLOBALS['cammino_tipsters_frontend_login'] ) || cammino_tipsters_is_tipster( $target ) ) {
		cammino_tipsters_login_failed( (string) $username );
	}
} );

/** Each browser gets its own login CSRF token, unlike the shared guest nonce. */
function cammino_tipsters_login_token(): string {
	$name = 'cammino_tipsters_login_' . COOKIEHASH;
	$token = isset( $_COOKIE[ $name ] ) && is_string( $_COOKIE[ $name ] ) ? $_COOKIE[ $name ] : '';
	if ( ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
		$token = bin2hex( random_bytes( 32 ) );
		setcookie( $name, $token, array(
			'expires' => 0, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax',
		) );
		$_COOKIE[ $name ] = $token;
	}
	return $token;
}

function cammino_tipsters_handle_login() {
	$cookie = $_COOKIE[ 'cammino_tipsters_login_' . COOKIEHASH ] ?? '';
	$token = cammino_tipsters_input( 'cammino_login_token' );
	if ( ! is_string( $cookie ) || ! preg_match( '/^[a-f0-9]{64}$/', $cookie ) || ! hash_equals( $cookie, $token ) || ! wp_verify_nonce( cammino_tipsters_input( '_wpnonce' ), 'cammino_tipster_login' ) ) {
		return new WP_Error( 'expired_form', __( 'Platnosť formulára vypršala. Obnovte stránku a skúste to znova.', 'cammino' ) );
	}
	$GLOBALS['cammino_tipsters_frontend_login'] = true;
	try {
		$user = wp_signon( array(
			'user_login' => cammino_tipsters_input( 'username' ),
			'user_password' => cammino_tipsters_input( 'password' ),
			'remember' => false,
		), is_ssl() );
	} finally {
		unset( $GLOBALS['cammino_tipsters_frontend_login'] );
	}
	if ( is_wp_error( $user ) ) {
		return new WP_Error( 'login_failed', __( 'Prihlásenie sa nepodarilo. Skontrolujte údaje, skúste to neskôr alebo kontaktujte administrátora.', 'cammino' ) );
	}
	wp_set_current_user( $user->ID );
	return $user;
}

add_action( 'template_redirect', 'cammino_tipsters_handle_frontend', -10 );
function cammino_tipsters_handle_frontend(): void {
	$route = cammino_tipsters_route();
	if ( ! $route ) {
		return;
	}
	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
	header( 'X-Robots-Tag: noindex, nofollow', true );
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
	if ( ! in_array( $method, array( 'GET', 'HEAD', 'POST' ), true ) ) {
		wp_die( esc_html__( 'Nepodporovaná požiadavka.', 'cammino' ), '', array( 'response' => 405 ) );
	}
	$user = wp_get_current_user();
	if ( $user->ID && ! cammino_tipsters_is_tipster( $user ) ) {
		wp_die( esc_html__( 'Táto stránka je určená pre účty tipsterov. Na testovanie použite samostatné okno prehliadača.', 'cammino' ), '', array( 'response' => 403 ) );
	}
	if ( $user->ID && ! cammino_tipsters_account_enabled( (int) $user->ID ) ) {
		wp_logout();
		wp_safe_redirect( cammino_tipsters_url( 'login' ) );
		exit;
	}
	if ( 'dashboard' === $route && ! $user->ID ) {
		wp_safe_redirect( cammino_tipsters_url( 'login' ) );
		exit;
	}
	if ( 'POST' === $method && 'dashboard' === $route ) {
		if ( 'logout' !== cammino_tipsters_input( 'operation' ) || ! wp_verify_nonce( cammino_tipsters_input( '_wpnonce' ), 'cammino_tipster_logout' ) ) {
			wp_die( esc_html__( 'Neplatná požiadavka.', 'cammino' ), '', array( 'response' => 403 ) );
		}
		wp_logout();
		wp_safe_redirect( cammino_tipsters_url( 'login' ) );
		exit;
	}
	if ( 'login' === $route && $user->ID ) {
		wp_safe_redirect( cammino_tipsters_url() );
		exit;
	}
	if ( 'login' === $route && 'POST' === $method ) {
		$result = cammino_tipsters_handle_login();
		if ( ! is_wp_error( $result ) ) {
			wp_safe_redirect( cammino_tipsters_url() );
			exit;
		}
		$GLOBALS['cammino_tipsters_login_error'] = $result->get_error_message();
	}
	if ( 'login' === $route ) {
		$GLOBALS['cammino_tipsters_login_token'] = cammino_tipsters_login_token();
	}
	global $wp_query;
	$wp_query->is_404 = false;
	$wp_query->is_home = false;
	status_header( 200 );
}

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( cammino_tipsters_route() ) {
		cammino_enqueue_design_assets( 'cammino-tipsters', '/tipsters/assets/portal.css', '' );
	}
}, 1003 );
