<?php

namespace Limak\Headless\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for registering a custom taxonomy. Concrete taxonomies provide
 * a slug, the post types it applies to, labels, and any args overriding the
 * headless-friendly defaults below.
 */
abstract class Taxonomy implements Registrable {

	abstract public function get_slug(): string;

	/**
	 * @return string[] Post type slugs this taxonomy applies to.
	 */
	abstract protected function get_post_types(): array;

	abstract protected function get_singular_label(): string;

	abstract protected function get_plural_label(): string;

	/**
	 * Taxonomy-specific args, merged over default_args(). Anything returned
	 * here (e.g. 'hierarchical', 'rest_base') overrides the default.
	 */
	abstract protected function get_args(): array;

	public function register(): void {
		add_action( 'init', [ $this, 'register_taxonomy' ] );
	}

	public function register_taxonomy(): void {
		register_taxonomy(
			$this->get_slug(),
			$this->get_post_types(),
			array_merge( $this->default_args(), $this->get_args() )
		);
	}

	protected function default_args(): array {
		return [
			'labels'            => $this->build_labels(),
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => false,
		];
	}

	protected function build_labels(): array {
		$singular = $this->get_singular_label();
		$plural   = $this->get_plural_label();

		return [
			'name'          => $plural,
			'singular_name' => $singular,
			/* translators: %s: taxonomy plural label */
			'search_items'  => sprintf( __( 'Search %s', 'limak-headless' ), $plural ),
			/* translators: %s: taxonomy plural label */
			'all_items'     => sprintf( __( 'All %s', 'limak-headless' ), $plural ),
			/* translators: %s: taxonomy singular label */
			'edit_item'     => sprintf( __( 'Edit %s', 'limak-headless' ), $singular ),
			/* translators: %s: taxonomy singular label */
			'update_item'   => sprintf( __( 'Update %s', 'limak-headless' ), $singular ),
			/* translators: %s: taxonomy singular label */
			'add_new_item'  => sprintf( __( 'Add New %s', 'limak-headless' ), $singular ),
			/* translators: %s: taxonomy singular label */
			'new_item_name' => sprintf( __( 'New %s Name', 'limak-headless' ), $singular ),
			/* translators: %s: taxonomy plural label (lowercase) */
			'not_found'     => sprintf( __( 'No %s found.', 'limak-headless' ), strtolower( $plural ) ),
			'menu_name'     => $plural,
		];
	}
}
