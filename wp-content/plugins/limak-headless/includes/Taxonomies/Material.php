<?php

namespace Limak\Headless\Taxonomies;

use Limak\Headless\PostTypes\Product;
use Limak\Headless\Support\Taxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Material extends Taxonomy {

	public const SLUG = 'material';

	public function get_slug(): string {
		return self::SLUG;
	}

	protected function get_post_types(): array {
		return [ Product::SLUG ];
	}

	protected function get_singular_label(): string {
		return __( 'Material', 'limak-headless' );
	}

	protected function get_plural_label(): string {
		return __( 'Materials', 'limak-headless' );
	}

	protected function get_args(): array {
		return [
			'hierarchical' => false,
			'rewrite'      => [ 'slug' => 'material' ],
			'rest_base'    => 'materials',
		];
	}
}
