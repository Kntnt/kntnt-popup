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
	 * Marks that assets should be enqueued for the current page.
	 */
	public function mark_assets_needed(): void {
		$this->assets_needed = true;
	}

	/**
	 * Adds configuration for a specific popup instance to be localized.
	 *
	 * @param array $config Popup configuration data.
	 */
	public function add_popup_config( array $config ): void {
		$this->popup_configs[] = $config;
	}

	/**
	 * Enqueues assets in the footer if mark_assets_needed() was called.
	 * Localizes data for the JavaScript.
	 */
	public function enqueue_assets_conditionally(): void {

		if ( $this->assets_needed ) {

			wp_localize_script( Plugin::slug() . '-script', 'kntntPopupData', [ 'popups' => $this->popup_configs ] );
			wp_enqueue_script( Plugin::slug() . '-script' ); // Micromodal will be loaded as a dependency

			wp_enqueue_style( Plugin::slug() . '-style' );

		}

	}

}