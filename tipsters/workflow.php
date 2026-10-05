<?php
/** Versioned private tip changes, status history, and retained attachments. @package Cammino */
defined( 'ABSPATH' ) || exit;

/** Existing Stage 2 tips work immediately, without a bulk migration or GET writes. */
function cammino_tipsters_tip_record( int $id ): array {
	$post = get_post( $id );
	if ( ! $post || CAMMINO_TIP_POST_TYPE !== $post->post_type ) {
		return array();
	}
	$record = get_post_meta( $id, CAMMINO_TIP_WORKFLOW_META, true );
	if ( is_array( $record ) && isset( $record['version'], $record['status'] ) ) {
		return $record;
	}
	$files = get_post_meta( $id, CAMMINO_TIP_FILES_META, true );
	$record = array(
		'title' => $post->post_title, 'short_description' => $post->post_excerpt, 'long_description' => $post->post_content,
		'file_link' => (string) get_post_meta( $id, '_cammino_tip_file_link', true ),
		'status' => (string) get_post_meta( $id, '_cammino_tip_status', true ),
		'files' => is_array( $files ) ? $files : array(), 'retained_files' => array(),
		'updated_at' => $post->post_modified_gmt, 'history' => array(), 'deleted' => array(),
	);
	$record['version'] = hash( 'sha256', wp_json_encode( $record ) );
	return $record;
}

function cammino_tipsters_tip_status( int $id ): string {
	return cammino_tipsters_tip_record( $id )['status'] ?? '';
}

function cammino_tipsters_tip_transitions( string $status ): array {
	return array( 'submitted' => array( 'discussion', 'approved', 'rejected' ), 'discussion' => array( 'approved', 'rejected' ), 'approved' => array( 'discussion', 'rejected' ), 'rejected' => array( 'discussion' ) )[ $status ] ?? array();
}

function cammino_tipsters_tip_files( int $id, bool $include_retained = false ): array {
	$record = cammino_tipsters_tip_record( $id );
	return array_merge( $record['files'] ?? array(), $include_retained ? ( $record['retained_files'] ?? array() ) : array() );
}

/** Include pending/retained paths in account purges; deduplicate physical files. */
function cammino_tipsters_all_tip_files( int $id ): array {
	$files = array_merge(
		cammino_tipsters_tip_files( $id, true ),
		(array) get_post_meta( $id, CAMMINO_TIP_FILES_META, true ),
		(array) get_post_meta( $id, CAMMINO_TIP_PENDING_FILES_META, true )
	);
	$result = array();
	foreach ( $files as $file ) {
		if ( is_array( $file ) && isset( $file['path'] ) ) { $result[ $file['path'] ] = $file; }
	}
	return array_values( $result );
}

function cammino_tipsters_lock_owned( array $lock ): bool {
	global $wpdb;
	return maybe_serialize( $lock['value'] ) === $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $lock['key'] ) );
}

/** Every change shares the account lock with status changes, disabling, and purge. */
function cammino_tipsters_mutate_tip( int $id, string $version, callable $operation, bool $purge = false ) {
	$post = get_post( $id );
	if ( ! $post || CAMMINO_TIP_POST_TYPE !== $post->post_type ) {
		return new WP_Error( 'not_found', __( 'Tip nie je dostupný.', 'cammino' ) );
	}
	$owner = (int) $post->post_author;
	if ( ! cammino_tipsters_can_manage() && ( get_current_user_id() !== $owner || ! cammino_tipsters_account_enabled( $owner ) ) ) {
		return new WP_Error( 'forbidden', __( 'Tento tip nemôžete zmeniť.', 'cammino' ) );
	}
	$lock = cammino_tipsters_write_lock( $owner );
	if ( is_wp_error( $lock ) ) { return $lock; }
	try {
		clean_post_cache( $id );
		wp_cache_delete( $owner, 'user_meta' );
		$post = get_post( $id );
		if ( ! $post || CAMMINO_TIP_POST_TYPE !== $post->post_type || (int) $post->post_author !== $owner
			|| ! cammino_tipsters_managed_account( $owner ) || 'deleting' === get_user_meta( $owner, CAMMINO_TIPSTER_STATE_META, true ) ) {
			return new WP_Error( 'not_found', __( 'Tip alebo jeho účet už nie je dostupný.', 'cammino' ) );
		}
		if ( ! cammino_tipsters_can_manage() && ( ! cammino_tipsters_account_enabled( $owner ) || ! cammino_tipsters_can_read_tip( $id ) ) ) {
			return new WP_Error( 'forbidden', __( 'Tento tip nemôžete zmeniť.', 'cammino' ) );
		}
		$record = cammino_tipsters_tip_record( $id );
		if ( ! $purge && get_post_meta( $id, '_cammino_tip_purging', true ) ) { return new WP_Error( 'purging', __( 'Mazanie tipu nie je dokončené. Administrátor musí zopakovať odstránenie.', 'cammino' ) ); }
		if ( ! cammino_tipsters_tip_ready( $id ) || ! preg_match( '/^[a-f0-9]{64}$/', $version ) || ! hash_equals( $record['version'], $version ) ) {
			return new WP_Error( 'stale', __( 'Tip bol medzičasom zmenený. Obnovte jeho detail a skontrolujte aktuálny stav pred ďalšou zmenou.', 'cammino' ) );
		}
		return $operation( $record, $lock );
	} finally {
		cammino_tipsters_release_lock( $lock );
	}
}

/** Mirror fields for WordPress search/indexing; the aggregate is the authoritative view. */
function cammino_tipsters_mirror_record( int $id, array $record ) {
	$result = cammino_tipsters_save_plain_post( array(
		'ID' => $id, 'post_title' => $record['title'], 'post_excerpt' => $record['short_description'], 'post_content' => $record['long_description'],
		'post_modified_gmt' => $record['updated_at'], 'post_modified' => get_date_from_gmt( $record['updated_at'] ),
	), true );
	if ( is_wp_error( $result ) ) { return false; }
	update_post_meta( $id, '_cammino_tip_status', $record['status'] );
	update_post_meta( $id, CAMMINO_TIP_FILES_META, wp_slash( $record['files'] ) );
	$post = get_post( $id );
	return $post && $post->post_title === $record['title'] && $post->post_excerpt === $record['short_description'] && $post->post_content === $record['long_description']
		&& get_post_meta( $id, '_cammino_tip_status', true ) === $record['status'] && get_post_meta( $id, CAMMINO_TIP_FILES_META, true ) === $record['files'];
}

/** Commit status/content/files/history together in one metadata value with compare-and-swap. */
function cammino_tipsters_commit_record( int $id, array $old, array $next, array $lock ) {
	if ( ! cammino_tipsters_lock_owned( $lock ) ) { return new WP_Error( 'busy', __( 'Platnosť operácie vypršala. Obnovte stránku.', 'cammino' ) ); }
	$stored = get_post_meta( $id, CAMMINO_TIP_WORKFLOW_META, true );
	if ( ! is_array( $stored ) ) {
		// Establish the old authoritative record before any mirror can change.
		add_post_meta( $id, CAMMINO_TIP_WORKFLOW_META, wp_slash( $old ), true );
		$stored = get_post_meta( $id, CAMMINO_TIP_WORKFLOW_META, true );
	}
	if ( $stored !== $old ) { return new WP_Error( 'save_failed', __( 'Zmenu sa nepodarilo uložiť. Obnovte stránku a skúste to znova.', 'cammino' ) ); }
	$next['version'] = bin2hex( random_bytes( 32 ) );
	$next['updated_at'] = gmdate( 'Y-m-d H:i:s' );
	if ( ! cammino_tipsters_mirror_record( $id, $next ) || ! cammino_tipsters_lock_owned( $lock ) ) {
		cammino_tipsters_mirror_record( $id, $old );
		return new WP_Error( 'save_failed', __( 'Zmenu sa nepodarilo uložiť. Obnovte stránku a skúste to znova.', 'cammino' ) );
	}
	update_post_meta( $id, CAMMINO_TIP_WORKFLOW_META, wp_slash( $next ), $old );
	if ( get_post_meta( $id, CAMMINO_TIP_WORKFLOW_META, true ) !== $next ) {
		cammino_tipsters_mirror_record( $id, $old );
		return new WP_Error( 'save_failed', __( 'Zmenu sa nepodarilo uložiť. Obnovte stránku a skúste to znova.', 'cammino' ) );
	}
	return true;
}

function cammino_tipsters_history_event( string $type, array $data = array() ): array {
	return array_merge( array( 'type' => $type, 'actor' => get_current_user_id(), 'time' => gmdate( 'Y-m-d H:i:s' ) ), $data );
}

function cammino_tipsters_change_status( int $id, string $status, string $version, bool $confirm_reopen = false ) {
	if ( ! cammino_tipsters_can_manage() ) { return new WP_Error( 'forbidden', __( 'Stav môže meniť iba administrátor.', 'cammino' ) ); }
	return cammino_tipsters_mutate_tip( $id, $version, static function ( $old, $lock ) use ( $id, $status, $confirm_reopen ) {
		if ( ! in_array( $status, cammino_tipsters_tip_transitions( $old['status'] ), true ) ) {
			return new WP_Error( 'transition', __( 'Táto zmena stavu nie je povolená.', 'cammino' ) );
		}
		if ( 'discussion' === $status && in_array( $old['status'], array( 'approved', 'rejected' ), true ) && ! $confirm_reopen ) {
			return new WP_Error( 'confirmation', __( 'Potvrďte opätovné otvorenie tipu.', 'cammino' ) );
		}
		$next = $old; $next['status'] = $status;
		$next['history'][] = cammino_tipsters_history_event( 'status', array( 'from' => $old['status'], 'to' => $status ) );
		return cammino_tipsters_commit_record( $id, $old, $next, $lock );
	} );
}

function cammino_tipsters_delete_tip( int $id, string $version, bool $confirmed = false ) {
	if ( ! cammino_tipsters_is_tipster( wp_get_current_user() ) || ! $confirmed ) {
		return new WP_Error( 'confirmation', __( 'Odstránenie musí potvrdiť vlastník tipu.', 'cammino' ) );
	}
	return cammino_tipsters_mutate_tip( $id, $version, static function ( $old, $lock ) use ( $id ) {
		$next = $old; $next['status'] = 'deleted_by_tipster';
		$next['deleted'] = array( 'previous_status' => $old['status'], 'actor' => get_current_user_id(), 'time' => gmdate( 'Y-m-d H:i:s' ) );
		$next['history'][] = cammino_tipsters_history_event( 'deleted', array( 'from' => $old['status'], 'to' => 'deleted_by_tipster' ) );
		return cammino_tipsters_commit_record( $id, $old, $next, $lock );
	} );
}

function cammino_tipsters_edit_tip( int $id, array $input, array $files, array $remove, string $version ) {
	if ( ! cammino_tipsters_is_tipster( wp_get_current_user() ) ) { return new WP_Error( 'forbidden', __( 'Obsah môže upraviť iba vlastník tipu.', 'cammino' ) ); }
	return cammino_tipsters_mutate_tip( $id, $version, static function ( $old, $lock ) use ( $id, $input, $files, $remove ) {
		if ( 'discussion' !== $old['status'] ) { return new WP_Error( 'locked', __( 'Formulár je uzamknutý. Úpravy sú povolené iba v diskusii.', 'cammino' ) ); }
		$data = cammino_tipsters_validate_tip( $input );
		if ( is_wp_error( $data ) ) { return $data; }
		$uploads = cammino_tipsters_upload_inputs( $files );
		if ( is_wp_error( $uploads ) ) { return $uploads; }
		if ( $uploads || $remove ) { return new WP_Error( 'files', __( 'Použite odkaz na zdieľané súbory. Staršie prílohy zostávajú zachované.', 'cammino' ) ); }
		$next = array_merge( $old, $data );
		$fields = array();
		foreach ( $data as $key => $value ) { if ( ( $old[ $key ] ?? '' ) !== $value ) { $fields[] = $key; } }
		if ( ! $fields ) { return true; }
		$next['history'][] = cammino_tipsters_history_event( 'edited', array( 'fields' => $fields, 'added' => array(), 'removed' => array() ) );
		return cammino_tipsters_commit_record( $id, $old, $next, $lock );
	} );
}

/** Explicit admin purge, serialized with writes/account cleanup and safe to retry. */
function cammino_tipsters_admin_delete_tip( int $id, string $version, string $title, bool $confirmed = false ) {
	if ( ! cammino_tipsters_can_manage() ) { return new WP_Error( 'forbidden', __( 'Tip môže natrvalo odstrániť iba administrátor.', 'cammino' ) ); }
	if ( ! $confirmed ) { return new WP_Error( 'confirmation', __( 'Potvrďte trvalé odstránenie tipu.', 'cammino' ) ); }
	return cammino_tipsters_mutate_tip( $id, $version, static function ( $record, $lock ) use ( $id, $title ) {
		if ( $title !== $record['title'] ) { return new WP_Error( 'confirmation', __( 'Pre potvrdenie zadajte presný názov tipu.', 'cammino' ) ); }
		update_post_meta( $id, '_cammino_tip_purging', 1 );
		if ( ! get_post_meta( $id, '_cammino_tip_purging', true ) ) { return new WP_Error( 'save_failed', __( 'Tip sa nepodarilo zablokovať na odstránenie.', 'cammino' ) ); }
		$paths = array();
		foreach ( cammino_tipsters_all_tip_files( $id ) as $file ) {
			$path = cammino_tipsters_private_file( (string) $file['path'] );
			if ( is_wp_error( $path ) ) { return $path; }
			$paths[] = $path;
		}
		if ( ! cammino_tipsters_lock_owned( $lock ) ) { return new WP_Error( 'busy', __( 'Operácia vypršala. Zopakujte odstránenie.', 'cammino' ) ); }
		foreach ( array_unique( $paths ) as $path ) {
			if ( file_exists( $path ) && ! unlink( $path ) ) { return new WP_Error( 'cleanup', __( 'Súbor sa nepodarilo odstrániť. Tip je zablokovaný; zopakujte odstránenie.', 'cammino' ) ); }
		}
		foreach ( cammino_tipsters_tip_message_ids( $id ) as $message ) {
			if ( ! cammino_tipsters_delete_message_record( $message ) ) { return new WP_Error( 'cleanup', __( 'Správu sa nepodarilo odstrániť. Zopakujte odstránenie tipu.', 'cammino' ) ); }
		}
		foreach ( get_post_meta( $id, '_cammino_message_draft_user' ) as $sender ) { delete_transient( 'cammino_message_draft_' . (int) $sender . '_' . $id ); }
		return wp_delete_post( $id, true ) ? true : new WP_Error( 'cleanup', __( 'Tip sa nepodarilo odstrániť. Zopakujte odstránenie.', 'cammino' ) );
	}, true );
}
