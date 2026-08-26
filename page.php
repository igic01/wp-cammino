<?php
/**
 * Delegate ordinary WordPress pages, including Elementor pages, to Astra.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$parent_page = nstarter_get_parent_theme_file( 'page.php' );

if ( '' !== $parent_page ) {
	require $parent_page;
	return;
}

get_header( 'nstarter' );
?>
<main id="primary" class="site-main">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>
<?php
get_footer( 'nstarter' );
