<?php

namespace Limak\Headless\REST\Transformers;

use Limak\Headless\PostTypes\Product;
use Limak\Headless\Support\Media\Image_Resolver;
use WP_Post;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps a Collection WP_Post into the frontend-facing shape. Collections
 * are small (Name, Cover Image, Description are all native WP fields), so
 * unlike Product/Project the summary and detail shapes only differ by the
 * list of products belonging to the collection.
 */
final class Collection_Transformer {

	public static function to_summary( WP_Post $post ): array {
		return self::base_fields( $post );
	}

	public static function to_detail( WP_Post $post ): array {
		return array_merge(
			self::base_fields( $post ),
			[
				'products' => self::transform_products( $post->ID ),
			]
		);
	}

	private static function base_fields( WP_Post $post ): array {
		return [
			'id'          => $post->ID,
			'title'       => get_the_title( $post ),
			'slug'        => $post->post_name,
			'coverImage'  => Image_Resolver::resolve( get_post_thumbnail_id( $post ) ?: null ),
			'description' => apply_filters( 'the_content', $post->post_content ),
		];
	}

	private static function transform_products( int $collection_id ): array {
		$query = new WP_Query(
			[
				'post_type'      => Product::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'meta_query'     => [
					[
						'key'   => 'collection',
						'value' => $collection_id,
						'type'  => 'NUMERIC',
					],
				],
			]
		);

		return array_values( array_map( [ Product_Transformer::class, 'to_summary' ], $query->posts ) );
	}
}
