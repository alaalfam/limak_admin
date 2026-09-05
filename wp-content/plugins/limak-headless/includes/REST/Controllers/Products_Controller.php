<?php

namespace Limak\Headless\REST\Controllers;

use Limak\Headless\PostTypes\Collection;
use Limak\Headless\PostTypes\Product;
use Limak\Headless\REST\Transformers\Product_Transformer;
use Limak\Headless\Support\Registrable;
use WP_Error;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public, read-only Products endpoints under limak/v1 — a clean, shaped
 * alternative to /wp/v2/products. Only GET routes are registered, so there
 * is no write surface here at all; content changes happen in wp-admin.
 */
final class Products_Controller extends WP_REST_Controller implements Registrable {

	public function __construct() {
		$this->namespace = 'limak/v1';
		$this->rest_base = 'products';
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

		// `[a-z0-9-]+` used to be the only pattern here, which silently
		// excluded any slug WordPress had percent-encoded (its default
		// behaviour for a non-Latin title with no manually-typed
		// permalink — e.g. a Persian-only product title) — such a
		// request never even reached get_item(), it 404'd at the
		// routing layer with a generic rest_no_route. `[^/]+` accepts a
		// single path segment of any kind — numeric ID or slug — and
		// get_item()/get_related() branch on which one it is. The
		// frontend now links to products by their numeric ID (stable
		// and language-independent); slug lookup remains for anything
		// still using it.
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id_or_slug>[^/]+)/related',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_related' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'id_or_slug' => [
							'type'     => 'string',
							'required' => true,
						],
						'limit'      => [
							'type'    => 'integer',
							'default' => 4,
							'minimum' => 1,
							'maximum' => 20,
						],
					],
				],
			]
		);
	}

	/**
	 * A purely-numeric path segment is treated as the product's post ID
	 * (stable, unique, assigned automatically at creation regardless of
	 * title language); anything else is looked up as the post_name slug,
	 * same as before.
	 */
	private function find_product( string $id_or_slug ): ?\WP_Post {
		if ( ctype_digit( $id_or_slug ) ) {
			$post = get_post( (int) $id_or_slug );

			return ( $post instanceof \WP_Post && Product::SLUG === $post->post_type && 'publish' === $post->post_status )
				? $post
				: null;
		}

		$query = new WP_Query(
			[
				'post_type'      => Product::SLUG,
				'name'           => $id_or_slug,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			]
		);

		return $query->posts[0] ?? null;
	}

	public function get_items( $request ) {
		$query_args = [
			'post_type'      => Product::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ),
			'paged'          => $request->get_param( 'page' ),
			'orderby'        => [
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			],
		];

		$tax_query = $this->build_tax_query( $request );
		if ( $tax_query ) {
			$query_args['tax_query'] = $tax_query;
		}

		$meta_query = $this->build_meta_query( $request );
		if ( $meta_query ) {
			$query_args['meta_query'] = $meta_query;
		}

		$query = new WP_Query( $query_args );

		$items = array_map( [ Product_Transformer::class, 'to_summary' ], $query->posts );

		$response = new WP_REST_Response( $items );
		$response->header( 'X-WP-Total', (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

		return $response;
	}

	public function get_item( $request ) {
		$post = $this->find_product( $request->get_param( 'id_or_slug' ) );

		if ( ! $post ) {
			return new WP_Error(
				'limak_product_not_found',
				__( 'Product not found.', 'limak-headless' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( Product_Transformer::to_detail( $post ) );
	}

	public function get_related( $request ) {
		$post = $this->find_product( $request->get_param( 'id_or_slug' ) );

		if ( ! $post ) {
			return new WP_Error(
				'limak_product_not_found',
				__( 'Product not found.', 'limak-headless' ),
				[ 'status' => 404 ]
			);
		}

		$limit         = (int) $request->get_param( 'limit' );
		$category_ids  = wp_get_post_terms( $post->ID, 'product_category', [ 'fields' => 'ids' ] );
		$related_posts = [];

		if ( ! empty( $category_ids ) ) {
			$related_posts = get_posts(
				[
					'post_type'      => Product::SLUG,
					'post_status'    => 'publish',
					'posts_per_page' => $limit,
					'post__not_in'   => [ $post->ID ],
					'orderby'        => 'rand',
					'tax_query'      => [
						[
							'taxonomy' => 'product_category',
							'field'    => 'term_id',
							'terms'    => $category_ids,
						],
					],
				]
			);
		}

		if ( empty( $related_posts ) ) {
			$collection_id = get_field( 'collection', $post->ID );

			if ( $collection_id ) {
				$related_posts = get_posts(
					[
						'post_type'      => Product::SLUG,
						'post_status'    => 'publish',
						'posts_per_page' => $limit,
						'post__not_in'   => [ $post->ID ],
						'orderby'        => 'rand',
						'meta_query'     => [
							[
								'key'   => 'collection',
								'value' => $collection_id,
								'type'  => 'NUMERIC',
							],
						],
					]
				);
			}
		}

		$items = array_map( [ Product_Transformer::class, 'to_summary' ], $related_posts );

		return new WP_REST_Response( array_values( $items ) );
	}

	private function build_tax_query( WP_REST_Request $request ): array {
		$map = [
			'category' => 'product_category',
			'material' => 'material',
			'color'    => 'color',
		];

		$tax_query = [];

		foreach ( $map as $param => $taxonomy ) {
			$value = $request->get_param( $param );

			if ( ! $value ) {
				continue;
			}

			$tax_query[] = [
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', explode( ',', $value ) ),
			];
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		return $tax_query;
	}

	private function build_meta_query( WP_REST_Request $request ): array {
		$meta_query = [];

		if ( $request->get_param( 'featured' ) ) {
			$meta_query[] = [
				'key'   => 'featured',
				'value' => '1',
			];
		}

		$collection_slug = $request->get_param( 'collection' );

		if ( $collection_slug ) {
			$collection = get_page_by_path( sanitize_title( $collection_slug ), OBJECT, Collection::SLUG );

			$meta_query[] = [
				'key'   => 'collection',
				'value' => $collection ? $collection->ID : 0,
				'type'  => 'NUMERIC',
			];
		}

		return $meta_query;
	}

	public function get_collection_params(): array {
		return [
			'page'       => [
				'description' => __( 'Current page of the collection.', 'limak-headless' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'per_page'   => [
				'description' => __( 'Maximum number of items to return.', 'limak-headless' ),
				'type'        => 'integer',
				'default'     => 12,
				'minimum'     => 1,
				'maximum'     => 100,
			],
			'category'   => [
				'description' => __( 'Filter by one or more product category slugs (comma-separated).', 'limak-headless' ),
				'type'        => 'string',
			],
			'material'   => [
				'description' => __( 'Filter by one or more material slugs (comma-separated).', 'limak-headless' ),
				'type'        => 'string',
			],
			'color'      => [
				'description' => __( 'Filter by one or more color slugs (comma-separated).', 'limak-headless' ),
				'type'        => 'string',
			],
			'collection' => [
				'description' => __( 'Filter by collection slug.', 'limak-headless' ),
				'type'        => 'string',
			],
			'featured'   => [
				'description' => __( 'Only return featured products.', 'limak-headless' ),
				'type'        => 'boolean',
			],
		];
	}
}
