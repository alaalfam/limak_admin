<?php

namespace Limak\Headless\Fields;

use Limak\Headless\Support\Registrable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF field group for the native `post` type, which powers the frontend's
 * "Journal" (مجله) section. Persian title/excerpt/body are the native
 * post_title/post_excerpt/post_content (edited with the regular block
 * editor); everything English-only lives here, same pattern as
 * Product_Fields. See Support\Content\Block_Parser for how the Persian
 * (Gutenberg blocks) and English (a wysiwyg field) bodies both resolve to
 * the same bilingual block-array shape the frontend expects.
 */
final class Post_Fields implements Registrable {

	public function register(): void {
		add_action( 'acf/init', [ $this, 'register_field_group' ] );
	}

	public function register_field_group(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'group_limak_post',
				'title'    => __( 'Article Details', 'limak-headless' ),
				'fields'   => array_merge(
					$this->bilingual_content_fields(),
					$this->seo_fields(),
					$this->article_fields()
				),
				'location' => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'post',
						],
					],
				],
			]
		);
	}

	private function bilingual_content_fields(): array {
		return [
			[
				'key'   => 'field_limak_post_tab_bilingual',
				'label' => __( 'Bilingual Content', 'limak-headless' ),
				'type'  => 'tab',
			],
			[
				'key'   => 'field_limak_post_title_en',
				'name'  => 'title_en',
				'label' => __( 'Title (English)', 'limak-headless' ),
				'type'  => 'text',
			],
			[
				'key'   => 'field_limak_post_excerpt_en',
				'name'  => 'excerpt_en',
				'label' => __( 'Excerpt (English)', 'limak-headless' ),
				'type'  => 'textarea',
				'rows'  => 2,
			],
			[
				'key'          => 'field_limak_post_body_note',
				'label'        => __( 'Body Content', 'limak-headless' ),
				'type'         => 'message',
				'message'      => __( 'The Persian body is the editor above (the regular block editor) — use Paragraph, Heading, Quote, List and Image blocks; they are read and matched up with the English body below by their order. For a highlighted callout, an image-with-text side-by-side block, or a call-to-action button, type a shortcode as its own paragraph in both bodies: [limak_highlight]text[/limak_highlight], [limak_imagetext id="123" side="start"]text[/limak_imagetext] (id is the image\'s Media Library attachment ID), or [limak_cta href="/contact" label="Button label"]text[/limak_cta].', 'limak-headless' ),
			],
			[
				'key'          => 'field_limak_post_body_en',
				'name'         => 'body_en',
				'label'        => __( 'Body (English)', 'limak-headless' ),
				'instructions' => __( 'Must have the same number and order of paragraphs/headings/images/etc. as the Persian body above — they are paired up by position.', 'limak-headless' ),
				'type'         => 'wysiwyg',
				'tabs'         => 'visual',
				'media_upload' => 1,
			],
		];
	}

	/**
	 * Same reasoning as Product_Fields::seo_fields() — a meta description
	 * is written for a search-result snippet, not on-page reading, so it's
	 * its own required field rather than reusing the excerpt.
	 */
	private function seo_fields(): array {
		return [
			[
				'key'   => 'field_limak_post_tab_seo',
				'label' => __( 'SEO', 'limak-headless' ),
				'type'  => 'tab',
			],
			[
				'key'          => 'field_limak_post_meta_description_fa',
				'name'         => 'meta_description_fa',
				'label'        => __( 'Meta Description (Persian)', 'limak-headless' ),
				'instructions' => __( 'Shown as the search-result snippet on the Persian site. Aim for about 150-160 characters.', 'limak-headless' ),
				'type'         => 'textarea',
				'rows'         => 2,
				'maxlength'    => 160,
				'required'     => 1,
			],
			[
				'key'          => 'field_limak_post_meta_description_en',
				'name'         => 'meta_description_en',
				'label'        => __( 'Meta Description (English)', 'limak-headless' ),
				'instructions' => __( 'Shown as the search-result snippet on the English site. Aim for about 150-160 characters.', 'limak-headless' ),
				'type'         => 'textarea',
				'rows'         => 2,
				'maxlength'    => 160,
				'required'     => 1,
			],
		];
	}

	private function article_fields(): array {
		return [
			[
				'key'   => 'field_limak_post_tab_article',
				'label' => __( 'Article', 'limak-headless' ),
				'type'  => 'tab',
			],
			[
				'key'   => 'field_limak_post_featured',
				'name'  => 'featured',
				'label' => __( 'Featured', 'limak-headless' ),
				'type'  => 'true_false',
				'ui'    => 1,
			],
			[
				'key'           => 'field_limak_post_related_posts',
				'name'          => 'related_posts',
				'label'         => __( 'Related Articles', 'limak-headless' ),
				'instructions'  => __( 'Optional — pick specific articles to show as related. Leave empty to fall back to other articles in the same category.', 'limak-headless' ),
				'type'          => 'post_object',
				'post_type'     => [ 'post' ],
				'multiple'      => 1,
				'return_format' => 'id',
				'ui'            => 1,
			],
		];
	}
}
