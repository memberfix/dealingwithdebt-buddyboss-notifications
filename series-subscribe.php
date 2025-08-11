<?php
/**
 * Plugin Name: Series Subscribe
 * Plugin URI: https://memberfix.rocks
 * Description: Allow users to subscribe to series and receive BuddyBoss notifications when new posts are published in subscribed series.
 * Version: 1.0.0
 * Author: Memberfix
 * Text Domain: series-subscribe
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 *
 * @package SeriesSubscribe
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'SERIES_SUBSCRIBE_VERSION', '1.0.0' );
define( 'SERIES_SUBSCRIBE_PLUGIN_FILE', __FILE__ );
define( 'SERIES_SUBSCRIBE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SERIES_SUBSCRIBE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Series Subscribe Class
 */
class Series_Subscribe {

    /**
     * The single instance of the class.
     *
     * @var Series_Subscribe
     */
    protected static $_instance = null;

    /**
     * Main Series Subscribe Instance.
     *
     * @return Series_Subscribe - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Series Subscribe Constructor.
     */
    public function __construct() {
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ), 0 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_series_subscribe_toggle', array( $this, 'ajax_toggle_subscription' ) );
        add_action( 'wp_ajax_nopriv_series_subscribe_toggle', array( $this, 'ajax_toggle_subscription' ) );
        
        // Hook into wp_after_insert_post to ensure taxonomy data is available
        add_action( 'wp_after_insert_post', array( $this, 'handle_post_after_insert' ), 10, 4 );
        
        register_activation_hook( SERIES_SUBSCRIBE_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( SERIES_SUBSCRIBE_PLUGIN_FILE, array( $this, 'deactivate' ) );
    }

    /**
     * Include required core files.
     */
    public function includes() {
        require_once SERIES_SUBSCRIBE_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once SERIES_SUBSCRIBE_PLUGIN_DIR . 'includes/class-notifications.php';
        require_once SERIES_SUBSCRIBE_PLUGIN_DIR . 'includes/class-database.php';
    }

    /**
     * Init Series Subscribe when WordPress Initialises.
     */
    public function init() {
        do_action( 'before_series_subscribe_init' );

        $this->load_plugin_textdomain();

        new Series_Subscribe_Shortcodes();
        new Series_Subscribe_Notifications();
        new Series_Subscribe_Database();
        do_action( 'series_subscribe_init' );
    }

    /**
     * Load Localisation files.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'series-subscribe', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'series-subscribe', SERIES_SUBSCRIBE_PLUGIN_URL . 'assets/js/series-subscribe.js', array( 'jquery' ), SERIES_SUBSCRIBE_VERSION, true );
        wp_enqueue_style( 'series-subscribe', SERIES_SUBSCRIBE_PLUGIN_URL . 'assets/css/series-subscribe.css', array(), SERIES_SUBSCRIBE_VERSION );
        
        wp_localize_script( 'series-subscribe', 'series_subscribe_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'series_subscribe_nonce' ),
            'subscribe_text' => __( 'Subscribe', 'series-subscribe' ),
            'unsubscribe_text' => __( 'Unsubscribe', 'series-subscribe' ),
            'loading_text' => __( 'Loading...', 'series-subscribe' ),
        ) );
    }

    /**
     * Handle AJAX subscription toggle.
     */
    public function ajax_toggle_subscription() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'series_subscribe_nonce' ) ) {
            wp_die( __( 'Security check failed', 'series-subscribe' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to subscribe', 'series-subscribe' ) ) );
        }

        $series_id = intval( $_POST['series_id'] );
        $user_id = get_current_user_id();

        if ( ! $series_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid series', 'series-subscribe' ) ) );
        }

        $db = new Series_Subscribe_Database();
        $is_subscribed = $db->is_user_subscribed( $user_id, $series_id );

        if ( $is_subscribed ) {
            $result = $db->unsubscribe_user( $user_id, $series_id );
            $action = 'unsubscribed';
            $button_text = __( 'Subscribe', 'series-subscribe' );
        } else {
            $result = $db->subscribe_user( $user_id, $series_id );
            $action = 'subscribed';
            $button_text = __( 'Unsubscribe', 'series-subscribe' );
        }

        if ( $result ) {
            wp_send_json_success( array(
                'action' => $action,
                'button_text' => $button_text,
                'message' => $action === 'subscribed' ? __( 'Successfully subscribed!', 'series-subscribe' ) : __( 'Successfully unsubscribed!', 'series-subscribe' )
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update subscription', 'series-subscribe' ) ) );
        }
    }

    /**
     * Handle post publishing and send notifications to subscribed users only.
     */
    public function handle_post_after_insert( $post_id, $post, $update, $post_before ) {
        if ( $post->post_type !== 'post' ) {
            return;
        }
        
        if ( $post->post_status !== 'publish' ) {
            return;
        }
        
        if ( $update && $post_before && $post_before->post_status === 'publish' ) {
            return;
        }
        
        $notifications = new Series_Subscribe_Notifications();
        $all_series_subscriber_ids = array();
        
        $series_terms = get_the_terms( $post_id, 'series' );
        
        if ( ! empty( $series_terms ) && ! is_wp_error( $series_terms ) ) {
            $db = new Series_Subscribe_Database();
            
            foreach ( $series_terms as $series ) {
                $series_subscriber_ids = $db->get_series_subscribers( $series->term_id );
                if ( ! empty( $series_subscriber_ids ) ) {
                    $all_series_subscriber_ids = array_merge( $all_series_subscriber_ids, $series_subscriber_ids );
                    $notifications->send_series_post_notifications( $post, $series );
                }
            }
            
            $all_series_subscriber_ids = array_unique( $all_series_subscriber_ids );
        }
        
        $notifications->send_author_post_notifications( $post, $all_series_subscriber_ids );
    }

    /**
     * Plugin activation hook.
     */
    public function activate() {
        $db = new Series_Subscribe_Database();
        $db->create_tables();
    }

    /**
     * Plugin deactivation hook.
     */
    public function deactivate() {
    }

}

/**
 * Main instance of Series Subscribe.
 *
 * @return Series_Subscribe
 */
function Series_Subscribe() {
    return Series_Subscribe::instance();
}

$GLOBALS['series_subscribe'] = Series_Subscribe();
