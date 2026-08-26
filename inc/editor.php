<?php
/**
 * Frontend visual-editor shell and save/regenerate endpoints.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is this the top-level editor shell request?
 */
function nstarter_is_editor_request(): bool {
	return isset( $_GET['nstarter_editor'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['nstarter_editor'] ) );
}

/**
 * Is this a page request inside the editor iframe?
 */
function nstarter_is_preview_request(): bool {
	return isset( $_GET['nstarter_preview'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['nstarter_preview'] ) );
}

/**
 * Build a visual-editor URL for a page.
 */
function nstarter_get_editor_url( int $post_id ): string {
	return (string) add_query_arg(
		array(
			'nstarter_editor' => '1',
			'nstarter_post'   => $post_id,
		),
		get_permalink( $post_id )
	);
}

/**
 * Build the clean iframe-preview URL.
 */
function nstarter_get_preview_url( int $post_id ): string {
	return (string) add_query_arg(
		array(
			'nstarter_preview' => '1',
			'nstarter_post'    => $post_id,
		),
		get_permalink( $post_id )
	);
}

add_action( 'template_redirect', 'nstarter_maybe_render_editor', 0 );

/**
 * Render the toolbar outside the designMode iframe.
 */
function nstarter_maybe_render_editor(): void {
	if ( nstarter_is_preview_request() ) {
		show_admin_bar( false );
		remove_action( 'wp_head', '_admin_bar_bump_cb' );
		return;
	}

	if ( ! nstarter_is_editor_request() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		auth_redirect();
		exit;
	}

	$post_id = isset( $_GET['nstarter_post'] ) ? absint( $_GET['nstarter_post'] ) : get_queried_object_id();
	$post    = get_post( $post_id );

	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die(
			esc_html__( 'You cannot edit this page.', 'nstarter' ),
			esc_html__( 'Visual editor', 'nstarter' ),
			array( 'response' => 403 )
		);
	}

	if ( ! nstarter_is_visual_page( $post_id ) ) {
		wp_die(
			esc_html__( 'This page does not use an NStarter visual page template.', 'nstarter' ),
			esc_html__( 'Visual editor', 'nstarter' ),
			array( 'response' => 400 )
		);
	}

	show_admin_bar( false );
	remove_action( 'wp_head', '_admin_bar_bump_cb' );
	wp_enqueue_media( array( 'post' => $post_id ) );
	wp_enqueue_style( 'nstarter-editor', NSTARTER_URL . '/assets/css/editor.css', array(), NSTARTER_VERSION );
	wp_enqueue_script( 'nstarter-editor', NSTARTER_URL . '/assets/js/editor.js', array( 'media-editor' ), NSTARTER_VERSION, true );
	wp_localize_script(
		'nstarter-editor',
		'nstarterEditor',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'nstarter_editor_' . $post_id ),
			'postId'     => $post_id,
			'previewUrl' => nstarter_get_preview_url( $post_id ),
			'viewUrl'    => get_permalink( $post_id ),
			'strings'    => array(
				'confirmRegenerate' => __( 'Regenerate this page from its PHP template? All saved visual edits will be replaced.', 'nstarter' ),
				'chooseMedia'       => __( 'Choose an image or video', 'nstarter' ),
				'useMedia'          => __( 'Use this media', 'nstarter' ),
				'editVideoSettings'   => __( 'Edit video settings', 'nstarter' ),
				'editSectionVariable' => __( 'Edit section variable', 'nstarter' ),
				'confirmRemoveItems'  => __( 'Reducing this value removes %d editable item(s). Continue?', 'nstarter' ),
				'unsupportedVariable' => __( 'This section variable is not configured correctly.', 'nstarter' ),
				'unsupportedMedia'    => __( 'Please choose an image or video.', 'nstarter' ),
				'saved'             => __( 'Saved', 'nstarter' ),
				'regenerated'       => __( 'Regenerated from PHP', 'nstarter' ),
				'unsaved'           => __( 'Unsaved changes', 'nstarter' ),
				'error'             => __( 'Something went wrong. Please try again.', 'nstarter' ),
				'collapseControls'  => __( 'Collapse controls', 'nstarter' ),
				'expandControls'    => __( 'Expand controls', 'nstarter' ),
			),
		)
	);

	status_header( 200 );
	nocache_headers();
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo esc_html( sprintf( __( 'Edit “%s”', 'nstarter' ), get_the_title( $post ) ) ); ?></title>
		<?php wp_head(); ?>
	</head>
	<body class="nstarter-editor-shell">
		<div class="nstarter-editor-stage">
			<div class="nstarter-editor-loading" data-nstarter-loading><?php esc_html_e( 'Loading page…', 'nstarter' ); ?></div>
			<iframe
				class="nstarter-editor-frame"
				data-nstarter-frame
				title="<?php esc_attr_e( 'Editable page preview', 'nstarter' ); ?>"
				src="<?php echo esc_url( nstarter_get_preview_url( $post_id ) ); ?>"
			></iframe>

			<aside class="nstarter-editor-panel" aria-label="<?php esc_attr_e( 'Visual editor controls', 'nstarter' ); ?>">
				<div class="nstarter-editor-panel__header">
					<div class="nstarter-editor-status" data-nstarter-status aria-live="polite">
						<span aria-hidden="true"></span>
						<strong><?php esc_html_e( 'Ready', 'nstarter' ); ?></strong>
					</div>
					<button class="nstarter-panel-toggle" type="button" data-nstarter-panel-toggle aria-expanded="true" aria-label="<?php esc_attr_e( 'Collapse controls', 'nstarter' ); ?>">
						<span aria-hidden="true">−</span>
					</button>
				</div>

				<div class="nstarter-editor-panel__body">
					<label class="nstarter-mode-field">
						<span><?php esc_html_e( 'Mode', 'nstarter' ); ?></span>
						<select data-nstarter-mode aria-label="<?php esc_attr_e( 'Editor mode', 'nstarter' ); ?>">
							<option value="text" selected><?php esc_html_e( 'Text editing', 'nstarter' ); ?></option>
							<option value="media"><?php esc_html_e( 'Media editing', 'nstarter' ); ?></option>
							<option value="interaction"><?php esc_html_e( 'Interaction', 'nstarter' ); ?></option>
						</select>
					</label>

					<button type="button" class="nstarter-control nstarter-control--primary" data-nstarter-save><?php esc_html_e( 'Save', 'nstarter' ); ?></button>
					<a class="nstarter-control" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'nstarter' ); ?></a>
					<button type="button" class="nstarter-control nstarter-control--quiet" data-nstarter-regenerate><?php esc_html_e( 'Regenerate page', 'nstarter' ); ?></button>
				</div>
			</aside>

			<dialog class="nstarter-video-dialog" data-nstarter-video-dialog>
				<form data-nstarter-video-form>
					<h2><?php esc_html_e( 'Video options', 'nstarter' ); ?></h2>
					<label><input type="checkbox" data-nstarter-video-autoplay> <?php esc_html_e( 'Auto start', 'nstarter' ); ?></label>
					<label><input type="checkbox" data-nstarter-video-muted> <?php esc_html_e( 'Muted', 'nstarter' ); ?></label>
					<label><input type="checkbox" data-nstarter-video-controls> <?php esc_html_e( 'Show controls', 'nstarter' ); ?></label>
					<p><?php esc_html_e( 'Browsers commonly block auto-starting video when it is not muted.', 'nstarter' ); ?></p>
					<div>
						<button type="button" data-nstarter-video-cancel><?php esc_html_e( 'Cancel', 'nstarter' ); ?></button>
						<button type="submit"><?php esc_html_e( 'Apply video', 'nstarter' ); ?></button>
					</div>
				</form>
			</dialog>

			<dialog class="nstarter-variable-dialog" data-nstarter-variable-dialog>
				<form data-nstarter-variable-form>
					<h2 data-nstarter-variable-title><?php esc_html_e( 'Edit section variable', 'nstarter' ); ?></h2>
					<label>
						<span data-nstarter-variable-label><?php esc_html_e( 'Value', 'nstarter' ); ?></span>
						<input type="number" data-nstarter-variable-input>
					</label>
					<p><?php esc_html_e( 'This changes the editable snapshot immediately. Use the main Save button afterward to persist it.', 'nstarter' ); ?></p>
					<div>
						<button type="button" data-nstarter-variable-cancel><?php esc_html_e( 'Cancel', 'nstarter' ); ?></button>
						<button type="submit"><?php esc_html_e( 'Save', 'nstarter' ); ?></button>
					</div>
				</form>
			</dialog>
		</div>
		<?php wp_footer(); ?>
	</body>
	</html>
	<?php
	exit;
}

add_action( 'wp_ajax_nstarter_save_snapshot', 'nstarter_ajax_save_snapshot' );

/**
 * Save the complete editable snapshot sent by the iframe.
 */
function nstarter_ajax_save_snapshot(): void {
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

	check_ajax_referer( 'nstarter_editor_' . $post_id, 'nonce' );

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'You cannot edit this page.', 'nstarter' ) ), 403 );
	}

	if ( ! isset( $_POST['html'] ) ) {
		wp_send_json_error( array( 'message' => __( 'No snapshot HTML was received.', 'nstarter' ) ), 400 );
	}

	// This intentionally stores the editor's complete HTML. Access is capability + nonce protected.
	$html = (string) wp_unslash( $_POST['html'] );
	nstarter_update_snapshot_html( $post_id, $html );

	wp_send_json_success( array( 'message' => __( 'Saved', 'nstarter' ) ) );
}

add_action( 'wp_ajax_nstarter_regenerate_snapshot', 'nstarter_ajax_regenerate_snapshot' );

/**
 * Replace all saved edits with a fresh rendering of the selected PHP source.
 */
function nstarter_ajax_regenerate_snapshot(): void {
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

	check_ajax_referer( 'nstarter_editor_' . $post_id, 'nonce' );

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'You cannot edit this page.', 'nstarter' ) ), 403 );
	}

	$html = nstarter_render_source_template( $post_id );
	nstarter_update_snapshot_html( $post_id, $html );

	wp_send_json_success( array( 'message' => __( 'Regenerated from PHP.', 'nstarter' ) ) );
}

add_action( 'admin_bar_menu', 'nstarter_add_admin_bar_editor_link', 90 );

/**
 * Add an editor shortcut while viewing a visual page normally.
 */
function nstarter_add_admin_bar_editor_link( WP_Admin_Bar $admin_bar ): void {
	if ( ! is_page() || nstarter_is_preview_request() || nstarter_is_editor_request() ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( ! nstarter_is_visual_page( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$admin_bar->add_node(
		array(
			'id'    => 'nstarter-visual-editor',
			'title' => __( 'Visual editor', 'nstarter' ),
			'href'  => nstarter_get_editor_url( $post_id ),
		)
	);
}
