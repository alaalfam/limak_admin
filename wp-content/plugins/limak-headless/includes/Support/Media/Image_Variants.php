<?php

namespace Limak\Headless\Support\Media;

use Limak\Headless\Support\Registrable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates .webp/.avif sibling files next to every uploaded image, using
 * GD (confirmed available in the container — no new dependency). No extra
 * postmeta bookkeeping: variant paths are derived by swapping the file
 * extension both here and in Image_Resolver, and existence is checked with
 * file_exists() at read time, so a missing/failed variant just falls back
 * to the original file rather than producing a broken image reference.
 */
final class Image_Variants implements Registrable {

	private const WEBP_QUALITY = 82;
	private const AVIF_QUALITY = 55;

	public function register(): void {
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_variants' ], 10, 2 );
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function generate_variants( array $metadata, int $attachment_id ): array {
		$file = get_attached_file( $attachment_id );

		if ( ! $file || ! file_exists( $file ) ) {
			return $metadata;
		}

		$mime = get_post_mime_type( $attachment_id );

		if ( ! in_array( $mime, [ 'image/jpeg', 'image/png' ], true ) ) {
			return $metadata;
		}

		$image = 'image/png' === $mime ? @imagecreatefrompng( $file ) : @imagecreatefromjpeg( $file );

		if ( ! $image ) {
			return $metadata;
		}

		// PNGs can have an alpha channel; preserve it rather than compositing
		// onto black when re-encoding.
		imagepalettetotruecolor( $image );
		imagealphablending( $image, true );
		imagesavealpha( $image, true );

		$webp_path = self::variant_path( $file, 'webp' );
		$avif_path = self::variant_path( $file, 'avif' );

		if ( function_exists( 'imagewebp' ) ) {
			imagewebp( $image, $webp_path, self::WEBP_QUALITY );
		}

		if ( function_exists( 'imageavif' ) ) {
			imageavif( $image, $avif_path, self::AVIF_QUALITY );
		}

		imagedestroy( $image );

		return $metadata;
	}

	public static function variant_path( string $original_path, string $extension ): string {
		return preg_replace( '/\.[^.]+$/', '.' . $extension, $original_path );
	}

	public static function variant_url( string $original_url, string $extension ): string {
		return preg_replace( '/\.[^.]+$/', '.' . $extension, $original_url );
	}
}
