<?php

namespace Limak\Headless\REST\Transformers;

use Limak\Headless\Support\Content\Block_Parser;
use Limak\Headless\Support\Media\Image_Resolver;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps a native WP blog post — plus its ACF fields, categories and tags —
 * into the bilingual shape the frontend's "Journal" (مجله) section
 * consumes. Persian is native (post_title, post_excerpt, post_content,
 * category/post_tag term names); English lives in companion ACF fields —
 * same split as Product_Transformer. See Support\Content\Block_Parser for
 * how the structured, block-based body is assembled from the two.
 */
final class Post_Transformer {

	public static function to_summary( WP_Post $post ): array {
		return self::base_fields( $post );
	}

	public static function to_detail( WP_Post $post ): array {
		$fa_body = Block_Parser::parse_gutenberg_blocks( $post->post_content );
		$en_body = Block_Parser::parse_html_blocks( (string) get_field( 'body_en', $post->ID ) );

		return array_merge(
			self::base_fields( $post ),
			[
				'metaDescription' => [
					'fa' => get_field( 'meta_description_fa', $post->ID ) ?: null,
					'en' => get_field( 'meta_description_en', $post->ID ) ?: null,
				],
				'body'            => Block_Parser::to_bilingual( $fa_body, $en_body ),
				'relatedSlugs'    => self::transform_related( $post->ID ),
			]
		);
	}

	/**
	 * Fields present on both the summary and detail shape.
	 */
	private static function base_fields( WP_Post $post ): array {
		return [
			'id'        => $post->ID,
			'title'     => [
				'fa' => get_the_title( $post ),
				'en' => get_field( 'title_en', $post->ID ) ?: null,
			],
			'slug'      => $post->post_name,
			'heroImage' => Image_Resolver::resolve( get_post_thumbnail_id( $post ) ?: null ),
			'excerpt'   => [
				'fa' => get_the_excerpt( $post ),
				'en' => get_field( 'excerpt_en', $post->ID ) ?: null,
			],
			// WordPress's real multi-term model, same as Product's
			// category/materials/colors — the frontend picks the first
			// entry until/unless it needs to show more than one.
			'category'  => self::transform_terms( $post->ID, 'category' ),
			'tags'      => self::transform_terms( $post->ID, 'post_tag' ),
			'date'      => get_the_date( 'Y-m-d', $post ),
			'author'    => get_the_author_meta( 'display_name', (int) $post->post_author ) ?: null,
			'featured'  => (bool) get_field( 'featured', $post->ID ),
		];
	}

	private static function transform_terms( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );

		return is_array( $terms ) ? Term_Transformer::to_array_many( $terms ) : [];
	}

	/**
	 * Explicit editor picks (the `related_posts` field) first, since an
	 * empty return here just means "no explicit picks" — the frontend
	 * already falls back to same-category articles on its own when this
	 * is empty (see getRelatedArticles), so no fallback is computed here.
	 */
	private static function transform_related( int $post_id ): array {
		$related_ids = (array) get_field( 'related_posts', $post_id );

		return array_values(
			array_filter(
				array_map(
					static function ( $related_id ): ?string {
						$related = get_post( (int) $related_id );

						return $related instanceof WP_Post ? $related->post_name : null;
					},
					$related_ids
				)
			)
		);
	}
}
