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
			'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ],
			'rewrite'       => [ 'slug' => 'products' ],
			'rest_base'     => 'products',
		];
	}
}
