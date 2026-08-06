<?php

namespace Limak\Headless\REST\Transformers;

use Limak\Headless\Support\Media\Image_Resolver;
use WP_Post;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps a native WP blog post into the frontend-facing shape. Blog posts use
 * only core WordPress fields (title, excerpt, content, featured image,
 * categories, tags) — no ACF is involved.
 */
final class Post_Transformer {

	public static function to_summary( WP_Post $post ): array {
		return self::base_fields( $post );
	}

	public static function to_detail( WP_Post $post ): array {
		return array_merge(
			self::base_fields( $post ),
			[
				'content' => apply_filters( 'the_content', $post->post_content ),
				'tags'    => self::transform_terms( $post->ID, 'post_tag' ),
			]
		);
	}

	private static function base_fields( WP_Post $post ): array {
		return [
			'id'        => $post->ID,
			'title'     => get_the_title( $post ),
			'slug'      => $post->post_name,
			'image'     => Image_Resolver::resolve( get_post_thumbnail_id( $post ) ?: null ),
			'excerpt'   => get_the_excerpt( $post ),
			'date'      => get_the_date( 'Y-m-d', $post ),
			'categories'=> self::transform_terms( $post->ID, 'category' ),
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
}
