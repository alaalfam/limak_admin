<?php

namespace Limak\Headless\REST\Controllers;

use Limak\Headless\PostTypes\Collection;
use Limak\Headless\REST\Transformers\Collection_Transformer;
use Limak\Headless\Support\Registrable;
use WP_Error;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Collections_Controller extends WP_REST_Controller implements Registrable {

	public function __construct() {
		$this->namespace = 'limak/v1';
		$this->rest_base = 'collections';
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
					'args'                => $this->get_collection_params(),
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<slug>[a-z0-9-]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'slug' => [
							'type'     => 'string',
							'required' => true,
						],
					],
				],
			]
		);
	}

	public function get_items( $request ) {
		$query = new WP_Query(
			[
				'post_type'      => Collection::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => $request->get_param( 'per_page' ),
				'paged'          => $request->get_param( 'page' ),
				'orderby'        => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
			]
		);

		$items = array_map( [ Collection_Transformer::class, 'to_summary' ], $query->posts );

		$response = new WP_REST_Response( $items );
		$response->header( 'X-WP-Total', (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

		return $response;
	}

	public function get_item( $request ) {
		$query = new WP_Query(
			[
				'post_type'      => Collection::SLUG,
				'name'           => $request->get_param( 'slug' ),
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			]
		);

		$post = $query->posts[0] ?? null;

		if ( ! $post ) {
			return new WP_Error(
				'limak_collection_not_found',
				__( 'Collection not found.', 'limak-headless' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( Collection_Transformer::to_detail( $post ) );
	}

	public function get_collection_params(): array {
		return [
			'page'     => [
				'type'    => 'integer',
				'default' => 1,
				'minimum' => 1,
			],
			'per_page' => [
				'type'    => 'integer',
				'default' => 12,
				'minimum' => 1,
				'maximum' => 100,
			],
		];
	}
}
