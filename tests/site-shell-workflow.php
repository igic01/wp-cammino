<?php
/** Run with PHP. Checks routing, theme migration, support, and dependency isolation without WordPress. */
define( 'ABSPATH', __DIR__ );
define( 'NSTARTER_PATH', dirname( __DIR__ ) );
$state = array( 'kind' => 'page', 'parent' => 'astra', 'theme_has_parent' => false );
$hooks = array(); $checks = 0;
function add_action( $name, $callback, $priority = 10 ) { global $hooks; $hooks[$name][$priority][] = $callback; }
function add_filter( $name, $callback, $priority = 10 ) { add_action( $name, $callback, $priority ); }
function is_admin() { return false; }
function is_feed() { global $state; return $state['kind'] === 'feed'; }
function is_embed() { global $state; return $state['kind'] === 'embed'; }
function is_page() { global $state; return in_array( $state['kind'], array( 'page', 'visual', 'cart', 'checkout', 'account', 'elementor', 'catalogue' ), true ); }
function is_singular() { global $state; return is_page() || in_array( $state['kind'], array( 'post', 'product', 'custom-post' ), true ); }
function is_shop() { global $state; return $state['kind'] === 'shop'; }
function is_product_taxonomy() { global $state; return $state['kind'] === 'product-category'; }
function get_queried_object_id() { return 42; }
function cammino_is_single_product() { global $state; return $state['kind'] === 'product'; }
function cammino_is_product_archive() { return is_shop() || is_product_taxonomy(); }
function cammino_is_product_catalogue() { global $state; return cammino_is_product_archive() || $state['kind'] === 'catalogue'; }
function cammino_is_managed_post_request() { global $state; return $state['kind'] === 'post'; }
function nstarter_is_visual_page( $id ) { global $state; return $state['kind'] === 'visual'; }
function nstarter_is_editor_request() { return false; }
function get_template() { global $state; return $state['parent']; }
function get_stylesheet() { return 'wp-cammino'; }
function wp_clean_themes_cache( $clear_updates ) { global $state; $state['cache_refreshed'] = true; }
function wp_get_theme() { return new class { public function parent() { global $state; return $state['theme_has_parent']; } }; }
function switch_theme( $stylesheet ) { global $state; $state['switches'] = ( $state['switches'] ?? 0 ) + 1; $state['parent'] = $stylesheet; }
function add_theme_support( $feature, $options = null ) { global $state; $state['supports'][] = $feature; }
function cammino_enqueue_design_assets( ...$args ) { global $state; $state['assets'] = $args; }
function wp_dequeue_style( $handle ) { global $state; $state['removed_styles'][] = $handle; }
function wp_dequeue_script( $handle ) { global $state; $state['removed_scripts'][] = $handle; }
function expect_shell( $condition, $message ) { global $checks; ++$checks; if ( ! $condition ) throw new RuntimeException( $message ); }
require NSTARTER_PATH . '/inc/site-shell.php';

cammino_migrate_standalone_theme();
expect_shell( $state['parent'] === 'wp-cammino' && $state['cache_refreshed'], 'Former child theme becomes standalone after reloading theme metadata.' );
cammino_migrate_standalone_theme();
expect_shell( $state['switches'] === 1, 'Migration is performed once, retaining the same active stylesheet.' );
$state['parent'] = 'astra'; $state['theme_has_parent'] = true;
cammino_migrate_standalone_theme();
expect_shell( $state['switches'] === 1, 'Migration waits until the standalone theme header is installed.' );
cammino_setup_standalone_theme();
foreach ( array( 'title-tag', 'post-thumbnails', 'woocommerce', 'wc-product-gallery-zoom', 'wc-product-gallery-lightbox', 'wc-product-gallery-slider' ) as $support ) {
	expect_shell( in_array( $support, $state['supports'], true ), 'Standalone support: ' . $support );
}
$routes = array(
	'page' => '/page.php', 'elementor' => '/page.php', 'cart' => '/page.php', 'checkout' => '/page.php', 'account' => '/page.php',
	'custom-post' => '/page.php', 'visual' => '/templates/visual-page.php', 'post' => '/templates/single-post.php',
	'product' => '/woocommerce/single-product.php', 'catalogue' => '/woocommerce/page-products.php',
	'shop' => '/woocommerce/archive-products.php', 'product-category' => '/woocommerce/archive-products.php',
	'blog' => '/index.php', 'archive' => '/index.php', 'search' => '/index.php', '404' => '/index.php',
);
foreach ( $routes as $kind => $path ) {
	$state['kind'] = $kind;
	expect_shell( cammino_use_site_shell( '/astra/old.php' ) === NSTARTER_PATH . $path, 'Cammino template for ' . $kind );
}
foreach ( array( 'embed', 'feed' ) as $kind ) {
	$state['kind'] = $kind;
	expect_shell( cammino_use_site_shell( '/wp/native.php' ) === '/wp/native.php', 'WordPress native output for ' . $kind );
}
$state['kind'] = 'archive';
$hooks['wp_enqueue_scripts'][1002][0]();
expect_shell( $state['assets'][1] === '/assets/css/pages/default-page.css', 'Fallback page types receive shared design assets.' );
$body = $hooks['body_class'][PHP_INT_MAX][0]( array( 'ast-container', 'astra-theme', 'woocommerce' ) );
expect_shell( ! in_array( 'ast-container', $body, true ) && in_array( 'woocommerce', $body, true ) && in_array( 'cammino-default-page', $body, true ), 'Legacy Astra body classes are removed and plugin classes preserved.' );
$state['parent'] = 'wp-cammino';
$wp_styles = (object) array( 'registered' => array(
	'astra-theme-css' => (object) array( 'src' => '/themes/astra/main.css', 'deps' => array(), 'extra' => array( 'after' => array( 'legacy CSS' ) ) ),
	'cammino-base' => (object) array( 'src' => '/themes/wp-cammino/base.css', 'deps' => array(), 'extra' => array() ),
	'plugin-style' => (object) array( 'src' => '/plugins/style.css', 'deps' => array( 'astra-theme-css' ), 'extra' => array() ),
) );
$wp_scripts = (object) array( 'registered' => array(
	'legacy-asset' => (object) array( 'src' => '/themes/astra/main.js', 'deps' => array(), 'extra' => array() ),
	'wc-gallery' => (object) array( 'src' => '/plugins/woocommerce/gallery.js', 'deps' => array(), 'extra' => array() ),
) );
cammino_disable_astra_assets();
expect_shell( false === $wp_styles->registered['astra-theme-css']->src && array() === $wp_styles->registered['astra-theme-css']->extra, 'Astra dependencies cannot output stylesheet or inline CSS.' );
expect_shell( false === $wp_scripts->registered['legacy-asset']->src, 'Astra scripts cannot load under a different handle.' );
expect_shell( $wp_styles->registered['cammino-base']->src && $wp_scripts->registered['wc-gallery']->src, 'Cammino CSS and WooCommerce gallery scripts are preserved.' );
expect_shell( array( 'astra-theme-css' ) === $wp_styles->registered['plugin-style']->deps, 'Plugin dependency graph still resolves via the empty Astra registration.' );
$style_header = file_get_contents( NSTARTER_PATH . '/style.css' );
expect_shell( ! preg_match( '/^Template:/m', $style_header ) && is_file( NSTARTER_PATH . '/index.php' ), 'Theme meets standalone requirements without a parent declaration.' );
echo "Passed $checks standalone site-shell checks.\n";
