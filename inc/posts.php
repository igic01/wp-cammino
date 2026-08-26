<?php
/**
 * Cammino post routing, event details, and shared rendering helpers.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CAMMINO_EVENT_DATE_META     = '_cammino_event_date';
const CAMMINO_EVENT_LOCATION_META = '_cammino_event_location';
const CAMMINO_EVENT_STATUS_META   = '_cammino_event_status';

/**
 * Return the supported Cammino post destinations.
 *
 * @return array<string,string>
 */
function cammino_get_post_placements(): array {
	return array(
		'article' => __( 'Článok', 'cammino' ),
		'event'   => __( 'Podujatie', 'cammino' ),
	);
}

/**
 * Infer a post's destination from its slug.
 */
function cammino_get_post_placement( int $post_id ): string {
	if ( 'post' !== get_post_type( $post_id ) ) {
		return '';
	}

	$slug = (string) get_post_field( 'post_name', $post_id );

	return preg_match( '/^(event|podujatie)(-|$)/i', $slug ) ? 'event' : 'article';
}

/**
 * Is the current request a Cammino-managed single post?
 */
function cammino_is_managed_post_request(): bool {
	return is_singular( 'post' );
}

add_action( 'init', 'cammino_register_post_meta' );

/**
 * Register optional event fields for REST and block-editor compatibility.
 */
function cammino_register_post_meta(): void {
	$common = array(
		'object_subtype' => 'post',
		'single'         => true,
		'show_in_rest'   => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'  => static fn(): bool => current_user_can( 'edit_posts' ),
	);

	register_post_meta( 'post', CAMMINO_EVENT_DATE_META, array_merge( $common, array( 'type' => 'string' ) ) );
	register_post_meta( 'post', CAMMINO_EVENT_LOCATION_META, array_merge( $common, array( 'type' => 'string' ) ) );
	register_post_meta( 'post', CAMMINO_EVENT_STATUS_META, array_merge( $common, array( 'type' => 'string' ) ) );
}

add_action( 'add_meta_boxes_post', 'cammino_add_post_settings_meta_box' );

/**
 * Add destination guidance and optional event settings to normal posts.
 */
function cammino_add_post_settings_meta_box(): void {
	add_meta_box(
		'cammino-post-settings',
		__( 'Cammino príspevok', 'cammino' ),
		'cammino_render_post_settings_meta_box',
		'post',
		'side',
		'high',
		array( '__block_editor_compatible_meta_box' => true )
	);
}

/**
 * Render destination guidance and optional event fields.
 */
function cammino_render_post_settings_meta_box( WP_Post $post ): void {
	$placement = cammino_get_post_placement( $post->ID );
	$date      = (string) get_post_meta( $post->ID, CAMMINO_EVENT_DATE_META, true );
	$location  = (string) get_post_meta( $post->ID, CAMMINO_EVENT_LOCATION_META, true );
	$status    = (string) get_post_meta( $post->ID, CAMMINO_EVENT_STATUS_META, true );

	wp_nonce_field( 'cammino_save_post_settings', 'cammino_post_settings_nonce' );
	?>
	<p>
		<strong><?php esc_html_e( 'Aktuálne umiestnenie:', 'cammino' ); ?></strong>
		<?php echo esc_html( cammino_get_post_placements()[ $placement ] ?? cammino_get_post_placements()['article'] ); ?>
	</p>
	<p>
		<?php esc_html_e( 'Slug začínajúci na', 'cammino' ); ?> <code>event-</code> <?php esc_html_e( 'alebo', 'cammino' ); ?> <code>podujatie-</code> <?php esc_html_e( 'zaradí príspevok medzi Podujatia. Každý iný slug ho zaradí medzi Novinky.', 'cammino' ); ?>
	</p>
	<p style="margin-top:14px"><strong><?php esc_html_e( 'Údaje podujatia', 'cammino' ); ?></strong><br><small><?php esc_html_e( 'Použijú sa iba pri type Podujatie.', 'cammino' ); ?></small></p>
	<p>
		<label for="cammino-event-date"><?php esc_html_e( 'Dátum a čas', 'cammino' ); ?></label>
		<input id="cammino-event-date" name="cammino_event_date" type="datetime-local" value="<?php echo esc_attr( $date ); ?>" style="width:100%">
	</p>
	<p>
		<label for="cammino-event-location"><?php esc_html_e( 'Miesto', 'cammino' ); ?></label>
		<input id="cammino-event-location" name="cammino_event_location" type="text" value="<?php echo esc_attr( $location ); ?>" style="width:100%">
	</p>
	<p>
		<label for="cammino-event-status"><?php esc_html_e( 'Stav', 'cammino' ); ?></label>
		<input id="cammino-event-status" name="cammino_event_status" type="text" value="<?php echo esc_attr( $status ); ?>" placeholder="<?php esc_attr_e( 'Registrácia otvorená', 'cammino' ); ?>" style="width:100%">
	</p>
	<?php
}

add_action( 'save_post_post', 'cammino_save_post_settings' );

/**
 * Save optional event fields.
 */
function cammino_save_post_settings( int $post_id ): void {
	$nonce = isset( $_POST['cammino_post_settings_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['cammino_post_settings_nonce'] ) )
		: '';

	if (
		'' === $nonce
		|| ! wp_verify_nonce( $nonce, 'cammino_save_post_settings' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}

	$fields = array(
		CAMMINO_EVENT_DATE_META     => isset( $_POST['cammino_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['cammino_event_date'] ) ) : '',
		CAMMINO_EVENT_LOCATION_META => isset( $_POST['cammino_event_location'] ) ? sanitize_text_field( wp_unslash( $_POST['cammino_event_location'] ) ) : '',
		CAMMINO_EVENT_STATUS_META   => isset( $_POST['cammino_event_status'] ) ? sanitize_text_field( wp_unslash( $_POST['cammino_event_status'] ) ) : '',
	);

	if ( '' !== $fields[ CAMMINO_EVENT_DATE_META ] && ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $fields[ CAMMINO_EVENT_DATE_META ] ) ) {
		$fields[ CAMMINO_EVENT_DATE_META ] = '';
	}

	foreach ( $fields as $meta_key => $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}
}

/**
 * Get posts assigned to one Cammino placement.
 *
 * @return WP_Post[]
 */
function cammino_get_placed_posts( string $placement, int $limit = 12 ): array {
	if ( ! isset( cammino_get_post_placements()[ $placement ] ) ) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
		)
	);

	$posts = array_values(
		array_filter(
			$posts,
			static fn( WP_Post $post ): bool => $placement === cammino_get_post_placement( $post->ID )
		)
	);

	return array_slice( $posts, 0, $limit );
}

/**
 * Return a post image, with the local design placeholder as fallback.
 */
function cammino_get_post_image_url( int $post_id, string $size = 'large' ): string {
	$image = get_the_post_thumbnail_url( $post_id, $size );

	return is_string( $image ) && '' !== $image
		? $image
		: NSTARTER_URL . '/assets/images/placeholder.webp';
}

/**
 * Return the first category used as a visual label/filter.
 *
 * @return array{slug:string,name:string}
 */
function cammino_get_post_category( int $post_id ): array {
	$categories = get_the_category( $post_id );

	if ( ! empty( $categories ) ) {
		return array(
			'slug' => sanitize_title( $categories[0]->slug ),
			'name' => $categories[0]->name,
		);
	}

	return array(
		'slug' => 'novinky',
		'name' => __( 'Novinky', 'cammino' ),
	);
}

/**
 * Estimate reading time from post content.
 */
function cammino_get_reading_minutes( int $post_id ): int {
	$content = (string) get_post_field( 'post_content', $post_id );
	$text    = wp_strip_all_tags( strip_shortcodes( $content ) );
	$words   = preg_match_all( '/[\p{L}\p{N}]+/u', $text, $matches );

	return max( 1, (int) ceil( ( false === $words ? 0 : $words ) / 200 ) );
}

/**
 * Resolve the page currently assigned to the Cammino News design.
 */
function cammino_get_news_page_url(): string {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template',
			'meta_value'     => nstarter_get_source_template_path( 'news' ),
		)
	);

	return ! empty( $pages ) ? (string) get_permalink( (int) $pages[0] ) : (string) home_url( '/novinky/' );
}

/**
 * Get the timestamp used on event listing cards.
 */
function cammino_get_event_timestamp( int $post_id ): int {
	$value = (string) get_post_meta( $post_id, CAMMINO_EVENT_DATE_META, true );

	if ( '' !== $value ) {
		$timezone = wp_timezone();
		$date     = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $value, $timezone );
		if ( $date instanceof DateTimeImmutable ) {
			return $date->getTimestamp();
		}
	}

	return (int) get_post_timestamp( $post_id );
}

/**
 * Format a title with the final word highlighted like the reference design.
 */
function cammino_format_display_title( string $title ): string {
	$parts = preg_split( '/\s+/u', trim( $title ) );

	if ( ! is_array( $parts ) || count( $parts ) < 2 ) {
		return esc_html( $title );
	}

	$last = array_pop( $parts );

	return esc_html( implode( ' ', $parts ) ) . ' <em>' . esc_html( (string) $last ) . '</em>';
}

/**
 * Render the event placement on the News page.
 */
function cammino_render_news_events(): string {
	$events = cammino_get_placed_posts( 'event', 20 );
	$now    = current_datetime()->getTimestamp();

	usort(
		$events,
		static function ( WP_Post $first, WP_Post $second ) use ( $now ): int {
			$first_time  = cammino_get_event_timestamp( $first->ID );
			$second_time = cammino_get_event_timestamp( $second->ID );
			$first_past  = $first_time < $now;
			$second_past = $second_time < $now;

			if ( $first_past !== $second_past ) {
				return $first_past ? 1 : -1;
			}

			return $first_past ? $second_time <=> $first_time : $first_time <=> $second_time;
		}
	);

	if ( empty( $events ) ) {
		return '<div class="news-live-empty"><strong>' . esc_html__( 'Zatiaľ nie sú publikované žiadne podujatia.', 'cammino' ) . '</strong><span>' . esc_html__( 'Slug podujatia musí začínať na event- alebo podujatie-.', 'cammino' ) . '</span></div>';
	}

	$featured = array_shift( $events );
	$events   = array_slice( $events, 0, 2 );
	$render_date = static function ( int $post_id ): array {
		$timestamp = cammino_get_event_timestamp( $post_id );

		return array(
			'day'   => wp_date( 'd', $timestamp ),
			'month' => mb_strtoupper( wp_date( 'M', $timestamp ) ),
			'time'  => wp_date( 'H:i', $timestamp ),
			'iso'   => wp_date( 'c', $timestamp ),
		);
	};

	ob_start();
	?>
	<div class="active-events-grid">
		<?php
		$date      = $render_date( $featured->ID );
		$category  = cammino_get_post_category( $featured->ID );
		$location  = (string) get_post_meta( $featured->ID, CAMMINO_EVENT_LOCATION_META, true );
		$status    = (string) get_post_meta( $featured->ID, CAMMINO_EVENT_STATUS_META, true );
		$location  = '' !== $location ? $location : __( 'Cammino', 'cammino' );
		$status    = '' !== $status ? $status : __( 'Viac informácií', 'cammino' );
		$permalink = get_permalink( $featured );
		?>
		<article class="active-event active-event--featured" data-news-reveal="left">
			<a class="active-event__image" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Zobraziť podujatie: %s', 'cammino' ), get_the_title( $featured ) ) ); ?>">
				<img src="<?php echo esc_url( cammino_get_post_image_url( $featured->ID, 'large' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $featured ) ); ?>" width="1200" height="800">
				<span class="event-status"><i class="fa-solid fa-circle" aria-hidden="true"></i> <?php echo esc_html( $status ); ?></span>
			</a>
			<div class="active-event__content">
				<div class="active-event__topline">
					<time class="active-event__date" datetime="<?php echo esc_attr( $date['iso'] ); ?>"><strong><?php echo esc_html( $date['day'] ); ?></strong><span><?php echo esc_html( $date['month'] ); ?></span></time>
					<div><span><?php echo esc_html( $location ); ?></span><strong><?php echo esc_html( $date['time'] ); ?></strong></div>
				</div>
				<span class="active-event__type"><?php echo esc_html( $category['name'] ); ?></span>
				<h2><?php echo esc_html( get_the_title( $featured ) ); ?></h2>
				<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $featured ), 28 ) ); ?></p>
				<div class="active-event__footer">
					<span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?php echo esc_html( $location ); ?></span>
					<a href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'Zobraziť podujatie', 'cammino' ); ?> <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
				</div>
			</div>
		</article>

		<?php if ( ! empty( $events ) ) : ?>
			<div class="active-events-list">
				<?php foreach ( $events as $index => $event ) : ?>
					<?php
					$date     = $render_date( $event->ID );
					$location = (string) get_post_meta( $event->ID, CAMMINO_EVENT_LOCATION_META, true );
					$location = '' !== $location ? $location : __( 'Cammino', 'cammino' );
					?>
					<a class="active-event active-event--compact <?php echo 0 === $index % 2 ? 'active-event--sage' : 'active-event--apricot'; ?>" href="<?php echo esc_url( get_permalink( $event ) ); ?>" data-news-reveal="right" data-delay="<?php echo esc_attr( (string) ( 90 * ( $index + 1 ) ) ); ?>">
						<time class="active-event__date" datetime="<?php echo esc_attr( $date['iso'] ); ?>"><strong><?php echo esc_html( $date['day'] ); ?></strong><span><?php echo esc_html( $date['month'] ); ?></span></time>
						<div class="active-event__compact-copy">
							<span class="active-event__type"><?php echo esc_html( $location . ' · ' . $date['time'] ); ?></span>
							<h2><?php echo esc_html( get_the_title( $event ) ); ?></h2>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $event ), 20 ) ); ?></p>
						</div>
						<span class="active-event__arrow"><i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render searchable article cards on the News page.
 */
function cammino_render_news_articles(): string {
	$posts      = cammino_get_placed_posts( 'article', 24 );
	$categories = array();

	foreach ( $posts as $post ) {
		$category                         = cammino_get_post_category( $post->ID );
		$categories[ $category['slug'] ] = $category['name'];
	}

	ob_start();
	?>
	<div class="article-toolbar" data-news-reveal="up" data-delay="80">
		<label class="article-search" for="cammino-article-search">
			<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
			<span class="sr-only"><?php esc_html_e( 'Hľadať články', 'cammino' ); ?></span>
			<input id="cammino-article-search" type="search" placeholder="<?php esc_attr_e( 'Hľadať článok', 'cammino' ); ?>" autocomplete="off" data-article-search>
		</label>
		<div class="category-filters" role="group" aria-label="<?php esc_attr_e( 'Filtrovať články podľa kategórie', 'cammino' ); ?>">
			<button class="filter-chip is-active" type="button" data-filter="all" aria-pressed="true"><i class="fa-solid fa-sliders" aria-hidden="true"></i> <?php esc_html_e( 'Všetko', 'cammino' ); ?></button>
			<?php foreach ( $categories as $slug => $name ) : ?>
				<button class="filter-chip" type="button" data-filter="<?php echo esc_attr( $slug ); ?>" aria-pressed="false"><i class="fa-solid fa-tag" aria-hidden="true"></i> <?php echo esc_html( $name ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( empty( $posts ) ) : ?>
		<div class="news-live-empty"><strong><?php esc_html_e( 'Zatiaľ nie sú publikované žiadne články.', 'cammino' ); ?></strong><span><?php esc_html_e( 'Každý publikovaný príspevok bez event- alebo podujatie- prefixu sa zobrazí tu.', 'cammino' ); ?></span></div>
	<?php else : ?>
		<div class="posts-grid" data-posts-grid>
			<?php
			$card_styles = array( 'post-card--sage', 'post-card--apricot', 'post-card--cream', 'post-card--coral' );
			foreach ( $posts as $index => $post ) :
				$category = cammino_get_post_category( $post->ID );
				$minutes  = cammino_get_reading_minutes( $post->ID );
				$permalink = get_permalink( $post );
				?>
				<article class="post-card <?php echo esc_attr( $card_styles[ $index % count( $card_styles ) ] ); ?>" data-category="<?php echo esc_attr( $category['slug'] ); ?>" data-news-reveal="up" data-delay="<?php echo esc_attr( (string) ( 80 * ( $index % 3 ) ) ); ?>">
					<a class="post-card__image" href="<?php echo esc_url( $permalink ); ?>">
						<img src="<?php echo esc_url( cammino_get_post_image_url( $post->ID, 'large' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $post ) ); ?>" width="1200" height="800" loading="lazy">
						<span class="post-category"><?php echo esc_html( $category['name'] ); ?></span>
						<span class="post-card__arrow"><i class="fa-solid fa-arrow-right-long icon-diagonal" aria-hidden="true"></i></span>
					</a>
					<div class="post-card__content">
						<div class="post-meta"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $post ) ); ?>"><?php echo esc_html( get_the_date( '', $post ) ); ?></time><span></span><span><?php echo esc_html( sprintf( _n( '%d min', '%d min', $minutes, 'cammino' ), $minutes ) ); ?></span></div>
						<h3><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 22 ) ); ?></p>
						<a class="post-read-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Čítať: %s', 'cammino' ), get_the_title( $post ) ) ); ?>"><?php esc_html_e( 'Čítať ďalej', 'cammino' ); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="empty-results" hidden data-empty-results>
		<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
		<h3><?php esc_html_e( 'Nenašli sme žiadny článok', 'cammino' ); ?></h3>
		<p><?php esc_html_e( 'Skúste iné slovo alebo kategóriu.', 'cammino' ); ?></p>
		<button type="button" class="button button--coral" data-clear-filters><?php esc_html_e( 'Vymazať filtre', 'cammino' ); ?></button>
	</div>
	<?php

	return (string) ob_get_clean();
}

add_filter( 'template_include', 'cammino_use_single_post_template', 99 );

/**
 * Route every normal post through the shared Cammino single design.
 */
function cammino_use_single_post_template( string $template ): string {
	if ( cammino_is_managed_post_request() ) {
		$cammino_template = NSTARTER_PATH . '/templates/single-post.php';
		if ( is_file( $cammino_template ) ) {
			return $cammino_template;
		}
	}

	return $template;
}
