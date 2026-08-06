<?php

namespace Limak\Headless\REST\Controllers;

use Limak\Headless\PostTypes\Collection;
use Limak\Headless\PostTypes\Product;
use Limak\Headless\PostTypes\Project;
use Limak\Headless\REST\Transformers\Collection_Transformer;
use Limak\Headless\REST\Transformers\Post_Transformer;
use Limak\Headless\REST\Transformers\Product_Transformer;
use Limak\Headless\REST\Transformers\Project_Transformer;
use Limak\Headless\Support\Registrable;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single aggregate payload for the homepage, so the frontend doesn't need
 * four separate round trips on first paint.
 */
final class Homepage_Controller extends WP_REST_Controller implements Registrable {

	public function __construct() {
		$this->namespace = 'limak/v1';
		$this->rest_base = 'homepage';
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
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	public function get_item( $request ) {
		return new WP_REST_Response(
			[
				'featuredProducts' => $this->featured( Product::SLUG, Product_Transformer::class, 8 ),
				'featuredProjects' => $this->featured( Project::SLUG, Project_Transformer::class, 4 ),
				'collections'      => $this->latest( Collection::SLUG, Collection_Transformer::class, 8 ),
				'latestPosts'      => $this->latest( 'post', Post_Transformer::class, 3 ),
			]
		);
	}

	private function featured( string $post_type, string $transformer, int $limit ): array {
		$query = new WP_Query(
			[
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'meta_query'     => [
					[
						'key'   => 'featured',
						'value' => '1',
					],
				],
			]
		);

		return array_values( array_map( [ $transformer, 'to_summary' ], $query->posts ) );
	}

	private function latest( string $post_type, string $transformer, int $limit ): array {
		$query = new WP_Query(
			[
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		return array_values( array_map( [ $transformer, 'to_summary' ], $query->posts ) );
	}
}
