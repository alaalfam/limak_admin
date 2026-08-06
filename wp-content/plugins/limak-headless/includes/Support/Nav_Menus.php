<?php

namespace Limak\Headless\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the theme-independent nav menu locations editors assign menus
 * to under Appearance > Menus. Menu *locations* work regardless of the
 * active theme; only menu *rendering* (wp_nav_menu()) is theme-bound, and
 * we don't use that here — Menu_Controller reads the raw menu items instead.
 */
final class Nav_Menus implements Registrable {

	public const PRIMARY_LOCATION = 'primary';
	public const FOOTER_LOCATION  = 'footer';

	public function register(): void {
		add_action( 'after_setup_theme', [ $this, 'register_locations' ] );
	}

	public function register_locations(): void {
		register_nav_menus(
			[
				self::PRIMARY_LOCATION => __( 'Primary Navigation', 'limak-headless' ),
				self::FOOTER_LOCATION  => __( 'Footer Navigation', 'limak-headless' ),
			]
		);
	}
}
