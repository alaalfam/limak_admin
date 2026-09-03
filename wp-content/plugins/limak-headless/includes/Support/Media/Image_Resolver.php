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

		$url  = wp_get_attachment_url( $attachment_id ) ?: '';
		$file = get_attached_file( $attachment_id );

		return [
			// Not part of the public frontend shape, but Gallery_Field's
			// admin picker (save/reorder/remove) tracks images by this —
			// harmless extra key for REST consumers, which just ignore it.
			'attachment_id' => $attachment_id,
			'avifSrc'        => self::variant_url_if_exists( $file, $url, 'avif' ),
			'webpSrc'        => self::variant_url_if_exists( $file, $url, 'webp' ),
			'fallbackSrc'    => $url,
			'alt'            => [
				'fa' => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ?: '',
				'en' => get_field( 'alt_en', $attachment_id ) ?: '',
			],
		];
	}

	/**
	 * Falls back to the original file so a missing/failed variant (e.g. a
	 * format GD couldn't produce) never breaks the frontend's <picture>
	 * markup — it just serves the original in that slot instead.
	 */
	private static function variant_url_if_exists( string $file, string $fallback_url, string $extension ): string {
		if ( ! $file ) {
			return $fallback_url;
		}

		$variant_path = Image_Variants::variant_path( $file, $extension );

		return file_exists( $variant_path ) ? Image_Variants::variant_url( $fallback_url, $extension ) : $fallback_url;
	}

	/**
	 * @param int[] $attachment_ids
	 */
	public static function resolve_many( array $attachment_ids ): array {
		$images = array_map( [ self::class, 'resolve' ], $attachment_ids );

		return array_values( array_filter( $images ) );
	}
}
