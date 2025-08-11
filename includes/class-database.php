<?php
/**
 * Series Subscribe Database
 *
 * @package SeriesSubscribe
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Series Subscribe Database Class
 */
class Series_Subscribe_Database {

    /**
     * Table name for series subscriptions.
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'series_subscriptions';
    }

    /**
     * Create the series subscriptions table.
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            series_id bigint(20) NOT NULL,
            subscribed_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_subscription (user_id, series_id),
            KEY user_id (user_id),
            KEY series_id (series_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Subscribe a user to a series.
     *
     * @param int $user_id User ID.
     * @param int $series_id Series ID.
     * @return bool True on success, false on failure.
     */
    public function subscribe_user( $user_id, $series_id ) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'series_id' => $series_id,
                'subscribed_date' => current_time( 'mysql' )
            ),
            array( '%d', '%d', '%s' )
        );

        if ( $result ) {
            // Fire action for successful subscription
            do_action( 'series_subscribe_user_subscribed', $user_id, $series_id );
            return true;
        }

        return false;
    }

    /**
     * Unsubscribe a user from a series.
     *
     * @param int $user_id User ID.
     * @param int $series_id Series ID.
     * @return bool True on success, false on failure.
     */
    public function unsubscribe_user( $user_id, $series_id ) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'series_id' => $series_id
            ),
            array( '%d', '%d' )
        );

        if ( $result ) {
            // Fire action for successful unsubscription
            do_action( 'series_subscribe_user_unsubscribed', $user_id, $series_id );
            return true;
        }

        return false;
    }

    /**
     * Check if a user is subscribed to a series.
     *
     * @param int $user_id User ID.
     * @param int $series_id Series ID.
     * @return bool True if subscribed, false otherwise.
     */
    public function is_user_subscribed( $user_id, $series_id ) {
        global $wpdb;

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND series_id = %d",
            $user_id,
            $series_id
        ) );

        return $count > 0;
    }

    /**
     * Get all subscribed users for a series.
     *
     * @param int $series_id Series ID.
     * @return array Array of user IDs.
     */
    public function get_series_subscribers( $series_id ) {
        global $wpdb;

        $user_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT user_id FROM {$this->table_name} WHERE series_id = %d",
            $series_id
        ) );

        return array_map( 'intval', $user_ids );
    }

    /**
     * Get all series subscriptions for a user.
     *
     * @param int $user_id User ID.
     * @return array Array of series IDs.
     */
    public function get_user_subscriptions( $user_id ) {
        global $wpdb;

        $series_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT series_id FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ) );

        return array_map( 'intval', $series_ids );
    }

    /**
     * Get the number of subscribers for a series.
     *
     * @param int $series_id Series ID.
     * @return int Number of subscribers.
     */
    public function get_subscriber_count( $series_id ) {
        global $wpdb;

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE series_id = %d",
            $series_id
        ) );

        return intval( $count );
    }

    /**
     * Get subscription statistics.
     *
     * @return array Statistics array.
     */
    public function get_subscription_stats() {
        global $wpdb;

        $stats = array();

        // Total subscriptions
        $stats['total_subscriptions'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );

        // Unique subscribers
        $stats['unique_subscribers'] = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$this->table_name}" );

        // Unique series with subscribers
        $stats['subscribed_series'] = $wpdb->get_var( "SELECT COUNT(DISTINCT series_id) FROM {$this->table_name}" );

        // Most subscribed series
        $most_subscribed = $wpdb->get_row(
            "SELECT series_id, COUNT(*) as subscriber_count 
             FROM {$this->table_name} 
             GROUP BY series_id 
             ORDER BY subscriber_count DESC 
             LIMIT 1"
        );

        if ( $most_subscribed ) {
            $series = get_term( $most_subscribed->series_id, 'series' );
            $stats['most_subscribed_series'] = array(
                'series_id' => $most_subscribed->series_id,
                'series_name' => $series ? $series->name : 'Unknown',
                'subscriber_count' => $most_subscribed->subscriber_count
            );
        }

        return $stats;
    }

    /**
     * Clean up orphaned subscriptions (for series or users that no longer exist).
     */
    public function cleanup_orphaned_subscriptions() {
        global $wpdb;

        // Remove subscriptions for non-existent users
        $wpdb->query(
            "DELETE s FROM {$this->table_name} s 
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
             WHERE u.ID IS NULL"
        );

        // Remove subscriptions for non-existent series
        $wpdb->query(
            "DELETE s FROM {$this->table_name} s 
             LEFT JOIN {$wpdb->terms} t ON s.series_id = t.term_id 
             LEFT JOIN {$wpdb->term_taxonomy} tt ON (t.term_id = tt.term_id AND tt.taxonomy = 'series')
             WHERE t.term_id IS NULL OR tt.term_id IS NULL"
        );
    }

}
