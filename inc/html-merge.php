<?php
/** Merge saved page content into current template markup, without content fields. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IDs and existing section markers identify nodes across layout changes.
 * Anonymous siblings are matched only when their type counts still agree.
 * Old HTML is never used as a replacement for a layout container.
 *
 * @return array{html:string,conflicts:array<string>}
 */
function nstarter_merge_page_html( string $template, string $saved ): array {
	$template = nstarter_strip_page_shell( $template );
	$saved = nstarter_strip_page_shell( $saved );
	if ( '' === trim( $saved ) ) {
		return array( 'html' => $template, 'conflicts' => array() );
	}
	if ( ! class_exists( 'DOMDocument' ) ) {
		// Preserve existing pages if the PHP DOM extension is missing.
		return array( 'html' => $saved, 'conflicts' => array( 'HTML merging requires the PHP DOM extension.' ) );
	}
	return ( new NStarter_HTML_Merge() )->merge( $template, $saved );
}

/** Legacy source files and snapshots can contain their own shared shell. */
function nstarter_strip_page_shell( string $html ): string {
	foreach ( array( 'header' => 'site-header', 'footer' => 'site-footer', 'a' => 'skip-link' ) as $tag => $class ) {
		$html = (string) preg_replace( '#<' . $tag . '\b[^>]*class=["\'][^"\']*\b' . $class . '\b[^"\']*["\'][^>]*>.*?</' . $tag . '>#is', '', $html );
	}
	return $html;
}

function nstarter_render_merged_page_html( int $post_id, ?string $saved = null ): array {
	$saved = $saved ?? nstarter_get_snapshot_html( $post_id );
	if ( function_exists( 'cammino_upgrade_legacy_home_event_picker_html' ) ) {
		$saved = cammino_upgrade_legacy_home_event_picker_html( $saved );
	}
	return nstarter_merge_page_html( nstarter_render_source_template( $post_id ), $saved );
}

final class NStarter_HTML_Merge {
	private array $old_keys = array();
	private array $new_keys = array();
	private array $used = array();
	private array $covered = array();
	private array $conflicts = array();
	// Retain DOM wrappers so spl_object_id remains stable throughout the merge.
	private array $nodes = array();
	private ?DOMElement $item_scope = null;
	private array $matched = array();

	private function parse( string $html ): DOMElement {
		$document = new DOMDocument( '1.0', 'UTF-8' );
		$previous = libxml_use_internal_errors( true );
		try {
			$document->loadHTML( '<?xml encoding="UTF-8"><!doctype html><html><body><div data-merge-root>' . $html . '</div></body></html>', LIBXML_NONET );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}
		// libxml's HTML4 parser nests following siblings inside HTML5 void tags.
		foreach ( array( 'source', 'track', 'wbr', 'embed' ) as $tag ) {
			foreach ( iterator_to_array( $document->getElementsByTagName( $tag ) ) as $void ) {
				$next = $void->nextSibling;
				while ( $void->firstChild ) {
					$void->parentNode->insertBefore( $void->firstChild, $next );
				}
			}
		}
		return $document->getElementsByTagName( 'body' )->item( 0 )->firstChild;
	}

	private function children( DOMElement $node ): array {
		return array_values( array_filter( iterator_to_array( $node->childNodes ), static fn( DOMNode $child ): bool => $child instanceof DOMElement ) );
	}

	private function excluded( DOMElement $node ): bool {
		return in_array( $node->tagName, array( 'script', 'style', 'svg', 'template', 'noscript' ), true )
			|| (bool) preg_match( '/(?:^|\s)(?:site-header|site-footer|skip-link)(?:\s|$)/', $node->getAttribute( 'class' ) )
			|| 'true' === $node->getAttribute( 'aria-hidden' )
			|| $node->hasAttribute( 'data-nstarter-editor-runtime' )
			|| ( 'i' === $node->tagName && $node->hasAttribute( 'class' ) );
	}

	private function key( DOMElement $node ): string {
		foreach ( array( 'id', 'data-nstarter-variable-section', 'data-nstarter-live-section', 'aria-labelledby' ) as $attribute ) {
			if ( '' !== $node->getAttribute( $attribute ) ) {
				return $attribute . ':' . $node->getAttribute( $attribute );
			}
		}
		return '';
	}

	private function index( DOMElement $root, array &$keys ): void {
		$this->nodes[ spl_object_id( $root ) ] = $root;
		if ( $this->excluded( $root ) ) {
			return;
		}
		$key = $this->key( $root );
		if ( '' !== $key ) {
			$keys[ $key ][] = $root;
		}
		if ( $root->hasAttribute( 'data-nstarter-live-section' ) ) {
			return;
		}
		foreach ( $this->children( $root ) as $child ) {
			$this->index( $child, $keys );
		}
	}

	private function type( DOMElement $node ): string {
		if ( in_array( $node->tagName, array( 'img', 'video', 'iframe', 'picture' ), true ) ) {
			return 'media';
		}
		return preg_match( '/^h[1-6]$/', $node->tagName ) ? 'heading' : $node->tagName;
	}

	private function within( DOMElement $scope, DOMElement $candidate ): bool {
		for ( $parent = $candidate; null !== $parent; $parent = $parent->parentNode ) {
			if ( $parent === $scope ) {
				return true;
			}
		}
		return false;
	}

	private function transparent_wrapper( DOMElement $node ): bool {
		if ( '' !== $this->key( $node ) ) {
			return false;
		}
		if ( in_array( $node->tagName, array( 'div', 'figure' ), true ) ) {
			return true;
		}
		// A newly linked image keeps its saved media while using the new href.
		$children = $this->children( $node );
		return 'a' === $node->tagName && ! $this->texts( $node ) && 1 === count( $children ) && 'media' === $this->type( $children[0] );
	}

	private function descendants( DOMElement $scope ): array {
		$nodes = array();
		foreach ( $this->children( $scope ) as $child ) {
			if ( $this->excluded( $child ) ) {
				continue;
			}
			$nodes[] = $child;
			// Search through wrappers, never into other identified sections.
			if ( $this->transparent_wrapper( $child ) ) {
				$nodes = array_merge( $nodes, $this->descendants( $child ) );
			}
		}
		return $nodes;
	}

	private function match( DOMElement $fresh, ?DOMElement $scope, DOMElement $fresh_scope ): ?DOMElement {
		$key = $this->key( $fresh );
		if ( '' !== $key ) {
			$old = $this->old_keys[ $key ] ?? array();
			$new = $this->new_keys[ $key ] ?? array();
			if ( $this->item_scope ) {
				$old = array_values( array_filter( $old, fn( DOMElement $node ): bool => $this->within( $this->item_scope, $node ) ) );
				$new = array( $fresh );
			}
			return 1 === count( $old ) && 1 === count( $new ) && ! isset( $this->used[ spl_object_id( $old[0] ) ] ) ? $old[0] : null;
		}
		if ( null === $scope ) {
			return null;
		}
		$candidates = array_filter( $this->descendants( $scope ), fn( DOMElement $node ): bool => $this->type( $node ) === $this->type( $fresh ) && '' === $this->key( $node ) );
		$new_candidates = array_filter( $this->descendants( $fresh_scope ), fn( DOMElement $node ): bool => $this->type( $node ) === $this->type( $fresh ) && '' === $this->key( $node ) );
		$classes = preg_split( '/\s+/', trim( $fresh->getAttribute( 'class' ) ), -1, PREG_SPLIT_NO_EMPTY );
		$class_matches = array();
		foreach ( $classes as $class ) {
			$matching = array_values( array_filter( $candidates, static fn( DOMElement $node ): bool => in_array( $class, preg_split( '/\s+/', $node->getAttribute( 'class' ) ), true ) ) );
			$new_matching = array_filter( $new_candidates, static fn( DOMElement $node ): bool => in_array( $class, preg_split( '/\s+/', $node->getAttribute( 'class' ) ), true ) );
			if ( 1 === count( $matching ) && 1 === count( $new_matching ) ) {
				$class_matches[ spl_object_id( $matching[0] ) ] = $matching[0];
			}
		}
		if ( 1 === count( $class_matches ) ) {
			$match = reset( $class_matches );
			return isset( $this->used[ spl_object_id( $match ) ] ) ? null : $match;
		}
		if ( count( $class_matches ) > 1 ) {
			return null;
		}
		// New wrappers must not disguise an inserted anonymous sibling.
		if ( count( $candidates ) !== count( $new_candidates ) ) {
			return null;
		}
		if ( 1 === count( $candidates ) ) {
			$match = reset( $candidates );
			return isset( $this->used[ spl_object_id( $match ) ] ) ? null : $match;
		}
		$old_siblings = array_values( array_filter( $this->children( $scope ), fn( DOMElement $node ): bool => ! $this->excluded( $node ) && '' === $this->key( $node ) && $this->type( $node ) === $this->type( $fresh ) ) );
		$new_siblings = array_values( array_filter( $this->children( $fresh->parentNode ), fn( DOMElement $node ): bool => ! $this->excluded( $node ) && '' === $this->key( $node ) && $this->type( $node ) === $this->type( $fresh ) ) );
		if ( count( $old_siblings ) === count( $new_siblings ) ) {
			$position = array_search( $fresh, $new_siblings, true );
			$match = $old_siblings[ $position ] ?? null;
			return $match && ! isset( $this->used[ spl_object_id( $match ) ] ) ? $match : null;
		}
		return null;
	}

	private function texts( DOMElement $node ): array {
		return array_values( array_filter( iterator_to_array( $node->childNodes ), static fn( DOMNode $child ): bool => $child instanceof DOMText && '' !== trim( $child->nodeValue ) ) );
	}

	private function safe_url( string $url ): bool {
		$url = preg_replace( '/[\x00-\x20]+/', '', $url );
		return ! preg_match( '/^[a-z][a-z0-9+.-]*:/i', $url ) || (bool) preg_match( '/^(https?:|mailto:|tel:)/i', $url );
	}

	private function attributes( DOMElement $fresh, DOMElement $old, array $names, bool $remove_missing = false ): void {
		foreach ( $names as $name ) {
			if ( $old->hasAttribute( $name ) ) {
				$value = $old->getAttribute( $name );
				if ( in_array( $name, array( 'href', 'src', 'poster' ), true ) && ! $this->safe_url( $value ) ) {
					continue;
				}
				$fresh->setAttribute( $name, $value );
			} elseif ( $remove_missing ) {
				$fresh->removeAttribute( $name );
			}
		}
	}

	private function cover( DOMElement $node ): void {
		$this->covered[ spl_object_id( $node ) ] = true;
		foreach ( $this->children( $node ) as $child ) {
			$this->cover( $child );
		}
	}

	private function inline_only( DOMElement $node, bool $presentation = false ): bool {
		foreach ( $this->children( $node ) as $child ) {
			if ( $presentation && $this->excluded( $child ) ) {
				continue;
			}
			if ( ! in_array( $child->tagName, array( 'em', 'strong', 'b', 'i', 'u', 's', 'br', 'a', 'span' ), true ) || ( ! $presentation && ( $child->hasAttribute( 'class' ) || $child->hasAttribute( 'id' ) ) ) || ! $this->inline_only( $child, $presentation ) ) {
				return false;
			}
		}
		return true;
	}

	private function region_texts( DOMElement $node ): array {
		$texts = array();
		foreach ( $node->childNodes as $child ) {
			if ( $child instanceof DOMText && '' !== trim( $child->nodeValue ) ) {
				$texts[] = $child;
			} elseif ( $child instanceof DOMElement && ! $this->excluded( $child ) ) {
				$texts = array_merge( $texts, $this->region_texts( $child ) );
			}
		}
		return $texts;
	}

	/** Copy only supported inline formatting, never layout or event attributes. */
	private function copy_inline( DOMNode $node, DOMDocument $document ): DOMNode {
		if ( ! $node instanceof DOMElement ) {
			return $document->createTextNode( $node->textContent );
		}
		$copy = $document->createElement( $node->tagName );
		if ( 'a' === $node->tagName ) {
			$this->attributes( $copy, $node, array( 'href', 'title' ) );
		}
		foreach ( $node->childNodes as $child ) {
			if ( $child instanceof DOMElement || $child instanceof DOMText ) {
				$copy->appendChild( $this->copy_inline( $child, $document ) );
			}
		}
		return $copy;
	}

	private function merge_node( DOMElement $fresh, DOMElement $old ): DOMElement {
		$this->used[ spl_object_id( $old ) ] = true;
		$this->matched[ spl_object_id( $old ) ] = $fresh;
		if ( 'picture' === $old->tagName && 'picture' !== $fresh->tagName ) {
			$image = $old->getElementsByTagName( 'img' )->item( 0 );
			if ( $image ) {
				$fresh = $this->merge_node( $fresh, $image );
				$this->cover( $old );
			}
			return $fresh;
		}
		if ( 'picture' === $fresh->tagName && in_array( $old->tagName, array( 'img', 'video', 'iframe' ), true ) ) {
			$image = $fresh->getElementsByTagName( 'img' )->item( 0 );
			if ( $image ) {
				if ( 'img' === $old->tagName ) {
					foreach ( iterator_to_array( $fresh->getElementsByTagName( 'source' ) ) as $source ) {
						$source->parentNode->removeChild( $source );
					}
					$this->merge_node( $image, $old );
					return $fresh;
				}
				$replacement = $image->cloneNode( true );
				$fresh->parentNode->replaceChild( $replacement, $fresh );
				return $this->merge_node( $replacement, $old );
			}
		}
		if ( 'media' === $this->type( $fresh ) && 'media' === $this->type( $old ) && 'picture' !== $fresh->tagName && 'picture' !== $old->tagName ) {
			if ( $fresh->tagName !== $old->tagName ) {
				$replacement = $fresh->ownerDocument->createElement( $old->tagName );
				foreach ( $fresh->attributes as $attribute ) {
					$replacement->setAttribute( $attribute->name, $attribute->value );
				}
				$fresh->parentNode->replaceChild( $replacement, $fresh );
				$fresh = $replacement;
			}
			$this->attributes( $fresh, $old, array( 'src', 'srcset', 'sizes', 'alt', 'poster', 'autoplay', 'muted', 'controls', 'playsinline', 'preload', 'loop', 'allow', 'allowfullscreen', 'title', 'data-attachment-id', 'data-nstarter-embed' ), true );
			if ( 'video' === $fresh->tagName ) {
				while ( $fresh->firstChild ) {
					$fresh->removeChild( $fresh->firstChild );
				}
				foreach ( $this->children( $old ) as $source ) {
					if ( in_array( $source->tagName, array( 'source', 'track' ), true ) ) {
						$copy = $fresh->ownerDocument->createElement( $source->tagName );
						$this->attributes( $copy, $source, array( 'src', 'type', 'kind', 'srclang', 'label', 'default' ) );
						$fresh->appendChild( $copy );
					}
				}
			}
			$this->cover( $old );
			$this->matched[ spl_object_id( $old ) ] = $fresh;
			return $fresh;
		}
		if ( 'a' === $fresh->tagName && 'a' === $old->tagName ) {
			$this->attributes( $fresh, $old, array( 'href', 'title' ) );
		}
		if ( 'source' === $fresh->tagName && 'source' === $old->tagName ) {
			$this->attributes( $fresh, $old, array( 'src', 'srcset', 'sizes', 'type' ), true );
		}
		if ( $fresh->hasAttribute( 'data-nstarter-variable-section' ) ) {
			$this->attributes( $fresh, $old, array( 'data-nstarter-variable-value' ) );
		}
		if ( $fresh->hasAttribute( 'data-nstarter-live-section' ) ) {
			$this->attributes( $fresh, $old, array( 'data-nstarter-live-args' ) );
			$this->cover( $old );
			return $fresh;
		}
		if ( $fresh->hasAttribute( 'data-nstarter-variable-items' ) ) {
			$this->merge_items( $fresh, $old );
			return $fresh;
		}
		// Text may move into new inline wrappers while its outer ID stays stable.
		$is_text = 'heading' === $this->type( $fresh ) || in_array( $fresh->tagName, array( 'p', 'a', 'span', 'em', 'strong', 'b', 'i', 'u', 's', 'li', 'blockquote', 'caption', 'th', 'td' ), true );
		if ( $is_text && $this->inline_only( $fresh, true ) && $this->inline_only( $old, true ) ) {
			$old_region = $this->region_texts( $old );
			$new_region = $this->region_texts( $fresh );
			if ( ! $old_region || ( $this->children( $fresh ) && count( $old_region ) === count( $new_region ) ) ) {
				foreach ( $new_region as $index => $text ) {
					$text->nodeValue = $old_region[ $index ]->nodeValue ?? '';
				}
				$old_links = iterator_to_array( $old->getElementsByTagName( 'a' ) );
				$new_links = iterator_to_array( $fresh->getElementsByTagName( 'a' ) );
				if ( count( $old_links ) === count( $new_links ) ) {
					foreach ( $new_links as $index => $link ) {
						if ( $this->key( $link ) === $this->key( $old_links[ $index ] ) ) {
							$this->attributes( $link, $old_links[ $index ], array( 'href', 'title' ) );
						} else {
							$this->conflicts[] = $this->label( $old_links[ $index ] ) . ': inline link identity changed';
						}
					}
				} elseif ( $old_links ) {
					$this->conflicts[] = $this->label( $old ) . ': inline links changed';
				}
				$this->cover( $old );
				return $fresh;
			}
		}
		$old_texts = $this->texts( $old );
		$new_texts = $this->texts( $fresh );
		if ( $this->inline_only( $fresh ) && $this->inline_only( $old ) && ( count( $old_texts ) !== count( $new_texts ) || count( $this->children( $old ) ) !== count( $this->children( $fresh ) ) ) ) {
			while ( $fresh->firstChild ) {
				$fresh->removeChild( $fresh->firstChild );
			}
			foreach ( $old->childNodes as $child ) {
				if ( $child instanceof DOMText || $child instanceof DOMElement ) {
					$fresh->appendChild( $this->copy_inline( $child, $fresh->ownerDocument ) );
				}
			}
			$this->cover( $old );
			return $fresh;
		}
		if ( count( $old_texts ) === count( $new_texts ) ) {
			foreach ( $new_texts as $index => $text ) {
				$text->nodeValue = $old_texts[ $index ]->nodeValue;
			}
			$this->covered[ spl_object_id( $old ) ] = true;
		} elseif ( $old_texts ) {
			$this->conflicts[] = $this->label( $old ) . ': text structure changed';
		}
		$this->walk( $fresh, $old );
		return $fresh;
	}

	private function merge_items( DOMElement $fresh, DOMElement $old ): void {
		$section = $fresh->parentNode;
		while ( $section instanceof DOMElement && ! $section->hasAttribute( 'data-nstarter-variable-section' ) ) {
			$section = $section->parentNode;
		}
		$templates = $section instanceof DOMElement ? ( new DOMXPath( $fresh->ownerDocument ) )->query( './/template[@data-nstarter-variable-template]', $section ) : array();
		$prototype = null;
		foreach ( $templates as $template ) {
			$prototype = $this->children( $template )[0] ?? null;
			break;
		}
		$old_items = array_filter( $this->children( $old ), static fn( DOMElement $node ): bool => $node->hasAttribute( 'data-nstarter-variable-item' ) );
		$new_items = array_values( array_filter( $this->children( $fresh ), static fn( DOMElement $node ): bool => $node->hasAttribute( 'data-nstarter-variable-item' ) ) );
		if ( ! $prototype && $old_items ) {
			$this->conflicts[] = $this->label( $old ) . ': repeatable item template is unavailable';
			return;
		}
		while ( $fresh->firstChild ) {
			$fresh->removeChild( $fresh->firstChild );
		}
		$position = 0;
		foreach ( $old_items as $item ) {
			++$position;
			$copy = ( $new_items[ $position - 1 ] ?? $prototype )->cloneNode( true );
			$token = $section instanceof DOMElement ? $section->getAttribute( 'data-nstarter-variable-token' ) : '';
			if ( '' !== $token ) {
				$this->expand_tokens( $copy, '{{' . $token . '}}', (string) $position );
			}
			$fresh->appendChild( $copy );
			$previous_scope = $this->item_scope;
			$this->item_scope = $item;
			$this->merge_node( $copy, $item );
			$this->item_scope = $previous_scope;
		}
		$this->covered[ spl_object_id( $old ) ] = true;
	}

	private function expand_tokens( DOMElement $node, string $token, string $value ): void {
		foreach ( $node->attributes as $attribute ) {
			$attribute->value = str_replace( $token, $value, $attribute->value );
		}
		foreach ( $node->childNodes as $child ) {
			if ( $child instanceof DOMText ) {
				$child->nodeValue = str_replace( $token, $value, $child->nodeValue );
			} elseif ( $child instanceof DOMElement ) {
				$this->expand_tokens( $child, $token, $value );
			}
		}
	}

	private function walk( DOMElement $fresh, ?DOMElement $scope, ?DOMElement $fresh_scope = null ): void {
		$fresh_scope = $fresh_scope ?? $fresh;
		foreach ( $this->children( $fresh ) as $child ) {
			if ( $this->excluded( $child ) ) {
				continue;
			}
			$old = $this->match( $child, $scope, $fresh_scope );
			if ( $old ) {
				$this->merge_node( $child, $old );
			} elseif ( ! $child->hasAttribute( 'data-nstarter-live-section' ) ) {
				// Unidentified wrappers may be new; identified unmatched sections are new content.
				$wrapper = $this->transparent_wrapper( $child );
				$this->walk( $child, $wrapper ? $scope : null, $wrapper ? $fresh_scope : null );
			}
		}
	}

	private function label( DOMElement $node ): string {
		return $node->tagName . ( '' !== $node->getAttribute( 'id' ) ? '#' . $node->getAttribute( 'id' ) : ( '' !== $node->getAttribute( 'class' ) ? '.' . str_replace( ' ', '.', $node->getAttribute( 'class' ) ) : '' ) );
	}

	private function unmatched( DOMElement $node ): void {
		if ( $this->excluded( $node ) || $node->hasAttribute( 'data-nstarter-live-section' ) ) {
			return;
		}
		if ( ! isset( $this->covered[ spl_object_id( $node ) ] ) && ( $this->texts( $node ) || in_array( $node->tagName, array( 'img', 'video', 'iframe' ), true ) || $node->hasAttribute( 'href' ) ) ) {
			$this->conflicts[] = $this->label( $node ) . ': saved content could not be matched';
		}
		foreach ( $this->children( $node ) as $child ) {
			$this->unmatched( $child );
		}
	}

	private function order_sections( DOMElement $fresh, DOMElement $old ): void {
		$new_main = $fresh->ownerDocument->getElementById( 'main-content' );
		$old_main = $old->ownerDocument->getElementById( 'main-content' );
		if ( ! $new_main || ! $old_main ) {
			return;
		}
		$ordered = array();
		foreach ( $this->children( $old_main ) as $node ) {
			$match = $this->matched[ spl_object_id( $node ) ] ?? null;
			if ( $match && $match->parentNode === $new_main && in_array( $match->tagName, array( 'section', 'div', 'article' ), true ) ) {
				$ordered[] = $match;
			}
		}
		// Leave new sections in their template slots; reorder only known sections.
		$slots = array();
		foreach ( $this->children( $new_main ) as $node ) {
			if ( in_array( $node, $ordered, true ) ) {
				$slot = $fresh->ownerDocument->createComment( 'section slot' );
				$new_main->replaceChild( $slot, $node );
				$slots[] = $slot;
			}
		}
		foreach ( $slots as $index => $slot ) {
			$new_main->replaceChild( $ordered[ $index ], $slot );
		}
	}

	public function merge( string $template, string $saved ): array {
		$fresh = $this->parse( $template );
		$old = $this->parse( $saved );
		$this->index( $old, $this->old_keys );
		$this->index( $fresh, $this->new_keys );
		$this->walk( $fresh, $old );
		$this->order_sections( $fresh, $old );
		$this->unmatched( $old );
		$html = '';
		foreach ( $fresh->childNodes as $node ) {
			$html .= $fresh->ownerDocument->saveHTML( $node );
		}
		return array( 'html' => $html, 'conflicts' => array_values( array_unique( $this->conflicts ) ) );
	}
}
