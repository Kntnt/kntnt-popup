<?php

declare( strict_types = 1 );

namespace Kntnt\Popup;

/**
 * Handles registration and conditional enqueueing of CSS and JavaScript assets for the plugin.
 */
final class Assets {

	/**
	 * Flag indicating whether assets (CSS/JS) are needed for the current page.
	 *
	 * @var bool
	 */
	private bool $assets_are_needed = false;

	/**
	 * Stores configurations for each popup instance to be passed to JavaScript.
	 * Each configuration is an associative array.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $popup_configurations = [];

	/**
	 * Registers CSS and JavaScript files with WordPress.
	 * These assets are not enqueued immediately but are made available for later conditional enqueueing.
	 *
	 * @return void
	 */
	public function register_assets(): void {

		// Register Micromodal library script (dependency for main script).
		wp_register_script( Plugin::get_slug() . '-micromodal', // Handle
		                    Plugin::get_plugin_url() . 'js/micromodal.min.js', // Source URL
		                    [], // Dependencies
		                    Plugin::get_version(), // Version
		                    true // In footer
		);

		// Register the main plugin JavaScript file.
		wp_register_script( Plugin::get_slug() . '-script', // Handle
		                    Plugin::get_plugin_url() . 'js/kntnt-popup.js', // Source URL
		                    [ Plugin::get_slug() . '-micromodal' ], // Dependencies
		                    Plugin::get_version(), // Version
		                    true // In footer
		);
		// Add 'defer' attribute to the main plugin script for optimized loading.
		wp_script_add_data( Plugin::get_slug() . '-script', 'defer', true );

		// Register the plugin's stylesheet.
		wp_register_style( Plugin::get_slug() . '-style', // Handle
		                   Plugin::get_plugin_url() . 'css/kntnt-popup.css', // Source URL
		                   [], // Dependencies
		                   Plugin::get_version() // Version
		);

	}

	/**
	 * Checks if any post content on the current page contains the popup shortcode.
	 * Sets a flag if the shortcode is found. This method is hooked to 'wp_enqueue_scripts'.
	 *
	 * @return void
	 */
	public function check_for_shortcode_presence(): void {

		// Exit early if assets are already marked as needed.
		if ( $this->assets_are_needed ) {
			return; // No further checks necessary.
		}

		// Check the main global $post object, typically for single views.
		global $post;
		if ( is_singular() && $post instanceof \WP_Post && property_exists( $post, 'post_content' ) ) {

			// Determine if shortcode exists in post_content or within its blocks.
			if ( has_shortcode( $post->post_content, 'popup' ) || $this->has_popup_shortcode_in_blocks( $post->post_content ) ) {
				$this->mark_assets_as_needed();
				return; // Assets are needed, no need to continue.
			}

		}

		// Check posts within the global $wp_query, for archives or other loops.
		global $wp_query;
		if ( isset( $wp_query->posts ) && is_array( $wp_query->posts ) ) {
			foreach ( $wp_query->posts as $queried_post ) {
				if ( $queried_post instanceof \WP_Post && property_exists( $queried_post, 'post_content' ) ) {

					// Determine if shortcode exists in queried post or its blocks.
					if ( has_shortcode( $queried_post->post_content, 'popup' ) || $this->has_popup_shortcode_in_blocks( $queried_post->post_content ) ) {

						// Mark assets as needed if shortcode is found.
						$this->mark_assets_as_needed();

						// Asset requirement determined, exit the check process.
						return;

					}
				}
			}
		}

	}

	/**
	 * Recursively checks for the 'popup' shortcode within block-based content.
	 *
	 * @param string $content The post content, potentially containing blocks.
	 *
	 * @return bool True if the 'popup' shortcode is found within any block, false otherwise.
	 */
	private function has_popup_shortcode_in_blocks( string $content ): bool {

		// Return early if block functions are unavailable or content has no blocks.
		if ( ! function_exists( 'parse_blocks' ) || ! has_blocks( $content ) ) {
			return false;
		}

		// Parse content into an array of blocks.
		$blocks = parse_blocks( $content );

		// Initialize state and define recursive block checking function.
		$found_shortcode = false; // Flag to indicate if the shortcode has been found.
		$check_blocks_recursively = function ( array $blocks_to_check ) use ( &$check_blocks_recursively, &$found_shortcode ): void {

			// Iterate through each block in the current list to check for the shortcode.
			foreach ( $blocks_to_check as $block_item ) {

				// If shortcode is already found, cease further checks within this recursion.
				if ( $found_shortcode ) {
					return;
				}

				// Check if the block is a 'core/shortcode' block containing the target shortcode.
				if ( isset( $block_item['blockName'], $block_item['attrs']['text'] )
				     && $block_item['blockName'] === 'core/shortcode'
				     && has_shortcode( $block_item['attrs']['text'], 'popup' ) ) {
					$found_shortcode = true;
					return;
				}

				// Check if the block's inner HTML (rendered content) contains the target shortcode.
				if ( ! empty( $block_item['innerHTML'] ) && has_shortcode( $block_item['innerHTML'], 'popup' ) ) {
					$found_shortcode = true;
					return;
				}

				// If the block has inner blocks, recursively call this function to check them.
				if ( ! empty( $block_item['innerBlocks'] ) ) {
					$check_blocks_recursively( $block_item['innerBlocks'] );
				}

			}

		};

		// Execute the recursive check starting with the top-level blocks.
		$check_blocks_recursively( $blocks );

		// Return the result of the search.
		return $found_shortcode;

	}


	/**
	 * Marks that assets (CSS/JS) should be enqueued for the current page.
	 *
	 * @return void
	 */
	public function mark_assets_as_needed(): void {
		$this->assets_are_needed = true;
	}

	/**
	 * Adds configuration data for a specific popup instance.
	 * This data will be localized and made available to the frontend JavaScript.
	 *
	 * @param array<string, mixed> $configuration The configuration array for a popup.
	 *
	 * @return void
	 */
	public function add_popup_configuration( array $configuration ): void {
		$this->popup_configurations[] = $configuration;
	}

	/**
	 * Enqueues registered CSS assets if they are marked as needed.
	 * This method is hooked to 'wp_enqueue_scripts'.
	 *
	 * @return void
	 */
	public function enqueue_assets_conditionally(): void {

		// Proceed only if assets have been flagged as necessary for the current page.
		if ( $this->assets_are_needed ) {

			// Enqueue the plugin stylesheet early.
			wp_enqueue_style( Plugin::get_slug() . '-style' );

		}
	}

	/**
	 * Enqueues JavaScript assets and popup data after shortcodes have been processed.
	 * This method is hooked to 'wp_footer' to ensure shortcodes are processed first.
	 *
	 * @return void
	 */
	public function enqueue_popup_data(): void {

		// Proceed only if assets have been flagged as necessary for the current page.
		if ( $this->assets_are_needed ) {

			// Make popup configurations available to the main JavaScript file via localization.
			wp_localize_script( Plugin::get_slug() . '-script', // Script handle to attach data to
			                    'kntntPopupData', // JavaScript object name where data will be available
			                    [ 'popups' => $this->popup_configurations ] // Data to pass
			);

			// Enqueue the main plugin script; Micromodal will be enqueued as its dependency.
			wp_enqueue_script( Plugin::get_slug() . '-script' );

		}
	}

}