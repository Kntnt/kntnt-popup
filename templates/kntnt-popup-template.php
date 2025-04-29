<?php
/**
 * Template for the Kntnt Popup.
 *
 * @var array  $atts    Sanitized and validated shortcode attributes.
 * @var string $content Processed shortcode content.
 */
?>
<div id="<?= esc_attr( $atts['id'] ) ?>" class="<?= esc_attr( $atts['wrapper_class_string'] ) ?>" aria-hidden="true">
    <div class="kntnt-popup__overlay kntnt-popup--pos-<?= esc_attr( $atts['position'] ) ?>"
         tabindex="-1"
		<?= $atts['close-outside-click'] ? ' data-popup-close' : ''; ?>
         style="background: <?= esc_attr( $atts['overlay-color'] ) ?>; <?= esc_attr( $atts['style-overlay'] ) ?>">
        <div class="kntnt-popup__dialog"
             role="dialog"
             aria-modal="<?= $atts['modal'] ? 'true' : 'false'; ?>"
             aria-labelledby="<?= esc_attr( $atts['id'] ) ?>-title" <?php // Consider adding a title element for aria-labelledby ?>
             aria-describedby="<?= esc_attr( $atts['id'] ) ?>-content" <?php // Wrap content in div with this ID ?>
             style="width: <?= esc_attr( $atts['width'] ) ?>; max-height: <?= esc_attr( $atts['max-height'] ) ?>; padding: <?= esc_attr( $atts['padding'] ) ?>; <?= esc_attr( $atts['style-dialog'] ) ?>">
			<?php if ( $atts['close-button'] ) : ?>
                <button class="kntnt-popup__close-button"
                        aria-label="<?= esc_attr( $atts['aria-label-close'] ) ?>"
                        data-popup-close
                        style="<?= esc_attr( $atts['style-close-button'] ) ?>">
					<?= esc_html( $atts['close-button'] ) ?>
                </button>
			<?php endif; ?>
            <div class="kntnt-popup__content" id="<?= esc_attr( $atts['id'] ) ?>-content" style="<?= esc_attr( $atts['style-content'] ) ?>">
				<?= $content; // Content already processed with do_shortcode  ?>
            </div>
        </div>
    </div>
</div>