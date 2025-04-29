<?php

namespace Kntnt\Popup;

final class Popup {

	/**
	 * Nameof the shortcode.
	 *
	 * @var string
	 */
	private string $shortcode = 'popup';

	/**
	 * List of all shortcode attributes and their default values.
	 * Attributes not present here will be treated as flags if used without a value.
	 *
	 * @var array
	 */
	private array $omitted_defaults = [
		'id' => false,
		'shown-on-exit-intent' => false,
		'show-after-time' => false,
		'show-after-scroll' => false,
		'close-button' => false,
		'close-outside-click' => false,
		'reappear-delay' => 0,
		'modal' => false,
		'overlay-color' => 'rgba(0,0,0,80%)',
		'width' => 'clamp(300px, 90vw, 800px)',
		'max-height' => '95vh',
		'padding' => 'clamp(20px, calc(5.2vw - 20px), 160px)',
		'position' => 'center',
		'open-animation' => false,
		'close-animation' => false,
		'open-animation-duration' => false,
		'close-animation-duration' => false,
		'class' => '',
		'style-overlay' => '',
		'style-dialog' => '',
		'style-close-button' => '',
		'style-content' => '',
		'aria-label-popup' => 'Popup',       // Is localized in shortcode() if used
		'aria-label-close' => 'Close popup', // Is localized in shortcode() if used
	];

	/**
	 * List of shortcode attributes that can be used as flags (i.e. without
	 * an explicit value) and the values they represent.
	 *
	 * @var array
	 */
	private array $value_defaults = [
		'shown-on-exit-intent' => true,
		'show-after-time' => 30,
		'show-after-scroll' => 80,
		'close-button' => '✖',
		'close-outside-click' => true,
		'reappear-delay' => '1d',
		'modal' => true,
		'open-animation' => 'tada',
		'close-animation' => 'fade-out',
	];

	private int $popup_counter = 0;

	public function register_shortcode() {
		add_shortcode( $this->shortcode, [ $this, 'shortcode' ] );
	}

	public function shortcode( $atts, ?string $content = null ): string {

		// Filter that allows developers to decide whether to display
		// the popup. Available mainly for those cases where the shortcode is
		// to be used on almost all pages and is therefore in a global
		// template but should not be displayed on certain pages.
		$armed = apply_filters( 'kntnt-popup-armed', true );
		if ( ! $armed ) {
			return '';
		}

		// Add default values of flags and missing attributes.
		$atts = $this->shortcode_atts( $atts );

		// Sanitize shortcode attributes
		$atts = $this->sanitize_and_validate_attributes( $atts, $this->omitted_defaults );

		// Translate ARIA labels
		if ( $atts['aria-label-popup'] === $this->omitted_defaults['aria-label-popup'] ) {
			$atts['aria-label-popup'] = __( 'Popup', 'kntnt-popup' );
		}
		if ( $atts['aria-label-close'] === $this->omitted_defaults['aria-label-close'] ) {
			$atts['aria-label-close'] = __( 'Close popup', 'kntnt-popup' );
		}

		// Evaluate any shortcodes in content.
		$content = do_shortcode( $content );

		// Filters the final content of the popup before it is passed to the template.
		$content = apply_filters( 'kntnt-popup-content', $content, $atts['id'], $atts );

		// Load JavaScript and CSS on the page.
		Plugin::instance()->assets()->mark_assets_needed();

		// Make configuration available for JavaScript
		Plugin::instance()->assets()->add_popup_config( [
			                                                'instanceId' => $atts['id'],
			                                                'showOnExitIntent' => $atts['shown-on-exit-intent'],
			                                                'showAfterTime' => $atts['show-after-time'],
			                                                'showAfterScroll' => $atts['show-after-scroll'],
			                                                'closeButton' => $atts['close-button'] !== false,
			                                                'closeOutsideClick' => $atts['close-outside-click'],
			                                                'reappearDelay' => $atts['reappear-delay'],
			                                                'isModal' => $atts['modal'],
			                                                'openAnimation' => $atts['open-animation'],
			                                                'closeAnimation' => $atts['close-animation'],
			                                                'openAnimationDuration' => $atts['open-animation-duration'] !== false ? (int) $atts['open-animation-duration'] : false,
			                                                'closeAnimationDuration' => $atts['close-animation-duration'] !== false ? (int) $atts['close-animation-duration'] : false,
		                                                ] );

		// Generate popup
		$popup = $this->load_template( $content, $atts );

		// Filters that allows developers to modify the shortcode output.
		$popup = apply_filters( 'kntnt-popup-shortcode', $popup, $atts, $content );

		// Return the shortcode output
		return $popup;

	}

	/**
	 * Parses and merges raw shortcode attributes with predefined defaults.
	 *
	 * This method takes the raw attribute array provided by WordPress. It handles
	 * attributes passed without an explicit value (flags, e.g., `[popup modal]`)
	 * by looking up their corresponding default value in `$this->value_defaults`.
	 * Attributes provided with a value override any defaults.
	 *
	 * The processed attributes are then combined with the base defaults defined
	 * in `$this->omitted_defaults` (user-provided values taking precedence).
	 *
	 * Finally, it applies the standard `shortcode_atts_{$this->shortcode}` filter
	 * before returning the result.
	 *
	 * @access private
	 *
	 * @param array $atts The raw array of attributes parsed from the shortcode string.
	 *                    Example: `['width' => '500px', 0 => 'modal']`.
	 *
	 * @return array The processed and filtered array of attributes, merged with defaults,
	 * ready for use by the shortcode handler.
	 * Example: `['id' => false, ..., 'modal' => true, 'width' => '500px', ...]`.
	 */
	private function shortcode_atts( array $atts ): array {

		$out = array_reduce( array_keys( $atts ), function ( $carry, $key ) use ( $atts ) {
			if ( is_numeric( $key ) ) {
				$new_key = $atts[ $key ];
				// Ensure the flag exists in value_defaults before assigning
				if ( isset( $this->value_defaults[ $new_key ] ) ) {
					$carry[ $new_key ] = $this->value_defaults[ $new_key ];
				}
				else {
					$carry[ $new_key ] = true;
				}
			}
			else {
				$carry[ $key ] = $atts[ $key ];
			}
			return $carry;
		},                   [] );

		// Merge with omitted_defaults ensuring user-provided values take precedence
		$out = $out + $this->omitted_defaults;

		/**
		 * Filters shortcode attributes.
		 *
		 * @param array  $out       The output array of shortcode attributes.
		 * @param array  $pairs     The supported attributes and their defaults (omitted_defaults used here).
		 * @param array  $atts      The user defined shortcode attributes.
		 * @param string $shortcode The shortcode name.
		 */
		$out = apply_filters( "shortcode_atts_{$this->shortcode}", $out, $this->omitted_defaults, $atts, $this->shortcode );

		return $out;

	}

	/**
	 * Sanitizes and validates parsed attributes.
	 *
	 * @param array $atts     Parsed attributes.
	 * @param array $defaults Default attributes for fallback.
	 *
	 * @return array Sanitized and validated attributes.
	 */
	private function sanitize_and_validate_attributes( array $atts, array $defaults ): array {

		$validated = [];

		// Sanitize and validate 'id'
		$validated['id'] = $atts['id'] ? sanitize_html_class( $atts['id'] ) : Plugin::slug() . '-' . ++ $this->popup_counter;

		// Sanitize and validate 'shown-on-exit-intent'
		$validated['shown-on-exit-intent'] = filter_var( $atts['shown-on-exit-intent'], FILTER_VALIDATE_BOOLEAN );

		// Sanitize and validate 'show-after-time'
		if ( $atts['show-after-time'] !== false ) {
			if ( is_int( $atts['show-after-time'] ) && $atts['show-after-time'] >= 0 ) {
				$time = $atts['show-after-time'];
			}
			else {
				$time = filter_var( $atts['show-after-time'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] );
			}
			$validated['show-after-time'] = ( $time !== false && $time >= 0 ) ? $time : false;
		}
		else {
			$validated['show-after-time'] = false;
		}

		// Sanitize and validate 'show-after-scroll'
		if ( $atts['show-after-scroll'] !== false ) {
			if ( is_int( $atts['show-after-scroll'] ) && $atts['show-after-scroll'] >= 0 && $atts['show-after-scroll'] <= 100 ) {
				$scroll = $atts['show-after-scroll'];
			}
			else {
				$scroll = filter_var( $atts['show-after-scroll'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0, 'max_range' => 100 ] ] );
			}
			$validated['show-after-scroll'] = ( $scroll !== false && $scroll >= 0 && $scroll <= 100 ) ? $scroll : false;
		}
		else {
			$validated['show-after-scroll'] = false;
		}

		// Sanitize and validate 'close-button'
		$validated['close-button'] = $atts['close-button'] !== false ? esc_html( (string) $atts['close-button'] ) : false;

		// Sanitize and validate 'close-outside-click'
		$validated['close-outside-click'] = filter_var( $atts['close-outside-click'], FILTER_VALIDATE_BOOLEAN );

		// Sanitize and validate 'reappear-delay'
		$validated['reappear-delay'] = $this->parse_time_string( (string) $atts['reappear-delay'], is_int( $defaults['reappear-delay'] ) ? (string) $defaults['reappear-delay'] : $defaults['reappear-delay'] );

		// Sanitize and validate 'modal'
		$validated['modal'] = filter_var( $atts['modal'], FILTER_VALIDATE_BOOLEAN );

		// Sanitize and validate 'overlay-color'
		$validated['overlay-color'] = $this->is_valid_css_color( $atts['overlay-color'] ) ? $atts['overlay-color'] : $defaults['overlay-color'];

		// Sanitize and validate 'width'
		$validated['width'] = $this->sanitize_css_length( $atts['width'], $defaults['width'] );

		// Sanitize and validate 'max-height'
		$validated['max-height'] = $this->sanitize_css_length( $atts['max-height'], $defaults['max-height'] );

		// Sanitize and validate 'padding'
		$validated['padding'] = $this->sanitize_css_length( $atts['padding'], $defaults['padding'] );

		// Sanitize and validate 'position'
		$allowed_positions = [ 'center', 'top', 'top-right', 'right', 'bottom-right', 'bottom', 'bottom-left', 'left', 'top-left' ];
		$validated['position'] = in_array( $atts['position'], $allowed_positions, true ) ? $atts['position'] : $defaults['position'];

		// Sanitize and validate 'open-animation'
		$allowed_open_animations = [ false, 'tada', 'fade-in', 'fade-in-top', 'fade-in-right', 'fade-in-bottom', 'fade-in-left', 'slide-in-top', 'slide-in-right', 'slide-in-bottom', 'slide-in-left' ];
		$validated['open-animation'] = in_array( $atts['open-animation'], $allowed_open_animations, true ) ? $atts['open-animation'] : $defaults['open-animation'];

		// Sanitize and validate 'close-animation'
		$allowed_close_animations = [ false, 'fade-out', 'fade-out-top', 'fade-out-right', 'fade-out-bottom', 'fade-out-left', 'slide-out-top', 'slide-out-right', 'slide-out-bottom', 'slide-out-left' ];
		$validated['close-animation'] = in_array( $atts['close-animation'], $allowed_close_animations, true ) ? $atts['close-animation'] : $defaults['close-animation'];

		// Sanitize and validate 'open-animation-duration'
		if ( $atts['open-animation-duration'] !== false ) {
			$duration = filter_var( $atts['open-animation-duration'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] );
			$validated['open-animation-duration'] = ( $duration !== false && $duration >= 0 ) ? $duration : false;
		}
		else {
			$validated['open-animation-duration'] = false;
		}

		// Sanitize and validate 'close-animation-duration'
		if ( $atts['close-animation-duration'] !== false ) {
			$duration = filter_var( $atts['close-animation-duration'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] );
			$validated['close-animation-duration'] = ( $duration !== false && $duration >= 0 ) ? $duration : false;
		}
		else {
			$validated['close-animation-duration'] = false;
		}

		// Sanitize and validate 'class'
		$validated['class'] = $atts['class'] ? implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $atts['class'] ) ) ) : '';

		// Sanitize and validate 'style-overlay'
		$validated['style-overlay'] = safecss_filter_attr( $atts['style-overlay'] );

		// Sanitize and validate 'style-dialog'
		$validated['style-dialog'] = safecss_filter_attr( $atts['style-dialog'] );

		// Sanitize and validate 'style-close-button' - Added
		$validated['style-close-button'] = safecss_filter_attr( $atts['style-close-button'] );

		// Sanitize and validate 'style-content'
		$validated['style-content'] = safecss_filter_attr( $atts['style-content'] );

		// Sanitize and validate 'aria-label-popup'
		$validated['aria-label-popup'] = sanitize_text_field( $atts['aria-label-popup'] );

		// Sanitize and validate 'aria-label-close'
		$validated['aria-label-close'] = sanitize_text_field( $atts['aria-label-close'] );

		return $validated;

	}

	/**
	 * Parses a time string (e.g., "60s", "5m", "2h", "1d") into seconds.
	 * Handles potential integer input for the base default.
	 *
	 * @param string $time_string    The time string.
	 * @param string $default_string The default time string if parsing fails.
	 *
	 * @return int Time in seconds.
	 */
	private function parse_time_string( string $time_string, string $default_string ): int {

		// Available and allowed prefixes
		$multipliers = [
			'm' => 60,      // minutes
			'h' => 3600,    // hours
			'd' => 86400,   // days
			's' => 1,       // seconds
			'' => 1,        // no unit (assumed seconds)
		];

		// Local function to parse a time string and return seconds
		$parse = function ( string $str ) use ( $multipliers ): int {

			$str = strtolower( trim( $str ) );
			if ( is_numeric( $str ) ) {
				$value = (int) $str;
				$unit = '';
			}
			else {
				preg_match( '/^(\d+)([a-z]*)$/', $str, $matches );
				$value = isset( $matches[1] ) ? (int) $matches[1] : 0;
				$unit = $matches[2] ?? '';
			}

			// Validate unit and calculate seconds
			if ( array_key_exists( $unit, $multipliers ) ) {
				return $value * $multipliers[ $unit ];
			}

			// Return 0 if unit is invalid
			return 0;

		};

		// Parse the input time string
		$seconds = $parse( $time_string );

		// Fallback to default if parsing resulted in 0 or negative,
		// unless the original input string was exactly '0'.
		if ( $seconds <= 0 && trim( $time_string ) !== '0' ) {
			return $parse( $default_string );
		}

		return $seconds;

	}

	/**
	 * Basic check for valid CSS color.
	 * Allows hex, rgb, rgba, hsl, hsla, and named colors.
	 * This is not exhaustive but covers common cases.
	 *
	 * @param string $color
	 *
	 * @return bool
	 */
	private function is_valid_css_color( string $color ): bool {
		// Regex to match basic CSS color structures:
		// - Keywords (very loose matching: all words with [a-z]+, e.g. 'red',
		//   'transparent', but also 'void')
		// - Hex codes (#rgb, #rgba, #rrggbb, #rrggbbaa)
		// - Known function names (rgb, rgba, hsl, hsla, hwb, lab, lch, oklab, oklch,
		//   ictcp, jzazbz, jzczhz, color, light-dark) followed by parentheses.
		//   NOTE: Does NOT validate that the parameters inside the brackets of
		//   the functions are correct according to the CSS specification, only that
		//   the function name is known and that brackets exist around any content.
		$pattern = '/^(?:[a-z]+|#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})\b|(?:rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch|ictcp|jzazbz|jzczhz|color|light-dark)\((?:[^()]|\([^()]*\))*\))$/i';
		return (bool) preg_match( $pattern, trim( $color ) );
	}

	/**
	 * Basic sanitization for CSS length/value strings.
	 * Allows common units, calc(), clamp(), variables, etc.
	 * Removes potentially harmful characters but is not a full CSS parser.
	 *
	 * @param string $length  The CSS value.
	 * @param string $default The default value if sanitization fails badly.
	 *
	 * @return string Sanitized CSS value.
	 */
	private function sanitize_css_length( string $length, string $default ): string {

		// Allow broader range of characters common in CSS values like calc(), clamp(), etc.
		// Removes tags and dangerous attributes, but allows spaces, commas, operators.
		$sanitized = wp_strip_all_tags( $length ); // Remove HTML/XML tags

		// Basic check for potentially harmful CSS (very rudimentary)
		if ( preg_match( '/(url\(|expression\(|javascript:)/i', $sanitized ) ) {
			return $default; // Revert to default if suspicious patterns found
		}

		// Allow valid CSS units, numbers, functions like calc/clamp/var, percentages, etc.
		// This pattern is permissive but aims to catch grossly invalid inputs.
		// It allows numbers (int/float), common units, calc/min/max/clamp/var functions, percentages.
		$pattern = '/^([+-]?\d*\.?\d+(%|[a-z]{2,4})?|\b(calc|min|max|clamp|var)\(.*\)|auto|inherit|initial|unset|revert|normal)(\s*[,+\-*/]\s*([+-]?\d*\.?\d+(%|[a-z]{2,4})?|\b(calc|min|max|clamp|var)\(.*\)|auto|inherit|initial|unset|revert|normal))*$/i';

		if ( preg_match( $pattern, trim( $sanitized ) ) ) {
			return trim( $sanitized ); // Return trimmed sanitized value if it looks plausible
		}

		// Fallback to default if validation fails
		return $default;

	}


	/**
	 * Loads the popup template file and returns its output as a string.
	 * Includes the template file within an output buffer to capture its HTML.
	 * The included template file (popup-template.php) uses the $content and $atts variables.
	 *
	 * @param string $content The processed inner content of the shortcode.
	 * @param array  $atts    The array of sanitized and validated shortcode attributes.
	 *
	 * @return string The HTML output generated by the template file. Returns an empty string on failure.
	 * @throws \LogicException if templates/popup-template.php isn't readable (Note: This is implied by potential 'include' errors, not explicitly thrown in current code).
	 */
	private function load_template( string $content, array $atts ): string {
		ob_start();
		// Pass $atts and $content to the template scope
		include Plugin::plugin_dir() . 'templates/kntnt-popup-template.php';
		$output = ob_get_clean();
		return $output ?: ''; // Ensure a string is returned
	}
}