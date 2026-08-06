<?php

namespace Limak\Headless\REST\Controllers;

use Limak\Headless\PostTypes\Product;
use Limak\Headless\PostTypes\Project;
use Limak\Headless\REST\Transformers\Post_Transformer;
use Limak\Headless\REST\Transformers\Product_Transformer;
use Limak\Headless\REST\Transformers\Project_Transformer;
use Limak\Headless\Support\Registrable;
use WP_Post;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches across Products, Projects and Blog posts in a single query,
 * relying on WordPress's own relevance ordering for mixed post types.
 * Each result is tagged with a "type" so the frontend can route/render it.
 */
final class Search_Controller extends WP_REST_Controller implements Registrable {

	private const TYPE_MAP = [
		'product' => Product::SLUG,
		'project' => Project::SLUG,
		'post'    => 'post',
	];

	public function __construct() {
		$this->namespace = 'limak/v1';
		$this->rest_base = 'search';
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
					'args'                => [
						'q'        => [
							'type'     => 'string',
							'required' => true,
						],
						'type'     => [
							'type' => 'string',
							'enum' => array_keys( self::TYPE_MAP ),
						],
						'per_page' => [
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 50,
						],
					],
				],
			]
		);
	}

	public function get_items( $request ) {
		$type       = $request->get_param( 'type' );
		$post_types = $type ? [ self::TYPE_MAP[ $type ] ] : array_values( self::TYPE_MAP );

		$query = new WP_Query(
			[
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				's'              => $request->get_param( 'q' ),
				'posts_per_page' => $request->get_param( 'per_page' ),
			]
		);

		$items = array_map( [ self::class, 'transform' ], $query->posts );

		$response = new WP_REST_Response( array_values( $items ) );
		$response->header( 'X-WP-Total', (int) $query->found_posts );

		return $response;
	}

	private static function transform( WP_Post $post ): array {
		switch ( $post->post_type ) {
			case Product::SLUG:
				$data = Product_Transformer::to_summary( $post );
				$type = 'product';
				break;

			case Project::SLUG:
				$data = Project_Transformer::to_summary( $post );
				$type = 'project';
				break;

			default:
				$data = Post_Transformer::to_summary( $post );
				$type = 'post';
				break;
		}

		return array_merge( [ 'type' => $type ], $data );
	}
}
