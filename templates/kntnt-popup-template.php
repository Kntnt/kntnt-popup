<div id="<?= esc_attr( $atts['id'] ) ?>" class="kntnt-popup <?= esc_attr( $atts['class'] ) ?>" aria-hidden="true" style="<?= esc_attr( $atts['style-popup'] ) ?>">
    <div class="kntnt-popup__overlay kntnt-popup--pos-<?= esc_attr( $atts['position'] ) ?>" tabindex="-1" <?= $atts['close-outside-click'] ? ' data-popup-close' : ''; ?> style="<?= esc_attr( $atts['style-overlay'] ) ?>">
        <div class="kntnt-popup__dialog" role="dialog" aria-modal="<?= $atts['modal'] ? 'true' : 'false'; ?>" aria-label="<?= esc_attr( $atts['aria-label-popup'] ) ?>" style="<?= esc_attr( $atts['style-dialog'] ) ?>">
			<?php if ( $atts['close-button'] ) : ?>
                <button
                        class="kntnt-popup__close-button"
                        aria-label="<?= esc_attr( $atts['aria-label-close'] ) ?>"
                        data-popup-close
                        style="<?= esc_attr( $atts['style-close-button'] ) /* Added style attribute */ ?>"
                ><?= esc_html( $atts['close-button'] ) ?></button>
			<?php endif; ?>
            <div class="kntnt-popup__content">
				<?= $content; /* Content is already processed by do_shortcode in the main class */ ?>
            </div>
        </div>
    </div>
</div>