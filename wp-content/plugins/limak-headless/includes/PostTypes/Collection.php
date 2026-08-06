<?php

namespace Limak\Headless\PostTypes;

use Limak\Headless\Support\Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collections are a full entity (name, cover image, description) rather than
 * a plain taxonomy term, so they get their own CPT. Products relate to a
 * Collection via an ACF relationship field, not a shared taxonomy.
 */
final class Collection extends Post_Type {

	public const SLUG = 'collection';

	public function get_slug(): string {
		return self::SLUG;
	}

	protected function get_singular_label(): string {
		return __( 'Collection', 'limak-headless' );
	}

	protected function get_plural_label(): string {
		return __( 'Collections', 'limak-headless' );
	}

	protected function get_args(): array {
		return [
			'menu_icon'     => 'dashicons-category',
			'menu_position' => 8,
			'supports'      => [ 'title', 'editor', 'thumbnail', 'revisions', 'page-attributes' ],
			'rewrite'       => [ 'slug' => 'collections' ],
			'rest_base'     => 'collections',
		];
	}
}
