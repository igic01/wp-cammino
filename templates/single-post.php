<?php
/**
 * Template Name: Cammino Article / Event
 * Template Post Type: post
 *
 * Shared bare document for Cammino Article and Event posts.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cammino_post_id    = get_queried_object_id();
$cammino_post       = get_post( $cammino_post_id );
$cammino_placement  = cammino_get_post_placement( $cammino_post_id );
$cammino_news_url   = cammino_get_news_page_url();
$cammino_category   = cammino_get_post_category( $cammino_post_id );
$cammino_minutes    = cammino_get_reading_minutes( $cammino_post_id );
$cammino_image      = cammino_get_post_image_url( $cammino_post_id, 'full' );
$cammino_timestamp  = 'event' === $cammino_placement
	? cammino_get_event_timestamp( $cammino_post_id )
	: (int) get_post_timestamp( $cammino_post );
$cammino_type_label  = 'event' === $cammino_placement ? 'Podujatie' : 'Článok';
$cammino_sticker     = $cammino_type_label;
$cammino_deck        = has_excerpt( $cammino_post )
	? get_the_excerpt( $cammino_post )
	: '';
$cammino_caption     = '';
$cammino_thumbnail   = get_post_thumbnail_id( $cammino_post_id );

if ( $cammino_thumbnail ) {
	$cammino_caption = (string) wp_get_attachment_caption( $cammino_thumbnail );
}

$cammino_content    = cammino_get_post_visual_content( $cammino_post_id );
$cammino_related    = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'post__not_in'        => array( $cammino_post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#faf6ee">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div class="reading-progress" aria-hidden="true"><span data-reading-progress></span></div>
	<?php cammino_render_site_header(); ?>

	<main id="main-content">
		<article>
			<header class="article-hero container">
				<a class="article-back" href="<?php echo esc_url( $cammino_news_url ); ?>" data-article-reveal="up">
					<i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> Späť na novinky
				</a>
				<div class="article-tags" data-article-reveal="up" data-delay="60">
					<a href="<?php echo esc_url( $cammino_news_url . ( 'event' === $cammino_placement ? '#events' : '#articles' ) ); ?>" class="article-tag article-tag--primary"><?php echo esc_html( $cammino_type_label ); ?></a>
					<span class="article-tag"><?php echo esc_html( $cammino_category['name'] ); ?></span>
				</div>
				<h1 data-article-reveal="up" data-delay="120"><?php echo cammino_format_display_title( get_the_title( $cammino_post ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
				<?php if ( '' !== $cammino_deck ) : ?>
					<p class="article-deck" data-article-reveal="up" data-delay="180"><?php echo esc_html( $cammino_deck ); ?></p>
				<?php endif; ?>
				<div class="article-byline" data-article-reveal="up" data-delay="240">
					<span class="author-avatar" aria-hidden="true"><i class="fa-solid fa-pen-nib"></i></span>
					<span><strong><?php echo esc_html( get_the_author_meta( 'display_name', (int) $cammino_post->post_author ) ); ?></strong></span>
					<span class="byline-separator" aria-hidden="true"></span>
					<time datetime="<?php echo esc_attr( wp_date( 'c', $cammino_timestamp ) ); ?>"><?php echo esc_html( wp_date( get_option( 'date_format' ), $cammino_timestamp ) ); ?></time>
					<span class="byline-separator" aria-hidden="true"></span>
					<span><?php echo esc_html( $cammino_minutes . ' min čítania' ); ?></span>
				</div>
			</header>

			<div class="container article-cover" data-article-reveal="scale">
				<div class="article-cover__frame">
					<img src="<?php echo esc_url( $cammino_image ); ?>" alt="<?php echo esc_attr( get_the_title( $cammino_post ) ); ?>" width="1600" height="1000"<?php echo $cammino_thumbnail ? ' data-attachment-id="' . esc_attr( (string) $cammino_thumbnail ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<button class="cover-sticker" type="button" data-cover-sticker><i class="fa-solid <?php echo 'event' === $cammino_placement ? 'fa-calendar-days' : 'fa-book-open'; ?>" aria-hidden="true"></i> <?php echo esc_html( $cammino_sticker ); ?></button>
				</div>
				<?php if ( '' !== $cammino_caption ) : ?>
					<p class="image-caption"><?php echo esc_html( $cammino_caption ); ?></p>
				<?php endif; ?>
			</div>

			<div class="container article-layout">
				<aside class="article-share" aria-label="Zdieľať" data-article-reveal="left">
					<span>Zdieľať</span>
					<button type="button" data-share="facebook" aria-label="Zdieľať na Facebooku"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></button>
					<button type="button" data-share="linkedin" aria-label="Zdieľať na LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></button>
					<button type="button" data-share="copy" aria-label="Kopírovať odkaz"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
					<span class="copy-feedback" role="status" aria-live="polite" data-copy-feedback></span>
				</aside>

				<div class="article-content" data-article-reveal="up" data-nstarter-snapshot-root data-nstarter-content-builder="article" data-nstarter-content-label="Obsah článku / podujatia">
					<?php echo $cammino_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</article>

		<?php if ( ! empty( $cammino_related ) ) : ?>
			<section class="related-section section" aria-labelledby="related-title">
				<div class="container">
					<div class="related-heading" data-article-reveal="up">
						<div><span>Čítajte ďalej</span><h2 id="related-title">Ďalšie novinky z Cammina</h2></div>
						<a href="<?php echo esc_url( $cammino_news_url ); ?>">Všetky novinky <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
					</div>
					<div class="related-grid">
						<?php foreach ( $cammino_related as $index => $related ) : ?>
							<?php $related_category = cammino_get_post_category( $related->ID ); ?>
							<a class="related-card" href="<?php echo esc_url( get_permalink( $related ) ); ?>" data-article-reveal="up" data-delay="<?php echo esc_attr( (string) ( 100 * $index ) ); ?>">
								<div class="related-card__image"><img src="<?php echo esc_url( cammino_get_post_image_url( $related->ID, 'medium_large' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $related ) ); ?>" width="700" height="480" loading="lazy"><span><?php echo esc_html( $related_category['name'] ); ?></span></div>
								<div class="related-card__body"><small><?php echo esc_html( get_the_date( '', $related ) . ' · ' . cammino_get_reading_minutes( $related->ID ) . ' min' ); ?></small><h3><?php echo esc_html( get_the_title( $related ) ); ?></h3><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></div>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</main>

	<?php cammino_render_site_footer(); ?>
	<?php wp_footer(); ?>
</body>
</html>
