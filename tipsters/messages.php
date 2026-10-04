<?php
/** Persistent per-tip conversations. @package Cammino */
defined( 'ABSPATH' ) || exit;

function cammino_tipsters_can_message( int $id ): bool {
	$tip = get_post( $id );
	return $tip && CAMMINO_TIP_POST_TYPE === $tip->post_type && cammino_tipsters_can_read_tip( $id )
		&& cammino_tipsters_account_enabled( (int) $tip->post_author )
		&& in_array( cammino_tipsters_tip_status( $id ), array( 'discussion', 'approved' ), true );
}

/** Token binds a retry to both its sender and tip, independently of the form nonce. */
function cammino_tipsters_message_token( int $id ): string {
	$value = cammino_tipsters_submission_token();
	return $value . '.' . hash_hmac( 'sha256', $id . ':' . $value, wp_salt( 'nonce' ) );
}

function cammino_tipsters_valid_message_token( int $id, string $token ): bool {
	$split = strrpos( $token, '.' );
	if ( false === $split ) { return false; }
	$value = substr( $token, 0, $split );
	return cammino_tipsters_valid_submission_token( $value )
		&& hash_equals( hash_hmac( 'sha256', $id . ':' . $value, wp_salt( 'nonce' ) ), substr( $token, $split + 1 ) );
}

function cammino_tipsters_send_message( int $id, string $body, string $token ) {
	if ( ! cammino_tipsters_can_message( $id ) ) { return new WP_Error( 'closed', __( 'Komunikácia pri tomto tipe nie je dostupná.', 'cammino' ) ); }
	if ( ! cammino_tipsters_valid_message_token( $id, $token ) ) { return new WP_Error( 'expired', __( 'Platnosť formulára vypršala. Obnovte stránku.', 'cammino' ) ); }
	$text = trim( sanitize_textarea_field( $body ) );
	if ( '' === $text || mb_strlen( $body, 'UTF-8' ) > 5000 ) { return new WP_Error( 'message', __( 'Správa musí obsahovať 1 až 5 000 znakov.', 'cammino' ) ); }
	$tip = get_post( $id ); $owner = (int) $tip->post_author;
	$lock = cammino_tipsters_write_lock( $owner );
	if ( is_wp_error( $lock ) ) { return $lock; }
	try {
		clean_post_cache( $id ); wp_cache_delete( $owner, 'user_meta' );
		$tip = get_post( $id );
		if ( ! $tip || (int) $tip->post_author !== $owner || ! cammino_tipsters_can_message( $id ) || ! cammino_tipsters_lock_owned( $lock ) ) {
			return new WP_Error( 'closed', __( 'Komunikácia pri tomto tipe nie je dostupná.', 'cammino' ) );
		}
		$hash = hash( 'sha256', $token );
		$previous = get_posts( array( 'post_type' => CAMMINO_MESSAGE_POST_TYPE, 'post_status' => 'private', 'post_parent' => $id,
			'author' => get_current_user_id(), 'name' => 'message-' . $hash, 'numberposts' => 1 ) );
		if ( $previous ) { return (int) $previous[0]->ID; }
		// Linkage, sender, body, token and time live in one database row. Metadata is a legacy cleanup mirror.
		$now = gmdate( 'Y-m-d H:i:s' );
		$message_id = cammino_tipsters_save_plain_post( array(
			'post_type' => CAMMINO_MESSAGE_POST_TYPE, 'post_status' => 'private', 'post_parent' => $id,
			'post_author' => get_current_user_id(), 'post_title' => wp_get_current_user()->user_login,
			'post_content' => $text, 'post_name' => 'message-' . $hash,
			'post_date_gmt' => $now, 'post_date' => get_date_from_gmt( $now ),
			'meta_input' => array( CAMMINO_MESSAGE_TIP_META => $id ),
		) );
		if ( is_wp_error( $message_id ) ) { return new WP_Error( 'save_failed', __( 'Správu sa nepodarilo uložiť. Skúste to znova.', 'cammino' ) ); }
		$saved = get_post( $message_id );
		if ( ! $saved || $saved->post_content !== $text || (int) $saved->post_parent !== $id || (int) $saved->post_author !== get_current_user_id()
			|| $saved->post_name !== 'message-' . $hash || 'private' !== $saved->post_status || $saved->post_date_gmt !== $now || ! cammino_tipsters_lock_owned( $lock ) ) {
			cammino_tipsters_delete_message_record( (int) $message_id );
			return new WP_Error( 'save_failed', __( 'Správu sa nepodarilo uložiť. Skúste to znova.', 'cammino' ) );
		}
		return (int) $message_id;
	} finally { cammino_tipsters_release_lock( $lock ); }
}

/** Query only after checking the containing tip; older metadata-linked records remain readable. */
function cammino_tipsters_messages( int $id, int $page = 1 ): WP_Query {
	$filter = static function ( $where, $query ) use ( $id ) {
		global $wpdb;
		if ( $query->get( 'cammino_conversation' ) === $id ) {
			$where .= $wpdb->prepare( " AND ({$wpdb->posts}.post_parent = %d OR ({$wpdb->posts}.post_parent = 0 AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} cm WHERE cm.post_id = {$wpdb->posts}.ID AND cm.meta_key = %s AND cm.meta_value = %s)))", $id, CAMMINO_MESSAGE_TIP_META, (string) $id );
		}
		return $where;
	};
	add_filter( 'posts_where', $filter, 10, 2 );
	try {
		return new WP_Query( array( 'post_type' => CAMMINO_MESSAGE_POST_TYPE, 'post_status' => 'private',
			'post__in' => cammino_tipsters_can_read_tip( $id ) && cammino_tipsters_tip_ready( $id ) ? array() : array( 0 ),
			'cammino_conversation' => $id, 'posts_per_page' => 20, 'paged' => max( 1, $page ), 'orderby' => array( 'date' => 'ASC', 'ID' => 'ASC' ) ) );
	} finally { remove_filter( 'posts_where', $filter, 10 ); }
}

/** Internal cleanup only: full account purge or rollback of a failed insert. */
function cammino_tipsters_delete_message_record( int $id ) {
	$previous = $GLOBALS['cammino_tipsters_message_cleanup'] ?? 0;
	$GLOBALS['cammino_tipsters_message_cleanup'] = $id;
	try { return wp_delete_post( $id, true ); }
	finally { $GLOBALS['cammino_tipsters_message_cleanup'] = $previous; }
}

// Native editors, trash, APIs and ordinary wp_update_post/wp_delete_post cannot alter saved messages.
add_filter( 'wp_insert_post_data', static function ( $data, $input ) {
	$old = empty( $input['ID'] ) ? null : get_post( (int) $input['ID'] );
	if ( $old && CAMMINO_MESSAGE_POST_TYPE === $old->post_type ) {
		foreach ( $data as $key => $value ) { if ( property_exists( $old, $key ) ) { $data[ $key ] = wp_slash( $old->$key ); } }
	}
	return $data;
}, PHP_INT_MAX, 2 );
add_filter( 'pre_delete_post', static function ( $result, $post ) {
	return CAMMINO_MESSAGE_POST_TYPE === $post->post_type && ( $GLOBALS['cammino_tipsters_message_cleanup'] ?? 0 ) !== (int) $post->ID ? false : $result;
}, PHP_INT_MAX, 2 );
add_filter( 'pre_trash_post', static function ( $result, $post ) {
	return CAMMINO_MESSAGE_POST_TYPE === $post->post_type ? false : $result;
}, PHP_INT_MAX, 2 );

// Preserve the linkage of older metadata-only conversation records too.
foreach ( array( 'add', 'update', 'delete' ) as $operation ) {
	add_filter( $operation . '_post_metadata', static function ( $result, $id, $key ) use ( $operation ) {
		if ( CAMMINO_MESSAGE_TIP_META === $key && CAMMINO_MESSAGE_POST_TYPE === get_post_type( $id )
			&& ( $GLOBALS['cammino_tipsters_message_cleanup'] ?? 0 ) !== (int) $id
			&& ( 'delete' === $operation || metadata_exists( 'post', $id, $key ) ) ) { return false; }
		return $result;
	}, PHP_INT_MAX, 3 );
}

function cammino_tipsters_message_redirect_url( int $id, bool $admin = false ): string {
	$url = $admin ? cammino_tipsters_admin_tips_url( array( 'tip_id' => $id ) ) : cammino_tipsters_url( 'tip', $id );
	$page = max( 1, (int) cammino_tipsters_messages( $id )->max_num_pages );
	return add_query_arg( 'messages_page', $page, $url ) . '#conversation';
}

function cammino_tipsters_render_conversation( int $id, bool $admin = false ): void {
	if ( ! cammino_tipsters_can_read_tip( $id ) || ! cammino_tipsters_tip_ready( $id ) ) { return; }
	$page = isset( $_GET['messages_page'] ) && is_scalar( $_GET['messages_page'] ) ? max( 1, absint( $_GET['messages_page'] ) ) : 1;
	$query = cammino_tipsters_messages( $id, $page );
	$url = $admin ? cammino_tipsters_admin_tips_url( array( 'tip_id' => $id ) ) : cammino_tipsters_url( 'tip', $id );
	$error = $GLOBALS['cammino_tipsters_message_error'] ?? null;
	$draft = $GLOBALS['cammino_tipsters_message_draft'] ?? array();
	$body = $draft['body'] ?? ( 'send_message' === cammino_tipsters_input( 'operation' ) ? cammino_tipsters_input( 'message_body' ) : '' );
	$token = $draft['token'] ?? cammino_tipsters_input( 'message_token' );
	$token = cammino_tipsters_valid_message_token( $id, $token ) ? $token : cammino_tipsters_message_token( $id );
	require __DIR__ . '/templates/conversation.php';
}
