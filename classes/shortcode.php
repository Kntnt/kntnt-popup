<?php

namespace Kntnt\Popup;

/**
 * Trait Shortcode.
 * Provides a more flexible shortcode attribute parsing method.
 */
trait Shortcode {

    /**
     * A more forgiving version of WordPress' shortcode_atts()
     * that handles positional attributes.
     *
     * @param array<string, mixed> $pairs     Default attribute values.
     * @param array<mixed>|string  $atts      User-provided attributes.
     * @param string               $shortcode Optional. The shortcode name.
     *
     * @return array<string, mixed> Combined and filtered attributes.
     */
	public function shortcode_atts(array $pairs, $atts, string $shortcode = ''): array {
		$atts = (array)($atts ?? []);
		$out = [];
		$pos = 0;
		foreach ($pairs as $name => $default) {
			if (array_key_exists($name, $atts)) {
				$out[$name] = $atts[$name];
			}
			elseif (array_key_exists($pos, $atts)) {
				$out[$name] = $atts[$pos];
				$pos++;
			}
			else {
				$out[$name] = $default;
			}
		}
		if ($shortcode) {
			$out = apply_filters("shortcode_atts_{$shortcode}", $out, $pairs, $atts, $shortcode);
		}
		return $out;
	}

}