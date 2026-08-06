<?php

namespace Limak\Headless\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for registering a custom post type. Concrete post types provide
 * a slug, singular/plural labels and any args that should override the
 * headless-friendly defaults below.
 */
abstract class Post_Type implements Registrable {

	abstract public function get_slug(): string;

	abstract protected function get_singular_label(): string;

	abstract protected function get_plural_label(): string;

	/**
	 * Post-type-specific args, merged over default_args(). Anything returned
	 * here (e.g. 'supports', 'menu_icon', 'rewrite') overrides the default.
	 */
	abstract protected function get_args(): array;

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	public function register_post_type(): void {
		register_post_type( $this->get_slug(), array_merge( $this->default_args(), $this->get_args() ) );
	}

	protected function default_args(): array {
		return [
			'labels'       => $this->build_labels(),
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'supports'     => [ 'title', 'thumbnail', 'revisions' ],
			'has_archive'  => true,
			'hierarchical' => false,
		];
	}

	protected function build_labels(): array {
		$singular = $this->get_singular_label();
		$plural   = $this->get_plural_label();

		return [
			'name'               => $plural,
			'singular_name'      => $singular,
			/* translators: %s: post type singular label */
			'add_new_item'       => sprintf( __( 'Add New %s', 'limak-headless' ), $singular ),
			/* translators: %s: post type singular label */
			'edit_item'          => sprintf( __( 'Edit %s', 'limak-headless' ), $singular ),
			/* translators: %s: post type singular label */
			'new_item'           => sprintf( __( 'New %s', 'limak-headless' ), $singular ),
			/* translators: %s: post type singular label */
			'view_item'          => sprintf( __( 'View %s', 'limak-headless' ), $singular ),
			/* translators: %s: post type plural label */
			'view_items'         => sprintf( __( 'View %s', 'limak-headless' ), $plural ),
			/* translators: %s: post type plural label */
			'search_items'       => sprintf( __( 'Search %s', 'limak-headless' ), $plural ),
			/* translators: %s: post type plural label (lowercase) */
			'not_found'          => sprintf( __( 'No %s found.', 'limak-headless' ), strtolower( $plural ) ),
			/* translators: %s: post type plural label (lowercase) */
			'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'limak-headless' ), strtolower( $plural ) ),
			/* translators: %s: post type plural label */
			'all_items'          => sprintf( __( 'All %s', 'limak-headless' ), $plural ),
			'menu_name'          => $plural,
		];
	}
}
