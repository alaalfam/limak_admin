<?php

namespace Limak\Headless\REST\Controllers;

use Limak\Headless\REST\Transformers\Term_Transformer;
use Limak\Headless\Support\Registrable;
use Limak\Headless\Taxonomies\Product_Category;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public, read-only list of product categories — lets the frontend render
 * category filters/labels from whatever an admin has actually defined in
 * wp-admin (Products → Categories), rather than a fixed, build-time list.
 */
final class Product_Categories_Controller extends WP_REST_Controller implements Registrable {

	public function __construct() {
		$this->namespace = 'limak/v1';
		$this->rest_base = 'product-categories';
	}

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	public function get_items( $request ) {
		$terms = get_terms(
			[
				'taxonomy'   => Product_Category::SLUG,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) ) {
			$terms = [];
		}

		return new WP_REST_Response( Term_Transformer::to_array_many( $terms ) );
	}
}
