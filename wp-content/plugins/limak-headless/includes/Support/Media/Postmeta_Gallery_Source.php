<?php

namespace Limak\Headless\Support\Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores an ordered list of Media Library attachment IDs in post meta.
 *
 * This is the free-ACF-tier stand-in for an ACF PRO Gallery field. To
 * migrate later: implement Gallery_Source against get_field('gallery', ...),
 * swap the class used in the plugin bootstrap, and delete Gallery_Field
 * (the admin meta box) since ACF PRO renders its own UI. Nothing else —
 * not the transformers, not the REST contract — needs to change.
 */
final class Postmeta_Gallery_Source implements Gallery_Source {

	public const META_KEY = '_limak_gallery_ids';

	public function get_attachment_ids( int $post_id ): array {
		$ids = get_post_meta( $post_id, self::META_KEY, true );

		if ( ! is_array( $ids ) ) {
			return [];
		}

		return array_values( array_map( 'absint', $ids ) );
	}

	/**
	 * @param int[] $ids
	 */
	public function save_attachment_ids( int $post_id, array $ids ): void {
		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );

		update_post_meta( $post_id, self::META_KEY, $ids );
	}
}
