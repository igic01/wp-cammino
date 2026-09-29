<?php
/** Archives, search, blog index, and 404 pages with the shared site shell. @package Cammino */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main-content" class="cammino-default-content">
	<div class="container cammino-page">
		<?php if ( is_404() ) : ?>
			<h1 class="cammino-page-title"><?php esc_html_e( 'Stránku sa nepodarilo nájsť', 'cammino' ); ?></h1>
			<p><?php esc_html_e( 'Skúste vyhľadať, čo potrebujete, alebo sa vráťte na úvodnú stránku.', 'cammino' ); ?></p>
			<?php get_search_form(); ?>
			<a class="button button--coral" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Späť na domov', 'cammino' ); ?></a>
		<?php else : ?>
			<h1 class="cammino-page-title"><?php
			if ( is_search() ) {
				/* translators: %s: search query. */
				printf( esc_html__( 'Výsledky vyhľadávania: %s', 'cammino' ), esc_html( get_search_query() ) );
			} elseif ( is_archive() ) {
				the_archive_title();
			} else {
				esc_html_e( 'Novinky', 'cammino' );
			}
			?></h1>
			<?php if ( is_search() ) { get_search_form(); } ?>
			<?php if ( is_archive() ) { the_archive_description( '<div class="entry-content">', '</div>' ); } ?>
			<div class="cammino-listing">
				<?php while ( have_posts() ) : the_post(); ?>
					<article <?php post_class( 'cammino-listing-card' ); ?>>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<div class="entry-content"><?php the_excerpt(); ?></div>
						<a class="cammino-listing-link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Čítať viac', 'cammino' ); ?> <span aria-hidden="true">↗</span></a>
					</article>
				<?php endwhile; ?>
			</div>
			<?php if ( 0 === $GLOBALS['wp_query']->post_count ) : ?>
				<p><?php esc_html_e( 'Nenašli sa žiadne výsledky.', 'cammino' ); ?></p>
			<?php endif; ?>
			<?php the_posts_pagination(); ?>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
