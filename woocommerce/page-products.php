<?php
/**
 * Template Name: Cammino — WooCommerce products
 * Template Post Type: page
 *
 * Live product catalogue inspired by snapshot-templates/donate.php.
 * @package Cammino
 */

defined( 'ABSPATH' ) || exit;
$cammino_shop = function_exists( 'is_shop' ) && is_shop();
$cammino_page_id = $cammino_shop ? wc_get_page_id( 'shop' ) : get_queried_object_id();
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
		<section class="cammino-products section" aria-labelledby="products-title">
			<div class="container">
				<div class="cammino-products__heading">
					<h1 id="products-title"><?php echo esc_html( get_the_title( $cammino_page_id ) ); ?></h1>
					<p><?php esc_html_e( 'Vyberte si, čím chcete podporiť našu prácu. Každý nákup pomáha vytvárať nové príležitosti.', 'cammino' ); ?></p>
				</div>
				<div class="cammino-products__catalogue woocommerce">
					<?php
					if ( ! function_exists( 'WC' ) || ! shortcode_exists( 'products' ) ) {
						echo '<p class="woocommerce-info">' . esc_html__( 'Na zobrazenie produktov je potrebný WooCommerce.', 'cammino' ) . '</p>';
					} elseif ( $cammino_shop ) {
						// Preserve the main shop query, including visibility, stock and sorting.
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
					} else {
						woocommerce_output_all_notices();
						echo do_shortcode( '[products limit="12" columns="2" paginate="true" orderby="menu_order title" order="ASC" visibility="catalog"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>
		</section>
	</main>
	<?php cammino_render_site_footer(); ?>
	<?php wp_footer(); ?>
</body>
</html>
