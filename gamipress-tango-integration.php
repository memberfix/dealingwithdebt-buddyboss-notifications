<?php
/**
 * Plugin Name: GamiPress Tango Integration
 * Plugin URI: https://memberfix.com
 * Description: Integrates GamiPress with Tango Card rewards platform
 * Version: 1.0.0
 * Author: MemberFix
 * Author URI: https://memberfix.com
 * License: GPL-2.0+
 * Text Domain: gamipress-tango
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('GAMIPRESS_TANGO_VERSION', '1.0.0');
define('GAMIPRESS_TANGO_FILE', __FILE__);
define('GAMIPRESS_TANGO_DIR', plugin_dir_path(__FILE__));
define('GAMIPRESS_TANGO_URL', plugin_dir_url(__FILE__));

// Include required files
require_once GAMIPRESS_TANGO_DIR . 'includes/class-tango-api.php';
require_once GAMIPRESS_TANGO_DIR . 'includes/admin-settings.php';
require_once GAMIPRESS_TANGO_DIR . 'includes/shortcodes.php';

// Initialize plugin
add_action('plugins_loaded', 'gamipress_tango_init');

function gamipress_tango_init() {
    // Check if GamiPress is active
    if (!class_exists('GamiPress')) {
        add_action('admin_notices', 'gamipress_tango_missing_gamipress_notice');
        return;
    }

    // Initialize admin settings
    if (is_admin()) {
        new GamiPress_Tango_Admin_Settings();
    }

    // Register shortcodes
    add_shortcode('tango_catalog', 'gamipress_tango_catalog_shortcode');
}

function gamipress_tango_missing_gamipress_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('GamiPress Tango Integration requires GamiPress to be installed and activated.', 'gamipress-tango'); ?></p>
    </div>
    <?php
}
