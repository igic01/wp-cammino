<?php
/** Shop and product taxonomies with the shared Cammino site shell. @package Cammino */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main-content">
	<section class="cammino-products section" aria-labelledby="products-title">
		<div class="container">
			<div class="cammino-products__heading">
				<h1 id="products-title"><?php woocommerce_page_title(); ?></h1>
				<?php do_action( 'woocommerce_archive_description' ); ?>
			</div>
			<div class="cammino-products__catalogue woocommerce">
				<?php
				if ( woocommerce_product_loop() ) {
					do_action( 'woocommerce_before_shop_loop' );
					woocommerce_product_loop_start();
					if ( wc_get_loop_prop( 'total' ) ) {
						while ( have_posts() ) {
							the_post();
							do_action( 'woocommerce_shop_loop' );
							wc_get_template_part( 'content', 'product' );
						}
					}
					woocommerce_product_loop_end();
					do_action( 'woocommerce_after_shop_loop' );
				} else {
					do_action( 'woocommerce_no_products_found' );
				}
				?>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
