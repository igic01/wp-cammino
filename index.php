<?php
/**
 * Delegate the default template fallback to the Astra parent theme.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$parent_index = nstarter_get_parent_theme_file( 'index.php' );

if ( '' !== $parent_index ) {
	require $parent_index;
	return;
}

get_header( 'nstarter' );
?>
<main id="primary" class="site-main">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
				<?php the_content(); ?>
			</article>
			<?php
		}
	}
	?>
</main>
<?php
get_footer( 'nstarter' );
