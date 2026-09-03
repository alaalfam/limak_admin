<?php

namespace Limak\Headless\PostTypes;

use Limak\Headless\Support\Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Product extends Post_Type {

	public const SLUG = 'product';

	public function get_slug(): string {
		return self::SLUG;
	}

	protected function get_singular_label(): string {
		return __( 'Product', 'limak-headless' );
	}

	protected function get_plural_label(): string {
		return __( 'Products', 'limak-headless' );
	}

	protected function get_args(): array {
		return [
			'menu_icon'     => 'dashicons-store',
			'menu_position' => 6,
			// No 'editor': the full description is bilingual (description_fa/
			// description_en ACF repeaters, see Product_Fields), so the
			// native content editor would just be an unused, confusing
			// second place to write it.
			'supports'      => [ 'title', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ],
			'rewrite'       => [ 'slug' => 'products' ],
			'rest_base'     => 'products',
		];
	}
}
