<?php
/** Submission storage, validation, retry protection, and dashboard queries. @package Cammino */
defined( 'ABSPATH' ) || exit;

function cammino_tipsters_status_labels(): array {
	return array( 'submitted' => __( 'Odoslaný', 'cammino' ), 'discussion' => __( 'Diskusia', 'cammino' ), 'approved' => __( 'Schválený', 'cammino' ), 'rejected' => __( 'Zamietnutý', 'cammino' ), 'deleted_by_tipster' => __( 'Odstránený tipsterom', 'cammino' ) );
}

function cammino_tipsters_status_notice( string $status ): string {
	if ( 'rejected' === $status ) { return __( 'Tip je zamietnutý. Formulár aj komunikácia sú uzamknuté. Doterajšie správy zostávajú dostupné.', 'cammino' ); }
	if ( 'discussion' === $status ) { return __( 'Administrátor otvoril diskusiu. Úpravy sú povolené iba v diskusii. Ak bol tip znovu otvorený, obnovte stránku pred úpravou formulára.', 'cammino' ); }
	if ( 'approved' === $status ) { return __( 'Tip je schválený. Formulár je uzamknutý. Komunikácia zostáva otvorená.', 'cammino' ); }
	return __( 'Tip čaká na kontrolu administrátorom. Formulár je uzamknutý.', 'cammino' );
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
	$link = $input['file_link'] ?? '';
	if ( ! is_string( $link ) ) { $link = 'invalid'; }
	$link = trim( $link );
	$parts = wp_parse_url( $link );
	if ( '' !== $link && ( strlen( $link ) > 2048 || preg_match( '/[\x00-\x20\x7f<>"\\\\]/', $link )
		|| ! is_array( $parts ) || empty( $parts['host'] ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true )
		|| isset( $parts['user'] ) || isset( $parts['pass'] ) || esc_url_raw( $link, array( 'http', 'https' ) ) !== $link ) ) {
		$errors->add( 'file_link', __( 'Zadajte platný odkaz HTTP alebo HTTPS (najviac 2 048 znakov).', 'cammino' ) );
	}
	$data['file_link'] = $link;
	return $errors->has_errors() ? $errors : $data;
}

/** These private fields are already sanitized plain text and escaped on output.
 * WordPress's HTML filters otherwise encode literal ampersands in tipster writes.
 * Suspend only those core filters during this write, restoring their exact priorities.
 */
function cammino_tipsters_save_plain_post( array $fields, bool $update = false ) {
	$filters = array( 'title_save_pre' => 'wp_filter_kses', 'content_save_pre' => 'wp_filter_post_kses', 'excerpt_save_pre' => 'wp_filter_post_kses' );
	$priorities = array();
	foreach ( $filters as $hook => $callback ) {
		$priority = has_filter( $hook, $callback );
		if ( false !== $priority ) { $priorities[ $hook ] = $priority; remove_filter( $hook, $callback, $priority ); }
	}
	try { return $update ? wp_update_post( wp_slash( $fields ), true ) : wp_insert_post( wp_slash( $fields ), true ); }
	finally { foreach ( $priorities as $hook => $priority ) { add_filter( $hook, $filters[ $hook ], $priority ); } }
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
		if ( $uploads ) { return new WP_Error( 'files', __( 'Nahrávanie súborov bolo nahradené odkazom na zdieľané súbory.', 'cammino' ) ); }
		if ( ! cammino_tipsters_account_enabled( $owner ) ) {
			return new WP_Error( 'forbidden', __( 'Účet nie je aktívny.', 'cammino' ) );
		}
		$id = cammino_tipsters_save_plain_post( array(
			'post_type' => CAMMINO_TIP_POST_TYPE, 'post_status' => 'private', 'post_author' => $owner,
			'post_title' => $data['title'], 'post_excerpt' => $data['short_description'], 'post_content' => $data['long_description'],
			'meta_input' => array( '_cammino_tip_status' => 'building', '_cammino_tip_submission' => hash( 'sha256', $token ), '_cammino_tip_file_link' => $data['file_link'] ),
		) );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		wp_cache_delete( $lock['key'], 'options' );
		if ( ! cammino_tipsters_account_enabled( $owner ) || get_option( $lock['key'] ) !== $lock['value'] ) {
			throw new RuntimeException( 'Account state changed.' );
		}
		if ( get_post_meta( $id, '_cammino_tip_file_link', true ) !== $data['file_link'] ) { throw new RuntimeException( 'Link storage failed.' ); }
		update_post_meta( $id, '_cammino_tip_status', 'submitted' );
		if ( 'submitted' !== get_post_meta( $id, '_cammino_tip_status', true ) ) {
			throw new RuntimeException( 'Submission failed.' );
		}
		return (int) $id;
	} catch ( Throwable $error ) {
		if ( $id && ! is_wp_error( $id ) ) { wp_delete_post( $id, true ); }
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
		'meta_query' => array( array( 'key' => '_cammino_tip_status', 'value' => array( 'submitted', 'discussion', 'approved', 'rejected' ), 'compare' => 'IN' ), array( 'key' => '_cammino_tip_purging', 'compare' => 'NOT EXISTS' ) ),
	) );
}
