<?php

namespace Limak\Headless\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Registrable {

	/**
	 * Hooks this subsystem into WordPress.
	 */
	public function register(): void;
}
