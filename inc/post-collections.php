<?php
/** Editable, runtime-rendered collections of other posts. @package Cammino */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', static function (): void {
	nstarter_register_live_section( 'cammino_post_collection', 'cammino_render_post_collection' );
} );

function cammino_sanitize_post_collection( array $args ): array {
	$ids = isset( $args['ids'] ) && is_array( $args['ids'] ) ? $args['ids'] : array();
	return array(
		'title' => isset( $args['title'] ) && is_string( $args['title'] ) ? sanitize_text_field( $args['title'] ) : 'Čítajte ďalej',
		'mode' => 'selected',
		'type' => 'all',
		'limit' => 6,
		'ids' => array_slice( array_values( array_unique( array_filter( array_map( 'absint', array_filter( $ids, 'is_numeric' ) ) ) ) ), 0, 6 ),
	);
}

function cammino_get_post_collection_posts( array $args, int $post_id ): array {
	$args = cammino_sanitize_post_collection( $args );
	if ( empty( $args['ids'] ) ) { return array(); }
	$ids = array_values( array_diff( $args['ids'], array( $post_id ) ) );
	if ( empty( $ids ) ) { return array(); }
	$query = array(
		'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
		'posts_per_page' => count( $ids ), 'post__in' => $ids,
		'ignore_sticky_posts' => true, 'no_found_rows' => true, 'orderby' => 'post__in',
	);
	return get_posts( $query );
}

function cammino_render_post_collection( array $args, int $post_id, bool $preview = false ): string {
	$args = cammino_sanitize_post_collection( $args );
	$posts = cammino_get_post_collection_posts( $args, $post_id );
	$preview = $preview || ( function_exists( 'nstarter_is_preview_request' ) && nstarter_is_preview_request() && current_user_can( 'edit_post', $post_id ) );
	if ( ! $posts && ! $preview ) { return ''; }
	ob_start();
	?>
	<section class="cammino-post-collection" aria-label="<?php echo esc_attr( $args['title'] ?: 'Ďalšie príspevky' ); ?>">
		<?php if ( '' !== $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
		<?php if ( ! $posts ) : ?>
			<p class="cammino-collection-empty">No related posts are selected. Use this section's variable control to choose posts. This message is hidden from visitors.</p>
		<?php else : ?>
			<div class="related-grid">
			<?php foreach ( $posts as $related ) : ?>
				<a class="related-card" href="<?php echo esc_url( get_permalink( $related ) ); ?>">
					<div class="related-card__image"><img src="<?php echo esc_url( cammino_get_post_image_url( $related->ID, 'medium_large' ) ); ?>" alt="" width="700" height="480" loading="lazy"><span><?php echo esc_html( cammino_get_post_type_label( cammino_get_post_placement( $related->ID ) ) ); ?></span></div>
					<div class="related-card__body"><small><?php echo esc_html( get_the_date( '', $related ) ); ?></small><h3><?php echo esc_html( get_the_title( $related ) ); ?></h3><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></div>
				</a>
			<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}

/** Return the variable-section attributes shared by new and upgraded collections. */
function cammino_get_post_collection_variable_attributes(): string {
	ob_start();
	nstarter_variable_section_attributes(
		'cammino_related_posts',
		array(
			'label'   => __( 'Related posts', 'cammino' ),
			'type'    => 'collection',
			'control' => 'collection',
		)
	);
	return (string) ob_get_clean();
}

function cammino_get_post_collection_block( array $args = array() ): string {
	return '<div class="article-content-block article-content-block--posts" data-nstarter-content-item data-nstarter-content-type="posts"' . cammino_get_post_collection_variable_attributes() . '>'
		. nstarter_get_live_section_marker( 'cammino_post_collection', cammino_sanitize_post_collection( $args ) ) . '</div>';
}

function cammino_get_post_collection_template(): string {
	return '<template data-nstarter-content-template="posts">' . cammino_get_post_collection_block() . '</template>';
}

/** Inert builder templates must never contain expanded, stale post cards. */
function cammino_expand_post_live_content( string $html, int $post_id ): string {
	$parts = preg_split( '#(<template\b[^>]*>.*?</template>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $index => $part ) {
		if ( 0 === $index % 2 ) { $parts[ $index ] = nstarter_expand_live_sections( $part, $post_id ); }
	}
	return implode( '', $parts );
}

/** Search and preview use the visual editor's existing per-post nonce. */
add_action( 'wp_ajax_cammino_post_collection', 'cammino_ajax_post_collection' );
function cammino_ajax_post_collection(): void {
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	check_ajax_referer( 'nstarter_editor_' . $post_id, 'nonce' );
	if ( ! current_user_can( 'edit_post', $post_id ) || ! cammino_is_visual_post( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'You cannot edit this post.' ), 403 );
	}
	$raw = isset( $_POST['settings'] ) && is_string( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : array();
	$args = cammino_sanitize_post_collection( is_array( $raw ) ? $raw : array() );
	if ( isset( $_POST['preview'] ) ) {
		wp_send_json_success( array( 'settings' => $args, 'html' => cammino_render_post_collection( $args, $post_id, true ) ) );
	}
	$query = array(
		'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
		'posts_per_page' => -1, 'post__not_in' => array( $post_id ), 'no_found_rows' => true,
		'orderby' => 'date', 'order' => 'DESC',
		's' => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
	);
	$format = static fn( WP_Post $post ): array => array( 'id' => $post->ID, 'title' => get_the_title( $post ), 'type' => cammino_get_post_type_label( cammino_get_post_placement( $post->ID ) ) );
	$selected = cammino_get_post_collection_posts( $args, $post_id );
	wp_send_json_success( array( 'posts' => array_map( $format, get_posts( $query ) ), 'selected' => array_map( $format, $selected ) ) );
}
