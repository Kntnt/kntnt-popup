<?php

namespace Kntnt\Popup;

/**
 * Class Assets.
 * Handles registration and conditional enqueueing of CSS and JavaScript assets.
 */
final class Assets {


	private bool $assets_needed = false;

	private array $popup_configs = [];

	/**
	 * Registers CSS and JS files.
	 */
	public function register_assets(): void {

		wp_register_script( Plugin::slug() . '-micromodal', Plugin::plugin_url() . 'js/micromodal.min.js', [], Plugin::version(), true );
		wp_register_script( Plugin::slug() . '-script', Plugin::plugin_url() . 'js/kntnt-popup.js', [ Plugin::slug() . '-micromodal' ], Plugin::version(), true );
		wp_script_add_data( Plugin::slug() . '-script', 'defer', true );

		wp_register_style( Plugin::slug() . '-style', Plugin::plugin_url() . 'css/kntnt-popup.css', [], Plugin::version() );

	}

	/**
	 * Checks if any post on the current page contains the popup shortcode.
	 * Called during wp_enqueue_scripts hook (priority 15).
	 */
	public function check_for_shortcode(): void {

		// Skip if assets are already flagged as needed (e.g., by shortcode execution if it happens earlier)
		if ( $this->assets_needed ) {
			return;
		}

		// Check in global post first (single post/page view)
		global $post;
		if ( is_singular() && is_a( $post, 'WP_Post' ) && isset( $post->post_content ) ) {

			// Check both traditional content and blocks
			if ( has_shortcode( $post->post_content, 'popup' )
			     || $this->has_popup_shortcode_in_blocks( $post->post_content )
			) {
				$this->mark_assets_needed();
				return;
			}
		}

		// For archive pages, home page, or when global post isn't the primary query object.
		// We check the posts found by the main query.
		// Note: This runs *during* wp_enqueue_scripts. The main query has likely run,
		// but content filters haven't necessarily. We might need to access query vars.
		global $wp_query;
		if ( isset( $wp_query ) && $wp_query->posts ) {
			foreach ( $wp_query->posts as $queried_post ) {
				if ( is_a( $queried_post, 'WP_Post' ) && isset( $queried_post->post_content ) ) {
					if ( has_shortcode( $queried_post->post_content, 'popup' )
					     || $this->has_popup_shortcode_in_blocks( $queried_post->post_content )
					) {
						$this->mark_assets_needed();
						// error_log('KNTNT DEBUG: Shortcode found in $wp_query->posts'); // Optional debug
						// No need to break, just set the flag once.
						return;
					}
				}
			}
		}

	}

	/**
	 * Helper method to check if a post with blocks contains popup shortcode.
	 *
	 * @param string $content The post content to check.
	 *
	 * @return bool True if popup shortcode is found in blocks.
	 */
	private function has_popup_shortcode_in_blocks( string $content ): bool {

		if ( ! function_exists( 'parse_blocks' ) || ! has_blocks( $content ) ) {
			return false;
		}

		$blocks = parse_blocks( $content );

		// Using a recursive approach is safer
		$found = false;
		$check_blocks = function ( array $blocks ) use ( &$check_blocks, &$found ) {
			foreach ( $blocks as $block ) {

				// Stop searching if already found
				if ( $found ) {
					return;
				}

				// Check Shortcode blocks specifically looking for 'popup'
				if ( $block['blockName'] === 'core/shortcode' && isset( $block['attrs']['text'] ) && has_shortcode( $block['attrs']['text'], 'popup' ) ) {
					$found = true;
					return;
				}

				// Check for shortcodes in inner HTML of any block (less specific, might catch nested ones)
				if ( ! empty( $block['innerHTML'] ) && has_shortcode( $block['innerHTML'], 'popup' ) ) {
					$found = true;
					return;
				}

				// Recursively check inner blocks
				if ( ! empty( $block['innerBlocks'] ) ) {
					$check_blocks( $block['innerBlocks'] );
				}

			}

		};

		$check_blocks( $blocks );
		return $found;

	}


	/**
	 * Marks that assets should be enqueued for the current page.
	 */
	public function mark_assets_needed(): void {
		$this->assets_needed = true;
	}

	/**
	 * Adds configuration for a specific popup instance to be localized.
	 */
	public function add_popup_config( array $config ): void {
		$this->popup_configs[] = $config;
	}

	/**
	 * Enqueues assets if assets_needed flag is true.
	 * Hooked to wp_enqueue_scripts (priority 20).
	 */
	public function enqueue_assets_conditionally(): void {

		if ( $this->assets_needed ) {

			// Localize script data *before* enqueuing the script
			wp_localize_script( Plugin::slug() . '-script', 'kntntPopupData', [ 'popups' => $this->popup_configs ] );

			// Enqueue script (micromodal will be loaded automatically as a dependency)
			wp_enqueue_script( Plugin::slug() . '-script' );

			// Enqueue style
			wp_enqueue_style( Plugin::slug() . '-style' );

		}
	}
}