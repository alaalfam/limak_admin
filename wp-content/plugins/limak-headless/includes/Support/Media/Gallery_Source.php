<?php

namespace Limak\Headless\Support\Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read contract for "an ordered list of attachment IDs belonging to a post".
 * Consumers (transformers) depend only on this interface, not on how the
 * IDs are actually stored — swapping Postmeta_Gallery_Source for an
 * ACF-PRO-backed implementation later requires no change here.
 */
interface Gallery_Source {

	/**
	 * @return int[] Ordered attachment IDs.
	 */
	public function get_attachment_ids( int $post_id ): array;
}
