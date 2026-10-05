<?php
/** Persistent per-tip conversations. @package Cammino */
defined( 'ABSPATH' ) || exit;

function cammino_tipsters_can_message( int $id ): bool {
	$tip = get_post( $id );
	return $tip && CAMMINO_TIP_POST_TYPE === $tip->post_type && cammino_tipsters_can_read_tip( $id )
		&& ! get_post_meta( $id, '_cammino_tip_purging', true )
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
	if ( '' === $text || mb_strlen( $body, 'UTF-8' ) > CAMMINO_MESSAGE_MAX_LENGTH ) { return new WP_Error( 'message', sprintf( __( 'Správa musí obsahovať 1 až %d znakov.', 'cammino' ), CAMMINO_MESSAGE_MAX_LENGTH ) ); }
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

/** Internal cleanup only: confirmed tip/account purge or failed-insert rollback. */
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
	// Capture the watermark before querying history so a concurrent insert is never missed.
	$cursor = cammino_tipsters_message_cursor( $id );
	$page = isset( $_GET['messages_page'] ) && is_scalar( $_GET['messages_page'] ) ? max( 1, absint( $_GET['messages_page'] ) ) : 1;
	$query = cammino_tipsters_messages( $id, $page );
	if ( ! isset( $_GET['messages_page'] ) && $query->max_num_pages > 1 ) {
		$page = (int) $query->max_num_pages; $query = cammino_tipsters_messages( $id, $page );
	}
	$url = $admin ? cammino_tipsters_admin_tips_url( array( 'tip_id' => $id ) ) : cammino_tipsters_url( 'tip', $id );
	$error = $GLOBALS['cammino_tipsters_message_error'] ?? null;
	$draft = $GLOBALS['cammino_tipsters_message_draft'] ?? array();
	$body = $draft['body'] ?? ( 'send_message' === cammino_tipsters_input( 'operation' ) ? cammino_tipsters_input( 'message_body' ) : '' );
	$token = $draft['token'] ?? cammino_tipsters_input( 'message_token' );
	$token = cammino_tipsters_valid_message_token( $id, $token ) ? $token : cammino_tipsters_message_token( $id );
	require __DIR__ . '/templates/conversation.php';
}

/** One bounded query per poll, using the posts primary key as the incremental cursor. */
function cammino_tipsters_live_message_rows( int $id, int $after = 0, bool $latest = false ): array {
	if ( ! cammino_tipsters_can_read_tip( $id ) || ! cammino_tipsters_tip_ready( $id ) ) { return array(); }
	global $wpdb;
	$columns = $latest ? 'p.ID' : 'p.*';
	$order = $latest ? 'DESC LIMIT 1' : 'ASC LIMIT 51';
	// Legacy metadata-only records are immutable history. Include them in the initial
	// watermark, but poll newly accepted messages through the indexed parent column.
	$link = $latest ? $wpdb->prepare( "(p.post_parent = %d OR (p.post_parent = 0 AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = %s AND m.meta_value = %s)))", $id, CAMMINO_MESSAGE_TIP_META, (string) $id ) : $wpdb->prepare( 'p.post_parent = %d', $id );
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT $columns FROM {$wpdb->posts} p WHERE p.post_type = %s AND p.post_status = 'private' AND p.ID > %d
		AND $link ORDER BY p.ID $order",
		CAMMINO_MESSAGE_POST_TYPE, $after
	) ) ?: array();
}

function cammino_tipsters_message_cursor( int $id ): int {
	$rows = cammino_tipsters_live_message_rows( $id, 0, true );
	return $rows ? (int) $rows[0]->ID : 0;
}

/** Own-message keys let the browser reconcile an outgoing bubble after a lost response. */
function cammino_tipsters_live_message( WP_Post $message, int $owner ): array {
	$own = (int) $message->post_author === get_current_user_id();
	return array( 'id' => (int) $message->ID, 'body' => $message->post_content, 'own' => $own,
		'request_key' => $own ? $message->post_name : '',
		'sender' => ( $message->post_title ?: __( 'Odosielateľ', 'cammino' ) ) . ' · ' . ( (int) $message->post_author === $owner ? __( 'Tipster', 'cammino' ) : __( 'Administrátor', 'cammino' ) ),
		'time' => get_post_time( get_option( 'date_format' ) . ' H:i:s', false, $message, true ),
		'datetime' => str_replace( ' ', 'T', $message->post_date_gmt ) . 'Z' );
}

function cammino_tipsters_live_message_data( int $id, int $after ): array {
	$rows = cammino_tipsters_live_message_rows( $id, $after );
	$more = count( $rows ) > 50;
	$messages = array(); $owner = (int) get_post( $id )->post_author;
	foreach ( array_slice( $rows, 0, 50 ) as $row ) {
		$message = new WP_Post( $row );
		$messages[] = cammino_tipsters_live_message( $message, $owner );
		$after = (int) $message->ID;
	}
	return array( 'messages' => $messages, 'cursor' => $after, 'more' => $more, 'can_send' => cammino_tipsters_can_message( $id ), 'status' => cammino_tipsters_tip_status( $id ), 'status_label' => cammino_tipsters_status_labels()[ cammino_tipsters_tip_status( $id ) ] ?? '', 'status_notice' => cammino_tipsters_status_notice( cammino_tipsters_tip_status( $id ) ) );
}

/** AJAX requests use the same authorization, account lock and persistence service as normal forms. */
function cammino_tipsters_conversation_ajax(): void {
	nocache_headers(); header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	$id = absint( cammino_tipsters_input( 'tip_id' ) );
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! is_user_logged_in() || ! wp_verify_nonce( cammino_tipsters_input( '_wpnonce' ), 'cammino_conversation_' . $id ) ) {
		wp_send_json_error( array( 'message' => __( 'Prihlásenie alebo platnosť stránky vypršala. Prihláste sa znova alebo obnovte stránku.', 'cammino' ) ), 403 );
	}
	if ( ! cammino_tipsters_can_read_tip( $id ) || ! cammino_tipsters_tip_ready( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'Tip už nie je dostupný.', 'cammino' ) ), 404 );
	}
	$operation = cammino_tipsters_input( 'operation' );
	if ( ! in_array( $operation, array( 'poll', 'send_message' ), true ) ) { wp_send_json_error( array(), 400 ); }
	if ( 'send_message' === $operation ) {
		$result = cammino_tipsters_send_message( $id, cammino_tipsters_input( 'message_body' ), cammino_tipsters_input( 'message_token' ) );
		if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message(), 'can_send' => cammino_tipsters_can_message( $id ) ), 'busy' === $result->get_error_code() ? 409 : 422 ); }
	}
	$data = cammino_tipsters_live_message_data( $id, absint( cammino_tipsters_input( 'after' ) ) );
	if ( 'send_message' === $operation || 'yes' === cammino_tipsters_input( 'need_message_token' ) ) {
		$data['message_token'] = cammino_tipsters_message_token( $id );
	}
	if ( 'send_message' === $operation ) {
		// An acknowledged retry may already be behind the incremental cursor.
		$data['sent_message'] = cammino_tipsters_live_message( get_post( $result ), (int) get_post( $id )->post_author );
	}
	wp_send_json_success( $data );
}
add_action( 'wp_ajax_cammino_conversation', 'cammino_tipsters_conversation_ajax' );
add_action( 'wp_ajax_nopriv_cammino_conversation', 'cammino_tipsters_conversation_ajax' );

function cammino_tipsters_enqueue_conversation(): void {
	wp_enqueue_style( 'cammino-conversation', get_template_directory_uri() . '/tipsters/assets/conversation.css', array(), CAMMINO_TIPSTERS_VERSION );
	wp_enqueue_script( 'cammino-conversation', get_template_directory_uri() . '/tipsters/assets/conversation.js', array(), CAMMINO_TIPSTERS_VERSION, true );
}
add_action( 'wp_enqueue_scripts', static function (): void {
	if ( 'tip' === cammino_tipsters_route() ) { cammino_tipsters_enqueue_conversation(); }
} );
add_action( 'admin_enqueue_scripts', static function (): void {
	if ( isset( $_GET['page'], $_GET['tip_id'] ) && 'cammino-tips' === $_GET['page'] && cammino_tipsters_can_manage() ) { cammino_tipsters_enqueue_conversation(); }
} );
