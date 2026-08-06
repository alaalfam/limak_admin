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
 * Title, slug, hero image, gallery, short/full description, category,
 * materials, colors and display order are all handled outside ACF —
 * see Product_Transformer for how everything is assembled.
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
				'fields'   => [
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
					[
						'key'   => 'field_limak_product_featured',
						'name'  => 'featured',
						'label' => __( 'Featured', 'limak-headless' ),
						'type'  => 'true_false',
						'ui'    => 1,
					],
				],
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
}
