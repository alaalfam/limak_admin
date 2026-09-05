<?php

namespace Limak\Headless\REST\Controllers;

use Limak\Headless\REST\Transformers\Post_Transformer;
use Limak\Headless\Support\Registrable;
use WP_Error;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public, read-only Blog endpoints under limak/v1, backed by native
 * WordPress posts — a clean alternative to /wp/v2/posts.
 */
final class Blog_Controller extends WP_REST_Controller implements Registrable {

	public function __construct() {
		$this->namespace = 'limak/v1';
		$this->rest_base = 'blog';
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

		// See Products_Controller for why this isn't `[a-z0-9-]+`: that
		// pattern can never match a slug WordPress percent-encoded (its
		// default behaviour for a non-Latin title with no manually-typed
		// permalink), so a purely-numeric segment is tried as the post ID
		// (stable regardless of title language) before falling back to
		// the slug lookup.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id_or_slug>[^/]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'id_or_slug' => [
							'type'     => 'string',
							'required' => true,
						],
					],
				],
			]
		);
	}

	private function find_post( string $id_or_slug ): ?\WP_Post {
		if ( ctype_digit( $id_or_slug ) ) {
			$post = get_post( (int) $id_or_slug );

			return ( $post instanceof \WP_Post && 'post' === $post->post_type && 'publish' === $post->post_status )
				? $post
				: null;
		}

		$query = new WP_Query(
			[
				'post_type'      => 'post',
				'name'           => $id_or_slug,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			]
		);

		return $query->posts[0] ?? null;
	}

	public function get_items( $request ) {
		$args = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ),
			'paged'          => $request->get_param( 'page' ),
		];

		$category = $request->get_param( 'category' );

		if ( $category ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', explode( ',', $category ) ),
				],
			];
		}

		$query = new WP_Query( $args );

		$items = array_map( [ Post_Transformer::class, 'to_summary' ], $query->posts );

		$response = new WP_REST_Response( $items );
		$response->header( 'X-WP-Total', (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

		return $response;
	}

	public function get_item( $request ) {
		$post = $this->find_post( $request->get_param( 'id_or_slug' ) );

		if ( ! $post ) {
			return new WP_Error(
				'limak_post_not_found',
				__( 'Blog post not found.', 'limak-headless' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( Post_Transformer::to_detail( $post ) );
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
				'default' => 10,
				'minimum' => 1,
				'maximum' => 100,
			],
			'category' => [
				'type' => 'string',
			],
		];
	}
}
