<?php
/**
 * Runtime-rendered sections that stay outside the saved static snapshot.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a live-section renderer.
 *
 * The callback receives `(array $args, int $post_id)` and should return HTML.
 */
function nstarter_register_live_section( string $id, callable $renderer ): void {
	global $nstarter_live_sections;

	if ( ! is_array( $nstarter_live_sections ) ) {
		$nstarter_live_sections = array();
	}

	$nstarter_live_sections[ sanitize_key( $id ) ] = $renderer;
}

/**
 * Print an empty live-section marker from a PHP snapshot source template.
 *
 * @param string               $id   Registered renderer ID.
 * @param array<string,mixed>  $args Values passed to its renderer.
 */
function nstarter_live_section( string $id, array $args = array() ): void {
	$encoded_args = base64_encode( (string) wp_json_encode( $args ) );

	echo '<div class="nstarter-live-section" data-nstarter-live-section="' . esc_attr( sanitize_key( $id ) ) . '" data-nstarter-live-args="' . esc_attr( $encoded_args ) . '"></div>';
}

/**
 * Replace empty live markers with freshly rendered content.
 */
function nstarter_expand_live_sections( string $html, int $post_id ): string {
	global $nstarter_live_sections;

	if ( ! is_array( $nstarter_live_sections ) ) {
		$nstarter_live_sections = array();
	}

	$pattern = '#<div([^>]*data-nstarter-live-section=["\']([^"\']+)["\'][^>]*)>\s*</div>#i';

	return (string) preg_replace_callback(
		$pattern,
		static function ( array $match ) use ( $post_id, $nstarter_live_sections ): string {
			$attributes = $match[1];
			$id         = sanitize_key( $match[2] );
			$args       = array();

			if ( preg_match( '#data-nstarter-live-args=["\']([^"\']*)["\']#i', $attributes, $args_match ) ) {
				$decoded = json_decode( (string) base64_decode( $args_match[1], true ), true );
				$args    = is_array( $decoded ) ? $decoded : array();
			}

			$content = '';
			if ( isset( $nstarter_live_sections[ $id ] ) && is_callable( $nstarter_live_sections[ $id ] ) ) {
				$content = (string) call_user_func( $nstarter_live_sections[ $id ], $args, $post_id );
			} else {
				$content = '<p style="padding:24px">' . esc_html__( 'Unknown live section:', 'nstarter' ) . ' ' . esc_html( $id ) . '</p>';
			}

			$attributes = preg_replace( '#\scontenteditable=["\'][^"\']*["\']#i', '', $attributes );

			return '<div' . $attributes . ' contenteditable="false">' . $content . '</div>';
		},
		$html
	);
}

add_action( 'init', 'nstarter_register_default_live_sections' );

/**
 * Register the example DB-driven latest-posts section.
 */
function nstarter_register_default_live_sections(): void {
	nstarter_register_live_section(
		'site_metrics',
		static function (): string {
			$post_counts    = wp_count_posts( 'post' );
			$page_counts    = wp_count_posts( 'page' );
			$published_posts = isset( $post_counts->publish ) ? (int) $post_counts->publish : 0;
			$published_pages = isset( $page_counts->publish ) ? (int) $page_counts->publish : 0;
			$comments        = (int) get_comments( array( 'status' => 'approve', 'count' => true ) );

			ob_start();
			?>
			<section class="live-metrics" aria-label="<?php esc_attr_e( 'Current site statistics', 'nstarter' ); ?>">
				<div class="live-metric"><strong><?php echo esc_html( number_format_i18n( $published_posts ) ); ?></strong><span><?php esc_html_e( 'Published stories', 'nstarter' ); ?></span></div>
				<div class="live-metric"><strong><?php echo esc_html( number_format_i18n( $published_pages ) ); ?></strong><span><?php esc_html_e( 'Published pages', 'nstarter' ); ?></span></div>
				<div class="live-metric"><strong><?php echo esc_html( number_format_i18n( $comments ) ); ?></strong><span><?php esc_html_e( 'Approved comments', 'nstarter' ); ?></span></div>
			</section>
			<?php
			return (string) ob_get_clean();
		}
	);

	nstarter_register_live_section(
		'latest_posts',
		static function ( array $args ): string {
			$count = isset( $args['count'] ) ? max( 1, min( 12, absint( $args['count'] ) ) ) : 3;
			$query = new WP_Query(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => $count,
					'ignore_sticky_posts' => true,
				)
			);

			ob_start();
			?>
			<section class="nstarter-latest">
				<div class="nstarter-latest__heading">
					<p class="nstarter-kicker"><?php esc_html_e( 'Updated from WordPress', 'nstarter' ); ?></p>
					<h2><?php esc_html_e( 'Latest stories', 'nstarter' ); ?></h2>
				</div>
				<div class="nstarter-latest__grid">
					<?php if ( $query->have_posts() ) : ?>
						<?php while ( $query->have_posts() ) : ?>
							<?php $query->the_post(); ?>
							<article class="nstarter-story">
								<p><?php echo esc_html( get_the_date() ); ?></p>
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							</article>
						<?php endwhile; ?>
					<?php else : ?>
						<article class="nstarter-story"><h3><?php esc_html_e( 'Your newest posts will appear here.', 'nstarter' ); ?></h3></article>
					<?php endif; ?>
				</div>
			</section>
			<?php
			wp_reset_postdata();

			return (string) ob_get_clean();
		}
	);

	nstarter_register_live_section(
		'featured_pages',
		static function ( array $args, int $post_id ): string {
			$count = isset( $args['count'] ) ? max( 1, min( 8, absint( $args['count'] ) ) ) : 3;
			$query = new WP_Query(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => $count,
					'post__not_in'   => array( $post_id ),
					'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				)
			);

			ob_start();
			?>
			<section class="live-pages">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : ?>
						<?php $query->the_post(); ?>
						<article class="live-page-card">
							<span><?php echo esc_html( str_pad( (string) ( $query->current_post + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ?: __( 'Open this page to discover more.', 'nstarter' ) ); ?></p>
						</article>
					<?php endwhile; ?>
				<?php else : ?>
					<article class="live-page-card"><span>01</span><h3><?php esc_html_e( 'More pages will appear here', 'nstarter' ); ?></h3><p><?php esc_html_e( 'Publish another page to populate this live section.', 'nstarter' ); ?></p></article>
				<?php endif; ?>
			</section>
			<?php
			wp_reset_postdata();

			return (string) ob_get_clean();
		}
	);

	/**
	 * Register project-specific live sections from a child theme or plugin.
	 */
	do_action( 'nstarter_register_live_sections' );
}
