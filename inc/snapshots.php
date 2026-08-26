<?php
/**
 * Native PHP page templates and ACF-backed HTML snapshots.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NSTARTER_SNAPSHOT_FIELD_KEY   = 'field_nstarter_snapshot_html';
const NSTARTER_SNAPSHOT_FIELD_NAME  = 'nstarter_snapshot_html';
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
 * Get the NStarter source slug selected in WordPress's Template setting.
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
 * Is a page using a visual NStarter design?
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
			__( 'NStarter — %s', 'nstarter' ),
			$name
		);
	}

	return $page_templates;
}

add_filter( 'template_include', 'nstarter_use_visual_page_wrapper', 99 );

/**
 * Route selected source designs through the snapshot display wrapper.
 * If a host fails to resolve the inherited child-theme template, explicitly
 * fall back to Astra's page or index template instead of returning no output.
 *
 * @param mixed $template Template path supplied by WordPress.
 */
function nstarter_use_visual_page_wrapper( $template ): string {
	$template = is_string( $template ) ? $template : '';

	if ( is_page() && '' !== nstarter_get_native_source_template_slug( get_queried_object_id() ) ) {
		$wrapper = NSTARTER_PATH . '/' . NSTARTER_VISUAL_PAGE_TEMPLATE;

		if ( is_file( $wrapper ) ) {
			return $wrapper;
		}
	}

	if ( '' === $template ) {
		$parent_fallback = get_template_directory() . ( is_page() ? '/page.php' : '/index.php' );

		if ( is_file( $parent_fallback ) ) {
			return $parent_fallback;
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
	$html = '';

	if ( function_exists( 'get_field' ) ) {
		$value = get_field( NSTARTER_SNAPSHOT_FIELD_NAME, $post_id, false );
		$html  = is_string( $value ) ? $value : '';
	}

	if ( '' === $html ) {
		$html = (string) get_post_meta( $post_id, '_' . NSTARTER_SNAPSHOT_FIELD_NAME, true );
	}

	$saved_source   = (string) get_post_meta( $post_id, '_nstarter_snapshot_source', true );
	$current_source = nstarter_get_source_template_slug( $post_id );
	$native_source  = nstarter_get_native_source_template_slug( $post_id );

	// A newly selected native template immediately displays its clean PHP output.
	if ( '' !== $native_source && ( '' === $saved_source || $saved_source !== $current_source ) ) {
		return '';
	}

	return $html;
}

/**
 * Save a snapshot to ACF. Post meta is a fallback when ACF is unavailable.
 */
function nstarter_update_snapshot_html( int $post_id, string $html ): bool {
	update_post_meta( $post_id, '_nstarter_snapshot_source', nstarter_get_source_template_slug( $post_id ) );

	if ( function_exists( 'update_field' ) ) {
		update_field( NSTARTER_SNAPSHOT_FIELD_KEY, $html, $post_id );
		return true;
	}

	update_post_meta( $post_id, '_' . NSTARTER_SNAPSHOT_FIELD_NAME, $html );
	return true;
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
 * Register the HTML snapshot field for every native NStarter page design.
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
			'title'    => __( 'NStarter visual snapshot', 'nstarter' ),
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
		__( 'NStarter visual editor', 'nstarter' ),
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
		echo '<p>' . esc_html__( 'Choose an “NStarter — …” design in the page Template setting, then save the page.', 'nstarter' ) . '</p>';
		return;
	}

	$url = nstarter_get_editor_url( $post->ID );
	echo '<p><a class="button button-primary button-large" style="width:100%;text-align:center" href="' . esc_url( $url ) . '">';
	echo esc_html__( 'Open visual editor', 'nstarter' );
	echo '</a></p>';
	echo '<p class="description">' . esc_html__( 'The selected PHP design renders this page. Visual edits are stored in its ACF snapshot.', 'nstarter' ) . '</p>';
}
