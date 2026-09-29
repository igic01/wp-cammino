<?php
/** A catalogue card with one product action, independent of Astra's loop hooks. @package Cammino */
defined( 'ABSPATH' ) || exit;
global $product;
if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}
$cammino_product_url = $product->get_permalink();
$cammino_description = wp_trim_words( strip_shortcodes( wp_strip_all_tags( $product->get_short_description() ) ), 30 );
$cammino_price = $product->get_price_html();
?>
<li <?php wc_product_class( 'cammino-product-card', $product ); ?>>
	<a class="cammino-product-card__image" href="<?php echo esc_url( $cammino_product_url ); ?>" aria-label="<?php echo esc_attr( $product->get_name() ); ?>">
		<?php echo $product->get_image( 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( ! $product->is_in_stock() ) : ?>
			<span class="cammino-product-card__badge"><?php esc_html_e( 'Vypredané', 'cammino' ); ?></span>
		<?php elseif ( $product->is_on_sale() ) : ?>
			<span class="cammino-product-card__badge"><?php esc_html_e( 'Zľava', 'cammino' ); ?></span>
		<?php endif; ?>
	</a>
	<div class="cammino-product-card__body">
		<h2 class="cammino-product-card__title"><a href="<?php echo esc_url( $cammino_product_url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h2>
		<?php if ( '' !== $cammino_description ) : ?>
			<p class="cammino-product-card__description"><?php echo esc_html( $cammino_description ); ?></p>
		<?php endif; ?>
		<div class="cammino-product-card__footer">
			<?php if ( '' !== $cammino_price ) : ?>
				<div class="cammino-product-card__price"><?php echo wp_kses_post( $cammino_price ); ?></div>
			<?php endif; ?>
			<div class="cammino-product-card__action"><?php woocommerce_template_loop_add_to_cart(); ?></div>
		</div>
	</div>
</li>
