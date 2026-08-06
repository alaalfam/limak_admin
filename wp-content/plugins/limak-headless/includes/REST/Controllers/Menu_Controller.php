<?php

namespace Limak\Headless\REST\Controllers;

use Limak\Headless\Support\Nav_Menus;
use Limak\Headless\Support\Registrable;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves a registered nav menu location into a nested tree the frontend
 * can render directly, instead of the flat wp/v2/menu-items shape.
 */
final class Menu_Controller extends WP_REST_Controller implements Registrable {

	public function __construct() {
		$this->namespace = 'limak/v1';
		$this->rest_base = 'menu';
	}

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<location>[a-z0-9_-]+)',
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
		$location = $request->get_param( 'location' );
		$locations = get_nav_menu_locations();

		if ( empty( $locations[ $location ] ) ) {
			return new WP_REST_Response( [] );
		}

		$menu_items = wp_get_nav_menu_items( $locations[ $location ] );

		if ( ! is_array( $menu_items ) ) {
			return new WP_REST_Response( [] );
		}

		return new WP_REST_Response( self::build_tree( $menu_items ) );
	}

	/**
	 * @param \WP_Post[] $menu_items Flat list, as returned by wp_get_nav_menu_items().
	 */
	private static function build_tree( array $menu_items, int $parent_id = 0 ): array {
		$branch = [];

		foreach ( $menu_items as $item ) {
			if ( (int) $item->menu_item_parent !== $parent_id ) {
				continue;
			}

			$branch[] = [
				'id'       => $item->ID,
				'label'    => $item->title,
				'url'      => $item->url,
				'target'   => $item->target ?: null,
				'children' => self::build_tree( $menu_items, $item->ID ),
			];
		}

		return $branch;
	}
}
