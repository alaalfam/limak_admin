<?php

namespace Limak\Headless\Fields;

use Limak\Headless\Support\Registrable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an English alt-text field to the media library attachment edit
 * screen. The native `_wp_attachment_image_alt` stays Persian (unchanged).
 */
final class Attachment_Fields implements Registrable {

	public function register(): void {
		add_action( 'acf/init', [ $this, 'register_field_group' ] );
	}

	public function register_field_group(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'group_limak_attachment',
				'title'    => __( 'Translation', 'limak-headless' ),
				'fields'   => [
					[
						'key'   => 'field_limak_attachment_alt_en',
						'name'  => 'alt_en',
						'label' => __( 'Alt Text (English)', 'limak-headless' ),
						'type'  => 'text',
					],
				],
				'location' => [
					[
						[
							'param'    => 'attachment',
							'operator' => '==',
							'value'    => 'image',
						],
					],
				],
			]
		);
	}
}
