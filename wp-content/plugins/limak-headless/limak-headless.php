<?php
/**
 * Plugin Name:       LIMAK Headless
 * Description:       Headless CMS backend for the LIMAK furniture brand. Registers custom post types, taxonomies, ACF field groups and REST API endpoints consumed by the React frontend.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            LIMAK
 * Text Domain:       limak-headless
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LIMAK_HEADLESS_VERSION', '0.1.0' );
define( 'LIMAK_HEADLESS_DIR', plugin_dir_path( __FILE__ ) );
define( 'LIMAK_HEADLESS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	function ( string $class ): void {
		$prefix = 'Limak\\Headless\\';

		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$path     = LIMAK_HEADLESS_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

/**
 * Boots the plugin. Individual subsystems (post types, taxonomies, ACF field
 * groups, REST API) register themselves here as they are added in later steps.
 */
final class Limak_Headless_Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'boot' ] );
	}

	public function boot(): void {
		load_plugin_textdomain( 'limak-headless', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		foreach ( $this->get_registrables() as $registrable ) {
			$registrable->register();
		}
	}

	/**
	 * All self-registering subsystems (post types, taxonomies, ACF field
	 * groups, REST endpoints). Later steps append to this list.
	 *
	 * @return \Limak\Headless\Support\Registrable[]
	 */
	private function get_registrables(): array {
		$gallery_source = new \Limak\Headless\Support\Media\Postmeta_Gallery_Source();

		return [
			new \Limak\Headless\Security\Rest_Guard(),
			new \Limak\Headless\Security\Disable_Xmlrpc(),
			new \Limak\Headless\PostTypes\Product(),
			new \Limak\Headless\PostTypes\Project(),
			new \Limak\Headless\PostTypes\Collection(),
			new \Limak\Headless\Taxonomies\Product_Category(),
			new \Limak\Headless\Taxonomies\Material(),
			new \Limak\Headless\Taxonomies\Color(),
			new \Limak\Headless\Support\Media\Gallery_Field(
				[ \Limak\Headless\PostTypes\Product::SLUG, \Limak\Headless\PostTypes\Project::SLUG ],
				$gallery_source
			),
			new \Limak\Headless\Support\Media\Image_Variants(),
			new \Limak\Headless\Fields\Product_Fields(),
			new \Limak\Headless\Fields\Project_Fields(),
			new \Limak\Headless\Fields\Post_Fields(),
			new \Limak\Headless\Fields\Taxonomy_Term_Fields(),
			new \Limak\Headless\Fields\Attachment_Fields(),
			new \Limak\Headless\Support\Nav_Menus(),
			new \Limak\Headless\REST\Controllers\Products_Controller(),
			new \Limak\Headless\REST\Controllers\Product_Categories_Controller(),
			new \Limak\Headless\REST\Controllers\Projects_Controller(),
			new \Limak\Headless\REST\Controllers\Collections_Controller(),
			new \Limak\Headless\REST\Controllers\Blog_Controller(),
			new \Limak\Headless\REST\Controllers\Search_Controller(),
			new \Limak\Headless\REST\Controllers\Menu_Controller(),
			new \Limak\Headless\REST\Controllers\Homepage_Controller(),
		];
	}
}

Limak_Headless_Plugin::instance();
