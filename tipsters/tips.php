<?php
/** Submission storage, validation, retry protection, and dashboard queries. @package Cammino */
defined( 'ABSPATH' ) || exit;

function cammino_tipsters_status_labels(): array {
	return array( 'submitted' => __( 'Odoslaný', 'cammino' ), 'discussion' => __( 'Diskusia', 'cammino' ), 'approved' => __( 'Schválený', 'cammino' ), 'deleted_by_tipster' => __( 'Odstránený tipsterom', 'cammino' ) );
}

function cammino_tipsters_tip_ready( int $id ): bool {
	return in_array( cammino_tipsters_tip_status( $id ), array_keys( cammino_tipsters_status_labels() ), true );
}

function cammino_tipsters_validate_tip( array $input ) {
	$errors = new WP_Error();
	$data = array();
	foreach ( array( 'title' => 200, 'short_description' => 1000, 'long_description' => 20000 ) as $field => $max ) {
		$value = isset( $input[ $field ] ) && is_string( $input[ $field ] ) ? $input[ $field ] : '';
		$data[ $field ] = trim( 'title' === $field ? sanitize_text_field( $value ) : sanitize_textarea_field( $value ) );
		if ( '' === $data[ $field ] ) {
			$errors->add( $field, __( 'Toto pole je povinné.', 'cammino' ) );
		} elseif ( mb_strlen( $value, 'UTF-8' ) > $max ) {
			$errors->add( $field, sprintf( __( 'Zadajte najviac %d znakov.', 'cammino' ), $max ) );
		}
	}
	return $errors->has_errors() ? $errors : $data;
}

/** Signed one-day form identifiers are bound to the owner; nonces protect the POST. */
function cammino_tipsters_submission_token(): string {
	$value = bin2hex( random_bytes( 16 ) ) . '.' . time();
	return $value . '.' . hash_hmac( 'sha256', get_current_user_id() . ':' . $value, wp_salt( 'nonce' ) );
}

function cammino_tipsters_valid_submission_token( string $token ): bool {
	if ( ! preg_match( '/^([a-f0-9]{32})\.([0-9]{10})\.([a-f0-9]{64})$/', $token, $parts ) ) {
		return false;
	}
	return (int) $parts[2] <= time() && (int) $parts[2] >= time() - DAY_IN_SECONDS
		&& hash_equals( hash_hmac( 'sha256', get_current_user_id() . ':' . $parts[1] . '.' . $parts[2], wp_salt( 'nonce' ) ), $parts[3] );
}

/** Serialize submissions with disable/purge. Options have an atomic unique key. */
function cammino_tipsters_write_lock( int $user_id ) {
	global $wpdb;
	$key = 'cammino_tipster_write_' . $user_id;
	$value = array( 'token' => bin2hex( random_bytes( 16 ) ), 'time' => time() );
	// Read directly: cached missing/existing options must not affect mutual exclusion.
	$old = maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ) ) );
	if ( is_array( $old ) && (int) $old['time'] < time() - 30 * MINUTE_IN_SECONDS ) {
		// Compare-and-delete cannot erase a newer request's lock.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $key, maybe_serialize( $old ) ) );
		wp_cache_delete( $key, 'options' );
	}
	// add_option() upserts in some supported WP versions and is not a mutex.
	$inserted = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $key, maybe_serialize( $value ) ) );
	if ( 1 !== $inserted ) {
		return new WP_Error( 'busy', __( 'Pre tento účet práve prebieha iná operácia. Počkajte chvíľu a skúste to znova.', 'cammino' ) );
	}
	wp_cache_delete( $key, 'options' );
	wp_cache_delete( 'notoptions', 'options' );
	return array( 'key' => $key, 'value' => $value );
}

function cammino_tipsters_release_lock( array $lock ): void {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock['key'], maybe_serialize( $lock['value'] ) ) );
	wp_cache_delete( $lock['key'], 'options' );
	wp_cache_delete( 'notoptions', 'options' );
}

function cammino_tipsters_create_tip( array $input, array $files, string $token ) {
	$owner = get_current_user_id();
	if ( ! cammino_tipsters_account_enabled( $owner ) || ! cammino_tipsters_managed_account( $owner ) ) {
		return new WP_Error( 'forbidden', __( 'Nemáte oprávnenie odoslať tip.', 'cammino' ) );
	}
	if ( ! cammino_tipsters_valid_submission_token( $token ) ) {
		return new WP_Error( 'expired', __( 'Platnosť formulára vypršala. Obnovte stránku a skúste to znova.', 'cammino' ) );
	}
	$lock = cammino_tipsters_write_lock( $owner );
	if ( is_wp_error( $lock ) ) {
		return $lock;
	}
	$id = 0;
	$stored = array();
	try {
		$previous = get_posts( array( 'post_type' => CAMMINO_TIP_POST_TYPE, 'post_status' => 'private', 'author' => $owner, 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_cammino_tip_submission', 'meta_value' => hash( 'sha256', $token ) ) );
		if ( $previous && cammino_tipsters_tip_ready( (int) $previous[0] ) ) {
			return (int) $previous[0];
		}
		if ( $previous ) {
			return new WP_Error( 'incomplete', __( 'Predchádzajúce odoslanie sa nedokončilo. Kontaktujte administrátora.', 'cammino' ) );
		}
		$data = cammino_tipsters_validate_tip( $input );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$uploads = cammino_tipsters_upload_inputs( $files );
		if ( is_wp_error( $uploads ) ) {
			return $uploads;
		}
		$root = $uploads ? cammino_tipsters_storage_root() : '';
		if ( is_wp_error( $root ) ) {
			return $root;
		}
		$validated = array();
		foreach ( $uploads as $upload ) {
			if ( ! is_uploaded_file( $upload['tmp_name'] ) ) {
				return new WP_Error( 'files', __( 'Neplatný nahraný súbor.', 'cammino' ) );
			}
			$file = cammino_tipsters_validate_upload( $upload );
			if ( is_wp_error( $file ) ) {
				return $file;
			}
			$validated[] = $file;
		}
		if ( ! cammino_tipsters_account_enabled( $owner ) ) {
			return new WP_Error( 'forbidden', __( 'Účet nie je aktívny.', 'cammino' ) );
		}
		$id = wp_insert_post( wp_slash( array(
			'post_type' => CAMMINO_TIP_POST_TYPE, 'post_status' => 'private', 'post_author' => $owner,
			'post_title' => $data['title'], 'post_excerpt' => $data['short_description'], 'post_content' => $data['long_description'],
			'meta_input' => array( '_cammino_tip_status' => 'building', '_cammino_tip_submission' => hash( 'sha256', $token ) ),
		) ), true );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		foreach ( $validated as $file ) {
			$file['id'] = bin2hex( random_bytes( 16 ) );
			$file['path'] = bin2hex( random_bytes( 24 ) ) . '.' . $file['extension'];
			$source = $file['tmp_name'];
			unset( $file['tmp_name'], $file['extension'] );
			$stored[] = $file;
			// Record planned paths first, so interrupted uploads remain purgeable.
			update_post_meta( $id, CAMMINO_TIP_FILES_META, $stored );
			if ( $stored !== get_post_meta( $id, CAMMINO_TIP_FILES_META, true ) || ! move_uploaded_file( $source, $root . $file['path'] ) ) {
				throw new RuntimeException( 'File storage failed.' );
			}
			chmod( $root . $file['path'], 0600 );
		}
		wp_cache_delete( $lock['key'], 'options' );
		if ( ! cammino_tipsters_account_enabled( $owner ) || get_option( $lock['key'] ) !== $lock['value'] ) {
			throw new RuntimeException( 'Account state changed.' );
		}
		update_post_meta( $id, '_cammino_tip_status', 'submitted' );
		if ( 'submitted' !== get_post_meta( $id, '_cammino_tip_status', true ) ) {
			throw new RuntimeException( 'Submission failed.' );
		}
		return (int) $id;
	} catch ( Throwable $error ) {
		$clean = true;
		foreach ( $stored as $file ) {
			$path = cammino_tipsters_private_file( $file['path'] );
			if ( is_wp_error( $path ) || ( file_exists( $path ) && ! unlink( $path ) ) ) {
				$clean = false;
			}
		}
		if ( $id && ! is_wp_error( $id ) && $clean ) {
			wp_delete_post( $id, true );
		}
		return new WP_Error( 'save_failed', __( 'Tip sa nepodarilo uložiť. Skúste to znova alebo kontaktujte administrátora.', 'cammino' ) );
	} finally {
		cammino_tipsters_release_lock( $lock );
	}
}

function cammino_tipsters_own_tips( int $page = 1 ): WP_Query {
	return new WP_Query( array(
		'post_type' => CAMMINO_TIP_POST_TYPE, 'post_status' => 'private', 'author' => get_current_user_id(),
		'post__in' => cammino_tipsters_account_enabled( get_current_user_id() ) ? array() : array( 0 ),
		'posts_per_page' => 10, 'paged' => max( 1, $page ), 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ),
		'meta_query' => array( array( 'key' => '_cammino_tip_status', 'value' => array( 'submitted', 'discussion', 'approved' ), 'compare' => 'IN' ) ),
	) );
}
