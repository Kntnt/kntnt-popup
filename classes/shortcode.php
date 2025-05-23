<?php

declare( strict_types = 1 );

namespace Kntnt\Popup;

/**
 * Implements the [popup] shortcode functionality.
 *
 * Handles parsing attributes, sanitizing input, and generating the HTML for popups.
 * It also enqueues necessary assets and provides data to the frontend JavaScript.
 */
final class Shortcode {

	/**
	 * The name of the shortcode tag.
	 *
	 * @var string
	 */
	private string $shortcode_tag = 'popup';

	/**
	 * Default values for shortcode attributes if they are omitted entirely.
	 *
	 * @var array<string, mixed>
	 */
	private readonly array $attribute_omitted_defaults;

	/**
	 * Default values for shortcode attributes when they are used as flags
	 * (i.e., present without an explicit value).
	 *
	 * @var array<string, mixed>
	 */
	private readonly array $attribute_flag_defaults;

	/**
	 * Counter for generating unique IDs for popups when no ID is provided.
	 *
	 * @var int
	 */
	private int $popup_instance_counter = 0;

	/**
	 * Constructor for the Shortcode class.
	 * Initializes default attribute values.
	 */
	public function __construct() {

		// Define default values for attributes that are completely omitted.
		$this->attribute_omitted_defaults = [
			'id' => false,
			'show-on-exit-intent' => false,
			'show-after-time' => false,
			'show-after-scroll' => false,
			'close-button' => false,
			'close-outside-click' => false,
			'reappear-delay' => 0,
			'modal' => false,
			'overlay-color' => 'rgba(0,0,0,0.8)',
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
			'aria-label-popup' => 'Popup', // Localized in handle_shortcode if still default.
			'aria-label-close' => 'Close popup', // Localized in handle_shortcode if still default.
		];

		// Define default values for attributes when used as valueless flags.
		$this->attribute_flag_defaults = [
			'show-on-exit-intent' => true,
			'show-after-time' => 30, // seconds
			'show-after-scroll' => 80, // percent
			'close-button' => '✖', // HTML entity for multiplication sign
			'close-outside-click' => true,
			'reappear-delay' => '1d', // 1 day
			'modal' => true,
			'open-animation' => 'tada',
			'close-animation' => 'fade-out',
		];

	}

	/**
	 * Registers the popup shortcode with WordPress.
	 *
	 * @return void
	 */
	public function register_shortcode(): void {
		add_shortcode( $this->shortcode_tag, [ $this, 'handle_shortcode' ] );
	}

	/**
	 * Handles the popup shortcode rendering.
	 *
	 * Processes attributes, sanitizes them, prepares data for JavaScript,
	 * and loads the popup template.
	 *
	 * @param mixed       $raw_attributes The raw attributes passed to the shortcode. Expected array or string.
	 * @param string|null $content        The content enclosed within the shortcode.
	 *
	 * @return string The HTML output for the popup, or an empty string if not armed.
	 */
	public function handle_shortcode( mixed $raw_attributes, ?string $content = null ): string {

		/**
		 * Filters whether the Kntnt Popup should be armed and displayed.
		 *
		 * Allows developers to conditionally prevent a popup from rendering,
		 * for example, on specific pages or for certain user roles.
		 *
		 * @param bool $is_armed Whether the popup is armed. Default true.
		 *
		 * @since 1.0.0
		 *
		 */
		$is_armed = apply_filters( 'kntnt-popup-armed', true );
		if ( ! $is_armed ) {
			return ''; // Shortcode processing is bypassed if not armed.
		}

		// Process raw attributes and then sanitize and validate them.
		$processed_attributes = $this->process_shortcode_attributes( is_array( $raw_attributes ) ? $raw_attributes : [] );
		$validated_attributes = $this->sanitize_and_validate_attributes( $processed_attributes );

		// Localize ARIA labels if they are still set to their default English values.
		if ( $validated_attributes['aria-label-popup'] === $this->attribute_omitted_defaults['aria-label-popup'] ) {
			$validated_attributes['aria-label-popup'] = __( 'Popup', 'kntnt-popup' );
		}
		if ( $validated_attributes['aria-label-close'] === $this->attribute_omitted_defaults['aria-label-close'] ) {
			$validated_attributes['aria-label-close'] = __( 'Close popup', 'kntnt-popup' );
		}

		// Process any nested shortcodes within the main popup content.
		$content = $this->cleanup_wp_mess( (string) $content );
		$popup_content = do_shortcode( $content );

		/**
		 * Filters the content of the Kntnt Popup before it is displayed.
		 *
		 * Allows developers to modify the popup's inner content dynamically,
		 * for example, to personalize it for logged-in users.
		 *
		 * @param string $popup_content        The processed content of the popup.
		 * @param string $popup_id             The unique ID of the popup instance.
		 * @param array  $validated_attributes The sanitized and validated shortcode attributes.
		 *
		 * @since 1.0.0
		 *
		 */
		$popup_content = apply_filters( 'kntnt-popup-content', $popup_content, $validated_attributes['id'], $validated_attributes );

		// Mark assets as needed and add this popup's configuration for JavaScript.
		$assets_manager = Plugin::get_instance()->get_assets_manager();
		$assets_manager->mark_assets_as_needed();
		$assets_manager->add_popup_configuration( [
			                                          'instanceId' => $validated_attributes['id'],
			                                          'showOnExitIntent' => $validated_attributes['show-on-exit-intent'],
			                                          'showAfterTime' => $validated_attributes['show-after-time'],
			                                          'showAfterScroll' => $validated_attributes['show-after-scroll'],
			                                          'closeButton' => $validated_attributes['close-button'] !== false,
			                                          'closeOutsideClick' => $validated_attributes['close-outside-click'],
			                                          'reappearDelay' => $validated_attributes['reappear-delay'],
			                                          'isModal' => $validated_attributes['modal'],
			                                          'openAnimation' => $validated_attributes['open-animation'],
			                                          'closeAnimation' => $validated_attributes['close-animation'],
			                                          'openAnimationDuration' => $validated_attributes['open-animation-duration'] !== false ? (int) $validated_attributes['open-animation-duration'] : false,
			                                          'closeAnimationDuration' => $validated_attributes['close-animation-duration'] !== false ? (int) $validated_attributes['close-animation-duration'] : false,
		                                          ] );

		// Prepare CSS classes for the main popup wrapper element.
		$wrapper_classes = [
			'kntnt-popup',
			esc_attr( $validated_attributes['class'] ), // User-defined classes
			$validated_attributes['modal'] ? 'kntnt-popup--modal' : '', // Modal-specific class
		];
		$validated_attributes['wrapper_class_string'] = trim( implode( ' ', array_filter( $wrapper_classes ) ) );

		// Generate the popup HTML by loading the template.
		$popup_html = $this->load_template( $popup_content, $validated_attributes );

		/**
		 * Filters the entire HTML output of the Kntnt Popup shortcode.
		 *
		 * Allows developers to make final modifications to the generated popup HTML,
		 * such as adding tracking attributes or wrapper elements.
		 *
		 * @param string $popup_html           The complete HTML output for the popup.
		 * @param array  $validated_attributes The sanitized and validated shortcode attributes.
		 * @param string $popup_content        The processed inner content of the popup.
		 *
		 * @since 1.0.0
		 *
		 */
		$popup_html = apply_filters( 'kntnt-popup-shortcode', $popup_html, $validated_attributes, $popup_content );

		// Return the final HTML output for the shortcode.
		return $popup_html;

	}

	/**
	 * Parses and merges raw shortcode attributes with predefined defaults.
	 *
	 * Handles attributes passed as flags (without explicit values) by using
	 * `$this->attribute_flag_defaults`. Then merges with `$this->attribute_omitted_defaults`.
	 * Applies the `shortcode_atts_{$this->shortcode_tag}` filter.
	 *
	 * @param array<string|int, mixed> $raw_attributes The raw array from the shortcode.
	 *
	 * @return array<string, mixed> The processed and filtered attributes.
	 */
	private function process_shortcode_attributes( array $raw_attributes ): array {

		$processed_atts = [];

		// Iterate raw attributes to handle flags and explicit values.
		// WordPress parses `[popup flag1 flag2="val"]` into `[0 => 'flag1', 'flag2' => 'val']`.
		foreach ( $raw_attributes as $key => $value ) {
			if ( is_int( $key ) ) { // Attribute used as a flag, e.g., [popup modal].

				// Assign default value for recognized flags.
				$flag_name = (string) $value;
				if ( array_key_exists( $flag_name, $this->attribute_flag_defaults ) ) {
					$processed_atts[ $flag_name ] = $this->attribute_flag_defaults[ $flag_name ];
				}
				elseif ( array_key_exists( $flag_name, $this->attribute_omitted_defaults ) ) {
					// If known but not a typical flag, set to true (e.g. for boolean attributes).
					$processed_atts[ $flag_name ] = true;
				}

			}
			else { // Attribute with an explicit value, e.g., [popup width="500px"].
				$processed_atts[ (string) $key ] = $value;
			}
		}

		// Merge processed attributes with system-wide omitted defaults.
		// Attributes in $processed_atts take precedence.
		$final_attributes = array_merge( $this->attribute_omitted_defaults, $processed_atts );

		/**
		 * Filters the Kntnt Popup shortcode attributes after initial processing and merging with defaults.
		 * This is a dynamic filter hook, named based on the shortcode tag (e.g., `shortcode_atts_popup`).
		 *
		 * @param array  $final_attributes           The processed array of shortcode attributes.
		 * @param array  $attribute_omitted_defaults The plugin's default values for attributes if omitted.
		 * @param array  $raw_attributes             The original, unmodified attributes passed to the shortcode.
		 * @param string $shortcode_tag              The name of the shortcode being processed (e.g., "popup").
		 *
		 * @since 1.0.0
		 *
		 */
		$final_attributes_filtered = apply_filters( "shortcode_atts_{$this->shortcode_tag}", $final_attributes, $this->attribute_omitted_defaults, $raw_attributes, $this->shortcode_tag );

		// Return the fully processed and filtered attributes.
		return $final_attributes_filtered;

	}

	/**
	 * Sanitizes and validates all processed shortcode attributes.
	 *
	 * @param array<string, mixed> $processed_attributes Attributes after merging with defaults.
	 *
	 * @return array<string, mixed> Sanitized and validated attributes.
	 */
	private function sanitize_and_validate_attributes( array $processed_attributes ): array {

		$validated = []; // Initialize array for validated attributes.

		// Sanitize 'id': ensure valid HTML ID or generate a unique one.
		$id_attribute = $processed_attributes['id'];
		$validated['id'] = $id_attribute ? sanitize_html_class( (string) $id_attribute ) : Plugin::get_slug() . '-' . ++ $this->popup_instance_counter;

		// Sanitize boolean attributes using filter_var.
		$validated['show-on-exit-intent'] = filter_var( $processed_attributes['show-on-exit-intent'], FILTER_VALIDATE_BOOLEAN );
		$validated['close-outside-click'] = filter_var( $processed_attributes['close-outside-click'], FILTER_VALIDATE_BOOLEAN );
		$validated['modal'] = filter_var( $processed_attributes['modal'], FILTER_VALIDATE_BOOLEAN );

		// Sanitize 'show-after-time': integer (seconds) or false.
		$time_value = $processed_attributes['show-after-time'];
		if ( $time_value !== false ) {
			$time_int = filter_var( $time_value, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] );
			$validated['show-after-time'] = ( $time_int !== false && $time_int >= 0 ) ? $time_int : false;
		}
		else {
			$validated['show-after-time'] = false;
		}

		// Sanitize 'show-after-scroll': integer (percentage 0-100) or false.
		$scroll_value = $processed_attributes['show-after-scroll'];
		if ( $scroll_value !== false ) {
			$scroll_int = filter_var( $scroll_value, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0, 'max_range' => 100 ] ] );
			$validated['show-after-scroll'] = ( $scroll_int !== false && $scroll_int >= 0 && $scroll_int <= 100 ) ? $scroll_int : false;
		}
		else {
			$validated['show-after-scroll'] = false;
		}

		// Sanitize 'close-button': escaped HTML string or false.
		$close_button_value = $processed_attributes['close-button'];
		$validated['close-button'] = $close_button_value !== false ? esc_html( (string) $close_button_value ) : false;

		// Sanitize 'reappear-delay': parse time string into seconds.
		$reappear_delay_default = is_int( $this->attribute_omitted_defaults['reappear-delay'] ) ? (string) $this->attribute_omitted_defaults['reappear-delay'] : '0';
		$validated['reappear-delay'] = $this->parse_time_string_to_seconds( (string) $processed_attributes['reappear-delay'], $reappear_delay_default );

		// Sanitize 'overlay-color': must be a valid CSS color.
		$overlay_color_value = (string) $processed_attributes['overlay-color'];
		$overlay_color_default = (string) $this->attribute_omitted_defaults['overlay-color'];
		$validated['overlay-color'] = $this->sanitize_css( $overlay_color_value, $overlay_color_default );

		// Sanitize 'width': must be a valid CSS length value.
		$width_default = (string) $this->attribute_omitted_defaults['width'];
		$validated['width'] = $this->sanitize_css( (string) $processed_attributes['width'], $width_default );

		// Sanitize 'max-height': must be a valid CSS length value.
		$max_height_default = (string) $this->attribute_omitted_defaults['max-height'];
		$validated['max-height'] = $this->sanitize_css( (string) $processed_attributes['max-height'], $max_height_default );

		// Sanitize 'padding': must be a valid CSS padding value.
		$padding_default = (string) $this->attribute_omitted_defaults['padding'];
		$validated['padding'] = $this->sanitize_css( (string) $processed_attributes['padding'], $padding_default );

		// Sanitize 'position': must be one of the allowed enum values.
		$allowed_positions = [ 'center', 'top', 'top-right', 'right', 'bottom-right', 'bottom', 'bottom-left', 'left', 'top-left' ];
		$position_value = (string) $processed_attributes['position'];
		$position_default = (string) $this->attribute_omitted_defaults['position'];
		$validated['position'] = in_array( $position_value, $allowed_positions, true ) ? $position_value : $position_default;

		// Sanitize 'open-animation': must be false or an allowed animation name.
		$allowed_open_animations = [ false, 'tada', 'fade-in', 'fade-in-top', 'fade-in-right', 'fade-in-bottom', 'fade-in-left', 'slide-in-top', 'slide-in-right', 'slide-in-bottom', 'slide-in-left' ];
		$open_anim_value = $processed_attributes['open-animation'];
		if ( is_string( $open_anim_value ) && strtolower( $open_anim_value ) === 'false' ) { // Treat string "false" as boolean false
			$open_anim_value = false;
		}
		$validated['open-animation'] = in_array( $open_anim_value, $allowed_open_animations, true ) ? $open_anim_value : $this->attribute_omitted_defaults['open-animation'];

		// Sanitize 'close-animation': must be false or an allowed animation name.
		$allowed_close_animations = [ false, 'fade-out', 'fade-out-top', 'fade-out-right', 'fade-out-bottom', 'fade-out-left', 'slide-out-top', 'slide-out-right', 'slide-out-bottom', 'slide-out-left' ];
		$close_anim_value = $processed_attributes['close-animation'];
		if ( is_string( $close_anim_value ) && strtolower( $close_anim_value ) === 'false' ) { // Treat string "false" as boolean false
			$close_anim_value = false;
		}
		$validated['close-animation'] = in_array( $close_anim_value, $allowed_close_animations, true ) ? $close_anim_value : $this->attribute_omitted_defaults['close-animation'];

		// Sanitize 'open-animation-duration': integer (milliseconds) or false.
		$open_duration_value = $processed_attributes['open-animation-duration'];
		if ( $open_duration_value !== false ) {
			$duration_int = filter_var( $open_duration_value, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] );
			$validated['open-animation-duration'] = ( $duration_int !== false && $duration_int >= 0 ) ? $duration_int : false;
		}
		else {
			$validated['open-animation-duration'] = false;
		}

		// Sanitize 'close-animation-duration': integer (milliseconds) or false.
		$close_duration_value = $processed_attributes['close-animation-duration'];
		if ( $close_duration_value !== false ) {
			$duration_int = filter_var( $close_duration_value, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] );
			$validated['close-animation-duration'] = ( $duration_int !== false && $duration_int >= 0 ) ? $duration_int : false;
		}
		else {
			$validated['close-animation-duration'] = false;
		}

		// Sanitize 'class': space-separated list of valid HTML class names.
		$class_value = (string) $processed_attributes['class'];
		$validated['class'] = $class_value ? implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $class_value ) ) ) : '';

		// Sanitize inline style attributes using WordPress's $this->sanitize_css.
		$validated['style-overlay'] = $this->sanitize_css( (string) $processed_attributes['style-overlay'] );
		$validated['style-dialog'] = $this->sanitize_css( (string) $processed_attributes['style-dialog'] );
		$validated['style-close-button'] = $this->sanitize_css( (string) $processed_attributes['style-close-button'] );
		$validated['style-content'] = $this->sanitize_css( (string) $processed_attributes['style-content'] );

		// Sanitize ARIA labels as plain text fields.
		$validated['aria-label-popup'] = sanitize_text_field( (string) $processed_attributes['aria-label-popup'] );
		$validated['aria-label-close'] = sanitize_text_field( (string) $processed_attributes['aria-label-close'] );

		// Return the array of all sanitized and validated attributes.
		return $validated;

	}

	/**
	 * Parses a time string (e.g., "60s", "5m", "2h", "1d") into seconds.
	 *
	 * @param string $time_string       The time string to parse.
	 * @param string $default_value_str The default time string if parsing the primary one fails or results in zero (and wasn't "0").
	 *
	 * @return int Time in seconds.
	 */
	private function parse_time_string_to_seconds( string $time_string, string $default_value_str ): int {

		// Define multipliers for time units to convert to seconds.
		$multipliers = [
			's' => 1,       // seconds
			'm' => 60,      // minutes
			'h' => 3600,    // hours
			'd' => 86400,   // days
			'' => 1,        // no unit (assumed seconds if only digits)
		];

		// Define a local helper function to parse an individual time string.
		$parse_string = static function ( string $str_to_parse ) use ( $multipliers ): int { // Anonymous function body

			// Normalize the input string (lowercase, trimmed).
			$str_to_parse = strtolower( trim( $str_to_parse ) );

			// Handle purely numeric strings (assumed to be seconds).
			if ( is_numeric( $str_to_parse ) ) {
				return max( 0, (int) $str_to_parse );
			}

			// Attempt to match a number followed by an optional unit (s, m, h, d).
			if ( preg_match( '/^(\d+)([smhd]?)$/', $str_to_parse, $matches ) ) {
				$value = max( 0, (int) $matches[1] );
				$unit = $matches[2] ?? ''; // Default to empty unit if not captured.
				return $value * ( $multipliers[ $unit ] ?? 1 ); // Default multiplier 1 if unit is unrecognized.
			}

			// Return 0 if the format is invalid or cannot be parsed.
			return 0;

		}; // End of anonymous function body

		// Parse the primary time string.
		$parsed_seconds = $parse_string( $time_string );

		// Fallback to parsing the default string if primary parsing yielded 0,
		// unless the original input string was literally "0".
		if ( $parsed_seconds === 0 && trim( $time_string ) !== '0' ) {
			return $parse_string( $default_value_str );
		}

		// Return the parsed seconds.
		return $parsed_seconds;

	}

	/**
	 * Sanitize a CSS string by checking for a blacklist of dangerous patterns
	 * intended to prevent HTML/JS injection.
	 *
	 * This is a simpler filter that allows most CSS syntax as long as it
	 * doesn't match known XSS vectors.
	 *
	 * @param string $css         The CSS string to sanitize.
	 * @param string $default_css The default CSS string to return if the CSS string is considered unsafe.
	 *
	 * @return string The original CSS string if deemed safe, or an empty string otherwise.
	 */
	function sanitize_css( string $css, string $default_css = '' ): string {

		$original_css = $css;

		// Decode HTML entities to prevent malicious code from being hidden.
		$css = wp_kses_decode_entities( $css );

		// Remove CSS comments /* … */ to prevent them from hiding malicious patterns.
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );

		// Remove leading and trailing spaces
		$css = trim( $css );

		// Dangerous patterns to block
		$blocked_patterns = [
			'/</',                                 // Blockera '<' för att förhindra HTML-tagginjektion (t.ex. <script>, <img>).
			'/javascript\s*:/i',                   // "javascript:" URI-schema, med valfria blanksteg
			'/expression\s*\(/i',                  // "expression(...)" (IE-specifikt)
			'/(?:behaviour|behavior)\s*:\s*url/i', // "behaviour: url" (IE-specifikt)
			'/-moz-binding\s*:/i',                 // "-moz-binding:" (Firefox XUL-bindningar)
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',   // Kontrolltecken
		];

		foreach ( $blocked_patterns as $pattern ) {
			if ( preg_match( $pattern, $css ) ) {
				return $default_css; // Return default css, since css is considered unsafe.
			}
		}

		// No blocked patterns were found.
		return $original_css;

	}

	/**
	 * Loads the popup template file and returns its output as a string.
	 *
	 * The template file uses the `$popup_inner_content` and `$final_attributes` variables,
	 * aliased as `$content` and `$atts` respectively within the template scope for brevity.
	 * Output buffering is used to capture the template's HTML.
	 *
	 * @param string              $popup_inner_content The processed inner content for the popup.
	 * @param array<string,mixed> $final_attributes    The array of sanitized shortcode attributes.
	 *
	 * @return string The HTML output from the template file, or an empty string on failure.
	 */
	private function load_template( string $popup_inner_content, array $final_attributes ): string {

		// Start output buffering to capture the template's HTML.
		ob_start();

		// Make variables available to the template's scope using common names.
		$atts = $final_attributes;
		$content = $popup_inner_content;

		// Define the full path to the template file.
		$template_path = Plugin::get_plugin_directory() . 'templates/kntnt-popup-template.php';

		// Include the template file if it exists.
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

		// Get the contents of the output buffer and clean the buffer.
		$html_output = ob_get_clean();

		// Ensure a string is always returned, even if buffering failed or template was empty.
		return $html_output ?: '';

	}

	/**
	 * Cleans up common formatting issues in shortcode content likely caused by wpautop.
	 *
	 * This function addresses:
	 * - Leading </p> tags.
	 * - Leading <p> tags if the content immediately starts with a block-level element.
	 * - Redundant <br /> tags inside paragraphs that are properly closed (e.g., <p>text<br /></p> -> <p>text</p>).
	 * - Paragraphs at the very end of the content that are "closed" with <br /> instead of </p>
	 * (e.g., ...<p>text<br /> -> ...<p>text</p>).
	 * - Trailing <br /> tags.
	 * - Trailing <p> or </p> tags.
	 *
	 * @param string $content The raw content passed to the shortcode.
	 *
	 * @return string The cleaned content.
	 */
	function cleanup_wp_mess( $content ) {

		if ( ! is_string( $content ) || empty( trim( $content ) ) ) {
			return '';
		}

		// 1. Initial trim
		$content = trim( $content );

		// 2. Remove leading </p> and any immediately following whitespace (often </p>\n)
		$content = preg_replace( '/^<\/p>\s*/is', '', $content );
		$content = trim( $content ); // Re-trim as new leading whitespace might be exposed

		// 3. Conditionally remove a leading <p> tag if it's immediately followed by a known block-level element.
		// This handles cases where wpautop wraps the entire shortcode's content block in a <p>.
		// List of common block elements. Script and style are included as they behave like block elements in this context.
		$block_elements_pattern = '<(?:h[1-6r]|div|ul|ol|li|table|thead|tbody|tfoot|tr|th|td|blockquote|pre|form|figure|section|article|aside|footer|header|nav|main|details|summary|dl|dt|dd|fieldset|legend|address|hr|script|style)';
		$content = preg_replace( '/^<p>\s*(' . $block_elements_pattern . ')/is', '$1', $content );
		$content = trim( $content );

		// 4. Fix <p>Text<br /></p> to <p>Text</p> (removes redundant <br /> before closing </p>)
		// This targets paragraphs that are correctly closed but have an unnecessary <br /> just before the </p>.
		$content = preg_replace( '#<p>(.*?)(<br\s*/?>)\s*</p>#is', '<p>$1</p>', $content );

		// 5. Fix <p>Text<br /> (at the end of the content) to <p>Text</p>
		// This targets paragraphs at the very end of the content string that wpautop might
		// have "closed" with a <br /> instead of a </p>.
		// The regex tries to ensure it's a paragraph structure at the end.
		// It captures content within <p>...</p>, allowing other inline tags,
		// and ensures it's not crossing into other <p> or </p> tags.
		$content = preg_replace_callback( '#<p>([^<]*(?:<(?!p>|/p>)[^<]*)*?)(<br\s*/?>)\s*$#is', function ( $matches ) {
			// $matches[1] is the content inside <p> before <br />
			// $matches[2] is the <br /> tag
			return '<p>' . rtrim( $matches[1] ) . '</p>'; // Replace trailing <br /> with </p>
		},                                $content );

		// 6. Remove any remaining solitary <br /> tags at the very end of the content.
		$content = preg_replace( '/<br\s*\/?>\s*$/is', '', $content );

		// 7. Remove any trailing <p> or </p> tags (often empty or misplaced).
		$content = preg_replace( '/\s*<\/?p>\s*$/is', '', $content );

		// 8. Final trim
		$content = trim( $content );

		return $content;

	}

}
