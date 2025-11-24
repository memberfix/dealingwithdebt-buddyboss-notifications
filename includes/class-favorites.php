<?php
/**
 * Favorites Class
 *
 * @package SeriesSubscribe
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Series Subscribe Favorites Class
 */
class Series_Subscribe_Favorites {

    /**
     * The single instance of the class.
     *
     * @var Series_Subscribe_Favorites
     */
    protected static $_instance = null;

    /**
     * Main Instance.
     *
     * @return Series_Subscribe_Favorites - Main instance.
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
        // No hooks needed here, methods called directly
    }

    /**
     * Check if user has favorited an item.
     *
     * @param int    $user_id User ID.
     * @param int    $item_id Post or series ID.
     * @param string $type    Type: 'post' or 'series'.
     * @return bool Whether item is favorited.
     */
    public function is_favorited( $user_id, $item_id, $type = 'post' ) {
        $favorites = $this->get_user_favorites( $user_id, $type );
        return in_array( $item_id, $favorites );
    }

    /**
     * Get all favorites for a user.
     *
     * @param int    $user_id User ID.
     * @param string $type    Type: 'post' or 'series'.
     * @return array Array of favorited IDs.
     */
    public function get_user_favorites( $user_id, $type = 'post' ) {
        $meta_key = $type === 'series' ? 'series_favorites_series' : 'series_favorites_posts';
        $favorites = get_user_meta( $user_id, $meta_key, true );
        return is_array( $favorites ) ? array_map( 'intval', $favorites ) : array();
    }

    /**
     * Toggle favorite status for an item.
     *
     * @param int    $user_id User ID.
     * @param int    $item_id Post or series ID.
     * @param string $type    Type: 'post' or 'series'.
     * @return array Result with 'success' and 'favorited' status.
     */
    public function toggle_favorite( $user_id, $item_id, $type = 'post' ) {
        $favorites = $this->get_user_favorites( $user_id, $type );
        $meta_key = $type === 'series' ? 'series_favorites_series' : 'series_favorites_posts';

        if ( in_array( $item_id, $favorites ) ) {
            // Remove from favorites
            $favorites = array_values( array_diff( $favorites, array( $item_id ) ) );
            $favorited = false;

            // Decrement favorite count
            $count_key = $type === 'series' ? '_series_favorite_count' : '_series_favorite_count';
            $count = intval( get_term_meta( $item_id, $count_key, true ) );
            if ( $type === 'post' ) {
                $count = intval( get_post_meta( $item_id, $count_key, true ) );
                update_post_meta( $item_id, $count_key, max( 0, $count - 1 ) );
            } else {
                update_term_meta( $item_id, $count_key, max( 0, $count - 1 ) );
            }
        } else {
            // Add to favorites
            $favorites[] = $item_id;
            $favorites = array_values( array_unique( $favorites ) );
            $favorited = true;

            // Increment favorite count
            $count_key = $type === 'series' ? '_series_favorite_count' : '_series_favorite_count';
            if ( $type === 'post' ) {
                $count = intval( get_post_meta( $item_id, $count_key, true ) );
                update_post_meta( $item_id, $count_key, $count + 1 );
            } else {
                $count = intval( get_term_meta( $item_id, $count_key, true ) );
                update_term_meta( $item_id, $count_key, $count + 1 );
            }
        }

        update_user_meta( $user_id, $meta_key, $favorites );

        do_action( 'series_favorite_toggled', $user_id, $item_id, $type, $favorited );

        return array(
            'success' => true,
            'favorited' => $favorited,
        );
    }

    /**
     * Get favorite count for an item.
     *
     * @param int    $item_id Post or series ID.
     * @param string $type    Type: 'post' or 'series'.
     * @return int Favorite count.
     */
    public function get_favorite_count( $item_id, $type = 'post' ) {
        $count_key = '_series_favorite_count';
        if ( $type === 'post' ) {
            return intval( get_post_meta( $item_id, $count_key, true ) );
        } else {
            return intval( get_term_meta( $item_id, $count_key, true ) );
        }
    }
}
