<?php
/**
 * Template Name: Cammino — Podujatie / Projekt / Príbeh pomoci
 * Template Post Type: post
 *
 * Shared article layout for events, projects, impact stories and legacy articles.
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
$cammino_image      = cammino_get_post_image_url( $cammino_post_id, 'full' );
$cammino_event_date = 'event' === $cammino_placement
	? (string) get_post_meta( $cammino_post_id, CAMMINO_EVENT_DATE_META, true )
	: '';
$cammino_event_location = 'event' === $cammino_placement
	? (string) get_post_meta( $cammino_post_id, CAMMINO_EVENT_LOCATION_META, true )
	: '';
$cammino_hide_event_image = 'event' === $cammino_placement
	&& '1' === (string) get_post_meta( $cammino_post_id, CAMMINO_EVENT_HIDE_IMAGE_META, true );
$cammino_timestamp  = '' !== $cammino_event_date ? cammino_get_event_timestamp( $cammino_post_id ) : 0;
$cammino_type_label  = cammino_get_post_type_label( $cammino_placement );
$cammino_sticker     = $cammino_type_label;
$cammino_deck        = has_excerpt( $cammino_post )
	? get_the_excerpt( $cammino_post )
	: '';
$cammino_caption     = '';
$cammino_thumbnail   = get_post_thumbnail_id( $cammino_post_id );

if ( $cammino_thumbnail ) {
	$cammino_caption = (string) wp_get_attachment_caption( $cammino_thumbnail );
}

$cammino_content = cammino_get_post_visual_content( $cammino_post_id );
$cammino_facts = array();
if ( 'event' === $cammino_placement ) {
	$cammino_event_status = (string) get_post_meta( $cammino_post_id, CAMMINO_EVENT_STATUS_META, true );
	if ( '' !== $cammino_event_status ) {
		$cammino_facts['Stav'] = $cammino_event_status;
	}
}
foreach ( cammino_get_post_detail_fields() as $key => $field ) {
	$value = (string) get_post_meta( $cammino_post_id, '_cammino_' . $key, true );
	if ( $field['type'] === $cammino_placement && '' !== $value ) { $cammino_facts[ $field['label'] ] = $value; }
}
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
				<div class="cammino-post-details-variable" data-cammino-post-details-element data-nstarter-variable-section="cammino_post_details" data-nstarter-variable-label="<?php echo esc_attr( 'event' === $cammino_placement ? __( 'Event details', 'cammino' ) : __( 'Post title', 'cammino' ) ); ?>" data-nstarter-variable-type="text" data-nstarter-variable-control="post-details">
					<h1 data-cammino-post-title data-article-reveal="up" data-delay="120"><?php echo cammino_format_display_title( get_the_title( $cammino_post ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
					<?php if ( '' !== $cammino_deck ) : ?>
						<p class="article-deck" data-article-reveal="up" data-delay="180"><?php echo esc_html( $cammino_deck ); ?></p>
					<?php endif; ?>
					<?php if ( 'event' === $cammino_placement ) : ?>
						<div class="cammino-event-summary" data-cammino-event-summary data-article-reveal="up" data-delay="240">
							<span><i class="fa-regular fa-calendar" aria-hidden="true"></i><time data-cammino-event-date datetime="<?php echo esc_attr( $cammino_event_date ); ?>"><?php echo esc_html( $cammino_timestamp ? wp_date( get_option( 'date_format' ) . ' · H:i', $cammino_timestamp ) : __( 'Dátum bude doplnený', 'cammino' ) ); ?></time></span>
							<span><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span data-cammino-event-location><?php echo esc_html( '' !== $cammino_event_location ? $cammino_event_location : __( 'Miesto bude doplnené', 'cammino' ) ); ?></span></span>
						</div>
					<?php endif; ?>
				</div>
			</header>

			<div class="container article-cover" data-article-reveal="scale" data-cammino-event-cover<?php echo $cammino_hide_event_image ? ' hidden' : ''; ?>>
				<div class="article-cover__frame">
					<img src="<?php echo esc_url( $cammino_image ); ?>" alt="<?php echo esc_attr( get_the_title( $cammino_post ) ); ?>" width="1600" height="1000"<?php echo $cammino_thumbnail ? ' data-attachment-id="' . esc_attr( (string) $cammino_thumbnail ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<button class="cover-sticker" type="button" data-cover-sticker><i class="fa-solid <?php echo esc_attr( cammino_get_post_type_icon( $cammino_placement ) ); ?>" aria-hidden="true"></i> <?php echo esc_html( $cammino_sticker ); ?></button>
				</div>
				<?php if ( '' !== $cammino_caption ) : ?>
					<p class="image-caption"><?php echo esc_html( $cammino_caption ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $cammino_facts ) : ?>
				<dl class="container cammino-post-facts">
					<?php foreach ( $cammino_facts as $label => $value ) : ?><div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div><?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<div class="container article-layout">
				<aside class="article-share" aria-label="Zdieľať" data-article-reveal="left">
					<span>Zdieľať</span>
					<button type="button" data-share="facebook" aria-label="Zdieľať na Facebooku"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></button>
					<button type="button" data-share="linkedin" aria-label="Zdieľať na LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></button>
					<button type="button" data-share="copy" aria-label="Kopírovať odkaz"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
					<span class="copy-feedback" role="status" aria-live="polite" data-copy-feedback></span>
				</aside>

				<div class="article-content" data-nstarter-snapshot-root data-nstarter-content-builder="article" data-nstarter-content-label="Obsah príspevku">
					<?php echo $cammino_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</article>


	</main>

	<?php cammino_render_site_footer(); ?>
	<?php wp_footer(); ?>
</body>
</html>
