<?php
/**
 * Admin Settings for Tango Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class GamiPress_Tango_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_settings_page() {
        // Try to add under GamiPress menu if it exists
        if (function_exists('gamipress_get_admin_menu_hook')) {
            add_submenu_page(
                'gamipress',
                __('Tango Integration', 'gamipress-tango'),
                __('Tango Integration', 'gamipress-tango'),
                'manage_options',
                'gamipress-tango-settings',
                array($this, 'render_settings_page')
            );
        } else {
            // Otherwise add to Settings menu
            add_options_page(
                __('Tango Integration', 'gamipress-tango'),
                __('Tango Integration', 'gamipress-tango'),
                'manage_options',
                'gamipress-tango-settings',
                array($this, 'render_settings_page')
            );
        }
    }

    public function register_settings() {
        register_setting('gamipress_tango_settings', 'gamipress_tango_environment');
        register_setting('gamipress_tango_settings', 'gamipress_tango_platform_name');
        register_setting('gamipress_tango_settings', 'gamipress_tango_platform_key');
        register_setting('gamipress_tango_settings', 'gamipress_tango_account_identifier');
        register_setting('gamipress_tango_settings', 'gamipress_tango_send_email');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Tango Card Integration Settings', 'gamipress-tango'); ?></h1>

            <?php
            // Test connection if requested
            if (isset($_POST['test_connection']) && check_admin_referer('gamipress_tango_test_connection')) {
                $api = new GamiPress_Tango_API();
                $result = $api->test_connection();

                if ($result['success']) {
                    echo '<div class="notice notice-success"><p>' . __('Connection successful!', 'gamipress-tango') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Connection failed: ', 'gamipress-tango') . esc_html($result['error']) . '</p></div>';
                }
            }
            ?>

            <form method="post" action="options.php">
                <?php settings_fields('gamipress_tango_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gamipress_tango_environment"><?php _e('Environment', 'gamipress-tango'); ?></label>
                        </th>
                        <td>
                            <select name="gamipress_tango_environment" id="gamipress_tango_environment">
                                <option value="sandbox" <?php selected(get_option('gamipress_tango_environment', 'sandbox'), 'sandbox'); ?>>
                                    <?php _e('Sandbox', 'gamipress-tango'); ?>
                                </option>
                                <option value="production" <?php selected(get_option('gamipress_tango_environment'), 'production'); ?>>
                                    <?php _e('Production', 'gamipress-tango'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Use sandbox for testing, production for live rewards', 'gamipress-tango'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gamipress_tango_platform_name"><?php _e('Platform Name', 'gamipress-tango'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="gamipress_tango_platform_name" id="gamipress_tango_platform_name"
                                   value="<?php echo esc_attr(get_option('gamipress_tango_platform_name', '')); ?>"
                                   class="regular-text">
                            <p class="description"><?php _e('Your Tango platform name from the dashboard', 'gamipress-tango'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gamipress_tango_platform_key"><?php _e('Platform Key', 'gamipress-tango'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="gamipress_tango_platform_key" id="gamipress_tango_platform_key"
                                   value="<?php echo esc_attr(get_option('gamipress_tango_platform_key', '')); ?>"
                                   class="regular-text">
                            <p class="description"><?php _e('Your Tango platform key (API key)', 'gamipress-tango'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gamipress_tango_account_identifier"><?php _e('Account Identifier', 'gamipress-tango'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="gamipress_tango_account_identifier" id="gamipress_tango_account_identifier"
                                   value="<?php echo esc_attr(get_option('gamipress_tango_account_identifier', '')); ?>"
                                   class="regular-text">
                            <p class="description"><?php _e('Your Tango account identifier for processing orders', 'gamipress-tango'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gamipress_tango_send_email"><?php _e('Send Reward Emails', 'gamipress-tango'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="gamipress_tango_send_email" id="gamipress_tango_send_email"
                                   value="1" <?php checked(get_option('gamipress_tango_send_email', true), true); ?>>
                            <p class="description"><?php _e('Let Tango send reward emails to recipients', 'gamipress-tango'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2><?php _e('Test Connection', 'gamipress-tango'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('gamipress_tango_test_connection'); ?>
                <p>
                    <input type="submit" name="test_connection" class="button button-secondary"
                           value="<?php _e('Test API Connection', 'gamipress-tango'); ?>">
                </p>
            </form>
        </div>
        <?php
    }
}
