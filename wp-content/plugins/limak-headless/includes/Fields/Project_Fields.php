<?php

namespace Limak\Headless\Fields;

use Limak\Headless\PostTypes\Product;
use Limak\Headless\PostTypes\Project;
use Limak\Headless\Support\Registrable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF field group for Project data with no native WordPress equivalent.
 * Title, hero image, gallery, and description are handled outside ACF.
 */
final class Project_Fields implements Registrable {

	public function register(): void {
		add_action( 'acf/init', [ $this, 'register_field_group' ] );
	}

	public function register_field_group(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'group_limak_project',
				'title'    => __( 'Project Details', 'limak-headless' ),
				'fields'   => [
					[
						'key'   => 'field_limak_project_location',
						'name'  => 'location',
						'label' => __( 'Location', 'limak-headless' ),
						'type'  => 'text',
					],
					[
						'key'        => 'field_limak_project_area',
						'name'       => 'area',
						'label'      => __( 'Area', 'limak-headless' ),
						'type'       => 'group',
						'layout'     => 'table',
						'sub_fields' => [
							[
								'key'   => 'field_limak_project_area_value',
								'name'  => 'value',
								'label' => __( 'Value', 'limak-headless' ),
								'type'  => 'number',
							],
							[
								'key'           => 'field_limak_project_area_unit',
								'name'          => 'unit',
								'label'         => __( 'Unit', 'limak-headless' ),
								'type'          => 'select',
								'choices'       => [
									'sqm' => 'm²',
									'sqft' => 'ft²',
								],
								'default_value' => 'sqm',
							],
						],
					],
					[
						'key'           => 'field_limak_project_completion_date',
						'name'          => 'completion_date',
						'label'         => __( 'Completion Date', 'limak-headless' ),
						'type'          => 'date_picker',
						'return_format' => 'Y-m-d',
						'display_format' => 'Y-m-d',
					],
					[
						'key'           => 'field_limak_project_products_used',
						'name'          => 'products_used',
						'label'         => __( 'Products Used', 'limak-headless' ),
						'type'          => 'relationship',
						'post_type'     => [ Product::SLUG ],
						'return_format' => 'id',
					],
					[
						'key'   => 'field_limak_project_architect',
						'name'  => 'architect',
						'label' => __( 'Architect', 'limak-headless' ),
						'type'  => 'text',
					],
					[
						'key'   => 'field_limak_project_video_url',
						'name'  => 'video_url',
						'label' => __( 'Video URL', 'limak-headless' ),
						'type'  => 'url',
					],
					[
						'key'   => 'field_limak_project_featured',
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
							'value'    => Project::SLUG,
						],
					],
				],
			]
		);
	}
}
