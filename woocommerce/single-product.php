<?php
/** Individual products with the shared Cammino site shell. @package Cammino */
defined( 'ABSPATH' ) || exit;
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
	<?php cammino_render_site_header(); ?>
	<main id="main-content">
		<section class="cammino-product-detail section">
			<div class="container woocommerce">
				<a class="cammino-product-back" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
					<span aria-hidden="true">←</span> <?php esc_html_e( 'Späť na produkty', 'cammino' ); ?>
				</a>
				<?php
				while ( have_posts() ) {
					the_post();
					wc_get_template_part( 'content', 'single-product' );
				}
				?>
			</div>
		</section>
	</main>
	<?php cammino_render_site_footer(); ?>
	<?php wp_footer(); ?>
</body>
</html>
