<?php
/**
 * Default theme fallback.
 *
 * @package NStarter
 */

get_header();
?>
<main style="max-width: 760px; margin: 80px auto; padding: 0 24px;">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<h1><?php esc_html_e( 'Nothing found', 'nstarter' ); ?></h1>
	<?php endif; ?>
</main>
<?php
get_footer();
