<?php
/**
 * REST API Class
 *
 * @package SeriesSubscribe
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Series Subscribe REST API Class
 */
class Series_Subscribe_REST_API {

    /**
     * The single instance of the class.
     *
     * @var Series_Subscribe_REST_API
     */
    protected static $_instance = null;

    /**
     * Main Instance.
     *
     * @return Series_Subscribe_REST_API - Main instance.
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
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        register_rest_route( 'series-subscribe/v1', '/channels/rows', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_rows' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( 'series-subscribe/v1', '/channels/view', array(
            'methods' => 'POST',
            'callback' => array( $this, 'record_view' ),
            'permission_callback' => function() {
                return is_user_logged_in() && current_user_can( 'read' );
            },
        ) );

        register_rest_route( 'series-subscribe/v1', '/channels/favorite', array(
            'methods' => 'POST',
            'callback' => array( $this, 'toggle_favorite' ),
            'permission_callback' => function() {
                return is_user_logged_in() && current_user_can( 'read' );
            },
        ) );
    }

    /**
     * Get all content rows.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_rows( $request ) {
        $rows_param = sanitize_text_field( $request->get_param( 'rows' ) );
        $rows_param = $rows_param ? $rows_param : 'featured,popular_articles,popular_series';
        $rows_req = array_map( 'trim', explode( ',', $rows_param ) );

        $opts = get_option( 'series_subscribe_options', array() );
        $enabled = isset( $opts['channels_rows'] ) ? $opts['channels_rows'] : array();

        $rows = array();
        foreach ( $rows_req as $row ) {
            if ( ! empty( $enabled ) && empty( $enabled[ $row ] ) ) {
                continue;
            }

            switch ( $row ) {
                case 'featured':
                    $rows[] = array(
                        'key' => 'featured',
                        'title' => __( 'Featured Posts', 'series-subscribe' ),
                        'items' => $this->query_featured(),
                    );
                    break;
                case 'popular_articles':
                    $rows[] = array(
                        'key' => 'popular_articles',
                        'title' => __( 'Most Popular', 'series-subscribe' ),
                        'items' => $this->query_popular_articles(),
                    );
                    break;
                case 'popular_series':
                    $rows[] = array(
                        'key' => 'popular_series',
                        'title' => __( 'Most Popular', 'series-subscribe' ),
                        'items' => $this->query_popular_series(),
                    );
                    break;
                case 'recently_published':
                    $rows[] = array(
                        'key' => 'recently_published',
                        'title' => __( 'Most Recent', 'series-subscribe' ),
                        'items' => $this->query_recently_published(),
                    );
                    break;
                case 'categories':
                    $category_rows = $this->query_categories();
                    foreach ( $category_rows as $cat_row ) {
                        $rows[] = $cat_row;
                    }
                    break;
                case 'favorites':
                    if ( is_user_logged_in() ) {
                        $favorite_posts = $this->query_favorite_posts( get_current_user_id() );
                        if ( ! empty( $favorite_posts ) ) {
                            $rows[] = array(
                                'key' => 'favorite_posts',
                                'title' => __( 'My Favorites', 'series-subscribe' ),
                                'items' => $favorite_posts,
                            );
                        }

                        $favorite_series = $this->query_favorite_series( get_current_user_id() );
                        if ( ! empty( $favorite_series ) ) {
                            $rows[] = array(
                                'key' => 'favorite_series',
                                'title' => __( 'My Favorites', 'series-subscribe' ),
                                'items' => $favorite_series,
                            );
                        }
                    }
                    break;
            }
        }

        return rest_ensure_response( $rows );
    }

    /**
     * Map post to response format.
     *
     * @param WP_Post $post Post object.
     * @return array Post data.
     */
    private function map_post( $post ) {
        $featured_image_id = get_post_meta( $post->ID, '_series_featured_image', true );
        if ( $featured_image_id ) {
            $thumb = wp_get_attachment_image_url( $featured_image_id, 'large' );
        } else {
            $thumb = get_the_post_thumbnail_url( $post, 'large' );
        }

        $series_terms = wp_get_post_terms( $post->ID, 'series', array( 'fields' => 'names' ) );

        $is_subscribed = false;
        $is_favorited = false;

        if ( is_user_logged_in() ) {
            $db = new Series_Subscribe_Database();
            $series_ids = wp_get_post_terms( $post->ID, 'series', array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $series_ids ) && ! empty( $series_ids ) ) {
                foreach ( $series_ids as $series_id ) {
                    if ( $db->is_user_subscribed( get_current_user_id(), $series_id ) ) {
                        $is_subscribed = true;
                        break;
                    }
                }
            }

            $favorites = Series_Subscribe_Favorites::instance();
            $is_favorited = $favorites->is_favorited( get_current_user_id(), $post->ID, 'post' );
        }

        return array(
            'id' => $post->ID,
            'type' => 'post',
            'title' => get_the_title( $post ),
            'permalink' => get_permalink( $post ),
            'excerpt' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 24, '…' ),
            'image' => $thumb ? $thumb : '',
            'series' => is_wp_error( $series_terms ) ? array() : $series_terms,
            'isSubscribed' => $is_subscribed,
            'isFavorited' => $is_favorited,
            'views' => intval( get_post_meta( $post->ID, '_series_view_count_total', true ) ),
        );
    }

    /**
     * Query featured posts and series.
     *
     * @return array Featured posts and series.
     */
    private function query_featured() {
        $options = get_option( 'series_subscribe_options', array() );
        $limit = isset( $options['featured_carousel_limit'] ) ? intval( $options['featured_carousel_limit'] ) : 10;

        $items = array();

        $query = new WP_Query( array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'meta_key' => '_series_featured',
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC',
            'posts_per_page' => $limit,
            'no_found_rows' => true,
        ) );

        foreach ( $query->posts as $post ) {
            $items[] = $this->map_post( $post );
        }

        $featured_series = get_terms( array(
            'taxonomy' => 'series',
            'hide_empty' => false,
            'meta_key' => '_series_featured',
            'meta_value' => '1',
            'number' => $limit,
        ) );

        if ( ! is_wp_error( $featured_series ) && ! empty( $featured_series ) ) {
            foreach ( $featured_series as $term ) {
                $items[] = $this->map_series( $term );
            }
        }

        if ( empty( $items ) ) {
            $query = new WP_Query( array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'meta_key' => Series_Subscribe_Popularity::META_SCORE,
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'posts_per_page' => $limit,
                'no_found_rows' => true,
            ) );

            foreach ( $query->posts as $post ) {
                $items[] = $this->map_post( $post );
            }
        }

        return array_slice( $items, 0, $limit );
    }

    /**
     * Query popular articles.
     *
     * @return array Popular articles.
     */
    private function query_popular_articles() {
        $query = new WP_Query( array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'meta_key' => Series_Subscribe_Popularity::META_SCORE,
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'posts_per_page' => 18,
            'no_found_rows' => true,
        ) );

        if ( empty( $query->posts ) ) {
            $query = new WP_Query( array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'meta_key' => '_series_view_count_total',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'posts_per_page' => 18,
                'no_found_rows' => true,
            ) );
        }

        return array_map( array( $this, 'map_post' ), $query->posts );
    }

    /**
     * Query popular series.
     *
     * @return array Popular series.
     */
    private function query_popular_series() {
        $popularity = Series_Subscribe_Popularity::instance();
        $cache = $popularity->get_cached_series();

        if ( ! $cache || empty( $cache['items'] ) ) {
            return array();
        }

        $items = array();
        foreach ( array_slice( $cache['items'], 0, 18 ) as $row ) {
            $term_id = $row['term_id'];
            $term = get_term( $term_id, 'series' );
            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }

            $link = get_term_link( $term );
            $image = '';

            // Get image from first available post or series icon
            if ( function_exists( 'get_series_icon' ) ) {
                $icon_params = sprintf( 'fit_width=400&fit_height=300&series=%d&link=0&display=0&expand=true', $term_id );
                $icon_html = get_series_icon( $icon_params );
                if ( $icon_html ) {
                    preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', $icon_html, $matches );
                    if ( ! empty( $matches[1] ) ) {
                        $image = $matches[1];
                    }
                }
            }

            // Fallback to post thumbnail
            if ( empty( $image ) && ! empty( $row['posts'] ) ) {
                foreach ( $row['posts'] as $post_id ) {
                    $thumb = get_the_post_thumbnail_url( $post_id, 'large' );
                    if ( $thumb ) {
                        $image = $thumb;
                        break;
                    }
                }
            }

            $is_subscribed = false;
            $is_favorited = false;
            if ( is_user_logged_in() ) {
                $db = new Series_Subscribe_Database();
                $is_subscribed = $db->is_user_subscribed( get_current_user_id(), $term_id );

                // For series, favorite = subscribe
                $is_favorited = $is_subscribed;
            }

            $items[] = array(
                'id' => $term_id,
                'type' => 'series',
                'title' => $term->name,
                'permalink' => is_wp_error( $link ) ? '' : $link,
                'excerpt' => wp_trim_words( strip_tags( $term->description ), 24, '…' ),
                'image' => $image,
                'series' => array( $term->name ),
                'views' => isset( $row['score'] ) ? intval( $row['score'] ) : 0,
                'isSubscribed' => $is_subscribed,
                'isFavorited' => $is_favorited,
            );
        }

        return $items;
    }

    /**
     * Query user favorites.
     *
     * @param int $user_id User ID.
     * @return array Favorite posts.
     */
    /**
     * Query user's favorite posts.
     *
     * @param int $user_id User ID.
     * @return array Favorite posts.
     */
    private function query_favorite_posts( $user_id ) {
        $favorites_instance = Series_Subscribe_Favorites::instance();
        $favorite_posts = $favorites_instance->get_user_favorites( $user_id, 'post' );

        if ( empty( $favorite_posts ) ) {
            return array();
        }

        $query = new WP_Query( array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'post__in' => $favorite_posts,
            'orderby' => 'post__in',
            'posts_per_page' => 24,
            'no_found_rows' => true,
        ) );

        return array_map( array( $this, 'map_post' ), $query->posts );
    }

    /**
     * Query user's favorite series (subscribed series).
     *
     * @param int $user_id User ID.
     * @return array Subscribed series.
     */
    private function query_favorite_series( $user_id ) {
        $db = new Series_Subscribe_Database();
        $subscribed_series = $db->get_user_subscriptions( $user_id );

        if ( empty( $subscribed_series ) ) {
            return array();
        }

        $items = array();
        foreach ( $subscribed_series as $series_id ) {
            $term = get_term( $series_id, 'series' );
            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }

            $items[] = $this->map_series( $term );
        }

        return $items;
    }

    /**
     * Query recently published posts.
     *
     * @return array Recently published posts.
     */
    private function query_recently_published() {
        $query = new WP_Query( array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'posts_per_page' => 18,
            'no_found_rows' => true,
        ) );

        return array_map( array( $this, 'map_post' ), $query->posts );
    }

    /**
     * Query categories (series_group taxonomy) with series.
     *
     * @return array Array of category rows.
     */
    private function query_categories() {
        $rows = array();

        $groups = get_terms( array(
            'taxonomy' => 'series_group',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ) );

        if ( is_wp_error( $groups ) || empty( $groups ) ) {
            return $rows;
        }

        foreach ( $groups as $group ) {
            $series_in_group = array();
            if ( function_exists( 'get_series_in_group' ) ) {
                $series_in_group = get_series_in_group( $group->term_id );
            }

            if ( empty( $series_in_group ) ) {
                continue;
            }

            $items = array();

            // Add series objects
            foreach ( $series_in_group as $series_id ) {
                $term = get_term( $series_id, 'series' );
                if ( ! $term || is_wp_error( $term ) ) {
                    continue;
                }
                $items[] = $this->map_series( $term );
            }

            // Add posts from these series
            $query = new WP_Query( array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'series',
                        'field' => 'term_id',
                        'terms' => $series_in_group,
                    ),
                ),
                'orderby' => 'date',
                'order' => 'DESC',
                'posts_per_page' => 18,
                'no_found_rows' => true,
            ) );

            foreach ( $query->posts as $post ) {
                $items[] = $this->map_post( $post );
            }

            if ( ! empty( $items ) ) {
                $rows[] = array(
                    'key' => 'category_' . $group->term_id,
                    'title' => $group->name,
                    'items' => $items,
                );
            }
        }

        return $rows;
    }

    /**
     * Map series term to response format.
     *
     * @param WP_Term $term Series term object.
     * @return array Series data.
     */
    private function map_series( $term ) {
        $link = get_term_link( $term );
        $image = '';

        // Get series icon
        if ( function_exists( 'get_series_icon' ) ) {
            $icon_params = sprintf( 'fit_width=400&fit_height=300&series=%d&link=0&display=0&expand=true', $term->term_id );
            $icon_html = get_series_icon( $icon_params );
            if ( $icon_html ) {
                preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', $icon_html, $matches );
                if ( ! empty( $matches[1] ) ) {
                    $image = $matches[1];
                }
            }
        }

        $is_subscribed = false;
        $is_favorited = false;

        if ( is_user_logged_in() ) {
            $db = new Series_Subscribe_Database();
            $is_subscribed = $db->is_user_subscribed( get_current_user_id(), $term->term_id );

            // For series, favorite = subscribe
            $is_favorited = $is_subscribed;
        }

        return array(
            'id' => $term->term_id,
            'type' => 'series',
            'title' => $term->name,
            'permalink' => is_wp_error( $link ) ? '' : $link,
            'excerpt' => $term->description ? wp_trim_words( $term->description, 24, '…' ) : '',
            'image' => $image,
            'series' => array( $term->name ),
            'isSubscribed' => $is_subscribed,
            'isFavorited' => $is_favorited,
            'views' => 0,
        );
    }

    /**
     * Record a view via REST API.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function record_view( $request ) {
        $post_id = intval( $request->get_param( 'post_id' ) );
        if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
            return new WP_Error( 'invalid_post', __( 'Invalid post.', 'series-subscribe' ), array( 'status' => 400 ) );
        }

        if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
            return new WP_Error( 'bad_nonce', __( 'Invalid nonce.', 'series-subscribe' ), array( 'status' => 403 ) );
        }

        $tracking = Series_Subscribe_View_Tracking::instance();
        $recorded = $tracking->record_view( $post_id, get_current_user_id() );

        return rest_ensure_response( array( 'ok' => true, 'recorded' => $recorded ) );
    }

    /**
     * Toggle favorite via REST API.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function toggle_favorite( $request ) {
        $item_id = intval( $request->get_param( 'item_id' ) );
        $item_type = sanitize_text_field( $request->get_param( 'item_type' ) );

        if ( ! $item_id ) {
            return new WP_Error( 'invalid_item', __( 'Invalid item.', 'series-subscribe' ), array( 'status' => 400 ) );
        }

        if ( ! in_array( $item_type, array( 'post', 'series' ) ) ) {
            $item_type = 'post';
        }

        // Validate item exists
        if ( $item_type === 'post' ) {
            if ( get_post_status( $item_id ) !== 'publish' ) {
                return new WP_Error( 'invalid_post', __( 'Invalid post.', 'series-subscribe' ), array( 'status' => 400 ) );
            }
        } else {
            $term = get_term( $item_id, 'series' );
            if ( ! $term || is_wp_error( $term ) ) {
                return new WP_Error( 'invalid_series', __( 'Invalid series.', 'series-subscribe' ), array( 'status' => 400 ) );
            }
        }

        if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
            return new WP_Error( 'bad_nonce', __( 'Invalid nonce.', 'series-subscribe' ), array( 'status' => 403 ) );
        }

        // For series, toggle subscription instead of favorite
        if ( $item_type === 'series' ) {
            $db = new Series_Subscribe_Database();
            $is_subscribed = $db->is_user_subscribed( get_current_user_id(), $item_id );

            if ( $is_subscribed ) {
                $success = $db->unsubscribe_user( get_current_user_id(), $item_id );
                $favorited = false;
            } else {
                $success = $db->subscribe_user( get_current_user_id(), $item_id );
                $favorited = true;
            }

            return rest_ensure_response( array(
                'ok' => $success,
                'favorited' => $favorited,
            ) );
        }

        // For posts, use the favorites system
        $favorites = Series_Subscribe_Favorites::instance();
        $result = $favorites->toggle_favorite( get_current_user_id(), $item_id, $item_type );

        return rest_ensure_response( array(
            'ok' => $result['success'],
            'favorited' => $result['favorited'],
        ) );
    }
}
