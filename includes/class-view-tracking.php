<?php
/**
 * View Tracking Class
 *
 * @package SeriesSubscribe
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Series Subscribe View Tracking Class
 */
class Series_Subscribe_View_Tracking {

    /**
     * The single instance of the class.
     *
     * @var Series_Subscribe_View_Tracking
     */
    protected static $_instance = null;

    /**
     * Main Instance.
     *
     * @return Series_Subscribe_View_Tracking - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'template_redirect', array( $this, 'maybe_record_view_on_single' ) );
    }

    /**
     * Record view on single post page.
     */
    public function maybe_record_view_on_single() {
        if ( ! is_user_logged_in() || ! is_singular( 'post' ) ) {
            return;
        }

        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        $this->record_view( $post_id, get_current_user_id() );
    }

    /**
     * Record a view for a post by a user.
     *
     * @param int $post_id Post ID.
     * @param int $user_id User ID.
     * @return bool Whether the view was recorded.
     */
    public function record_view( $post_id, $user_id ) {
        $opts = get_option( 'series_subscribe_options', array() );
        $window = isset( $opts['tracking_window_minutes'] ) ? max( 5, intval( $opts['tracking_window_minutes'] ) ) : 30;

        $key = 'series_last_view_' . $post_id;
        $last = get_user_meta( $user_id, $key, true );
        $now = time();

        // Check if enough time has passed since last view
        if ( ! $last || ( $now - intval( $last ) ) > ( $window * 60 ) ) {
            // Update last view time
            update_user_meta( $user_id, $key, $now );

            // Increment total view count
            $total = intval( get_post_meta( $post_id, '_series_view_count_total', true ) );
            update_post_meta( $post_id, '_series_view_count_total', $total + 1 );

            do_action( 'series_view_recorded', $user_id, $post_id );

            return true;
        }

        return false;
    }

    /**
     * Get view count for a post.
     *
     * @param int $post_id Post ID.
     * @return int View count.
     */
    public function get_view_count( $post_id ) {
        return intval( get_post_meta( $post_id, '_series_view_count_total', true ) );
    }
}
