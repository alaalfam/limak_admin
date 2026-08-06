<?php

namespace Limak\Headless\Taxonomies;

use Limak\Headless\PostTypes\Product;
use Limak\Headless\Support\Taxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Product_Category extends Taxonomy {

	public const SLUG = 'product_category';

	public function get_slug(): string {
		return self::SLUG;
	}

	protected function get_post_types(): array {
		return [ Product::SLUG ];
	}

	protected function get_singular_label(): string {
		return __( 'Product Category', 'limak-headless' );
	}

	protected function get_plural_label(): string {
		return __( 'Product Categories', 'limak-headless' );
	}

	protected function get_args(): array {
		return [
			'hierarchical' => true,
			'rewrite'      => [ 'slug' => 'product-category' ],
			'rest_base'    => 'product-categories',
		];
	}
}
