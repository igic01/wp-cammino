<?php
/** Run with PHP, or pass --preview to render the real card and CSS for browser checks. */
define( 'ABSPATH', __DIR__ );
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_attr( $value ); }
function esc_html_e( $value, $domain ) { echo esc_html( $value ); }
function wp_kses_post( $value ) { return $value; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function strip_shortcodes( $value ) { return preg_replace( '/\[[^]]*\]/', '', $value ); }
function wp_trim_words( $value, $count ) { return implode( ' ', array_slice( explode( ' ', $value ), 0, $count ) ); }
function wc_product_class( $class, $product ) { echo 'class="product ' . esc_attr( $class ) . '"'; }
function woocommerce_template_loop_add_to_cart() {
	global $product;
	echo '<a class="button product_type_' . $product->type . '" href="/product" data-product_id="521">' . ( 'variable' === $product->type ? 'Vybrať možnosti' : 'Viac info' ) . '</a>';
}
class WC_Product {
	public $visible = true;
	public $stock = true;
	public $sale = false;
	public $type = 'simple';
	public $name = 'Test';
	public $price = '';
	public $description = 'Test Short Description Description Description Description Description';
	public function is_visible() { return $this->visible; }
	public function is_in_stock() { return $this->stock; }
	public function is_on_sale() { return $this->sale; }
	public function get_name() { return $this->name; }
	public function get_permalink() { return '/product'; }
	public function get_short_description() { return $this->description; }
	public function get_price_html() { return $this->price; }
	public function get_image( $size, $attrs ) { return '<img src="/assets/images/placeholder.webp" alt="' . esc_attr( $this->name ) . '" loading="lazy" width="768" height="768">'; }
}
function render_card() { ob_start(); include dirname( __DIR__ ) . '/parts/product-card.php'; return ob_get_clean(); }
$product = new WC_Product();
$cards = array( render_card() );
$product->name = 'Vzdelávanie & mentoring'; $product->type = 'variable'; $product->price = '20,00 € – 50,00 €';
$cards[] = render_card();
$product->name = 'Praktické dielne'; $product->sale = true; $product->price = '<del>30,00 €</del><ins>20,00 €</ins>'; $product->description = '';
$cards[] = render_card();
$product->name = 'Komunitné aktivity'; $product->stock = false; $product->description = '<b>Pomoc & nádej</b> [hidden]';
$cards[] = render_card();
if ( in_array( '--preview', $argv, true ) ) {
	echo '<!doctype html><html lang="sk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/cammino-base.css"><link rel="stylesheet" href="/woocommerce/assets/products.css"></head><body class="cammino-visual-page cammino-product-catalogue"><main id="main-content"><section class="section"><div class="container"><div class="cammino-products__catalogue woocommerce"><ul class="products">' . implode( '', $cards ) . '</ul></div></div></section></main></body></html>';
	exit;
}
$checks = 0;
function expect_card( $condition, $message ) { global $checks; ++$checks; if ( ! $condition ) throw new RuntimeException( $message ); }
foreach ( $cards as $html ) {
	$doc = new DOMDocument(); @$doc->loadHTML( '<?xml encoding="UTF-8">' . $html ); $xpath = new DOMXPath( $doc );
	expect_card( 1 === $xpath->query( '//div[@class="cammino-product-card__action"]/a' )->length, 'Card has exactly one WooCommerce action.' );
	expect_card( 0 === $xpath->query( '//a[@class="cammino-product-card__image"]//p' )->length, 'Description stays out of the image area.' );
	expect_card( 1 === $xpath->query( '//div[@class="cammino-product-card__body"]/h2/a' )->length, 'Title is a link in the card body.' );
	expect_card( ! str_contains( $html, 'astra-shop' ) && ! str_contains( $html, 'Uncategorized' ), 'Astra wrappers and generic category are absent.' );
}
expect_card( str_contains( $cards[1], 'Vzdelávanie &amp; mentoring' ), 'Product title is escaped.' );
expect_card( str_contains( $cards[1], 'product_type_variable' ) && str_contains( $cards[1], 'Vybrať možnosti' ), 'Variable product retains its native action.' );
expect_card( str_contains( $cards[2], 'Zľava' ) && ! str_contains( $cards[2], 'cammino-product-card__description' ), 'Sale and missing description render cleanly.' );
expect_card( str_contains( $cards[3], 'Vypredané' ) && str_contains( $cards[3], 'Pomoc &amp; nádej' ) && ! str_contains( $cards[3], '[hidden]' ), 'Out-of-stock badge and clean description render.' );
$product->visible = false;
expect_card( '' === render_card(), 'Hidden products produce no card.' );
echo "Passed $checks product card checks.\n";
