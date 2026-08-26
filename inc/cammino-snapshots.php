<?php
/**
 * Cammino static-page importer and snapshot component integration.
 *
 * The permanent files in snapshot-sources/cammino are the design reference.
 * This adapter extracts their page-specific content, supplies shared
 * header/footer parts, resolves static links for WordPress, and exposes
 * repeated collections to the visual editor's native repeat-variable control.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describe every imported Cammino design.
 *
 * @return array<string,array<string,mixed>>
 */
function nstarter_get_cammino_snapshot_pages(): array {
	return array(
		'index'         => array(
			'source'     => 'cammino-home',
			'body_class' => 'cammino-page cammino-home-page',
			'active'     => 'home',
			'footer_id'  => 'contact',
			'repeaters'  => array(
				array( 'id' => 'home_support_cards', 'container_class' => 'support-grid', 'item_class' => 'support-card', 'label' => 'Počet kariet podpory', 'max' => 9 ),
				array( 'id' => 'home_event_rows', 'container_class' => 'event-list', 'item_class' => 'event-row', 'label' => 'Počet ďalších podujatí', 'max' => 12 ),
			),
		),
		'aboutus'       => array(
			'source'       => 'cammino-about',
			'body_class'   => 'cammino-page about-page',
			'active'       => 'about',
			'footer_email' => 'management@ozcammino.sk',
			'footer_place' => 'Miletičova 7, Bratislava',
			'repeaters'    => array(
				array( 'id' => 'about_values', 'container_class' => 'values-grid', 'item_class' => 'value-card', 'label' => 'Počet hodnôt', 'max' => 12 ),
				array( 'id' => 'about_contacts', 'container_class' => 'contact-people', 'item_class' => 'contact-person', 'label' => 'Počet kontaktných osôb', 'max' => 8 ),
			),
		),
		'ss'            => array(
			'source'      => 'cammino-stories',
			'body_class'  => 'cammino-page stories-page',
			'active'      => 'stories',
			'repeaters'   => array(
				array( 'id' => 'success_stories', 'container_id' => 'main-content', 'item_class' => 'success-story', 'label' => 'Počet príbehov úspechu', 'max' => 12 ),
			),
		),
		'news'          => array(
			'source'     => 'cammino-news',
			'body_class' => 'cammino-page news-page',
			'active'     => 'news',
			'footer_id'  => 'contact',
			'repeaters'  => array(
				array( 'id' => 'news_active_events', 'container_class' => 'active-events-list', 'item_class' => 'active-event', 'label' => 'Počet menších podujatí', 'max' => 12 ),
				array( 'id' => 'news_articles', 'container_class' => 'posts-grid', 'item_class' => 'post-card', 'label' => 'Počet článkov', 'max' => 24 ),
			),
		),
		'article'       => array(
			'source'     => 'cammino-article',
			'body_class' => 'cammino-page article-page',
			'active'     => 'news',
			'footer_id'  => 'contact',
			'before'     => true,
			'repeaters'  => array(
				array( 'id' => 'article_related', 'container_class' => 'related-grid', 'item_class' => 'related-card', 'label' => 'Počet súvisiacich článkov', 'max' => 12 ),
			),
		),
		'contact'       => array(
			'source'       => 'cammino-contact',
			'body_class'   => 'cammino-page contact-page',
			'active'       => 'contact',
			'footer_email' => 'management@ozcammino.sk',
			'footer_place' => 'Miletičova 7, Bratislava',
			'repeaters'    => array(
				array( 'id' => 'contact_people', 'container_class' => 'contact-people', 'item_class' => 'person-card', 'label' => 'Počet kontaktných osôb', 'max' => 8 ),
			),
		),
		'donate'        => array(
			'source'     => 'cammino-donate',
			'body_class' => 'cammino-page donation-page',
			'active'     => 'donate',
			'footer_id'  => 'contact',
			'repeaters'  => array(
				array( 'id' => 'donation_options', 'container_class' => 'donation-grid', 'item_class' => 'donation-card', 'label' => 'Počet možností podpory', 'max' => 12 ),
			),
		),
		'donate-us'     => array(
			'source'       => 'cammino-donate-us',
			'body_class'   => 'cammino-page donate-us-page',
			'active'       => 'donate',
			'header_class' => 'donate-us-header',
		),
		'donate-detail' => array(
			'source'     => 'cammino-donate-detail',
			'body_class' => 'cammino-page article-page donate-detail-page',
			'active'     => 'donate',
			'repeaters'  => array(
				array( 'id' => 'donation_related', 'container_class' => 'related-grid', 'item_class' => 'related-card', 'label' => 'Počet ďalších možností pomoci', 'max' => 12 ),
			),
		),
	);
}

/**
 * Return a single page configuration by its static source name.
 *
 * @return array<string,mixed>
 */
function nstarter_get_cammino_snapshot_page( string $page ): array {
	$pages = nstarter_get_cammino_snapshot_pages();

	return isset( $pages[ $page ] ) ? $pages[ $page ] : array();
}

/**
 * Resolve a Cammino design name from a selected source-template slug.
 */
function nstarter_get_cammino_page_from_source( string $source ): string {
	foreach ( nstarter_get_cammino_snapshot_pages() as $page => $config ) {
		if ( $source === $config['source'] ) {
			return $page;
		}
	}

	return '';
}

add_action( 'wp_enqueue_scripts', 'nstarter_enqueue_cammino_snapshot_assets', 20 );

/**
 * Load the original design-system and page behavior for a Cammino snapshot.
 */
function nstarter_enqueue_cammino_snapshot_assets(): void {
	if ( ! is_page() ) {
		return;
	}

	$post_id = get_queried_object_id();
	$source  = nstarter_get_native_source_template_slug( $post_id );
	$page    = nstarter_get_cammino_page_from_source( $source );

	if ( '' === $page ) {
		return;
	}

	$asset_path = NSTARTER_PATH . '/assets/cammino/';
	$asset_url  = NSTARTER_URL . '/assets/cammino/';
	$main_css   = $asset_path . 'style/main.css';
	$page_css   = $asset_path . 'style/' . $page . '.css';
	$page_js    = $asset_path . 'script/' . $page . '.js';

	wp_enqueue_style( 'cammino-icons', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.2.0/css/all.min.css', array(), '7.2.0' );
	wp_enqueue_style( 'cammino-main', $asset_url . 'style/main.css', array( 'nstarter' ), is_file( $main_css ) ? (string) filemtime( $main_css ) : NSTARTER_VERSION );

	if ( is_file( $page_css ) ) {
		wp_enqueue_style( 'cammino-' . $page, $asset_url . 'style/' . $page . '.css', array( 'cammino-main' ), (string) filemtime( $page_css ) );
	}

	$snapshot_css = NSTARTER_PATH . '/snapshot-assets/cammino-snapshots.css';
	wp_enqueue_style( 'cammino-snapshots', NSTARTER_URL . '/snapshot-assets/cammino-snapshots.css', array( 'cammino-main' ), is_file( $snapshot_css ) ? (string) filemtime( $snapshot_css ) : NSTARTER_VERSION );

	if ( is_file( $page_js ) ) {
		wp_enqueue_script( 'cammino-' . $page, $asset_url . 'script/' . $page . '.js', array(), (string) filemtime( $page_js ), true );
	}
}

add_filter( 'body_class', 'nstarter_cammino_body_classes' );

/**
 * Restore the static prototype's page classes on the WordPress body.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function nstarter_cammino_body_classes( array $classes ): array {
	if ( ! is_page() ) {
		return $classes;
	}

	$page   = nstarter_get_cammino_page_from_source( nstarter_get_native_source_template_slug( get_queried_object_id() ) );
	$config = nstarter_get_cammino_snapshot_page( $page );

	if ( isset( $config['body_class'] ) ) {
		$classes = array_merge( $classes, preg_split( '/\s+/', (string) $config['body_class'] ) ?: array() );
	}

	return array_values( array_unique( array_filter( $classes ) ) );
}

/**
 * Find the WordPress URL of a page using one of the imported designs.
 */
function nstarter_cammino_page_url( string $page, string $suffix = '' ): string {
	static $urls = array();

	$config = nstarter_get_cammino_snapshot_page( $page );
	if ( empty( $config['source'] ) ) {
		return home_url( '/' );
	}

	if ( ! isset( $urls[ $page ] ) ) {
		$query = new WP_Query(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'meta_key'               => '_wp_page_template',
				'meta_value'             => nstarter_get_source_template_path( (string) $config['source'] ),
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! empty( $query->posts[0] ) ) {
			$urls[ $page ] = get_permalink( (int) $query->posts[0] );
		} elseif ( 'index' === $page ) {
			$urls[ $page ] = home_url( '/' );
		} else {
			$urls[ $page ] = home_url( '/' . sanitize_title( $page ) . '/' );
		}
	}

	return $urls[ $page ] . ltrim( $suffix, '/' );
}

/**
 * Find one balanced HTML element by a class token or ID.
 *
 * @param string $html       HTML to search.
 * @param string $attribute Either class or id.
 * @param string $value     Class token or exact ID.
 * @param int    $offset    Search offset.
 * @return array<string,int|string>|null
 */
function nstarter_cammino_find_element( string $html, string $attribute, string $value, int $offset = 0 ): ?array {
	$pattern = '/<([a-z][a-z0-9:-]*)\b[^>]*\b' . preg_quote( $attribute, '/' ) . '=([' . "'\"" . '])([^' . "'\"" . ']*)\2[^>]*>/i';
	$cursor  = $offset;

	while ( preg_match( $pattern, $html, $open_match, PREG_OFFSET_CAPTURE, $cursor ) ) {
		$attribute_value = $open_match[3][0];
		$matches_value   = 'class' === $attribute
			? in_array( $value, preg_split( '/\s+/', trim( $attribute_value ) ) ?: array(), true )
			: $value === $attribute_value;

		if ( ! $matches_value ) {
			$cursor = $open_match[0][1] + strlen( $open_match[0][0] );
			continue;
		}

		$tag        = strtolower( $open_match[1][0] );
		$start      = $open_match[0][1];
		$open_end   = $start + strlen( $open_match[0][0] );
		$tag_pattern = '/<\/?' . preg_quote( $tag, '/' ) . '\b[^>]*>/i';
		$depth      = 1;
		$tag_cursor = $open_end;

		while ( preg_match( $tag_pattern, $html, $tag_match, PREG_OFFSET_CAPTURE, $tag_cursor ) ) {
			$token      = $tag_match[0][0];
			$token_start = $tag_match[0][1];
			$tag_cursor = $token_start + strlen( $token );

			if ( str_starts_with( $token, '</' ) ) {
				--$depth;
			} elseif ( ! str_ends_with( rtrim( $token ), '/>' ) ) {
				++$depth;
			}

			if ( 0 === $depth ) {
				return array(
					'tag'         => $tag,
					'start'       => $start,
					'open_end'    => $open_end,
					'close_start' => $token_start,
					'end'         => $tag_cursor,
					'html'        => substr( $html, $start, $tag_cursor - $start ),
				);
			}
		}

		return null;
	}

	return null;
}

/**
 * Add an HTML attribute immediately before an opening tag's closing bracket.
 */
function nstarter_cammino_add_opening_attributes( string $opening, string $attributes ): string {
	if ( str_contains( $opening, 'data-nstarter-variable-section' ) && str_contains( $attributes, 'data-nstarter-variable-section' ) ) {
		return $opening;
	}

	return preg_replace( '/\s*>$/', $attributes . '>', $opening, 1 ) ?: $opening;
}

/**
 * Mark all matching collection items and return their count.
 *
 * @param string $html       Container inner HTML.
 * @param string $item_class Item class token.
 * @param int    $count      Populated item count.
 */
function nstarter_cammino_mark_items( string $html, string $item_class, int &$count ): string {
	$count   = 0;
	$pattern = '/<([a-z][a-z0-9:-]*)\b[^>]*\bclass=([' . "'\"" . '])([^' . "'\"" . ']*)\2[^>]*>/i';

	return preg_replace_callback(
		$pattern,
		static function ( array $match ) use ( $item_class, &$count ): string {
			$classes = preg_split( '/\s+/', trim( $match[3] ) ) ?: array();
			if ( ! in_array( $item_class, $classes, true ) ) {
				return $match[0];
			}

			++$count;
			return nstarter_cammino_add_opening_attributes( $match[0], ' data-nstarter-variable-item' );
		},
		$html
	) ?: $html;
}

/**
 * Prepare a safe reusable item for the editor's add-item operation.
 */
function nstarter_cammino_prepare_item_template( string $item ): string {
	$item = preg_replace_callback(
		'/^<([a-z][a-z0-9:-]*)\b[^>]*>/i',
		static function ( array $match ): string {
			return nstarter_cammino_add_opening_attributes( $match[0], ' data-nstarter-variable-item' );
		},
		$item,
		1
	) ?: $item;
	$item = preg_replace( '/\sdata-delay=([' . "'\"" . '])[^' . "'\"" . ']*\1/i', '', $item ) ?: $item;
	// Newly inserted items are not present when the page's reveal observer starts.
	// Remove reveal hooks from the reusable template so additions stay visible.
	$item = preg_replace( '/\sdata-(?:[a-z0-9-]+-)?reveal=([' . "'\"" . '])[^' . "'\"" . ']*\1/i', '', $item ) ?: $item;
	$item = preg_replace_callback(
		'/\b(id|aria-labelledby)=([' . "'\"" . '])([^' . "'\"" . ']+)\2/i',
		static function ( array $match ): string {
			return $match[1] . '=' . $match[2] . $match[3] . '-{{index}}' . $match[2];
		},
		$item
	) ?: $item;
	$item = preg_replace( '/(<[^>]+class=([' . "'\"" . '])[^' . "'\"" . ']*(?:number|index)[^' . "'\"" . ']*\2[^>]*>)\s*\d+\s*(<\/[^>]+>)/i', '$1{{index_padded}}$3', $item, 1 ) ?: $item;

	return $item;
}

/**
 * Convert one repeated static collection into a snapshot-native variable.
 *
 * @param string              $html   Page main HTML.
 * @param array<string,mixed> $config Repeater definition.
 */
function nstarter_cammino_add_repeater( string $html, array $config ): string {
	$attribute = isset( $config['container_id'] ) ? 'id' : 'class';
	$value     = isset( $config['container_id'] ) ? (string) $config['container_id'] : (string) ( $config['container_class'] ?? '' );
	$item_class = (string) ( $config['item_class'] ?? '' );

	if ( '' === $value || '' === $item_class ) {
		return $html;
	}

	$container = nstarter_cammino_find_element( $html, $attribute, $value );
	if ( null === $container ) {
		return $html;
	}

	$container_html = (string) $container['html'];
	$opening_length = (int) $container['open_end'] - (int) $container['start'];
	$closing_length = (int) $container['end'] - (int) $container['close_start'];
	$opening        = substr( $container_html, 0, $opening_length );
	$inner          = substr( $container_html, $opening_length, strlen( $container_html ) - $opening_length - $closing_length );
	$closing        = substr( $container_html, -$closing_length );
	$first_item     = nstarter_cammino_find_element( $inner, 'class', $item_class );

	if ( null === $first_item ) {
		return $html;
	}

	$count = 0;
	$inner = nstarter_cammino_mark_items( $inner, $item_class, $count );
	$attributes = nstarter_get_variable_section_attributes(
		(string) ( $config['id'] ?? $item_class ),
		array(
			'label'   => (string) ( $config['label'] ?? $item_class ),
			'type'    => 'number',
			'control' => 'repeat',
			'value'   => $count,
			'min'     => 0,
			'max'     => (int) ( $config['max'] ?? 12 ),
			'step'    => 1,
		)
	);
	$opening = nstarter_cammino_add_opening_attributes( $opening, $attributes . ' data-nstarter-variable-items' );
	$template = nstarter_cammino_prepare_item_template( (string) $first_item['html'] );
	$empty    = '<div class="nstarter-variable-empty" data-nstarter-variable-empty-state>Táto kolekcia je momentálne prázdna.</div>';
	$replacement = $opening . $inner . $empty . '<template data-nstarter-variable-template>' . $template . '</template>' . $closing;

	return substr_replace( $html, $replacement, (int) $container['start'], (int) $container['end'] - (int) $container['start'] );
}

/**
 * Resolve relative prototype assets and .html links for WordPress.
 */
function nstarter_prepare_cammino_snapshot_html( string $html ): string {
	$asset_url = esc_url( NSTARTER_URL . '/assets/cammino/' );
	$html      = str_replace( '../assets/', $asset_url, $html );

	return preg_replace_callback(
		'/\bhref=([' . "'\"" . '])([^' . "'\"" . ']+\.html)((?:[?#])[^' . "'\"" . ']*)?\1/i',
		static function ( array $match ): string {
			$page   = basename( $match[2], '.html' );
			$suffix = isset( $match[3] ) ? $match[3] : '';

			return 'href=' . $match[1] . esc_url( nstarter_cammino_page_url( $page, $suffix ) ) . $match[1];
		},
		$html
	) ?: $html;
}

/**
 * Render one static prototype as a reusable, variable-enabled snapshot source.
 */
function nstarter_render_cammino_snapshot( string $page ): void {
	$config = nstarter_get_cammino_snapshot_page( $page );
	$file   = NSTARTER_PATH . '/snapshot-sources/cammino/' . sanitize_file_name( $page ) . '.html';

	if ( empty( $config ) || ! is_file( $file ) ) {
		echo '<section style="padding:40px"><h1>Cammino design not found</h1></section>';
		return;
	}

	$source = file_get_contents( $file );
	if ( false === $source || ! preg_match( '/(<main\b[\s\S]*?<\/main>)/i', $source, $main_match ) ) {
		echo '<section style="padding:40px"><h1>Cammino page content could not be read</h1></section>';
		return;
	}

	$before = '';
	if ( preg_match( '/<body\b[^>]*>([\s\S]*?)<header\b/i', $source, $before_match ) ) {
		$before = trim( $before_match[1] );
	}

	$main = $main_match[1];
	foreach ( (array) ( $config['repeaters'] ?? array() ) as $repeater ) {
		$main = nstarter_cammino_add_repeater( $main, $repeater );
	}

	$main = nstarter_prepare_cammino_snapshot_html( $main );
	$before = nstarter_prepare_cammino_snapshot_html( $before );

	$cammino_active       = (string) ( $config['active'] ?? '' );
	$cammino_header_class = (string) ( $config['header_class'] ?? '' );
	$cammino_footer_id    = (string) ( $config['footer_id'] ?? '' );
	$cammino_footer_email = (string) ( $config['footer_email'] ?? 'ahoj@cammino.sk' );
	$cammino_footer_place = (string) ( $config['footer_place'] ?? 'Bratislava, Slovensko' );
	$cammino_form_id      = sanitize_html_class( $page . '-footer-email' );

	if ( '' !== $before ) {
		echo $before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	include NSTARTER_PATH . '/snapshot-parts/cammino-header.php';
	echo $main; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	include NSTARTER_PATH . '/snapshot-parts/cammino-footer.php';
}
