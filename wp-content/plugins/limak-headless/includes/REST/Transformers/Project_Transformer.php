<?php

namespace Limak\Headless\REST\Transformers;

use Limak\Headless\Support\Media\Image_Resolver;
use Limak\Headless\Support\Media\Postmeta_Gallery_Source;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps a Project WP_Post into the frontend-facing shape. Mirrors
 * Product_Transformer's summary/detail split.
 */
final class Project_Transformer {

	public static function to_summary( WP_Post $post ): array {
		return self::base_fields( $post );
	}

	public static function to_detail( WP_Post $post ): array {
		$area    = (array) get_field( 'area', $post->ID );
		$gallery = new Postmeta_Gallery_Source();

		return array_merge(
			self::base_fields( $post ),
			[
				'gallery'         => Image_Resolver::resolve_many( $gallery->get_attachment_ids( $post->ID ) ),
				'description'     => apply_filters( 'the_content', $post->post_content ),
				'area'            => [
					'value' => isset( $area['value'] ) && '' !== $area['value'] ? (float) $area['value'] : null,
					'unit'  => $area['unit'] ?? 'sqm',
				],
				'completionDate'  => get_field( 'completion_date', $post->ID ) ?: null,
				'architect'       => get_field( 'architect', $post->ID ) ?: null,
				'videoUrl'        => get_field( 'video_url', $post->ID ) ?: null,
				'productsUsed'    => self::transform_products_used( $post->ID ),
			]
		);
	}

	private static function base_fields( WP_Post $post ): array {
		return [
			'id'               => $post->ID,
			'title'            => get_the_title( $post ),
			'slug'             => $post->post_name,
			'heroImage'        => Image_Resolver::resolve( get_post_thumbnail_id( $post ) ?: null ),
			'shortDescription' => get_the_excerpt( $post ),
			'location'         => get_field( 'location', $post->ID ) ?: null,
			'featured'         => (bool) get_field( 'featured', $post->ID ),
			'order'            => (int) $post->menu_order,
		];
	}

	private static function transform_products_used( int $post_id ): array {
		$product_ids = get_field( 'products_used', $post_id );

		if ( ! is_array( $product_ids ) ) {
			return [];
		}

		$products = array_filter( array_map( 'get_post', $product_ids ) );

		return array_values( array_map( [ Product_Transformer::class, 'to_summary' ], $products ) );
	}
}
