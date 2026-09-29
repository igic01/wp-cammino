<?php
/** Run: C:/xampp/php/php.exe woocommerce/tests/catalogue-workflow.php. No database required. */
define( 'ABSPATH', __DIR__ );
define( 'NSTARTER_PATH', dirname( __DIR__, 2 ) );
$hooks = array();
$state = array( 'page' => true, 'shop' => false, 'template' => 'woocommerce/page-products.php', 'products' => 2 );
$checks = 0;
function add_filter( $name, $callback, $priority = 10 ) { global $hooks; $hooks[ $name ][ $priority ] = $callback; }
function add_action( $name, $callback, $priority = 10 ) { add_filter( $name, $callback, $priority ); }
function remove_action( $name, $callback, $priority = 10 ) { global $hooks; if ( ( $hooks[$name][$priority] ?? null ) === $callback ) unset( $hooks[$name][$priority] ); }
function is_page() { global $state; return $state['page']; }
function is_shop() { global $state; return $state['shop']; }
function get_queried_object_id() { return 42; }
function wc_get_page_id( $type ) { return 17; }
function get_page_template_slug( $id ) { global $state; $state['template_id'] = $id; return $state['template']; }
function trailingslashit( $value ) { return rtrim( $value, '/' ) . '/'; }
function get_template_directory_uri() { return '/astra'; }
function wp_dequeue_style( $handle ) { global $state; $state['removed_styles'][] = $handle; }
function wp_dequeue_script( $handle ) { global $state; $state['removed_scripts'][] = $handle; }
function cammino_enqueue_design_assets( ...$args ) { global $state; $state['assets'] = $args; }
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function strip_shortcodes( $text ) { return preg_replace( '/\[[^]]*\]/', '', $text ); }
function wp_trim_words( $text, $count ) { return implode( ' ', array_slice( explode( ' ', $text ), 0, $count ) ); }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain ) { return esc_html( $text ); }
function __( $text, $domain ) { return $text; }
function esc_html_e( $text, $domain ) { echo esc_html( $text ); }
function language_attributes() { echo 'lang="sk"'; }
function bloginfo( $key ) { echo 'UTF-8'; }
function wp_head() {}
function wp_footer() {}
function wp_body_open() {}
function body_class() { echo 'class="cammino-product-catalogue"'; }
function cammino_render_site_header() { echo '<header>Cammino</header>'; }
function cammino_render_site_footer() { echo '<footer>Cammino</footer>'; }
function get_the_title( $id ) { return 'Products & support'; }
function WC() {}
function shortcode_exists( $name ) { global $state; return empty( $state['disabled'] ); }
function woocommerce_output_all_notices() { echo '<div>Notices</div>'; }
function do_shortcode( $code ) { global $state; $state['shortcode'] = $code; return '<ul class="products"><li>Shortcode product</li></ul>'; }
function woocommerce_product_loop() { global $state; return $state['products'] > 0; }
function wc_get_loop_prop( $name ) { global $state; return $state['products']; }
function have_posts() { global $state; return $state['products'] > 0; }
function the_post() { global $state; --$state['products']; }
function wc_get_template_part( $name, $part ) { echo '<li>Native product</li>'; }
function woocommerce_product_loop_start() { echo '<ul class="products">'; }
function woocommerce_product_loop_end() { echo '</ul>'; }
function do_action( $name ) { global $state; $state['actions'][] = $name; }
class WC_Product { public function get_short_description() { return '<b>Help & hope</b> [hidden]'; } }
function expect( $condition, $message ) { global $checks; ++$checks; if ( ! $condition ) throw new RuntimeException( $message ); }
function render_catalogue() { ob_start(); include NSTARTER_PATH . '/woocommerce/page-products.php'; return ob_get_clean(); }
require NSTARTER_PATH . '/woocommerce/bootstrap.php';

$templates = $hooks['theme_page_templates'][30]( array( 'existing.php' => 'Existing template' ) );
expect( isset( $templates['woocommerce/page-products.php'] ) && 'Cammino — WooCommerce products' === $templates['woocommerce/page-products.php'], 'Catalogue is explicitly available in the template selector without file discovery.' );
expect( 'Existing template' === $templates['existing.php'], 'Registration preserves other page templates.' );
expect( cammino_is_product_catalogue(), 'Selected regular page uses catalogue.' );
expect( $hooks['template_include'][100]( '/original.php' ) === NSTARTER_PATH . '/woocommerce/page-products.php', 'Selected template is routed after WooCommerce.' );
expect( in_array( 'cammino-visual-page', $hooks['body_class'][10]( array() ), true ), 'Shared layout classes are set.' );
$wp_styles = (object) array( 'queue' => array( 'astra-theme-css', 'parent-extra', 'woocommerce-general' ), 'registered' => array( 'parent-extra' => (object) array( 'src' => '/astra/extra.css' ) ) );
$wp_scripts = (object) array( 'queue' => array( 'astra-theme-js', 'wc-add-to-cart' ), 'registered' => array() );
$hooks['wp_enqueue_scripts'][1001]();
expect( $state['removed_styles'] === array( 'astra-theme-css', 'parent-extra', 'cammino-child' ), 'Parent presentation is isolated while WooCommerce styles remain.' );
expect( $state['removed_scripts'] === array( 'astra-theme-js' ), 'Native cart scripts remain loaded.' );
expect( $state['assets'][1] === '/woocommerce/assets/products.css', 'Catalogue styles load from WooCommerce folder.' );
$html = render_catalogue();
expect( str_contains( $html, 'Products &amp; support' ) && str_contains( $html, 'Shortcode product' ), 'Regular page renders escaped title and live shortcode.' );
expect( str_contains( $state['shortcode'], 'paginate="true"' ) && str_contains( $state['shortcode'], 'visibility="catalog"' ), 'Regular catalogue paginates and respects catalogue visibility.' );
expect( $hooks['wc_get_template_part'][100]( '/original.php', 'content', 'product' ) === NSTARTER_PATH . '/woocommerce/parts/product-card.php', 'Catalogue uses its own card instead of Astra loop hooks.' );
$state['page'] = false; $state['shop'] = true;
expect( cammino_is_product_catalogue() && 17 === $state['template_id'], 'Shop archive checks the assigned Shop page template.' );
$html = render_catalogue();
expect( substr_count( $html, 'Native product' ) === 2, 'Shop archive renders the native product query.' );
expect( in_array( 'woocommerce_before_shop_loop', $state['actions'], true ) && in_array( 'woocommerce_after_shop_loop', $state['actions'], true ), 'Shop retains notices and pagination hooks.' );
$state['actions'] = array(); render_catalogue();
expect( in_array( 'woocommerce_no_products_found', $state['actions'], true ), 'Empty Shop uses the native empty-state hook.' );
$state['shop'] = false; $state['page'] = true; $state['disabled'] = true;
expect( str_contains( render_catalogue(), 'WooCommerce.' ), 'Inactive plugin shows an explanation without errors.' );
$state['template'] = '';
expect( ! cammino_is_product_catalogue() && '/original.php' === $hooks['template_include'][100]( '/original.php' ), 'Unassigned pages retain their original template.' );
$state['page'] = false;
expect( ! cammino_is_product_catalogue(), 'Product details and other archives are unaffected.' );
expect( $hooks['wc_get_template_part'][100]( '/original.php', 'content', 'product' ) === '/original.php', 'Other pages retain their original product cards.' );
echo "Passed $checks catalogue workflow checks.\n";
