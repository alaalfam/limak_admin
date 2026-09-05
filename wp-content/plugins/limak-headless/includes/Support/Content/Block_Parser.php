<?php

namespace Limak\Headless\Support\Content;

use DOMDocument;
use DOMElement;
use Limak\Headless\Support\Media\Image_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns a post's Persian body (native `post_content`, written with the
 * regular WordPress block editor) and English body (the `body_en` ACF
 * wysiwyg field, written with the classic TinyMCE editor) into the single
 * bilingual block-array shape the frontend's ArticleRenderer expects.
 *
 * Same "Persian = native, English = companion field" split used
 * everywhere else in this plugin, extended to a richer, structured body
 * instead of a single HTML blob — the two sides are parsed independently
 * into an identical intermediate shape (parse_gutenberg_blocks /
 * parse_html_blocks), then zipped together by position (to_bilingual):
 * the Nth paragraph/heading/image/etc. of one side is paired with the Nth
 * of the other. This requires an editor to keep both bodies in the same
 * order (documented in the field's instructions) — a mismatch degrades
 * gracefully (see merge_block) rather than throwing.
 *
 * `highlight`, `cta` and `imageText` have no native block-editor
 * equivalent in either editor, so they're written as a shortcode-like tag
 * on its own paragraph/line — see match_shortcode().
 */
final class Block_Parser {

	/**
	 * @return array<int, array<string, mixed>> Raw, single-language block descriptors.
	 */
	public static function parse_gutenberg_blocks( string $post_content ): array {
		if ( '' === trim( $post_content ) ) {
			return [];
		}

		$blocks = [];

		foreach ( parse_blocks( $post_content ) as $block ) {
			$parsed = self::parse_gutenberg_block( $block );

			if ( null !== $parsed ) {
				$blocks[] = $parsed;
			}
		}

		return $blocks;
	}

	/**
	 * @return array<int, array<string, mixed>> Raw, single-language block descriptors.
	 */
	public static function parse_html_blocks( string $html ): array {
		if ( '' === trim( $html ) ) {
			return [];
		}

		$wrapper = self::load_fragment( $html );
		$blocks  = [];

		foreach ( $wrapper->childNodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$parsed = self::parse_html_node( $node );

			if ( null !== $parsed ) {
				$blocks[] = $parsed;
			}
		}

		return $blocks;
	}

	/**
	 * Zips two independently-parsed raw block lists into the final
	 * bilingual shape, pairing entries by index. The structural shape
	 * (type, attachment id, list style, ...) is taken from whichever side
	 * has that slot, preferring Persian; text is taken per-language and
	 * falls back to the other language's text only when its own is empty
	 * — same "never simply empty" principle used for product fields.
	 *
	 * @param array<int, array<string, mixed>> $fa_blocks
	 * @param array<int, array<string, mixed>> $en_blocks
	 * @return array<int, array<string, mixed>>
	 */
	public static function to_bilingual( array $fa_blocks, array $en_blocks ): array {
		$count  = max( count( $fa_blocks ), count( $en_blocks ) );
		$result = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$fa = $fa_blocks[ $i ] ?? null;
			$en = $en_blocks[ $i ] ?? null;

			// A mismatched pairing (editor lost sync between the two
			// bodies) falls back to Persian-only for this slot rather
			// than mixing unrelated block shapes.
			if ( $en && ( $en['type'] ?? null ) !== ( $fa['type'] ?? ( $en['type'] ?? null ) ) ) {
				$en = null;
			}

			$type = $fa['type'] ?? ( $en['type'] ?? null );

			if ( null === $type ) {
				continue;
			}

			$merged = self::merge_block( $type, $fa, $en );

			if ( null !== $merged ) {
				$result[] = $merged;
			}
		}

		return $result;
	}

	// -- Gutenberg (Persian) parsing -----------------------------------

	private static function parse_gutenberg_block( array $block ): ?array {
		$name = $block['blockName'] ?? null;
		$html = $block['innerHTML'] ?? '';

		switch ( $name ) {
			case 'core/paragraph':
				$text = trim( wp_strip_all_tags( $html ) );

				return self::match_shortcode( $text ) ?? ( '' !== $text ? [
					'type' => 'paragraph',
					'text' => $text,
				] : null );

			case 'core/heading':
				$text = trim( wp_strip_all_tags( $html ) );

				return '' !== $text ? [ 'type' => 'heading', 'text' => $text ] : null;

			case 'core/shortcode':
			case 'core/html':
				return self::match_shortcode( trim( $html ) );

			case 'core/quote':
			case 'core/pullquote':
				[ $text, $attribution ] = self::parse_quote_html( $html );

				return '' !== $text ? [
					'type'        => 'quote',
					'text'        => $text,
					'attribution' => $attribution,
				] : null;

			case 'core/list':
				[ $style, $items ] = self::parse_list_html( $html );

				return $items ? [
					'type'  => 'list',
					'style' => $style,
					'items' => $items,
				] : null;

			case 'core/image':
				return self::parse_image_block( $block );

			default:
				return null;
		}
	}

	private static function parse_image_block( array $block ): ?array {
		$attachment_id = (int) ( $block['attrs']['id'] ?? 0 );
		$html          = $block['innerHTML'] ?? '';

		if ( ! $attachment_id && preg_match( '/wp-image-(\d+)/', $html, $m ) ) {
			$attachment_id = (int) $m[1];
		}

		if ( ! $attachment_id ) {
			return null;
		}

		$fragment   = self::load_fragment( $html );
		$figcaption = $fragment->getElementsByTagName( 'figcaption' )->item( 0 );

		return [
			'type'          => 'image',
			'attachment_id' => $attachment_id,
			'caption'       => $figcaption ? trim( $figcaption->textContent ) : '',
			'full_width'    => 'full' === ( $block['attrs']['align'] ?? '' ),
		];
	}

	// -- HTML (English, from the wysiwyg field) parsing -----------------

	private static function parse_html_node( DOMElement $node ): ?array {
		$tag = strtolower( $node->nodeName );

		if ( 'p' === $tag ) {
			$text      = trim( $node->textContent );
			$shortcode = self::match_shortcode( $text );

			if ( $shortcode ) {
				return $shortcode;
			}

			// The classic editor wraps an inserted image in its own <p> —
			// that's an image block, not a paragraph of text.
			$img = $node->getElementsByTagName( 'img' )->item( 0 );

			if ( $img instanceof DOMElement && '' === trim( str_replace( "\u{FEFF}", '', preg_replace( '/\s+/u', '', $text ) ) ) ) {
				return self::image_block_from_img( $img, $node );
			}

			return '' !== $text ? [ 'type' => 'paragraph', 'text' => $text ] : null;
		}

		if ( in_array( $tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], true ) ) {
			$text = trim( $node->textContent );

			return '' !== $text ? [ 'type' => 'heading', 'text' => $text ] : null;
		}

		if ( 'blockquote' === $tag ) {
			$p     = $node->getElementsByTagName( 'p' )->item( 0 );
			$cite  = $node->getElementsByTagName( 'cite' )->item( 0 );
			$text  = trim( $p ? $p->textContent : $node->textContent );

			return '' !== $text ? [
				'type'        => 'quote',
				'text'        => $text,
				'attribution' => $cite ? trim( $cite->textContent ) : '',
			] : null;
		}

		if ( in_array( $tag, [ 'ul', 'ol' ], true ) ) {
			$items = [];

			foreach ( $node->getElementsByTagName( 'li' ) as $li ) {
				$text = trim( $li->textContent );

				if ( '' !== $text ) {
					$items[] = $text;
				}
			}

			return $items ? [
				'type'  => 'list',
				'style' => 'ol' === $tag ? 'numbered' : 'bullet',
				'items' => $items,
			] : null;
		}

		if ( 'figure' === $tag ) {
			$img = $node->getElementsByTagName( 'img' )->item( 0 );

			return $img instanceof DOMElement ? self::image_block_from_img( $img, $node ) : null;
		}

		if ( 'img' === $tag ) {
			return self::image_block_from_img( $node, $node );
		}

		return null;
	}

	private static function image_block_from_img( DOMElement $img, DOMElement $container ): ?array {
		if ( ! preg_match( '/wp-image-(\d+)/', $img->getAttribute( 'class' ), $m ) ) {
			return null;
		}

		$figcaption = $container->getElementsByTagName( 'figcaption' )->item( 0 );

		return [
			'type'          => 'image',
			'attachment_id' => (int) $m[1],
			'caption'       => $figcaption ? trim( $figcaption->textContent ) : '',
			'full_width'    => false,
		];
	}

	// -- Shared HTML-fragment helpers -----------------------------------

	private static function parse_quote_html( string $html ): array {
		$fragment = self::load_fragment( $html );
		$p        = $fragment->getElementsByTagName( 'p' )->item( 0 );
		$cite     = $fragment->getElementsByTagName( 'cite' )->item( 0 );
		$text     = trim( $p ? $p->textContent : wp_strip_all_tags( $html ) );

		return [ $text, $cite ? trim( $cite->textContent ) : '' ];
	}

	private static function parse_list_html( string $html ): array {
		$fragment = self::load_fragment( $html );
		$ol       = $fragment->getElementsByTagName( 'ol' )->item( 0 );
		$ul       = $fragment->getElementsByTagName( 'ul' )->item( 0 );
		$list     = $ol ?? $ul;

		if ( ! $list instanceof DOMElement ) {
			return [ 'bullet', [] ];
		}

		$items = [];

		foreach ( $list->getElementsByTagName( 'li' ) as $li ) {
			$text = trim( $li->textContent );

			if ( '' !== $text ) {
				$items[] = $text;
			}
		}

		return [ $ol ? 'numbered' : 'bullet', $items ];
	}

	/**
	 * DOMDocument::loadHTML mangles multi-byte UTF-8 (splits characters
	 * mid-byte) unless told the encoding explicitly — the `<?xml
	 * encoding="utf-8">` prefix is the standard workaround. Wrapping in a
	 * <div> means callers always get one root element back regardless of
	 * how many/what top-level nodes the fragment contains.
	 */
	private static function load_fragment( string $html ): DOMElement {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();

		return $dom->getElementsByTagName( 'div' )->item( 0 );
	}

	/**
	 * Matches a whole-block shortcode for the 3 block types with no
	 * native block-editor equivalent: [limak_highlight]text[/limak_highlight],
	 * [limak_cta href="/contact" label="..."]text[/limak_cta],
	 * [limak_imagetext id="123" side="start"]text[/limak_imagetext].
	 */
	private static function match_shortcode( string $text ): ?array {
		// The wysiwyg field's editor (and WordPress's own save-time
		// texturizing) turns straight quotes into "smart" ones — a
		// closing quote right after a digit even becomes a prime mark
		// (7" -> 7″) rather than a right double quote. Normalize back to
		// a plain " before matching, or every shortcode typed by hand
		// through the actual editor UI would silently fail to parse.
		$text = strtr( $text, [ "\u{201C}" => '"', "\u{201D}" => '"', "\u{2033}" => '"' ] );

		if ( ! preg_match( '/^\[limak_(highlight|cta|imagetext)((?:\s+[a-z_]+="[^"]*")*)\s*\](.*)\[\/limak_\1\]$/su', $text, $m ) ) {
			return null;
		}

		$kind = $m[1];
		$body = trim( $m[3] );
		$attrs = [];

		preg_match_all( '/([a-z_]+)="([^"]*)"/', $m[2], $attr_matches, PREG_SET_ORDER );

		foreach ( $attr_matches as $attr_match ) {
			$attrs[ $attr_match[1] ] = $attr_match[2];
		}

		switch ( $kind ) {
			case 'highlight':
				return [ 'type' => 'highlight', 'text' => $body ];

			case 'cta':
				return [
					'type'      => 'cta',
					'text'      => $body,
					'cta_label' => $attrs['label'] ?? '',
					'href'      => $attrs['href'] ?? '/contact',
				];

			case 'imagetext':
				return [
					'type'          => 'imageText',
					'attachment_id' => (int) ( $attrs['id'] ?? 0 ),
					'text'          => $body,
					'image_side'    => in_array( $attrs['side'] ?? 'start', [ 'start', 'end' ], true ) ? $attrs['side'] : 'start',
				];
		}

		return null;
	}

	// -- Merging ----------------------------------------------------------

	private static function merge_block( string $type, ?array $fa, ?array $en ): ?array {
		switch ( $type ) {
			case 'paragraph':
			case 'heading':
			case 'highlight':
				return [
					'type' => $type,
					'text' => self::localized_text( $fa, $en, 'text' ),
				];

			case 'quote':
				$attribution = self::localized_text( $fa, $en, 'attribution' );

				return [
					'type'        => 'quote',
					'text'        => self::localized_text( $fa, $en, 'text' ),
					'attribution' => ( '' !== $attribution['fa'] || '' !== $attribution['en'] ) ? $attribution : null,
				];

			case 'list':
				return self::merge_list( $fa, $en );

			case 'image':
				return self::merge_image( $fa, $en );

			case 'imageText':
				$attachment_id = $fa['attachment_id'] ?? ( $en['attachment_id'] ?? 0 );
				$image         = Image_Resolver::resolve( $attachment_id ?: null );

				if ( ! $image ) {
					return null;
				}

				return [
					'type'      => 'imageText',
					'image'     => $image,
					'text'      => self::localized_text( $fa, $en, 'text' ),
					'imageSide' => $fa['image_side'] ?? ( $en['image_side'] ?? 'start' ),
				];

			case 'cta':
				return [
					'type'     => 'cta',
					'text'     => self::localized_text( $fa, $en, 'text' ),
					'ctaLabel' => self::localized_text( $fa, $en, 'cta_label' ),
					'href'     => $fa['href'] ?? ( $en['href'] ?? '/contact' ),
				];

			default:
				return null;
		}
	}

	private static function merge_list( ?array $fa, ?array $en ): ?array {
		$style     = $fa['style'] ?? ( $en['style'] ?? 'bullet' );
		$fa_items  = $fa['items'] ?? [];
		$en_items  = $en['items'] ?? [];
		$len       = max( count( $fa_items ), count( $en_items ) );

		if ( 0 === $len ) {
			return null;
		}

		$items_fa = [];
		$items_en = [];

		for ( $i = 0; $i < $len; $i++ ) {
			$items_fa[] = $fa_items[ $i ] ?? ( $en_items[ $i ] ?? '' );
			$items_en[] = $en_items[ $i ] ?? ( $fa_items[ $i ] ?? '' );
		}

		return [
			'type'  => 'list',
			'style' => $style,
			'items' => [ 'fa' => $items_fa, 'en' => $items_en ],
		];
	}

	private static function merge_image( ?array $fa, ?array $en ): ?array {
		$attachment_id = $fa['attachment_id'] ?? ( $en['attachment_id'] ?? 0 );
		$image         = Image_Resolver::resolve( $attachment_id ?: null );

		if ( ! $image ) {
			return null;
		}

		$caption = self::localized_text( $fa, $en, 'caption' );

		if ( '' !== $caption['fa'] || '' !== $caption['en'] ) {
			$image['caption'] = $caption;
		}

		return [
			'type'      => 'image',
			'image'     => $image,
			'fullWidth' => (bool) ( $fa['full_width'] ?? ( $en['full_width'] ?? false ) ),
		];
	}

	/**
	 * @return array{fa: string, en: string}
	 */
	private static function localized_text( ?array $fa, ?array $en, string $key ): array {
		$fa_value = $fa[ $key ] ?? '';
		$en_value = $en[ $key ] ?? '';

		return [
			'fa' => '' !== $fa_value ? $fa_value : $en_value,
			'en' => '' !== $en_value ? $en_value : $fa_value,
		];
	}
}
