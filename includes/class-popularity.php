<?php
/**
 * Popularity Scoring Class
 *
 * @package SeriesSubscribe
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Series Subscribe Popularity Class
 */
class Series_Subscribe_Popularity {

    const CRON_HOOK = 'series_subscribe_rebuild_popularity';
    const META_SCORE = '_series_popularity_score';
    const CACHE_SERIES = 'series_channels_popular_cache';

    /**
     * The single instance of the class.
     *
     * @var Series_Subscribe_Popularity
     */
    protected static $_instance = null;

    /**
     * Main Instance.
     *
     * @return Series_Subscribe_Popularity - Main instance.
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
        add_action( self::CRON_HOOK, array( $this, 'rebuild_all' ) );
        add_action( 'init', array( $this, 'schedule_if_needed' ) );
    }

    /**
     * Schedule cron job if not already scheduled.
     */
    public function schedule_if_needed() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
        }
    }

    /**
     * Unschedule cron job.
     */
    public static function unschedule() {
        $ts = wp_next_scheduled( self::CRON_HOOK );
        if ( $ts ) {
            wp_unschedule_event( $ts, self::CRON_HOOK );
        }
    }

    /**
     * Rebuild all popularity scores.
     */
    public function rebuild_all() {
        $opts = get_option( 'series_subscribe_options', array() );
        $weights = isset( $opts['popularity_weights'] )
            ? $opts['popularity_weights']
            : array( 'views' => 1.0, 'comments' => 1.0, 'subscriptions' => 1.0, 'favorites' => 1.5, 'recency' => 0.5 );

        $lookback_days = isset( $opts['popularity_lookback_days'] ) ? intval( $opts['popularity_lookback_days'] ) : 120;
        $date_after = date( 'Y-m-d', strtotime( '-' . $lookback_days . ' days' ) );

        $query = new WP_Query( array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => 2000,
            'date_query' => array(
                array( 'after' => $date_after ),
            ),
            'no_found_rows' => true,
        ) );

        $scores = array();
        foreach ( $query->posts as $post_id ) {
            $scores[ $post_id ] = $this->score_post( $post_id, $weights, $lookback_days );
            update_post_meta( $post_id, self::META_SCORE, $scores[ $post_id ] );
        }

        // Calculate series popularity
        $series_scores = array();
        $series_posts = array();

        foreach ( $scores as $post_id => $score ) {
            $terms = wp_get_post_terms( $post_id, 'series', array( 'fields' => 'ids' ) );
            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term_id ) {
                if ( ! isset( $series_scores[ $term_id ] ) ) {
                    $series_scores[ $term_id ] = 0;
                    $series_posts[ $term_id ] = array();
                }
                $series_scores[ $term_id ] += $score;
                if ( count( $series_posts[ $term_id ] ) < 3 ) {
                    $series_posts[ $term_id ][] = $post_id;
                }
            }
        }

        // Cache popular series
        $payload = array(
            'when' => time(),
            'lookback_days' => $lookback_days,
            'items' => array(),
        );

        if ( ! empty( $series_scores ) ) {
            arsort( $series_scores );
            foreach ( array_slice( $series_scores, 0, 50, true ) as $term_id => $score ) {
                $payload['items'][] = array(
                    'term_id' => $term_id,
                    'score' => $score,
                    'posts' => isset( $series_posts[ $term_id ] ) ? $series_posts[ $term_id ] : array(),
                );
            }
        }

        set_transient( self::CACHE_SERIES, $payload, DAY_IN_SECONDS );
        do_action( 'series_popularity_rebuilt', count( $scores ), count( $payload['items'] ) );
    }

    /**
     * Calculate popularity score for a post.
     *
     * @param int   $post_id Post ID.
     * @param array $weights Weights for each factor.
     * @param int   $lookback_days Days to look back for comments.
     * @return float Popularity score.
     */
    private function score_post( $post_id, $weights, $lookback_days ) {
        // Get views
        $views = intval( get_post_meta( $post_id, '_series_view_count_total', true ) );

        // Get recent comments count
        $recent_comments = get_comments( array(
            'post_id' => $post_id,
            'count' => true,
            'date_query' => array(
                array( 'after' => date( 'Y-m-d', strtotime( '-' . $lookback_days . ' days' ) ) ),
            ),
            'status' => 'approve',
        ) );

        // Get series subscriber count
        $subscriptions = 0;
        $terms = wp_get_post_terms( $post_id, 'series', array( 'fields' => 'ids' ) );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $db = new Series_Subscribe_Database();
            foreach ( $terms as $term_id ) {
                $subscriptions += $db->get_subscriber_count( $term_id );
            }
        }

        // Get favorites count
        $favorites = intval( get_post_meta( $post_id, '_series_favorite_count', true ) );

        // Calculate recency factor
        $post = get_post( $post_id );
        $age_days = max( 0, floor( ( time() - strtotime( $post->post_date_gmt ) ) / DAY_IN_SECONDS ) );
        $recency = max( 0, 1 - ( $age_days / max( 1, $lookback_days ) ) );

        // Calculate final score
        $score = ( $weights['views'] * $views )
               + ( $weights['comments'] * intval( $recent_comments ) )
               + ( $weights['subscriptions'] * $subscriptions )
               + ( isset( $weights['favorites'] ) ? $weights['favorites'] : 1.5 ) * $favorites
               + ( $weights['recency'] * $recency );

        return round( $score, 4 );
    }

    /**
     * Get cached popular series.
     *
     * @return array Cached series data.
     */
    public function get_cached_series() {
        $data = get_transient( self::CACHE_SERIES );
        if ( ! $data || empty( $data['items'] ) ) {
            $this->rebuild_all();
            $data = get_transient( self::CACHE_SERIES );
        }
        return $data;
    }

    /**
     * Get popularity score for a post.
     *
     * @param int $post_id Post ID.
     * @return float Popularity score.
     */
    public function get_score( $post_id ) {
        return floatval( get_post_meta( $post_id, self::META_SCORE, true ) );
    }
}
