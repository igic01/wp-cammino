<?php
/** Central account and ownership checks. @package Cammino */
defined( 'ABSPATH' ) || exit;

function cammino_tipsters_is_tipster( $user ): bool {
	return $user instanceof WP_User && in_array( CAMMINO_TIPSTER_ROLE, (array) $user->roles, true );
}

/** Management must never target an administrator or another mixed-role user. */
function cammino_tipsters_managed_account( int $id ) {
	$user = get_userdata( $id );
	return cammino_tipsters_is_tipster( $user ) && array( CAMMINO_TIPSTER_ROLE ) === array_values( $user->roles ) ? $user : false;
}

function cammino_tipsters_can_manage(): bool {
	return ! cammino_tipsters_is_tipster( wp_get_current_user() )
		&& current_user_can( 'manage_options' ) && current_user_can( CAMMINO_TIPSTERS_CAP );
}

function cammino_tipsters_account_enabled( int $id ): bool {
	return cammino_tipsters_is_tipster( get_userdata( $id ) ) && ! in_array(
		get_user_meta( $id, CAMMINO_TIPSTER_STATE_META, true ), array( 'disabled', 'deleting' ), true
	);
}

/** Use for all future tip, file, and conversation reads. */
function cammino_tipsters_can_read_tip( int $id ): bool {
	$post = get_post( $id );
	if ( ! $post || CAMMINO_TIP_POST_TYPE !== $post->post_type ) {
		return false;
	}
	if ( cammino_tipsters_can_manage() ) {
		return true;
	}
	$user = wp_get_current_user();
	return cammino_tipsters_account_enabled( (int) $user->ID )
		&& (int) $post->post_author === (int) $user->ID
		&& 'deleted_by_tipster' !== get_post_meta( $id, '_cammino_tip_status', true );
}

function cammino_tipsters_can_edit_tip( int $id ): bool {
	return cammino_tipsters_is_tipster( wp_get_current_user() )
		&& cammino_tipsters_can_read_tip( $id )
		&& 'discussion' === get_post_meta( $id, '_cammino_tip_status', true );
}

add_filter( 'authenticate', 'cammino_tipsters_authenticate', PHP_INT_MAX, 3 );
function cammino_tipsters_authenticate( $user, $username, $password ) {
	if ( cammino_tipsters_is_tipster( $user ) && cammino_tipsters_login_limited( $user->user_login ) ) {
		return new WP_Error( 'cammino_login_failed', __( 'Prihlásenie sa nepodarilo. Skúste to neskôr alebo kontaktujte administrátora.', 'cammino' ) );
	}
	if ( ! empty( $GLOBALS['cammino_tipsters_frontend_login'] ) && ! cammino_tipsters_is_tipster( $user ) ) {
		return new WP_Error( 'cammino_login_failed', __( 'Prihlásenie sa nepodarilo. Skontrolujte údaje alebo kontaktujte administrátora.', 'cammino' ) );
	}
	if ( cammino_tipsters_is_tipster( $user ) && ! cammino_tipsters_account_enabled( (int) $user->ID ) ) {
		return new WP_Error( 'cammino_login_failed', __( 'Prihlásenie sa nepodarilo. Skontrolujte údaje alebo kontaktujte administrátora.', 'cammino' ) );
	}
	return $user;
}

// Covers cookies and application-password authentication before current-user setup.
add_filter( 'determine_current_user', 'cammino_tipsters_current_user', PHP_INT_MAX );
function cammino_tipsters_current_user( $id ) {
	return $id && cammino_tipsters_is_tipster( get_userdata( (int) $id ) ) && ! cammino_tipsters_account_enabled( (int) $id ) ? 0 : $id;
}

add_filter( 'show_admin_bar', static function ( $show ) {
	return cammino_tipsters_is_tipster( wp_get_current_user() ) ? false : $show;
} );

add_action( 'admin_init', static function (): void {
	if ( cammino_tipsters_is_tipster( wp_get_current_user() ) && ! wp_doing_ajax() && ! ( isset( $GLOBALS['pagenow'] ) && 'admin-post.php' === $GLOBALS['pagenow'] ) ) {
		wp_safe_redirect( cammino_tipsters_url() );
		exit;
	}
}, 0 );

add_filter( 'login_redirect', static function ( $redirect, $requested, $user ) {
	return cammino_tipsters_is_tipster( $user ) ? cammino_tipsters_url() : $redirect;
}, 10, 3 );

// Native profile/REST user endpoints must not treat tipsters as self-editing users.
add_filter( 'map_meta_cap', static function ( $caps, $cap, $id, $args ) {
	if ( 'edit_user' === $cap && cammino_tipsters_is_tipster( get_userdata( (int) $id ) ) ) {
		return array( 'do_not_allow' );
	}
	return $caps;
}, 20, 4 );

add_filter( 'wp_is_application_passwords_available_for_user', static function ( $available, $user ) {
	return cammino_tipsters_is_tipster( $user ) ? false : $available;
}, 20, 2 );

add_filter( 'allow_password_reset', static function ( $allowed, $id ) {
	return cammino_tipsters_is_tipster( get_userdata( (int) $id ) ) ? false : $allowed;
}, PHP_INT_MAX, 2 );

/** Reject an already issued reset key too, before reset_password writes anything. */
add_action( 'password_reset', static function ( $user, $password ): void {
	if ( cammino_tipsters_is_tipster( $user ) ) {
		wp_die( esc_html__( 'Heslo tipstera môže zmeniť iba administrátor.', 'cammino' ), '', array( 'response' => 403 ) );
	}
}, 0, 2 );

/** Defense for wp_update_user calls made by frontend plugins. */
add_filter( 'wp_pre_insert_user_data', static function ( $data, $update, $id, $raw ) {
	if ( $update && cammino_tipsters_is_tipster( get_userdata( (int) $id ) ) && ! cammino_tipsters_can_manage() ) {
		$old = get_userdata( (int) $id );
		foreach ( array( 'user_pass', 'user_email', 'display_name', 'user_url', 'user_nicename', 'user_activation_key' ) as $field ) {
			$data[ $field ] = $old->$field;
		}
	}
	return $data;
}, PHP_INT_MAX, 4 );

add_action( 'user_profile_update_errors', static function ( $errors, $update, $user ): void {
	if ( $update && cammino_tipsters_is_tipster( get_userdata( (int) $user->ID ) ) && ! cammino_tipsters_can_manage() ) {
		$errors->add( 'cammino_admin_only', __( 'Účet tipstera môže upraviť iba administrátor.', 'cammino' ) );
	}
}, 10, 3 );

add_filter( 'rest_pre_insert_user', static function ( $user, $request ) {
	$id = (int) $request->get_param( 'id' );
	if ( $id && cammino_tipsters_is_tipster( get_userdata( $id ) ) && ! cammino_tipsters_can_manage() ) {
		return new WP_Error( 'cammino_admin_only', __( 'Účet tipstera môže upraviť iba administrátor.', 'cammino' ), array( 'status' => 403 ) );
	}
	return $user;
}, 10, 2 );

add_action( 'woocommerce_save_account_details_errors', static function ( $errors, $user ): void {
	if ( cammino_tipsters_is_tipster( get_userdata( (int) $user->ID ) ) ) {
		$errors->add( 'cammino_admin_only', __( 'Účet a heslo spravuje administrátor. Kontaktujte ho, ak potrebujete zmenu.', 'cammino' ) );
	}
}, 10, 2 );

add_action( 'profile_update', static function ( $id, $old ): void {
	$user = get_userdata( (int) $id );
	if ( cammino_tipsters_is_tipster( $user ) && $user->user_pass !== $old->user_pass ) {
		WP_Session_Tokens::get_instance( (int) $id )->destroy_all();
	}
}, 10, 2 );

add_action( 'wp_set_password', static function ( $password, $id ): void {
	if ( cammino_tipsters_is_tipster( get_userdata( (int) $id ) ) ) {
		WP_Session_Tokens::get_instance( (int) $id )->destroy_all();
	}
}, 10, 2 );

add_filter( 'registration_errors', static function ( $errors ) {
	if ( CAMMINO_TIPSTER_ROLE === get_option( 'default_role' ) ) {
		$errors->add( 'cammino_admin_only', __( 'Účty tipsterov vytvára iba administrátor.', 'cammino' ) );
	}
	return $errors;
} );

add_filter( 'send_password_change_email', static function ( $send, $user, $data ) {
	return cammino_tipsters_is_tipster( get_userdata( (int) $user['ID'] ) ) ? false : $send;
}, 10, 3 );
