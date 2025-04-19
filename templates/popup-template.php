<?php
/**
 * Template for displaying the Kntnt Popup.
 *
 * Available variables:
 * @var string $instance_id Unique ID for this popup instance.
 * @var string $content     Processed popup content (HTML).
 * @var array  $atts        Sanitized and validated shortcode attributes.
 */

// Prevent direct access
defined('WPINC') || die;

// Extract attributes for easier access
$position_class = 'kntnt-popup--pos-' . esc_attr($atts['position']);
$custom_classes = !empty($atts['class']) ? ' ' . esc_attr($atts['class']) : '';
$modal_attributes = $atts['modal'] ? 'role="dialog" aria-modal="true"' : 'role="complementary"';

// Set CSS Custom Properties for styling override
$style_vars = [
    '--kntnt-overlay-color:' . esc_attr($atts['overlay-color']),
    '--kntnt-width:' . esc_attr($atts['width']),
    '--kntnt-max-height:' . esc_attr($atts['max-height']),
    '--kntnt-padding:' . esc_attr($atts['padding']),
];
$style_attribute = 'style="' . implode('; ', $style_vars) . ';"';

// Determine if close on overlay click is enabled
$overlay_close_attr = $atts['close-outside-click'] ? 'data-micromodal-close' : '';

?>
<div class="kntnt-popup-modal micromodal-slide <?php echo $position_class . $custom_classes; ?>"
     id="<?php echo esc_attr($instance_id); ?>"
     aria-hidden="true"
     <?php echo $style_attribute; ?>>

    <div class="kntnt-popup-overlay" tabindex="-1" <?php echo $overlay_close_attr; ?>>

        <div class="kntnt-popup-container"
             <?php echo $modal_attributes; ?>
             aria-labelledby="<?php echo esc_attr($instance_id); ?>-title"
             aria-describedby="<?php echo esc_attr($instance_id); ?>-content">

            <header class="kntnt-popup-header">
                <?php if ($atts['close-button'] !== false) : ?>
                    <button class="kntnt-popup-close"
                            aria-label="<?php esc_attr_e('Close popup', 'kntnt-popup'); ?>"
                            data-micromodal-close>
                        <?php echo esc_html($atts['close-button']); ?>
                    </button>
                <?php endif; ?>
            </header>

            <main class="kntnt-popup-content" id="<?php echo esc_attr($instance_id); ?>-content">
                 <?php // Visually hidden title linked by aria-labelledby ?>
                <?php echo '<h2 id="' . esc_attr($instance_id) . '-title" class="screen-reader-text">Popup Content</h2>'; ?>
                <?php echo $content; // Output the filtered and processed content ?>
            </main>

        </div>
    </div>
</div>
