<?php

namespace Limak\Headless\Fields;

use Limak\Headless\PostTypes\Collection;
use Limak\Headless\PostTypes\Product;
use Limak\Headless\Support\Registrable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF field group for data that has no native WordPress equivalent.
 * Persian title/short description are the native post_title/post_excerpt
 * (unchanged); everything bilingual beyond that — English variants, and
 * the full description in both languages — lives here as ACF fields, since
 * this project has no multilingual plugin and doesn't need per-language
 * duplicate posts. See Product_Transformer for how everything is assembled
 * into the frontend's {fa, en} shape.
 */
final class Product_Fields implements Registrable {

	public function register(): void {
		add_action( 'acf/init', [ $this, 'register_field_group' ] );
	}

	public function register_field_group(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'group_limak_product',
				'title'    => __( 'Product Details', 'limak-headless' ),
				'fields'   => array_merge(
					$this->bilingual_content_fields(),
					$this->pricing_fields(),
					$this->specification_fields(),
					$this->media_fields(),
					$this->detail_page_fields()
				),
				'location' => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => Product::SLUG,
						],
					],
				],
			]
		);
	}

	private function bilingual_content_fields(): array {
		return [
			[
				'key'   => 'field_limak_product_tab_bilingual',
				'label' => __( 'Bilingual Content', 'limak-headless' ),
				'type'  => 'tab',
			],
			[
				'key'   => 'field_limak_product_title_en',
				'name'  => 'title_en',
				/* translators: English-language title field, shown next to the native (Persian) title. */
				'label' => __( 'Title (English)', 'limak-headless' ),
				'type'  => 'text',
			],
			[
				'key'   => 'field_limak_product_short_description_en',
				'name'  => 'short_description_en',
				'label' => __( 'Short Description (English)', 'limak-headless' ),
				'type'  => 'textarea',
				'rows'  => 2,
			],
			[
				'key'          => 'field_limak_product_description_fa',
				'name'         => 'description_fa',
				'label'        => __( 'Description (Persian)', 'limak-headless' ),
				'instructions' => __( 'One row per paragraph.', 'limak-headless' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add Paragraph', 'limak-headless' ),
				'sub_fields'   => [
					[
						'key'   => 'field_limak_product_description_fa_paragraph',
						'name'  => 'paragraph',
						'label' => __( 'Paragraph', 'limak-headless' ),
						'type'  => 'textarea',
						'rows'  => 3,
					],
				],
			],
			[
				'key'          => 'field_limak_product_description_en',
				'name'         => 'description_en',
				'label'        => __( 'Description (English)', 'limak-headless' ),
				'instructions' => __( 'One row per paragraph.', 'limak-headless' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add Paragraph', 'limak-headless' ),
				'sub_fields'   => [
					[
						'key'   => 'field_limak_product_description_en_paragraph',
						'name'  => 'paragraph',
						'label' => __( 'Paragraph', 'limak-headless' ),
						'type'  => 'textarea',
						'rows'  => 3,
					],
				],
			],
		];
	}

	private function pricing_fields(): array {
		return [
			[
				'key'   => 'field_limak_product_tab_pricing',
				'label' => __( 'Pricing & Collection', 'limak-headless' ),
				'type'  => 'tab',
			],
			[
				'key'          => 'field_limak_product_price',
				'name'         => 'price',
				'label'        => __( 'Price (Toman)', 'limak-headless' ),
				'instructions' => __( 'Leave empty if not yet priced — the price is simply hidden on the site.', 'limak-headless' ),
				'type'         => 'number',
				'min'          => 0,
				'step'         => 1000,
			],
			[
				'key'           => 'field_limak_product_collection',
				'name'          => 'collection',
				'label'         => __( 'Collection', 'limak-headless' ),
				'type'          => 'post_object',
				'post_type'     => [ Collection::SLUG ],
				'return_format' => 'id',
				'ui'            => 1,
			],
			[
				'key'   => 'field_limak_product_featured',
				'name'  => 'featured',
				'label' => __( 'Featured', 'limak-headless' ),
				'type'  => 'true_false',
				'ui'    => 1,
			],
		];
	}

	private function specification_fields(): array {
		return [
			[
				'key'   => 'field_limak_product_tab_specifications',
				'label' => __( 'Specifications', 'limak-headless' ),
				'type'  => 'tab',
			],
			[
				'key'   => 'field_limak_product_wood_type',
				'name'  => 'wood_type',
				'label' => __( 'Wood Type', 'limak-headless' ),
				'type'  => 'text',
			],
			[
				'key'   => 'field_limak_product_fabric_type',
				'name'  => 'fabric_type',
				'label' => __( 'Fabric Type', 'limak-headless' ),
				'type'  => 'text',
			],
			[
				'key'        => 'field_limak_product_dimensions',
				'name'       => 'dimensions',
				'label'      => __( 'Dimensions', 'limak-headless' ),
				'type'       => 'group',
				'layout'     => 'table',
				'sub_fields' => [
					[
						'key'   => 'field_limak_product_dimensions_width',
						'name'  => 'width',
						'label' => __( 'Width', 'limak-headless' ),
						'type'  => 'number',
					],
					[
						'key'   => 'field_limak_product_dimensions_height',
						'name'  => 'height',
						'label' => __( 'Height', 'limak-headless' ),
						'type'  => 'number',
					],
					[
						'key'   => 'field_limak_product_dimensions_depth',
						'name'  => 'depth',
						'label' => __( 'Depth', 'limak-headless' ),
						'type'  => 'number',
					],
					[
						'key'           => 'field_limak_product_dimensions_unit',
						'name'          => 'unit',
						'label'         => __( 'Unit', 'limak-headless' ),
						'type'          => 'select',
						'choices'       => [
							'cm' => 'cm',
							'in' => 'in',
						],
						'default_value' => 'cm',
					],
				],
			],
			[
				'key'   => 'field_limak_product_designer',
				'name'  => 'designer',
				'label' => __( 'Designer', 'limak-headless' ),
				'type'  => 'text',
			],
			[
				'key'   => 'field_limak_product_year',
				'name'  => 'year',
				'label' => __( 'Year', 'limak-headless' ),
				'type'  => 'number',
			],
		];
	}

	private function media_fields(): array {
		return [
			[
				'key'   => 'field_limak_product_tab_media',
				'label' => __( 'Media & Links', 'limak-headless' ),
				'type'  => 'tab',
			],
			[
				'key'   => 'field_limak_product_video_url',
				'name'  => 'video_url',
				'label' => __( 'Video URL', 'limak-headless' ),
				'type'  => 'url',
			],
			[
				'key'   => 'field_limak_product_model_3d_url',
				'name'  => 'model_3d_url',
				'label' => __( '3D Model URL', 'limak-headless' ),
				'type'  => 'url',
			],
			[
				'key'           => 'field_limak_product_catalog_pdf',
				'name'          => 'catalog_pdf',
				'label'         => __( 'Catalog PDF', 'limak-headless' ),
				'type'          => 'file',
				'return_format' => 'id',
				'mime_types'    => 'pdf',
			],
		];
	}

	private function detail_page_fields(): array {
		return [
			[
				'key'   => 'field_limak_product_tab_detail_page',
				'label' => __( 'Detail Page Extras', 'limak-headless' ),
				'type'  => 'tab',
			],
			[
				'key'          => 'field_limak_product_finishes',
				'name'         => 'finishes',
				'label'        => __( 'Finishes', 'limak-headless' ),
				'instructions' => __( 'Fabric/leather/wood finish swatches shown on the product detail page.', 'limak-headless' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add Finish', 'limak-headless' ),
				'sub_fields'   => [
					[
						'key'   => 'field_limak_product_finish_name',
						'name'  => 'name',
						'label' => __( 'Name (Persian)', 'limak-headless' ),
						'type'  => 'text',
					],
					[
						'key'   => 'field_limak_product_finish_name_en',
						'name'  => 'name_en',
						'label' => __( 'Name (English)', 'limak-headless' ),
						'type'  => 'text',
					],
					[
						'key'   => 'field_limak_product_finish_color',
						'name'  => 'color',
						'label' => __( 'Color', 'limak-headless' ),
						'type'  => 'color_picker',
					],
				],
			],
			[
				'key'          => 'field_limak_product_sizes',
				'name'         => 'sizes',
				'label'        => __( 'Sizes', 'limak-headless' ),
				'instructions' => __( 'Optional configuration sizes (e.g. "Two-Seat", "Three-Seat"), shown on the product detail page.', 'limak-headless' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add Size', 'limak-headless' ),
				'sub_fields'   => [
					[
						'key'   => 'field_limak_product_size_label',
						'name'  => 'label',
						'label' => __( 'Label (Persian)', 'limak-headless' ),
						'type'  => 'text',
					],
					[
						'key'   => 'field_limak_product_size_label_en',
						'name'  => 'label_en',
						'label' => __( 'Label (English)', 'limak-headless' ),
						'type'  => 'text',
					],
				],
			],
		];
	}
}
