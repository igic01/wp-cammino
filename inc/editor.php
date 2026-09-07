<?php
/**
 * Frontend visual-editor shell and save/regenerate endpoints.
 *
 * @package Cammino
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

	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'page', 'post' ), true ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die(
			esc_html__( 'You cannot edit this page.', 'nstarter' ),
			esc_html__( 'Visual editor', 'nstarter' ),
			array( 'response' => 403 )
		);
	}

	if ( ! nstarter_is_visual_document( $post_id ) ) {
		wp_die(
			esc_html__( 'This page does not use a Cammino visual page template.', 'cammino' ),
			esc_html__( 'Visual editor', 'nstarter' ),
			array( 'response' => 400 )
		);
	}

	$is_post    = 'post' === $post->post_type;
	$placement  = $is_post ? cammino_get_post_placement( $post_id ) : '';
	$is_event   = 'event' === $placement;
	$is_project = 'project' === $placement;
	$editable_category = $is_post ? cammino_get_editable_post_category( $post_id ) : array( 'name' => '' );

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
			'isPost'     => $is_post,
			'isEvent'    => $is_event,
			'isProject'  => $is_project,
			'postDetails' => $is_post ? array(
				'title'         => get_the_title( $post ),
				'category'      => $editable_category['name'],
				'eventDate'     => $is_event ? (string) get_post_meta( $post_id, CAMMINO_EVENT_DATE_META, true ) : '',
				'eventLocation' => $is_event ? (string) get_post_meta( $post_id, CAMMINO_EVENT_LOCATION_META, true ) : '',
				'eventType'     => $is_event ? (string) get_post_meta( $post_id, CAMMINO_EVENT_TYPE_META, true ) : '',
				'hideImage'     => ( $is_event && '1' === (string) get_post_meta( $post_id, CAMMINO_EVENT_HIDE_IMAGE_META, true ) )
					|| ( $is_project && '1' === (string) get_post_meta( $post_id, CAMMINO_PROJECT_HIDE_IMAGE_META, true ) ),
			) : array(),
			'placeholderUrl' => NSTARTER_URL . '/assets/images/placeholder.webp',
			'strings'    => array(
				'confirmRegenerate' => 'post' === $post->post_type
					? __( 'Reset this post body? All saved body edits will be replaced.', 'cammino' )
					: __( 'Regenerate this page from its PHP template? All saved visual edits will be replaced.', 'nstarter' ),
				'chooseMedia'       => __( 'Choose an image or video', 'nstarter' ),
				'useMedia'          => __( 'Use this media', 'nstarter' ),
				'invalidLink'       => __( 'Enter a valid web, email, phone, page, or anchor link.', 'nstarter' ),
				'editVideoSettings'   => __( 'Edit video settings', 'nstarter' ),
				'editSectionVariable' => __( 'Edit section variable', 'nstarter' ),
				'confirmRemoveItems'  => __( 'Reducing this value removes %d editable item(s). Continue?', 'nstarter' ),
				'unsupportedVariable' => __( 'This section variable is not configured correctly.', 'nstarter' ),
				'unsupportedMedia'    => __( 'Please choose an image or video.', 'nstarter' ),
				'sectionOrderUp'      => __( 'Move section up', 'nstarter' ),
				'sectionOrderDown'    => __( 'Move section down', 'nstarter' ),
				'noOrderableSections' => __( 'No reorderable content sections were found.', 'nstarter' ),
				'contentItemUp'       => __( 'Move content item up', 'cammino' ),
				'contentItemDown'     => __( 'Move content item down', 'cammino' ),
				'contentItemDelete'   => __( 'Delete content item', 'cammino' ),
				'confirmDeleteContent'=> __( 'Delete this content item?', 'cammino' ),
				'addHeading'          => __( 'Add heading', 'cammino' ),
				'addParagraph'        => __( 'Add paragraph', 'cammino' ),
				'addImage'            => __( 'Add image', 'cammino' ),
				'addImpactStory'      => __( 'Add impact story', 'cammino' ),
				'newHeading'          => __( 'New heading', 'cammino' ),
				'newParagraph'        => __( 'Write your paragraph here.', 'cammino' ),
				'editImage'           => __( 'Choose or replace image', 'cammino' ),
				'editLink'            => __( 'Edit link destination', 'cammino' ),
				'linkSelectedText'    => __( 'Link selected text', 'cammino' ),
				'removeTextLink'      => __( 'Remove link', 'cammino' ),
				'selectTextForLink'   => __( 'Select one or more words inside this paragraph first.', 'cammino' ),
				'overlappingTextLink' => __( 'The selection overlaps an existing link. Select only that link to edit it, or remove it first.', 'cammino' ),
				'selectLinkedText'    => __( 'Select linked words, or place the cursor inside a link, before removing it.', 'cammino' ),
				'selectOneTextLink'   => __( 'Select only one link at a time before removing it.', 'cammino' ),
				'emptyPostContent'    => __( 'Your content will appear here.', 'cammino' ),
				'missingEventDate'    => __( 'Dátum bude doplnený', 'cammino' ),
				'missingEventLocation'=> __( 'Miesto bude doplnené', 'cammino' ),
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
							<option value="link"><?php esc_html_e( 'Link editing', 'nstarter' ); ?></option>
							<option value="interaction"><?php esc_html_e( 'Interaction', 'nstarter' ); ?></option>
						</select>
					</label>

					<button type="button" class="nstarter-control nstarter-control--primary" data-nstarter-save><?php esc_html_e( 'Save', 'nstarter' ); ?></button>
					<a class="nstarter-control" data-nstarter-view href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'nstarter' ); ?></a>
					<button type="button" class="nstarter-control nstarter-control--order" data-nstarter-section-order><?php esc_html_e( 'Section order', 'nstarter' ); ?></button>
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

			<dialog class="nstarter-link-dialog" data-nstarter-link-dialog>
				<form data-nstarter-link-form>
					<h2><?php esc_html_e( 'Edit link', 'nstarter' ); ?></h2>
					<label>
						<span><?php esc_html_e( 'Destination URL', 'nstarter' ); ?></span>
						<input type="text" inputmode="url" autocomplete="url" data-nstarter-link-input required>
					</label>
					<p><?php esc_html_e( 'You can use a full URL, a site path such as /contact/, an email or phone link, or an anchor such as #about.', 'nstarter' ); ?></p>
					<div>
						<button type="button" data-nstarter-link-cancel><?php esc_html_e( 'Cancel', 'nstarter' ); ?></button>
						<button type="submit"><?php esc_html_e( 'Apply link', 'nstarter' ); ?></button>
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

			<dialog class="nstarter-section-order-dialog" data-nstarter-section-order-dialog>
				<form data-nstarter-section-order-form>
					<h2><?php esc_html_e( 'Change section order', 'nstarter' ); ?></h2>
					<p><?php esc_html_e( 'Use the arrow buttons to arrange the page content. The header and footer are excluded.', 'nstarter' ); ?></p>
					<ol data-nstarter-section-order-list></ol>
					<div class="nstarter-section-order-dialog__actions">
						<button type="button" data-nstarter-section-order-cancel><?php esc_html_e( 'Cancel', 'nstarter' ); ?></button>
						<button type="submit"><?php esc_html_e( 'Apply order', 'nstarter' ); ?></button>
					</div>
				</form>
			</dialog>

			<?php if ( $is_post ) : ?>
				<dialog class="nstarter-post-details-dialog" data-cammino-post-details-dialog>
					<form data-cammino-post-details-form>
						<h2><?php echo esc_html( $is_event ? __( 'Edit event details', 'cammino' ) : ( $is_project ? __( 'Edit project details', 'cammino' ) : __( 'Edit post title', 'cammino' ) ) ); ?></h2>
						<label><?php esc_html_e( 'Title', 'cammino' ); ?><input name="title" type="text" maxlength="200" required></label>
						<?php if ( $is_event ) : ?>
							<label><?php esc_html_e( 'Event date and time', 'cammino' ); ?><input name="event_date" type="datetime-local" required></label>
							<label><?php esc_html_e( 'Location', 'cammino' ); ?><input name="event_location" type="text" maxlength="200" required></label>
							<label><?php esc_html_e( 'Event type (optional)', 'cammino' ); ?><input name="event_type" type="text" maxlength="100" placeholder="<?php esc_attr_e( 'For example: workshop or webinar', 'cammino' ); ?>"></label>
						<?php endif; ?>
						<?php if ( $is_event || $is_project ) : ?>
							<label><?php esc_html_e( 'Category', 'cammino' ); ?><input name="category" type="text" maxlength="100" placeholder="<?php esc_attr_e( 'Enter a category name', 'cammino' ); ?>"></label>
							<label class="nstarter-post-details-dialog__check"><input name="hide_image" type="checkbox"> <?php echo esc_html( $is_event ? __( 'Hide the event photo', 'cammino' ) : __( 'Hide the project image', 'cammino' ) ); ?></label>
							<p><?php esc_html_e( 'The title, category, and image setting are saved directly to this post.', 'cammino' ); ?></p>
						<?php endif; ?>
						<div><button type="button" data-cammino-post-details-cancel><?php esc_html_e( 'Cancel', 'nstarter' ); ?></button><button type="submit"><?php esc_html_e( 'Apply', 'cammino' ); ?></button></div>
					</form>
				</dialog>
			<?php endif; ?>

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

	if ( 'post' === get_post_type( $post_id ) ) {
		$title = isset( $_POST['post_title'] ) ? (string) wp_unslash( $_POST['post_title'] ) : get_the_title( $post_id );
		$date = isset( $_POST['event_date'] )
			? (string) wp_unslash( $_POST['event_date'] )
			: (string) get_post_meta( $post_id, CAMMINO_EVENT_DATE_META, true );
		$location = isset( $_POST['event_location'] )
			? (string) wp_unslash( $_POST['event_location'] )
			: (string) get_post_meta( $post_id, CAMMINO_EVENT_LOCATION_META, true );
		$event_type = isset( $_POST['event_type'] )
			? (string) wp_unslash( $_POST['event_type'] )
			: (string) get_post_meta( $post_id, CAMMINO_EVENT_TYPE_META, true );
		$hide_image = isset( $_POST['hide_image'] )
			? '1' === sanitize_text_field( wp_unslash( $_POST['hide_image'] ) )
			: ( 'event' === cammino_get_post_placement( $post_id )
				? '1' === (string) get_post_meta( $post_id, CAMMINO_EVENT_HIDE_IMAGE_META, true )
				: '1' === (string) get_post_meta( $post_id, CAMMINO_PROJECT_HIDE_IMAGE_META, true ) );
		$category = isset( $_POST['category'] )
			? (string) wp_unslash( $_POST['category'] )
			: cammino_get_editable_post_category( $post_id )['name'];

		if ( ! cammino_update_visual_post_details( $post_id, $title, $date, $location, $hide_image, $event_type, $category ) ) {
			wp_send_json_error( array( 'message' => __( 'The post details could not be saved. Check the title, date, and location.', 'cammino' ) ), 400 );
		}
	}

	// This intentionally stores the editor's complete HTML. Access is capability + nonce protected.
	$html = (string) wp_unslash( $_POST['html'] );
	if ( ! nstarter_is_visual_document( $post_id ) || ! nstarter_update_visual_document_html( $post_id, $html ) ) {
		wp_send_json_error( array( 'message' => __( 'The saved page could not be verified. Please try again.', 'cammino' ) ), 500 );
	}

	if ( 'post' === get_post_type( $post_id ) && isset( $_POST['featured_image_id'] ) ) {
		$featured_image_id = absint( $_POST['featured_image_id'] );
		if ( $featured_image_id && wp_attachment_is_image( $featured_image_id ) ) {
			set_post_thumbnail( $post_id, $featured_image_id );
		}
	}

	nstarter_invalidate_snapshot_cache( $post_id );

	wp_send_json_success(
		array(
			'message' => __( 'Saved', 'cammino' ),
			'viewUrl' => add_query_arg( 'cammino_snapshot', wp_generate_uuid4(), get_permalink( $post_id ) ),
		)
	);
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

	$html = nstarter_render_visual_document( $post_id );
	if ( ! nstarter_is_visual_document( $post_id ) || ! nstarter_update_visual_document_html( $post_id, $html ) ) {
		wp_send_json_error( array( 'message' => __( 'The regenerated page could not be verified. Please try again.', 'cammino' ) ), 500 );
	}

	nstarter_invalidate_snapshot_cache( $post_id );

	wp_send_json_success(
		array(
			'message' => __( 'Regenerated from PHP.', 'cammino' ),
			'viewUrl' => add_query_arg( 'cammino_snapshot', wp_generate_uuid4(), get_permalink( $post_id ) ),
		)
	);
}

add_action( 'admin_bar_menu', 'nstarter_add_admin_bar_editor_link', 90 );

/**
 * Add an editor shortcut while viewing a visual page normally.
 */
function nstarter_add_admin_bar_editor_link( WP_Admin_Bar $admin_bar ): void {
	if ( ! is_singular( array( 'page', 'post' ) ) || nstarter_is_preview_request() || nstarter_is_editor_request() ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( ! nstarter_is_visual_document( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
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
