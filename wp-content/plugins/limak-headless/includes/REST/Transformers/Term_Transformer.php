<?php

namespace Limak\Headless\REST\Transformers;

use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps a WP_Term into the {id, slug, name: {fa, en}} shape shared by every
 * taxonomy-derived response — product/project taxonomy term lists
 * (Product_Transformer) and the standalone category-list endpoints alike.
 * Persian is the term's native `name`; English is the `name_en` ACF field
 * added by Fields\Taxonomy_Term_Fields.
 */
final class Term_Transformer {

	public static function to_array( WP_Term $term ): array {
		return [
			'id'   => $term->term_id,
			'slug' => $term->slug,
			'name' => [
				'fa' => $term->name,
				'en' => get_field( 'name_en', $term ) ?: null,
			],
		];
	}

	/**
	 * @param WP_Term[] $terms
	 */
	public static function to_array_many( array $terms ): array {
		return array_values( array_map( [ self::class, 'to_array' ], $terms ) );
	}
}
