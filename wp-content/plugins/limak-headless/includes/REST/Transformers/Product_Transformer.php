<?php

namespace Limak\Headless\REST\Transformers;

use Limak\Headless\Support\Media\Image_Resolver;
use Limak\Headless\Support\Media\Postmeta_Gallery_Source;
use WP_Post;
use WP_Term;

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
 * to_summary() is for list views (product cards); to_detail() is for a
 * single product page. Keeping them separate avoids shipping full
 * descriptions, galleries and dimensions on every item of a list response.
 */
final class Product_Transformer {

	public static function to_summary( WP_Post $post ): array {
		return self::base_fields( $post );
	}

	public static function to_detail( WP_Post $post ): array {
		$dimensions    = (array) get_field( 'dimensions', $post->ID );
		$collection_id = get_field( 'collection', $post->ID );
		$gallery       = new Postmeta_Gallery_Source();

		return array_merge(
			self::base_fields( $post ),
			[
				'gallery'     => Image_Resolver::resolve_many( $gallery->get_attachment_ids( $post->ID ) ),
				'description' => apply_filters( 'the_content', $post->post_content ),
				'collection'  => self::transform_collection( $collection_id ),
				'woodType'    => get_field( 'wood_type', $post->ID ) ?: null,
				'fabricType'  => get_field( 'fabric_type', $post->ID ) ?: null,
				'dimensions'  => [
					'width'  => self::to_nullable_float( $dimensions['width'] ?? null ),
					'height' => self::to_nullable_float( $dimensions['height'] ?? null ),
					'depth'  => self::to_nullable_float( $dimensions['depth'] ?? null ),
					'unit'   => $dimensions['unit'] ?? 'cm',
				],
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
			'title'            => get_the_title( $post ),
			'slug'             => $post->post_name,
			'heroImage'        => Image_Resolver::resolve( get_post_thumbnail_id( $post ) ?: null ),
			'shortDescription' => get_the_excerpt( $post ),
			'category'         => self::transform_terms( $post->ID, 'product_category' ),
			'materials'        => self::transform_terms( $post->ID, 'material' ),
			'colors'           => self::transform_terms( $post->ID, 'color' ),
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

		if ( ! is_array( $terms ) ) {
			return [];
		}

		return array_values(
			array_map(
				static fn( WP_Term $term ): array => [
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				],
				$terms
			)
		);
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
