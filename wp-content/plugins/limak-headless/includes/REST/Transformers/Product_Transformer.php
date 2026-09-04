<?php

namespace Limak\Headless\REST\Transformers;

use Limak\Headless\Support\Media\Image_Resolver;
use Limak\Headless\Support\Media\Postmeta_Gallery_Source;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps a Product WP_Post — plus its ACF fields, taxonomy terms, featured
 * image and gallery meta — into the flat, camelCase shape the React
 * frontend consumes. This is the single seam between WordPress internals
 * (ACF field names, meta keys, taxonomy slugs) and the public API contract:
 * nothing outside this class should know how a field is actually stored,
 * and nothing in here should leak into the REST response un-transformed.
 *
 * Persian is WordPress's native content (post_title, post_excerpt); English
 * lives in companion ACF fields (see Product_Fields) — this project has no
 * multilingual plugin and doesn't need per-language duplicate posts. Every
 * bilingual value in the response is a {fa, en} object; `en` is null when
 * no English value has been entered.
 *
 * to_summary() is for list views (product cards); to_detail() is for a
 * single product page. Keeping them separate avoids shipping full
 * descriptions, galleries and dimensions on every item of a list response.
 */
final class Product_Transformer {

	public static function to_summary( WP_Post $post ): array {
		return self::base_fields( $post );
	}

	public static function to_detail( WP_Post $post ): array {
		$collection_id = get_field( 'collection', $post->ID );
		$gallery       = new Postmeta_Gallery_Source();

		return array_merge(
			self::base_fields( $post ),
			[
				'gallery'     => Image_Resolver::resolve_many( $gallery->get_attachment_ids( $post->ID ) ),
				'description' => [
					'fa' => self::transform_paragraphs( $post->ID, 'description_fa' ),
					'en' => self::transform_paragraphs( $post->ID, 'description_en' ),
				],
				'collection'  => self::transform_collection( $collection_id ),
				'woodType'    => get_field( 'wood_type', $post->ID ) ?: null,
				'fabricType'  => get_field( 'fabric_type', $post->ID ) ?: null,
				'dimensions'  => self::transform_dimensions( $post->ID ),
				'finishes'    => self::transform_finishes( $post->ID ),
				'sizes'       => self::transform_sizes( $post->ID ),
				'year'        => self::to_nullable_int( get_field( 'year', $post->ID ) ),
				'videoUrl'    => get_field( 'video_url', $post->ID ) ?: null,
				'modelUrl'    => get_field( 'model_3d_url', $post->ID ) ?: null,
				'catalogPdf'  => self::transform_file( get_field( 'catalog_pdf', $post->ID ) ),
			]
		);
	}

	/**
	 * Fields present on both the summary and detail shape.
	 */
	private static function base_fields( WP_Post $post ): array {
		return [
			'id'               => $post->ID,
			'title'            => [
				'fa' => get_the_title( $post ),
				'en' => get_field( 'title_en', $post->ID ) ?: null,
			],
			'slug'             => $post->post_name,
			'heroImage'        => Image_Resolver::resolve( get_post_thumbnail_id( $post ) ?: null ),
			'shortDescription' => [
				'fa' => get_the_excerpt( $post ),
				'en' => get_field( 'short_description_en', $post->ID ) ?: null,
			],
			'category'         => self::transform_terms( $post->ID, 'product_category' ),
			'materials'        => self::transform_terms( $post->ID, 'material' ),
			'colors'           => self::transform_terms( $post->ID, 'color' ),
			// Independent per-locale prices, not a currency conversion of a
			// single value — Toman for the Persian site, USD for the
			// English site, each optional on its own.
			'price'            => [
				'fa' => self::to_nullable_int( get_field( 'price', $post->ID ) ),
				'en' => self::to_nullable_int( get_field( 'price_usd', $post->ID ) ),
			],
			'designer'         => get_field( 'designer', $post->ID ) ?: null,
			'featured'         => (bool) get_field( 'featured', $post->ID ),
			'order'            => (int) $post->menu_order,
		];
	}

	private static function transform_collection( $collection_id ): ?array {
		if ( ! $collection_id ) {
			return null;
		}

		$collection = get_post( $collection_id );

		if ( ! $collection instanceof WP_Post ) {
			return null;
		}

		return [
			'id'    => $collection->ID,
			'title' => get_the_title( $collection ),
			'slug'  => $collection->post_name,
		];
	}

	private static function transform_terms( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );

		return is_array( $terms ) ? Term_Transformer::to_array_many( $terms ) : [];
	}

	/**
	 * description_fa/description_en are a plain textarea (not a repeater —
	 * see the note in Product_Fields), paragraphs separated by a blank
	 * line. Splits on one-or-more blank lines so a stray extra newline
	 * doesn't produce an empty paragraph.
	 *
	 * @return string[]
	 */
	private static function transform_paragraphs( int $post_id, string $field_name ): array {
		$value = (string) get_field( $field_name, $post_id );

		if ( '' === trim( $value ) ) {
			return [];
		}

		// The /u (UTF-8) modifier is required here: without it, \R matches
		// raw byte 0x85 (PCRE's "NEL" case for \R) wherever it happens to
		// occur as a continuation byte inside a multi-byte UTF-8 character
		// (e.g. "م" is 0xD9 0x85) — silently corrupting Persian text by
		// splitting mid-character.
		$paragraphs = preg_split( '/\R{2,}/u', trim( $value ) );

		return array_values( array_filter( array_map( 'trim', $paragraphs ) ) );
	}

	/**
	 * finishes is a plain textarea (not a repeater — see the note in
	 * Product_Fields), one finish per line: "fa name | en name | hex".
	 */
	private static function transform_finishes( int $post_id ): array {
		return array_values(
			array_filter(
				array_map(
					static function ( string $line ): ?array {
						$parts = array_map( 'trim', explode( '|', $line ) );

						if ( '' === ( $parts[0] ?? '' ) ) {
							return null;
						}

						return [
							'id'   => sanitize_title( $parts[0] ?: ( $parts[1] ?? '' ) ),
							'name' => [
								'fa' => $parts[0],
								'en' => ( $parts[1] ?? '' ) ?: null,
							],
							'hex'  => $parts[2] ?? '',
						];
					},
					self::split_lines( (string) get_field( 'finishes', $post_id ) )
				)
			)
		);
	}

	/**
	 * sizes is a plain textarea (not a repeater — see the note in
	 * Product_Fields), one size per line: "fa label | en label".
	 *
	 * @return array{fa: string, en: ?string}[]
	 */
	private static function transform_sizes( int $post_id ): array {
		return array_values(
			array_filter(
				array_map(
					static function ( string $line ): ?array {
						$parts = array_map( 'trim', explode( '|', $line ) );

						if ( '' === ( $parts[0] ?? '' ) ) {
							return null;
						}

						return [
							'fa' => $parts[0],
							'en' => ( $parts[1] ?? '' ) ?: null,
						];
					},
					self::split_lines( (string) get_field( 'sizes', $post_id ) )
				)
			)
		);
	}

	/**
	 * @return string[]
	 */
	private static function split_lines( string $value ): array {
		if ( '' === trim( $value ) ) {
			return [];
		}

		// See the /u note on the paragraph split above — same reason.

		return preg_split( '/\R/u', trim( $value ) );
	}

	/**
	 * A single pre-formatted display string per locale, derived from the
	 * structured width/height/depth/unit fields (kept as the source of
	 * truth) — e.g. "228 × 96 × 74 cm" / "۲۲۸ × ۹۶ × ۷۴ سانتی‌متر".
	 */
	private static function transform_dimensions( int $post_id ): ?array {
		$dimensions = (array) get_field( 'dimensions', $post_id );
		$width      = self::to_nullable_float( $dimensions['width'] ?? null );
		$height     = self::to_nullable_float( $dimensions['height'] ?? null );
		$depth      = self::to_nullable_float( $dimensions['depth'] ?? null );

		if ( null === $width || null === $height || null === $depth ) {
			return null;
		}

		$unit = $dimensions['unit'] ?? 'cm';

		$unit_labels = [
			'en' => [
				'cm' => 'cm',
				'in' => 'in',
			],
			'fa' => [
				'cm' => 'سانتی‌متر',
				'in' => 'اینچ',
			],
		];

		$format = static fn( float $value ): string => rtrim( rtrim( number_format( $value, 1 ), '0' ), '.' );

		return [
			'en' => sprintf( '%s × %s × %s %s', $format( $width ), $format( $height ), $format( $depth ), $unit_labels['en'][ $unit ] ?? $unit ),
			'fa' => self::to_persian_digits(
				sprintf( '%s × %s × %s %s', $format( $width ), $format( $height ), $format( $depth ), $unit_labels['fa'][ $unit ] ?? $unit )
			),
		];
	}

	private static function to_persian_digits( string $value ): string {
		return strtr( $value, [ '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ] );
	}

	private static function to_nullable_int( $value ): ?int {
		return ( '' === $value || null === $value || false === $value ) ? null : (int) $value;
	}

	private static function to_nullable_float( $value ): ?float {
		return ( '' === $value || null === $value || false === $value ) ? null : (float) $value;
	}

	private static function transform_file( $attachment_id ): ?array {
		if ( ! $attachment_id ) {
			return null;
		}

		return [
			'id'  => (int) $attachment_id,
			'url' => wp_get_attachment_url( $attachment_id ) ?: '',
		];
	}
}
