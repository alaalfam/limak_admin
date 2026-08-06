<?php

namespace Limak\Headless\Support\Media;

use Limak\Headless\Support\Registrable;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin meta box that lets editors pick and order Media Library images,
 * backed by Postmeta_Gallery_Source. This class owns the admin UI only;
 * reads go through Gallery_Source so other code never touches the meta box.
 */
final class Gallery_Field implements Registrable {

	private const NONCE_ACTION = 'limak_save_gallery';
	private const NONCE_NAME   = 'limak_gallery_nonce';
	private const INPUT_NAME   = 'limak_gallery_ids';

	/** @var string[] */
	private array $post_types;

	private Postmeta_Gallery_Source $source;

	public function __construct( array $post_types, Postmeta_Gallery_Source $source ) {
		$this->post_types = $post_types;
		$this->source      = $source;
	}

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function add_meta_box(): void {
		foreach ( $this->post_types as $post_type ) {
			add_meta_box(
				'limak_gallery',
				__( 'Gallery', 'limak-headless' ),
				[ $this, 'render' ],
				$post_type,
				'side',
				'default'
			);
		}
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->post_type, $this->post_types, true ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'limak-gallery-meta-box',
			LIMAK_HEADLESS_URL . 'assets/admin/gallery-meta-box.css',
			[],
			LIMAK_HEADLESS_VERSION
		);

		wp_enqueue_script(
			'limak-gallery-meta-box',
			LIMAK_HEADLESS_URL . 'assets/admin/gallery-meta-box.js',
			[ 'jquery', 'jquery-ui-sortable' ],
			LIMAK_HEADLESS_VERSION,
			true
		);

		wp_localize_script(
			'limak-gallery-meta-box',
			'limakGalleryField',
			[
				'selectTitle'   => __( 'Select Gallery Images', 'limak-headless' ),
				'addButtonText' => __( 'Add to Gallery', 'limak-headless' ),
				'removeLabel'   => __( 'Remove image', 'limak-headless' ),
			]
		);
	}

	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$ids    = $this->source->get_attachment_ids( $post->ID );
		$images = Image_Resolver::resolve_many( $ids );
		?>
		<div class="limak-gallery-field">
			<ul class="limak-gallery-field__list">
				<?php foreach ( $images as $image ) : ?>
					<li class="limak-gallery-field__item" data-id="<?php echo esc_attr( (string) $image['attachment_id'] ); ?>">
						<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>" />
						<button type="button" class="limak-gallery-field__remove button-link" aria-label="<?php esc_attr_e( 'Remove image', 'limak-headless' ); ?>">&times;</button>
					</li>
				<?php endforeach; ?>
			</ul>
			<input
				type="hidden"
				name="<?php echo esc_attr( self::INPUT_NAME ); ?>"
				class="limak-gallery-field__input"
				value="<?php echo esc_attr( wp_json_encode( wp_list_pluck( $images, 'attachment_id' ) ) ); ?>"
			/>
			<button type="button" class="button limak-gallery-field__add"><?php esc_html_e( 'Add Images', 'limak-headless' ); ?></button>
		</div>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::INPUT_NAME ] ) ) {
			return;
		}

		$raw = json_decode( wp_unslash( $_POST[ self::INPUT_NAME ] ), true );
		$ids = is_array( $raw ) ? array_map( 'absint', $raw ) : [];

		$this->source->save_attachment_ids( $post_id, $ids );
	}
}
