<?php

namespace Limak\Headless\PostTypes;

use Limak\Headless\Support\Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Project extends Post_Type {

	public const SLUG = 'project';

	public function get_slug(): string {
		return self::SLUG;
	}

	protected function get_singular_label(): string {
		return __( 'Project', 'limak-headless' );
	}

	protected function get_plural_label(): string {
		return __( 'Projects', 'limak-headless' );
	}

	protected function get_args(): array {
		return [
			'menu_icon'     => 'dashicons-admin-multisite',
			'menu_position' => 7,
			'supports'      => [ 'title', 'editor', 'thumbnail', 'revisions', 'page-attributes' ],
			'rewrite'       => [ 'slug' => 'projects' ],
			'rest_base'     => 'projects',
		];
	}
}
