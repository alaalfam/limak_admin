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
					$this->seo_fields(),
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
				// A plain textarea, not a repeater: ACF's Repeater field
				// requires ACF PRO, which this project doesn't have (same
				// reason the gallery field is a custom meta box instead of
				// ACF's Gallery field — see Support\Media\Gallery_Field).
				'instructions' => __( 'Separate paragraphs with a blank line.', 'limak-headless' ),
				'type'         => 'textarea',
				'rows'         => 8,
			],
			[
				'key'          => 'field_limak_product_description_en',
				'name'         => 'description_en',
				'label'        => __( 'Description (English)', 'limak-headless' ),
				'instructions' => __( 'Separate paragraphs with a blank line.', 'limak-headless' ),
				'type'         => 'textarea',
				'rows'         => 8,
			],
		];
	}

	/**
	 * Meta description is deliberately its own field, separate from the
	 * short description above: the short description is on-site copy
	 * (shown on the product card), written for a visitor already looking
	 * at the page. A meta description is written for someone who hasn't
	 * clicked yet — it needs to work as a search-result snippet, which
	 * often means different phrasing/length constraints (~150-160
	 * characters) than what reads best on the card itself. Required on
	 * both locales so a new product can't be published without one.
	 */
	private function seo_fields(): array {
		return [
			[
				'key'   => 'field_limak_product_tab_seo',
				'label' => __( 'SEO', 'limak-headless' ),
				'type'  => 'tab',
			],
			[
				'key'          => 'field_limak_product_meta_description_fa',
				'name'         => 'meta_description_fa',
				'label'        => __( 'Meta Description (Persian)', 'limak-headless' ),
				'instructions' => __( 'Shown as the search-result snippet on the Persian site. Aim for about 150-160 characters.', 'limak-headless' ),
				'type'         => 'textarea',
				'rows'         => 2,
				'maxlength'    => 160,
				'required'     => 1,
			],
			[
				'key'          => 'field_limak_product_meta_description_en',
				'name'         => 'meta_description_en',
				'label'        => __( 'Meta Description (English)', 'limak-headless' ),
				'instructions' => __( 'Shown as the search-result snippet on the English site. Aim for about 150-160 characters.', 'limak-headless' ),
				'type'         => 'textarea',
				'rows'         => 2,
				'maxlength'    => 160,
				'required'     => 1,
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
				'label'        => __( 'Price — Persian site (Toman)', 'limak-headless' ),
				'instructions' => __( 'Shown on the Persian site. Leave empty if not yet priced — the price is simply hidden on the site.', 'limak-headless' ),
				'type'         => 'number',
				'min'          => 0,
				'step'         => 1000,
			],
			[
				'key'          => 'field_limak_product_price_usd',
				'name'         => 'price_usd',
				'label'        => __( 'Price — English site (USD)', 'limak-headless' ),
				'instructions' => __( 'Shown on the English site. Independent of the Toman price above — leave empty if not yet priced.', 'limak-headless' ),
				'type'         => 'number',
				'min'          => 0,
				'step'         => 1,
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
				// One per line, not a repeater (needs ACF PRO — see the
				// note on description_fa above): "Persian name | English
				// name | hex color".
				'instructions' => __( 'Fabric/leather/wood finish swatches shown on the product detail page. One per line: Persian name | English name | hex color. Example: چرم امبر | Umber Leather | #6b4a34', 'limak-headless' ),
				'type'         => 'textarea',
				'rows'         => 5,
			],
			[
				'key'          => 'field_limak_product_sizes',
				'name'         => 'sizes',
				'label'        => __( 'Sizes', 'limak-headless' ),
				'instructions' => __( 'Optional configuration sizes shown on the product detail page. One per line: Persian label | English label. Example: دونفره | Two-Seat', 'limak-headless' ),
				'type'         => 'textarea',
				'rows'         => 4,
			],
		];
	}
}
