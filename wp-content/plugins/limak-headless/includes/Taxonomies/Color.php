<?php

namespace Limak\Headless\Taxonomies;

use Limak\Headless\PostTypes\Product;
use Limak\Headless\Support\Taxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Color extends Taxonomy {

	public const SLUG = 'color';

	public function get_slug(): string {
		return self::SLUG;
	}

	protected function get_post_types(): array {
		return [ Product::SLUG ];
	}

	protected function get_singular_label(): string {
		return __( 'Color', 'limak-headless' );
	}

	protected function get_plural_label(): string {
		return __( 'Colors', 'limak-headless' );
	}

	protected function get_args(): array {
		return [
			'hierarchical' => false,
			'rewrite'      => [ 'slug' => 'color' ],
			'rest_base'    => 'colors',
		];
	}
}
