<div id="<?= esc_attr( $atts['id'] ) ?>" class="kntnt-popup" aria-hidden="true" style="<?= esc_attr( $atts['style_popup'] ) ?>">
    <div class="kntnt-popup__overlay" tabindex="-1" <?= $atts['close_outside_click'] ? ' data-popup-close' : ''; ?> style="<?= esc_attr( $atts['style_overlay'] ) ?>">
        <div class="kntnt-popup__dialog" role="dialog" aria-modal="true" aria-label="<?= esc_attr( $atts['aria_label_popup'] ) ?>" style="<?= esc_attr( $atts['style_dialog'] ) ?>">
			<?php if ( $atts['close_button'] ) : ?>
                <button class="kntnt-popup__close-button" aria-label="<?= esc_attr( $atts['aria_label_close'] ) ?>" data-popup-close><?= esc_html( $atts['close_button'] ) ?></button>
			<?php endif; ?>
            <div class="kntnt-popup__content">
				<?= $atts['content']; ?>
            </div>
        </div>
    </div>
</div>