<?php
/** Administrator account operations and complete record cleanup. @package Cammino */
defined( 'ABSPATH' ) || exit;

function cammino_tipsters_validate_password( string $password ) {
	if ( mb_strlen( $password, 'UTF-8' ) < 8 || strlen( $password ) > 4096 || trim( $password ) !== $password ) {
		return new WP_Error( 'invalid_password', __( 'Heslo musí mať aspoň 8 znakov, najviac 4096 bajtov a nesmie začínať ani končiť medzerou.', 'cammino' ) );
	}
	return true;
}

function cammino_tipsters_create_account( string $username, string $password, string $name = '' ) {
	if ( ! cammino_tipsters_can_manage() ) {
		return new WP_Error( 'forbidden', __( 'Nemáte oprávnenie spravovať tipsterov.', 'cammino' ) );
	}
	if ( '' === $username || strlen( $username ) > 60 || sanitize_user( $username, true ) !== $username || ! validate_username( $username ) || username_exists( $username ) ) {
		return new WP_Error( 'invalid_username', __( 'Zadajte jedinečné používateľské meno (najviac 60 znakov, bez diakritiky).', 'cammino' ) );
	}
	$valid = cammino_tipsters_validate_password( $password );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}
	return wp_insert_user( array(
		'user_login' => $username, 'user_pass' => $password, 'user_email' => '',
		'display_name' => sanitize_text_field( '' === trim( $name ) ? $username : $name ),
		'role' => CAMMINO_TIPSTER_ROLE, 'show_admin_bar_front' => 'false',
		'meta_input' => array( CAMMINO_TIPSTER_STATE_META => 'enabled' ),
	) );
}

function cammino_tipsters_update_account( int $id, string $name, string $password = '' ) {
	if ( ! cammino_tipsters_can_manage() || ! cammino_tipsters_managed_account( $id ) ) {
		return new WP_Error( 'forbidden', __( 'Tento účet nemôžete upraviť.', 'cammino' ) );
	}
	$data = array( 'ID' => $id, 'display_name' => sanitize_text_field( $name ) );
	if ( '' === trim( $data['display_name'] ) ) {
		return new WP_Error( 'invalid_name', __( 'Zadajte zobrazované meno.', 'cammino' ) );
	}
	if ( '' !== $password ) {
		$valid = cammino_tipsters_validate_password( $password );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$data['user_pass'] = $password;
		$data['user_activation_key'] = '';
	}
	$result = wp_update_user( $data );
	if ( ! is_wp_error( $result ) && '' !== $password ) {
		WP_Session_Tokens::get_instance( $id )->destroy_all();
	}
	return $result;
}

function cammino_tipsters_set_enabled( int $id, bool $enabled ) {
	if ( ! cammino_tipsters_can_manage() || ! cammino_tipsters_managed_account( $id ) ) {
		return new WP_Error( 'forbidden', __( 'Tento účet nemôžete upraviť.', 'cammino' ) );
	}
	$lock = cammino_tipsters_write_lock( $id );
	if ( is_wp_error( $lock ) ) {
		return $lock;
	}
	try {
		return cammino_tipsters_set_enabled_locked( $id, $enabled );
	} finally {
		cammino_tipsters_release_lock( $lock );
	}
}

function cammino_tipsters_set_enabled_locked( int $id, bool $enabled ) {
	if ( ! cammino_tipsters_can_manage() || ! cammino_tipsters_managed_account( $id ) ) {
		return new WP_Error( 'forbidden', __( 'Tento účet nemôžete upraviť.', 'cammino' ) );
	}
	if ( 'deleting' === get_user_meta( $id, CAMMINO_TIPSTER_STATE_META, true ) ) {
		return new WP_Error( 'deleting', __( 'Mazanie účtu ešte nebolo dokončené. Dokončite ho opätovným potvrdením odstránenia.', 'cammino' ) );
	}
	$state = $enabled ? 'enabled' : 'disabled';
	update_user_meta( $id, CAMMINO_TIPSTER_STATE_META, $state );
	if ( $state !== get_user_meta( $id, CAMMINO_TIPSTER_STATE_META, true ) ) {
		return new WP_Error( 'state_failed', __( 'Stav účtu sa nepodarilo uložiť. Skúste to znova.', 'cammino' ) );
	}
	if ( ! $enabled ) {
		WP_Session_Tokens::get_instance( $id )->destroy_all();
	}
	return true;
}

/** Include drafts, trash, and soft-deleted tips in deletion totals. */
function cammino_tipsters_account_records( int $id ): array {
	$tips = get_posts( array(
		'post_type' => CAMMINO_TIP_POST_TYPE, 'author' => $id,
		'post_status' => array_keys( get_post_stati() ), 'numberposts' => -1, 'fields' => 'ids',
	) );
	$messages = get_posts( array(
		'post_type' => CAMMINO_MESSAGE_POST_TYPE, 'author' => $id,
		'post_status' => array_keys( get_post_stati() ), 'numberposts' => -1, 'fields' => 'ids',
	) );
	$files = array();
	foreach ( $tips as $tip ) {
		$messages = array_merge( $messages, get_posts( array(
			'post_type' => CAMMINO_MESSAGE_POST_TYPE,
			'post_status' => array_keys( get_post_stati() ), 'numberposts' => -1, 'fields' => 'ids',
			'meta_key' => CAMMINO_MESSAGE_TIP_META, 'meta_value' => $tip,
		) ) );
		$messages = array_merge( $messages, get_posts( array( 'post_type' => CAMMINO_MESSAGE_POST_TYPE, 'post_status' => array_keys( get_post_stati() ), 'numberposts' => -1, 'fields' => 'ids', 'post_parent' => $tip ) ) );
		foreach ( cammino_tipsters_all_tip_files( (int) $tip ) as $file ) {
			if ( is_array( $file ) && isset( $file['path'] ) ) {
				$files[] = $file;
			}
		}
	}
	return array( 'tips' => array_map( 'intval', $tips ), 'messages' => array_unique( array_map( 'intval', $messages ) ), 'files' => $files );
}

/** Resolve only relative paths within explicitly configured private storage. */
function cammino_tipsters_private_file( string $relative ) {
	if ( ! defined( 'CAMMINO_TIPSTERS_STORAGE_PATH' ) || ! realpath( CAMMINO_TIPSTERS_STORAGE_PATH ) ) {
		return new WP_Error( 'storage_missing', __( 'Súkromné úložisko nie je nakonfigurované. Súbory neboli odstránené.', 'cammino' ) );
	}
	$relative = wp_normalize_path( $relative );
	if ( '' === $relative || preg_match( '~(^/|:|(^|/)\.\.(/|$)|\x00)~i', $relative ) ) {
		return new WP_Error( 'unsafe_path', __( 'Neplatná cesta súboru. Mazanie bolo zastavené.', 'cammino' ) );
	}
	$root = trailingslashit( wp_normalize_path( realpath( CAMMINO_TIPSTERS_STORAGE_PATH ) ) );
	$path = $root . $relative;
	// Resolve the parent as well, so a missing file behind a symlink is not accepted.
	$parent = realpath( dirname( $path ) );
	if ( ! $parent || 0 !== strpos( trailingslashit( wp_normalize_path( $parent ) ), $root ) || is_link( $path ) ) {
		return new WP_Error( 'unsafe_path', __( 'Súbor nie je v súkromnom úložisku. Mazanie bolo zastavené.', 'cammino' ) );
	}
	if ( file_exists( $path ) && ( ! is_file( $path ) || 0 !== strpos( wp_normalize_path( realpath( $path ) ), $root ) ) ) {
		return new WP_Error( 'unsafe_path', __( 'Neplatná cesta súboru. Mazanie bolo zastavené.', 'cammino' ) );
	}
	return $path;
}

/** Safe to retry: keep file metadata until all unlink operations succeed. */
function cammino_tipsters_purge_records( int $id ) {
	$lock = cammino_tipsters_write_lock( $id );
	if ( is_wp_error( $lock ) ) {
		return $lock;
	}
	try {
		return cammino_tipsters_purge_records_locked( $id );
	} finally {
		cammino_tipsters_release_lock( $lock );
	}
}

function cammino_tipsters_purge_records_locked( int $id ) {
	update_user_meta( $id, CAMMINO_TIPSTER_STATE_META, 'deleting' );
	if ( 'deleting' !== get_user_meta( $id, CAMMINO_TIPSTER_STATE_META, true ) ) {
		return new WP_Error( 'state_failed', __( 'Účet sa nepodarilo zablokovať. Mazanie bolo zastavené.', 'cammino' ) );
	}
	WP_Session_Tokens::get_instance( $id )->destroy_all();
	$records = cammino_tipsters_account_records( $id );
	$paths = array();
	foreach ( $records['files'] as $file ) {
		$path = cammino_tipsters_private_file( (string) $file['path'] );
		if ( is_wp_error( $path ) ) {
			return $path;
		}
		$paths[] = $path;
	}
	foreach ( array_unique( $paths ) as $path ) {
		if ( file_exists( $path ) && ! unlink( $path ) ) {
			return new WP_Error( 'file_delete_failed', __( 'Súbor sa nepodarilo odstrániť. Účet zostáva zablokovaný; zopakujte mazanie.', 'cammino' ) );
		}
	}
	foreach ( array_merge( $records['messages'], $records['tips'] ) as $post_id ) {
		if ( CAMMINO_TIP_POST_TYPE === get_post_type( $post_id ) ) {
			foreach ( get_post_meta( $post_id, '_cammino_message_draft_user' ) as $sender ) {
				delete_transient( 'cammino_message_draft_' . (int) $sender . '_' . $post_id );
			}
		}
		if ( ! ( CAMMINO_MESSAGE_POST_TYPE === get_post_type( $post_id ) ? cammino_tipsters_delete_message_record( $post_id ) : wp_delete_post( $post_id, true ) ) ) {
			return new WP_Error( 'record_delete_failed', __( 'Záznam sa nepodarilo odstrániť. Účet zostáva zablokovaný; zopakujte mazanie.', 'cammino' ) );
		}
	}
	return true;
}

function cammino_tipsters_delete_account( int $id ) {
	if ( ! cammino_tipsters_can_manage() || ! cammino_tipsters_managed_account( $id ) ) {
		return new WP_Error( 'forbidden', __( 'Tento účet nemôžete odstrániť.', 'cammino' ) );
	}
	if ( is_multisite() ) {
		return new WP_Error( 'multisite', __( 'Úplné odstránenie účtu je podporované iba na samostatnej WordPress inštalácii.', 'cammino' ) );
	}
	$result = cammino_tipsters_purge_records( $id );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}
	return wp_delete_user( $id ) ? true : new WP_Error( 'delete_failed', __( 'Účet sa nepodarilo odstrániť. Zopakujte mazanie.', 'cammino' ) );
}

// Native Users -> Delete must not leave private business records/files behind.
add_action( 'delete_user', static function ( $id ): void {
	if ( ! cammino_tipsters_is_tipster( get_userdata( (int) $id ) ) ) {
		return;
	}
	if ( ! cammino_tipsters_can_manage() || ! cammino_tipsters_managed_account( (int) $id ) ) {
		wp_die( esc_html__( 'Účet odstráňte cez správu tipsterov ako administrátor.', 'cammino' ), '', array( 'response' => 403 ) );
	}
	$result = cammino_tipsters_purge_records( (int) $id );
	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 500 ) );
	}
}, 0 );
