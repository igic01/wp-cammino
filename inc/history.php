<?php
/** Bounded HTML save history, independently stored for each page design. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NSTARTER_HISTORY_LIMIT = 10;

function nstarter_snapshot_history_key( int $post_id ): string {
	return '_cammino_html_versions_' . nstarter_get_source_template_slug( $post_id );
}

function nstarter_get_snapshot_history( int $post_id ): array {
	$raw = (string) get_post_meta( $post_id, nstarter_snapshot_history_key( $post_id ), true );
	$state = json_decode( $raw, true );
	return is_array( $state ) && isset( $state['current']['html'], $state['previous'] ) && is_array( $state['previous'] ) ? $state : array();
}

function nstarter_snapshot_record( string $html ): array {
	return array(
		'id'       => wp_generate_uuid4(),
		'html'     => $html,
		'saved_at' => gmdate( 'Y-m-d H:i:s' ) . ' UTC',
		'author'   => get_current_user_id(),
	);
}

/** Preserve a legacy design before the first save of a different design. */
function nstarter_preserve_legacy_design( int $post_id ): bool {
	$source = (string) get_post_meta( $post_id, '_nstarter_snapshot_source', true );
	if ( '' === $source || $source === nstarter_get_source_template_slug( $post_id ) || ! isset( nstarter_get_source_templates()[ $source ] ) ) {
		return true;
	}
	$key = '_cammino_html_versions_' . $source;
	if ( metadata_exists( 'post', $post_id, $key ) ) {
		return true;
	}
	$html = (string) get_post_meta( $post_id, NSTARTER_SNAPSHOT_META_KEY, true );
	if ( '' === $html && function_exists( 'get_field' ) ) {
		$value = get_field( NSTARTER_SNAPSHOT_FIELD_NAME, $post_id, false );
		$html = is_string( $value ) ? $value : '';
	}
	if ( '' === $html ) {
		$legacy = (string) get_post_meta( $post_id, '_' . NSTARTER_SNAPSHOT_FIELD_NAME, true );
		$html = str_starts_with( $legacy, 'field_' ) ? '' : $legacy;
	}
	if ( '' === $html ) {
		return true;
	}
	$record = nstarter_snapshot_record( $html );
	$record['saved_at'] = __( 'Existing saved content', 'cammino' );
	$encoded = (string) wp_json_encode( array( 'current' => $record, 'previous' => array() ) );
	add_post_meta( $post_id, $key, wp_slash( $encoded ), true );
	return $encoded === (string) get_post_meta( $post_id, $key, true );
}

/**
 * Commit the new current HTML and archive up to ten preceding saves.
 * Compare-and-swap prevents concurrent requests from dropping a save.
 */
function nstarter_commit_snapshot_history( int $post_id, string $html, string $legacy_html, string $expected_token = '' ): bool {
	$key = nstarter_snapshot_history_key( $post_id );
	$raw = (string) get_post_meta( $post_id, $key, true );
	$state = json_decode( $raw, true );
	$state = is_array( $state ) && isset( $state['current']['html'], $state['previous'] ) ? $state : array();
	$token = nstarter_snapshot_content_token( nstarter_get_source_template_slug( $post_id ), $state['current']['html'] ?? $legacy_html );
	if ( '' !== $expected_token && ! hash_equals( $token, $expected_token ) ) {
		return false;
	}
	if ( ! $state ) {
		$state = array( 'previous' => array() );
		if ( '' !== $legacy_html ) {
			$state['current'] = nstarter_snapshot_record( $legacy_html );
			$state['current']['saved_at'] = __( 'Existing saved content', 'cammino' );
		}
	}
	if ( '' !== $raw && isset( $state['current'] ) && $state['current']['html'] === $html ) {
		return true;
	}
	if ( isset( $state['current'] ) && $state['current']['html'] !== $html ) {
		array_unshift( $state['previous'], $state['current'] );
	}
	$state['previous'] = array_slice( $state['previous'], 0, NSTARTER_HISTORY_LIMIT );
	$state['current'] = nstarter_snapshot_record( $html );
	$encoded = (string) wp_json_encode( $state );
	if ( metadata_exists( 'post', $post_id, $key ) ) {
		update_post_meta( $post_id, $key, wp_slash( $encoded ), $raw );
	} else {
		add_post_meta( $post_id, $key, wp_slash( $encoded ), true );
	}
	return $encoded === (string) get_post_meta( $post_id, $key, true );
}

/** A fingerprint of the raw content the editor loaded (including its design). */
function nstarter_snapshot_token( int $post_id ): string {
	return nstarter_snapshot_content_token( nstarter_get_source_template_slug( $post_id ), nstarter_get_snapshot_html( $post_id ) );
}

function nstarter_snapshot_content_token( string $source, string $html ): string {
	return hash( 'sha256', $source . '\n' . $html );
}

function nstarter_get_snapshot_version( int $post_id, string $id ): ?array {
	$state = nstarter_get_snapshot_history( $post_id );
	foreach ( $state ? array_merge( array( $state['current'] ), $state['previous'] ) : array() as $record ) {
		if ( hash_equals( $record['id'], $id ) ) {
			return $record;
		}
	}
	// The existing snapshot is previewable before the first history-enabled save.
	if ( 'current' === $id ) {
		$html = nstarter_get_snapshot_html( $post_id );
		return '' !== $html ? array( 'id' => 'current', 'html' => $html, 'saved_at' => __( 'Existing saved content', 'cammino' ), 'author' => 0 ) : null;
	}
	return null;
}

function nstarter_snapshot_history_list( int $post_id ): array {
	$state = nstarter_get_snapshot_history( $post_id );
	$records = $state ? array_merge( array( $state['current'] ), $state['previous'] ) : array_filter( array( nstarter_get_snapshot_version( $post_id, 'current' ) ) );
	return array_map(
		static function ( array $record ): array {
			$author = get_userdata( (int) $record['author'] );
			unset( $record['html'] );
			$record['author_name'] = $author ? $author->display_name : '';
			return $record;
		},
		array_values( $records )
	);
}

/** Protect history and edits from stale tabs or a changed page template. */
function nstarter_require_snapshot_context( int $post_id ): void {
	$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
	$token = isset( $_POST['snapshot_token'] ) ? sanitize_text_field( wp_unslash( $_POST['snapshot_token'] ) ) : '';
	if ( $source !== nstarter_get_source_template_slug( $post_id ) || ! hash_equals( nstarter_snapshot_token( $post_id ), $token ) ) {
		wp_send_json_error( array( 'message' => __( 'This page changed since you opened it. Reload the editor before saving.', 'cammino' ) ), 409 );
	}
}

function nstarter_history_request_post(): int {
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	check_ajax_referer( 'nstarter_editor_' . $post_id, 'nonce' );
	if ( ! $post_id || 'page' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! nstarter_is_visual_page( $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'You cannot edit this page.', 'cammino' ) ), 403 );
	}
	return $post_id;
}

add_action( 'wp_ajax_nstarter_snapshot_history', 'nstarter_ajax_snapshot_history' );
function nstarter_ajax_snapshot_history(): void {
	$post_id = nstarter_history_request_post();
	nstarter_require_snapshot_context( $post_id );
	wp_send_json_success( array( 'versions' => nstarter_snapshot_history_list( $post_id ) ) );
}

add_action( 'wp_ajax_nstarter_preview_snapshot_version', 'nstarter_ajax_preview_snapshot_version' );
function nstarter_ajax_preview_snapshot_version(): void {
	$post_id = nstarter_history_request_post();
	nstarter_require_snapshot_context( $post_id );
	$id = isset( $_POST['version_id'] ) ? sanitize_text_field( wp_unslash( $_POST['version_id'] ) ) : '';
	$version = nstarter_get_snapshot_version( $post_id, $id );
	if ( ! $version ) {
		wp_send_json_error( array( 'message' => __( 'This saved version is no longer available.', 'cammino' ) ), 404 );
	}
	$merged = nstarter_render_merged_page_html( $post_id, $version['html'] );
	wp_send_json_success( array( 'html' => nstarter_expand_live_sections( $merged['html'], $post_id ), 'savedHtml' => $version['html'], 'conflicts' => $merged['conflicts'] ) );
}

add_action( 'wp_ajax_nstarter_restore_snapshot_version', 'nstarter_ajax_restore_snapshot_version' );
function nstarter_ajax_restore_snapshot_version(): void {
	$post_id = nstarter_history_request_post();
	nstarter_require_snapshot_context( $post_id );
	$id = isset( $_POST['version_id'] ) ? sanitize_text_field( wp_unslash( $_POST['version_id'] ) ) : '';
	$version = nstarter_get_snapshot_version( $post_id, $id );
	if ( ! $version ) {
		wp_send_json_error( array( 'message' => __( 'This saved version is no longer available.', 'cammino' ) ), 404 );
	}
	// Store the original HTML, so unmatched content remains available for review.
	if ( ! nstarter_update_snapshot_html( $post_id, $version['html'], (string) wp_unslash( $_POST['snapshot_token'] ) ) ) {
		wp_send_json_error( array( 'message' => __( 'The version could not be restored. Reload the editor and try again.', 'cammino' ) ), 409 );
	}
	nstarter_invalidate_snapshot_cache( $post_id );
	wp_send_json_success( array( 'message' => __( 'Content restored', 'cammino' ), 'snapshotToken' => nstarter_snapshot_content_token( (string) wp_unslash( $_POST['source'] ), $version['html'] ) ) );
}
