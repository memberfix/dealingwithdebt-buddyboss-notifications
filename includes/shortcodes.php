<?php
/**
 * Shortcodes for Tango Catalog Display
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display Tango catalog
 * Usage: [tango_catalog]
 */
function gamipress_tango_catalog_shortcode($atts) {
    $atts = shortcode_atts(array(
        'columns' => '3',
        'show_disclaimer' => 'yes',
        'currency' => 'USD',
    ), $atts);

    wp_enqueue_style('gamipress-tango-catalog', GAMIPRESS_TANGO_URL . 'assets/css/catalog.css', array(), GAMIPRESS_TANGO_VERSION);

    $api = new GamiPress_Tango_API();
    $result = $api->get_catalog();

    if (!$result['success']) {
        return '<div class="tango-error">' . __('Unable to load catalog. Please try again later.', 'gamipress-tango') . '</div>';
    }

    $items = $result['items'];

    // Filter by currency if specified
    if ($atts['currency']) {
        $items = array_filter($items, function($item) use ($atts) {
            return $item['currency_code'] === $atts['currency'];
        });
    }

    // Filter only active items
    $items = array_filter($items, function($item) {
        return $item['status'] === 'active';
    });

    ob_start();
    ?>
    <div class="tango-catalog" data-columns="<?php echo esc_attr($atts['columns']); ?>">
        <?php if (empty($items)): ?>
            <p><?php _e('No rewards available at this time.', 'gamipress-tango'); ?></p>
        <?php else: ?>
            <div class="tango-catalog-grid">
                <?php foreach ($items as $item): ?>
                    <div class="tango-catalog-item" data-utid="<?php echo esc_attr($item['utid']); ?>">
                        <div class="tango-catalog-item-inner">
                            <?php if (!empty($item['image_url'])): ?>
                                <div class="tango-catalog-image">
                                    <img src="<?php echo esc_url($item['image_url']); ?>"
                                         alt="<?php echo esc_attr($item['brand_name']); ?>">
                                </div>
                            <?php endif; ?>

                            <div class="tango-catalog-content">
                                <h3 class="tango-catalog-title"><?php echo esc_html($item['brand_name']); ?></h3>

                                <?php if ($item['min_value'] > 0 && $item['max_value'] > 0): ?>
                                    <p class="tango-catalog-value">
                                        <?php
                                        printf(
                                            __('%s%s - %s%s', 'gamipress-tango'),
                                            esc_html($item['currency_code']),
                                            number_format($item['min_value'], 2),
                                            esc_html($item['currency_code']),
                                            number_format($item['max_value'], 2)
                                        );
                                        ?>
                                    </p>
                                <?php endif; ?>

                                <button class="tango-view-details" data-utid="<?php echo esc_attr($item['utid']); ?>">
                                    <?php _e('View Details', 'gamipress-tango'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Hidden data for modal -->
                        <div class="tango-item-data" style="display:none;">
                            <div class="tango-description"><?php echo wp_kses_post($item['description']); ?></div>
                            <div class="tango-disclaimer"><?php echo wp_kses_post($item['disclaimer']); ?></div>
                            <div class="tango-terms"><?php echo wp_kses_post($item['terms']); ?></div>
                            <div class="tango-redemption"><?php echo wp_kses_post($item['redemption_instructions']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($atts['show_disclaimer'] === 'yes' && count($items) >= 3): ?>
                <div class="tango-general-disclaimer">
                    <p><small><?php _e('The merchants represented are not sponsors of the rewards or otherwise affiliated with this company. The logos and other identifying marks attached are trademarks of and owned by each represented company and/or its affiliates. Please visit each company\'s website for additional terms and conditions.', 'gamipress-tango'); ?></small></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal for brand details -->
    <div id="tango-modal" class="tango-modal" style="display:none;">
        <div class="tango-modal-content">
            <span class="tango-modal-close">&times;</span>
            <div class="tango-modal-body">
                <h2 id="tango-modal-title"></h2>
                <div id="tango-modal-image"></div>
                <div id="tango-modal-description"></div>
                <div id="tango-modal-disclaimer"></div>
                <div id="tango-modal-terms"></div>
            </div>
        </div>
    </div>
    <?php

    wp_enqueue_script('gamipress-tango-catalog', GAMIPRESS_TANGO_URL . 'assets/js/catalog.js', array('jquery'), GAMIPRESS_TANGO_VERSION, true);

    return ob_get_clean();
}
