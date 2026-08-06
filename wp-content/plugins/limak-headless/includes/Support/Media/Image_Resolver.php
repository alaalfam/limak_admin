<?php

namespace Limak\Headless\Support\Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves a Media Library attachment ID into the flat shape the frontend
 * expects. Used for both the gallery and any single-image field (e.g. hero
 * image via featured image), so the image shape is identical everywhere.
 */
final class Image_Resolver {

	public static function resolve( ?int $attachment_id ): ?array {
		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			return null;
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		return [
			'attachment_id' => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ) ?: '',
			'alt'           => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ?: '',
			'width'         => isset( $metadata['width'] ) ? (int) $metadata['width'] : null,
			'height'        => isset( $metadata['height'] ) ? (int) $metadata['height'] : null,
		];
	}

	/**
	 * @param int[] $attachment_ids
	 */
	public static function resolve_many( array $attachment_ids ): array {
		$images = array_map( [ self::class, 'resolve' ], $attachment_ids );

		return array_values( array_filter( $images ) );
	}
}
