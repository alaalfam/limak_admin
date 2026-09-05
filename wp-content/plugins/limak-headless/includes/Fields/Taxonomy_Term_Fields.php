<?php

namespace Limak\Headless\Fields;

use Limak\Headless\Support\Registrable;
use Limak\Headless\Taxonomies\Color;
use Limak\Headless\Taxonomies\Material;
use Limak\Headless\Taxonomies\Product_Category;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an English name field to each product-related taxonomy's term-edit
 * screen, and to the two native blog taxonomies (category, post_tag) used
 * by articles. The term's native `name` stays Persian (unchanged); this is
 * the only extra field needed since a taxonomy term has nothing else the
 * frontend consumes beyond id/slug/name.
 */
final class Taxonomy_Term_Fields implements Registrable {

	public function register(): void {
		add_action( 'acf/init', [ $this, 'register_field_group' ] );
	}

	public function register_field_group(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'group_limak_taxonomy_term',
				'title'    => __( 'Translation', 'limak-headless' ),
				'fields'   => [
					[
						'key'   => 'field_limak_term_name_en',
						'name'  => 'name_en',
						'label' => __( 'Name (English)', 'limak-headless' ),
						'type'  => 'text',
					],
				],
				'location' => [
					[
						[
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => Product_Category::SLUG,
						],
					],
					[
						[
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => Material::SLUG,
						],
					],
					[
						[
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => Color::SLUG,
						],
					],
					[
						[
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => 'category',
						],
					],
					[
						[
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => 'post_tag',
						],
					],
				],
			]
		);
	}
}
