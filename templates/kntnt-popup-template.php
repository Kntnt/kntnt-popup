<div id="<?= esc_attr( $atts['id'] ) ?>" class="kntnt-popup <?= esc_attr( $atts['class'] ) ?>" aria-hidden="true">
    <div class="kntnt-popup__overlay kntnt-popup--pos-<?= esc_attr( $atts['position'] ) ?>"
         tabindex="-1"
		<?= $atts['close-outside-click'] ? ' data-popup-close' : ''; ?>
         style="background: <?= esc_attr( $atts['overlay-color'] ) ?>; <?= esc_attr( $atts['style-overlay'] ) ?>">
        <div class="kntnt-popup__dialog"
             role="dialog"
             aria-modal="<?= $atts['modal'] ? 'true' : 'false'; ?>"
             aria-label="<?= esc_attr( $atts['aria-label-popup'] ) ?>"
             style="width: <?= esc_attr( $atts['width'] ) ?>; max-height: <?= esc_attr( $atts['max-height'] ) ?>; padding: <?= esc_attr( $atts['padding'] ) ?>; <?= esc_attr( $atts['style-dialog'] ) ?>">
			<?php if ( $atts['close-button'] ) : ?>
                <button class="kntnt-popup__close-button"
                        aria-label="<?= esc_attr( $atts['aria-label-close'] ) ?>"
                        data-popup-close
                        style="<?= esc_attr( $atts['style-close-button'] ) ?>">
					<?= esc_html( $atts['close-button'] ) ?>
                </button>
			<?php endif; ?>
            <div class="kntnt-popup__content" style="<?= esc_attr( $atts['style-content'] ) ?>">
				<?= $content; ?>
            </div>
        </div>
    </div>
</div>