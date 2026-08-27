<?php
/**
 * Native PHP page templates and ACF-backed HTML snapshots.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NSTARTER_SNAPSHOT_FIELD_KEY   = 'field_nstarter_snapshot_html';
const NSTARTER_SNAPSHOT_FIELD_NAME  = 'nstarter_snapshot_html';
const NSTARTER_SNAPSHOT_META_KEY    = '_cammino_snapshot_html';
const NSTARTER_VISUAL_PAGE_TEMPLATE = 'templates/visual-page.php';

/**
 * Find all PHP designs available to the native WordPress Template selector.
 *
 * @return array<string,string> Source slug => human-readable name.
 */
function nstarter_get_source_templates(): array {
	$templates = array();
	$files     = glob( NSTARTER_PATH . '/snapshot-templates/*.php' );

	if ( false === $files ) {
		return $templates;
	}

	foreach ( $files as $file ) {
		$slug = basename( $file, '.php' );
		$data = get_file_data( $file, array( 'name' => 'Snapshot Name' ) );

		$templates[ $slug ] = $data['name'] ?: ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
	}

	return $templates;
}

/**
 * Turn a source-template slug into the relative path WordPress stores.
 */
function nstarter_get_source_template_path( string $slug ): string {
	return 'snapshot-templates/' . sanitize_file_name( $slug ) . '.php';
}

/**
 * Resolve the published page assigned to a visual source template.
 */
function nstarter_get_source_page_url( string $slug, string $fallback_path = '/' ): string {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template',
			'meta_value'     => nstarter_get_source_template_path( $slug ),
		)
	);

	return ! empty( $pages )
		? (string) get_permalink( (int) $pages[0] )
		: (string) home_url( '/' . ltrim( $fallback_path, '/' ) );
}

/**
 * Get the Cammino source slug selected in WordPress's Template setting.
 */
function nstarter_get_native_source_template_slug( int $post_id ): string {
	$template_path = (string) get_page_template_slug( $post_id );

	if ( ! preg_match( '#^snapshot-templates/([a-zA-Z0-9_-]+)\.php$#', $template_path, $match ) ) {
		return '';
	}

	$slug      = sanitize_file_name( $match[1] );
	$templates = nstarter_get_source_templates();

	return isset( $templates[ $slug ] ) ? $slug : '';
}

/**
 * Is a page using a visual Cammino design?
 */
function nstarter_is_visual_page( int $post_id ): bool {
	return '' !== nstarter_get_native_source_template_slug( $post_id );
}

add_filter( 'theme_page_templates', 'nstarter_register_native_page_templates', 20, 3 );

/**
 * Register every source design in the normal WordPress page-template selector.
 *
 * @param array<string,string> $page_templates Existing page templates.
 * @return array<string,string>
 */
function nstarter_register_native_page_templates( array $page_templates ): array {
	// This is an internal renderer now, not a design editors should select.
	unset( $page_templates[ NSTARTER_VISUAL_PAGE_TEMPLATE ] );

	foreach ( nstarter_get_source_templates() as $slug => $name ) {
		$page_templates[ nstarter_get_source_template_path( $slug ) ] = sprintf(
			/* translators: %s: source-template name. */
			__( 'Cammino — %s', 'cammino' ),
			$name
		);
	}

	return $page_templates;
}

add_filter( 'template_include', 'nstarter_use_visual_page_wrapper', 99 );

/**
 * Route selected source designs through the snapshot display wrapper.
 */
function nstarter_use_visual_page_wrapper( string $template ): string {
	if ( is_page() && '' !== nstarter_get_native_source_template_slug( get_queried_object_id() ) ) {
		$wrapper = NSTARTER_PATH . '/' . NSTARTER_VISUAL_PAGE_TEMPLATE;

		if ( is_file( $wrapper ) ) {
			return $wrapper;
		}
	}

	return $template;
}

/**
 * Read the currently selected PHP source-template slug.
 */
function nstarter_get_source_template_slug( int $post_id ): string {
	$slug = nstarter_get_native_source_template_slug( $post_id );

	$templates = nstarter_get_source_templates();

	if ( '' === $slug || ! isset( $templates[ $slug ] ) ) {
		$slug = (string) array_key_first( $templates );
	}

	return sanitize_file_name( $slug );
}

/**
 * Read the raw saved HTML snapshot for the currently selected source.
 */
function nstarter_get_snapshot_html( int $post_id ): string {
	$has_canonical_snapshot = metadata_exists( 'post', $post_id, NSTARTER_SNAPSHOT_META_KEY );
	$html                   = $has_canonical_snapshot
		? (string) get_post_meta( $post_id, NSTARTER_SNAPSHOT_META_KEY, true )
		: '';

	if ( ! $has_canonical_snapshot && function_exists( 'get_field' ) ) {
		$value = get_field( NSTARTER_SNAPSHOT_FIELD_NAME, $post_id, false );
		$html  = is_string( $value ) ? $value : '';
	}

	// Migrate snapshots saved by the original non-ACF fallback. ACF itself uses
	// this legacy key for its field reference, so values beginning with `field_`
	// are metadata, not page HTML.
	if ( ! $has_canonical_snapshot && '' === $html ) {
		$legacy = (string) get_post_meta( $post_id, '_' . NSTARTER_SNAPSHOT_FIELD_NAME, true );
		$html   = str_starts_with( $legacy, 'field_' ) ? '' : $legacy;
	}

	$saved_source   = (string) get_post_meta( $post_id, '_nstarter_snapshot_source', true );
	$current_source = nstarter_get_source_template_slug( $post_id );
	$native_source  = nstarter_get_native_source_template_slug( $post_id );

	// A newly selected native template immediately displays its clean PHP output.
	if ( '' !== $native_source && ( '' === $saved_source || $saved_source !== $current_source ) ) {
		return '';
	}

	// The original current-donation design was replaced by the Contact-style
	// campaign layout. Ignore only that obsolete saved structure so deployed
	// pages switch immediately without discarding snapshots saved afterward.
	if ( 'donate-current' === $current_source && str_contains( $html, 'current-donation-hero' ) ) {
		return '';
	}

	if ( ! $has_canonical_snapshot && '' !== $html ) {
		update_post_meta( $post_id, NSTARTER_SNAPSHOT_META_KEY, wp_slash( $html ) );
	}

	return $html;
}

/**
 * Save a snapshot to ACF. Post meta is a fallback when ACF is unavailable.
 */
function nstarter_update_snapshot_html( int $post_id, string $html ): bool {
	update_post_meta( $post_id, '_nstarter_snapshot_source', nstarter_get_source_template_slug( $post_id ) );
	update_post_meta( $post_id, NSTARTER_SNAPSHOT_META_KEY, wp_slash( $html ) );

	if ( function_exists( 'update_field' ) ) {
		update_field( NSTARTER_SNAPSHOT_FIELD_KEY, $html, $post_id );
	}

	clean_post_cache( $post_id );

	return hash_equals(
		hash( 'sha256', $html ),
		hash( 'sha256', (string) get_post_meta( $post_id, NSTARTER_SNAPSHOT_META_KEY, true ) )
	);
}

/**
 * Invalidate WordPress and common full-page caches after a visual save.
 */
function nstarter_invalidate_snapshot_cache( int $post_id ): void {
	clean_post_cache( $post_id );
	wp_cache_delete( $post_id, 'posts' );
	wp_cache_delete( $post_id, 'post_meta' );

	// Updating the modified time triggers the normal save hooks used by most
	// cache plugins without changing the page content or selected template.
	wp_update_post( array( 'ID' => $post_id ) );

	if ( function_exists( 'rocket_clean_post' ) ) {
		rocket_clean_post( $post_id );
	}

	if ( function_exists( 'w3tc_flush_post' ) ) {
		w3tc_flush_post( $post_id );
	}

	if ( function_exists( 'wp_cache_post_change' ) ) {
		wp_cache_post_change( $post_id );
	}

	do_action( 'litespeed_purge_post', $post_id );
}

/**
 * Render a clean page snapshot from the selected PHP source file.
 */
function nstarter_render_source_template( int $post_id ): string {
	$slug = nstarter_get_source_template_slug( $post_id );
	$file = NSTARTER_PATH . '/snapshot-templates/' . $slug . '.php';

	if ( ! is_file( $file ) ) {
		return '<section style="padding:40px"><h1>Page template not found</h1><p>Add a PHP file to <code>snapshot-templates</code>.</p></section>';
	}

	$nstarter_post_id = $post_id;
	$nstarter_page    = get_post( $post_id );

	ob_start();
	include $file;

	return (string) ob_get_clean();
}

add_action( 'acf/init', 'nstarter_register_acf_fields' );

/**
 * Register the HTML snapshot field for every native Cammino page design.
 */
function nstarter_register_acf_fields(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$locations = array();

	foreach ( nstarter_get_source_templates() as $slug => $name ) {
		$locations[] = array(
			array(
				'param'    => 'page_template',
				'operator' => '==',
				'value'    => nstarter_get_source_template_path( $slug ),
			),
		);
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_nstarter_visual_snapshot',
			'title'    => __( 'Cammino visual snapshot', 'cammino' ),
			'fields'   => array(
				array(
					'key'          => NSTARTER_SNAPSHOT_FIELD_KEY,
					'label'        => __( 'Saved HTML snapshot', 'nstarter' ),
					'name'         => NSTARTER_SNAPSHOT_FIELD_NAME,
					'type'         => 'textarea',
					'instructions' => __( 'Managed by the visual editor. The selected WordPress page template is its PHP source.', 'nstarter' ),
					'rows'         => 10,
					'new_lines'    => '',
				),
			),
			'location' => $locations,
		)
	);
}

add_action( 'add_meta_boxes_page', 'nstarter_add_editor_meta_box' );

/**
 * Add an obvious route from wp-admin to the visual editor.
 */
function nstarter_add_editor_meta_box(): void {
	add_meta_box(
		'nstarter-visual-editor',
		__( 'Cammino visual editor', 'cammino' ),
		'nstarter_render_editor_meta_box',
		'page',
		'side',
		'high'
	);
}

/**
 * Render the editor launch box.
 */
function nstarter_render_editor_meta_box( WP_Post $post ): void {
	if ( ! nstarter_is_visual_page( $post->ID ) ) {
		echo '<p>' . esc_html__( 'Choose a “Cammino — …” design in the page Template setting, then save the page.', 'cammino' ) . '</p>';
		return;
	}

	$url = nstarter_get_editor_url( $post->ID );
	echo '<p><a class="button button-primary button-large" style="width:100%;text-align:center" href="' . esc_url( $url ) . '">';
	echo esc_html__( 'Open visual editor', 'nstarter' );
	echo '</a></p>';
	echo '<p class="description">' . esc_html__( 'The selected PHP design renders this page. Visual edits are stored in its ACF snapshot.', 'nstarter' ) . '</p>';
}
