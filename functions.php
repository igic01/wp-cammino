<?php
/**
 * Cammino child theme bootstrap.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NSTARTER_VERSION', '1.5.60' );
define( 'NSTARTER_PATH', get_stylesheet_directory() );
define( 'NSTARTER_URL', get_stylesheet_directory_uri() );
define( 'CAMMINO_DONATE_URL', 'https://ozcammino.sk/darovat-v2/' );

require_once NSTARTER_PATH . '/inc/snapshots.php';
require_once NSTARTER_PATH . '/inc/live-sections.php';
require_once NSTARTER_PATH . '/inc/variable-sections.php';
require_once NSTARTER_PATH . '/inc/posts.php';
require_once NSTARTER_PATH . '/inc/editor.php';

add_action( 'init', 'cammino_register_live_sections' );
add_action( 'after_setup_theme', 'cammino_register_navigation' );

/**
 * Register shared Cammino navigation locations.
 */
function cammino_register_navigation(): void {
	register_nav_menus(
		array(
			'new-menu' => __( 'New Menu', 'cammino' ),
		)
	);
}

/**
 * Remove temporary version suffixes from menu labels.
 */
function cammino_clean_nav_title( string $title ): string {
	return trim( (string) preg_replace( '/\s*-v2$/i', '', $title ) );
}

/**
 * Point legacy donation-page links at the current donation page.
 */
function cammino_normalize_donate_url( string $url ): string {
	$path = wp_parse_url( $url, PHP_URL_PATH );

	if ( is_string( $path ) && '/podporte-nas/' === trailingslashit( '/' . ltrim( $path, '/' ) ) ) {
		return CAMMINO_DONATE_URL;
	}

	return $url;
}

/**
 * Render WordPress menu links without list wrappers to match the static design.
 */
class Cammino_Bare_Nav_Walker extends Walker_Nav_Menu {
	/**
	 * Prevent submenu wrappers in the bare-link navigation.
	 *
	 * @param string $output Used to append additional content.
	 * @param int    $depth  Depth of menu item.
	 * @param mixed  $args   Menu args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {}

	/**
	 * Prevent submenu wrapper closing tags in the bare-link navigation.
	 *
	 * @param string $output Used to append additional content.
	 * @param int    $depth  Depth of menu item.
	 * @param mixed  $args   Menu args.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {}

	/**
	 * Start a menu item.
	 *
	 * @param string   $output Used to append additional content.
	 * @param WP_Post  $item   Menu item data object.
	 * @param int      $depth  Depth of menu item.
	 * @param stdClass $args   Menu args.
	 * @param int      $id     Current item ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		if ( 0 !== $depth ) {
			return;
		}

		$classes = array_filter( (array) $item->classes );

		if ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current_page_item', $classes, true ) ) {
			$classes[] = 'is-active';
		}

		$class_attribute = implode( ' ', array_map( 'sanitize_html_class', array_unique( $classes ) ) );
		$attributes      = array(
			'href' => ! empty( $item->url ) ? cammino_normalize_donate_url( $item->url ) : '#',
		);

		if ( '' !== $class_attribute ) {
			$attributes['class'] = $class_attribute;
		}

		if ( '_blank' === $item->target ) {
			$attributes['target'] = '_blank';
			$attributes['rel']    = trim( (string) $item->xfn . ' noopener noreferrer' );
		} elseif ( ! empty( $item->xfn ) ) {
			$attributes['rel'] = $item->xfn;
		}

		if ( ! empty( $item->attr_title ) ) {
			$attributes['title'] = $item->attr_title;
		}

		$output .= '<a';

		foreach ( $attributes as $name => $value ) {
			$output .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
		}

		$output .= '>' . esc_html( cammino_clean_nav_title( (string) $item->title ) ) . '</a>';
	}

	/**
	 * Prevent list item closing tags in the bare-link navigation.
	 *
	 * @param string  $output Used to append additional content.
	 * @param WP_Post $item   Menu item data object.
	 * @param int     $depth  Depth of menu item.
	 * @param mixed   $args   Menu args.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}

/**
 * Render the shared Cammino menu.
 *
 * @param bool $with_cta_icon Whether to add a heart to the final menu link.
 */
function cammino_render_shared_menu( bool $with_cta_icon = false ): void {
	$args = array(
		'container'    => false,
		'depth'        => 1,
		'echo'         => false,
		'fallback_cb'  => false,
		'items_wrap'   => '%3$s',
		'walker'       => new Cammino_Bare_Nav_Walker(),
	);

	if ( has_nav_menu( 'new-menu' ) ) {
		$args['theme_location'] = 'new-menu';
	} else {
		$args['menu'] = 'new-menu';
	}

	$menu = wp_nav_menu( $args );

	if ( is_string( $menu ) && '' !== trim( $menu ) ) {
		if ( $with_cta_icon ) {
			$closing_tag_position = strrpos( $menu, '</a>' );

			if ( false !== $closing_tag_position ) {
				$menu = substr_replace(
					$menu,
					'<i class="fa-solid fa-heart" aria-hidden="true"></i></a>',
					$closing_tag_position,
					4
				);
			}
		}

		echo $menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}

	$fallback_links = array(
		array( home_url( '/' ), __( 'Domov', 'cammino' ) ),
		array( nstarter_get_source_page_url( 'about-us', '/o-nas/' ), __( 'O nás', 'cammino' ) ),
		array( nstarter_get_source_page_url( 'ss', '/pribehy/' ), __( 'Príbehy', 'cammino' ) ),
		array( nstarter_get_source_page_url( 'news', '/novinky/' ) . '#events', __( 'Podujatia', 'cammino' ) ),
		array( nstarter_get_source_page_url( 'news', '/novinky/' ), __( 'Novinky', 'cammino' ) ),
		array( nstarter_get_source_page_url( 'contact', '/kontakt/' ), __( 'Kontakt', 'cammino' ) ),
	);

	$last_fallback_index = array_key_last( $fallback_links );

	foreach ( $fallback_links as $index => $link ) {
		$icon = $with_cta_icon && $index === $last_fallback_index
			? '<i class="fa-solid fa-heart" aria-hidden="true"></i>'
			: '';

		printf(
			'<a href="%s">%s%s</a>',
			esc_url( $link[0] ),
			esc_html( $link[1] ),
			$icon // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}

/**
 * Render the shared Cammino header for custom visual pages.
 */
function cammino_render_site_header(): void {
	?>
	<a class="skip-link" href="#main-content"><?php esc_html_e( 'Preskočiť na obsah', 'cammino' ); ?></a>
	<header class="site-header" data-header>
		<div class="container header-inner">
			<a class="brand" href="<?php echo esc_url( 'https://ozcammino.sk/domov-v2/' ); ?>" aria-label="<?php esc_attr_e( 'Cammino – domov', 'cammino' ); ?>">
				<img src="<?php echo esc_url( NSTARTER_URL . '/assets/logos/new_long_logo.svg' ); ?>" alt="<?php esc_attr_e( 'Cammino', 'cammino' ); ?>" width="1666" height="297">
			</a>

			<button class="nav-toggle" type="button" aria-label="<?php esc_attr_e( 'Otvoriť menu', 'cammino' ); ?>" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
				<i class="fa-solid fa-bars" aria-hidden="true"></i>
			</button>

			<nav class="site-nav" id="site-nav" aria-label="<?php esc_attr_e( 'Hlavná navigácia', 'cammino' ); ?>" data-nav>
				<?php cammino_render_shared_menu( true ); ?>
			</nav>
		</div>
	</header>
	<?php
}

/**
 * Render the shared Cammino footer for custom visual pages.
 */
function cammino_render_site_footer(): void {
	?>
	<footer class="site-footer" id="contact">
		<div class="container footer-main">
			<div class="footer-brand">
				<a class="brand brand--footer" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Cammino – domov', 'cammino' ); ?>">
					<img src="<?php echo esc_url( NSTARTER_URL . '/assets/logos/new_long_logo.svg' ); ?>" alt="<?php esc_attr_e( 'Cammino', 'cammino' ); ?>" width="1666" height="297">
				</a>
				<p><?php esc_html_e( 'Pomáhame mladým ľuďom nájsť cestu k vzdelaniu, práci a samostatnej budúcnosti.', 'cammino' ); ?></p>
				<div class="social-links" aria-label="<?php esc_attr_e( 'Sociálne siete', 'cammino' ); ?>">
					<a href="#" aria-label="<?php esc_attr_e( 'Instagram', 'cammino' ); ?>"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
					<a href="#" aria-label="<?php esc_attr_e( 'Facebook', 'cammino' ); ?>"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
					<a href="#" aria-label="<?php esc_attr_e( 'LinkedIn', 'cammino' ); ?>"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
				</div>
			</div>

			<div class="footer-links">
				<h2><?php esc_html_e( 'Cammino', 'cammino' ); ?></h2>
				<?php cammino_render_shared_menu(); ?>
			</div>

			<div class="footer-contact">
				<h2><?php esc_html_e( 'Prihláste sa na newsletter', 'cammino' ); ?></h2>
				<a href="mailto:management@ozcammino.sk">management@ozcammino.sk</a>
				<p><?php esc_html_e( 'Miletičova 7, Bratislava', 'cammino' ); ?></p>
				<form class="newsletter" action="#" method="post">
					<label class="sr-only" for="cammino-footer-email"><?php esc_html_e( 'Váš e-mail', 'cammino' ); ?></label>
					<input id="cammino-footer-email" type="email" name="email" placeholder="<?php esc_attr_e( 'Váš e-mail', 'cammino' ); ?>" required>
					<button type="submit" aria-label="<?php esc_attr_e( 'Prihlásiť sa na odber', 'cammino' ); ?>"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
				</form>
			</div>
		</div>
		<div class="container footer-bottom">
			<p>© <span data-year></span> <?php esc_html_e( 'Cammino. Každý krok má zmysel.', 'cammino' ); ?></p>
			<div><a href="#"><?php esc_html_e( 'Ochrana súkromia', 'cammino' ); ?></a><a href="#"><?php esc_html_e( 'Cookies', 'cammino' ); ?></a></div>
		</div>
	</footer>
	<?php
}

/**
 * Register dynamic content that must never be stored in an editable snapshot.
 */
function cammino_register_live_sections(): void {
	nstarter_register_live_section(
		'cammino_contact_form',
		static function (): string {
			if ( ! shortcode_exists( 'contact-form-7' ) ) {
				return '<p class="cammino-live-section-error">' . esc_html__( 'Contact Form 7 is required to display this form.', 'cammino' ) . '</p>';
			}

			return (string) do_shortcode( '[contact-form-7 id="d43ca6f" title="Kontaktný formulár 1"]' );
		}
	);

	nstarter_register_live_section( 'cammino_news_events', 'cammino_render_news_events' );
	nstarter_register_live_section( 'cammino_news_articles', 'cammino_render_news_articles' );
}

add_action( 'wp_enqueue_scripts', 'cammino_enqueue_child_styles', 15 );

/**
 * Load shared child-theme CSS on ordinary Astra pages.
 */
function cammino_enqueue_child_styles(): void {
	if ( ( is_page() && nstarter_is_visual_page( get_queried_object_id() ) ) || cammino_is_managed_post_request() ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_style(
		'cammino-child',
		get_stylesheet_uri(),
		array( 'astra-theme-css' ),
		(string) $theme->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'cammino_enqueue_visual_page_assets', 1000 );

/**
 * Load the reusable Cammino foundation and the selected page's own assets.
 */
function cammino_enqueue_visual_page_assets(): void {
	if ( ! is_page() || nstarter_is_editor_request() ) {
		return;
	}

	$slug  = nstarter_get_native_source_template_slug( get_queried_object_id() );
	$pages = array(
		'home'     => array(
			'handle' => 'cammino-home',
			'style'  => '/assets/css/pages/home.css',
			'script' => '/assets/js/pages/home.js',
		),
		'about-us' => array(
			'handle' => 'cammino-about-us',
			'style'  => '/assets/css/pages/about-us.css',
			'script' => '/assets/js/pages/about-us.js',
		),
		'activities' => array(
			'handle' => 'cammino-activities',
			'style'  => '/assets/css/pages/activities.css',
			'script' => '/assets/js/pages/activities.js',
		),
		'darujme-usmev' => array(
			'handle' => 'cammino-darujme-usmev',
			'style'  => '/assets/css/pages/darujme-usmev.css',
			'script' => '/assets/js/pages/darujme-usmev.js',
		),
		'contact'  => array(
			'handle' => 'cammino-contact',
			'style'  => '/assets/css/pages/contact.css',
			'script' => '/assets/js/pages/contact.js',
		),
		'ss'       => array(
			'handle' => 'cammino-success-stories',
			'style'  => '/assets/css/pages/ss.css',
			'script' => '/assets/js/pages/ss.js',
		),
		'news'     => array(
			'handle' => 'cammino-news',
			'style'  => '/assets/css/pages/news.css',
			'script' => '/assets/js/pages/news.js',
		),
		'donate'   => array(
			'handle' => 'cammino-donate',
			'style'  => '/assets/css/pages/donate.css',
			'script' => '/assets/js/pages/donate.js',
		),
		'donate-us' => array(
			'handle' => 'cammino-donate-us',
			'style'  => '/assets/css/pages/donate-us.css',
			'script' => '/assets/js/pages/donate-us.js',
		),
		'donate-now' => array(
			'handle' => 'cammino-donate-now',
			'style'  => '/assets/css/pages/contact.css',
			'script' => '/assets/js/pages/contact.js',
		),
		'donate-detail' => array(
			'handle'        => 'cammino-donate-detail',
			'style'         => '/assets/css/pages/donate-detail.css',
			'script'        => '/assets/js/pages/donate-detail.js',
			'before_styles' => array(
				'cammino-donate-article' => '/assets/css/pages/article.css',
			),
		),
	);

	if ( ! isset( $pages[ $slug ] ) ) {
		return;
	}

	$page = $pages[ $slug ];

	if ( 'contact' === $slug ) {
		if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
			wpcf7_enqueue_styles();
		}

		if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
			wpcf7_enqueue_scripts();
		}
	}

	cammino_enqueue_design_assets(
		$page['handle'],
		$page['style'],
		$page['script'],
		$page['before_styles'] ?? array()
	);

	if ( nstarter_is_preview_request() ) {
		wp_enqueue_style(
			'cammino-editor-preview',
			NSTARTER_URL . '/assets/css/editor-preview.css',
			array( $page['handle'] ),
			NSTARTER_VERSION
		);
	}
}

/**
 * Enqueue the shared design system and one page-specific asset pair.
 */
function cammino_enqueue_design_assets( string $handle, string $style, string $script, array $before_styles = array() ): void {
	wp_enqueue_style(
		'cammino-font-awesome',
		'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.2.0/css/all.min.css',
		array(),
		'7.2.0'
	);

	wp_enqueue_style(
		'cammino-base',
		NSTARTER_URL . '/assets/css/cammino-base.css',
		array( 'cammino-font-awesome' ),
		NSTARTER_VERSION
	);

	$style_dependencies = array( 'cammino-base' );

	foreach ( $before_styles as $before_handle => $before_style ) {
		wp_enqueue_style(
			$before_handle,
			NSTARTER_URL . $before_style,
			$style_dependencies,
			NSTARTER_VERSION
		);
		$style_dependencies = array( $before_handle );
	}

	wp_enqueue_style( $handle, NSTARTER_URL . $style, $style_dependencies, NSTARTER_VERSION );

	wp_enqueue_script(
		'cammino-shell',
		NSTARTER_URL . '/assets/js/cammino-shell.js',
		array(),
		NSTARTER_VERSION,
		true
	);

	wp_enqueue_script(
		$handle,
		NSTARTER_URL . $script,
		array( 'cammino-shell' ),
		NSTARTER_VERSION,
		true
	);
}

add_action( 'wp_enqueue_scripts', 'cammino_enqueue_single_post_assets', 1000 );

/**
 * Load the shared Article/Event design for every normal post.
 */
function cammino_enqueue_single_post_assets(): void {
	if ( ! cammino_is_managed_post_request() ) {
		return;
	}

	cammino_enqueue_design_assets(
		'cammino-article',
		'/assets/css/pages/article.css',
		'/assets/js/pages/article.js'
	);

	if ( nstarter_is_preview_request() ) {
		wp_enqueue_style(
			'cammino-editor-preview',
			NSTARTER_URL . '/assets/css/editor-preview.css',
			array( 'cammino-article' ),
			NSTARTER_VERSION
		);
	}
}

add_action( 'wp_enqueue_scripts', 'cammino_isolate_visual_page_assets', 999 );

/**
 * Prevent Astra's presentation layer from leaking into opt-in Cammino pages.
 */
function cammino_isolate_visual_page_assets(): void {
	$is_visual_page = is_page() && nstarter_is_visual_page( get_queried_object_id() );

	if ( ! $is_visual_page && ! cammino_is_managed_post_request() ) {
		return;
	}

	global $wp_styles, $wp_scripts;

	$parent_url = trailingslashit( get_template_directory_uri() );
	$styles     = is_object( $wp_styles ) ? (array) $wp_styles->queue : array();
	$scripts    = is_object( $wp_scripts ) ? (array) $wp_scripts->queue : array();

	foreach ( $styles as $handle ) {
		$source = isset( $wp_styles->registered[ $handle ] ) ? (string) $wp_styles->registered[ $handle ]->src : '';
		if ( str_starts_with( $handle, 'astra-' ) || ( '' !== $source && str_contains( $source, $parent_url ) ) ) {
			wp_dequeue_style( $handle );
		}
	}

	foreach ( $scripts as $handle ) {
		$source = isset( $wp_scripts->registered[ $handle ] ) ? (string) $wp_scripts->registered[ $handle ]->src : '';
		if ( str_starts_with( $handle, 'astra-' ) || ( '' !== $source && str_contains( $source, $parent_url ) ) ) {
			wp_dequeue_script( $handle );
		}
	}
}

add_filter( 'body_class', 'cammino_visual_page_body_classes' );

/**
 * Add page and editor state classes to the custom document.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function cammino_visual_page_body_classes( array $classes ): array {
	if ( is_page() ) {
		$slug         = nstarter_get_native_source_template_slug( get_queried_object_id() );
		$page_classes = array(
			'home'          => array( 'home-page' ),
			'about-us'      => array( 'about-page' ),
			'activities'    => array( 'activities-page' ),
			'darujme-usmev' => array( 'smile-page' ),
			'contact'       => array( 'contact-page' ),
			'ss'            => array( 'stories-page' ),
			'news'          => array( 'news-page' ),
			'donate'        => array( 'donation-page' ),
			'donate-us'     => array( 'donate-us-page' ),
			'donate-now'    => array( 'contact-page', 'donate-now-page' ),
			'donate-detail'  => array( 'article-page', 'donate-detail-page' ),
		);

		if ( isset( $page_classes[ $slug ] ) ) {
			$classes[] = 'cammino-visual-page';
			$classes   = array_merge( $classes, $page_classes[ $slug ] );
		}
	}

	if ( cammino_is_managed_post_request() ) {
		$classes[] = 'cammino-visual-page';
		$classes[] = 'cammino-article-page';
		$classes[] = 'cammino-' . cammino_get_post_placement( get_queried_object_id() );
	}

	if ( nstarter_is_preview_request() ) {
		$classes[] = 'nstarter-editor-preview';
	}

	return $classes;
}
