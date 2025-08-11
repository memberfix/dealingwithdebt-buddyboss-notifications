<?php
/**
 * Series Subscribe Shortcodes
 *
 * @package SeriesSubscribe
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Series Subscribe Shortcodes Class
 */
class Series_Subscribe_Shortcodes {

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'series_subscribe_button', array( $this, 'series_subscribe_button_shortcode' ) );
    }

    /**
     * Series subscribe button shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function series_subscribe_button_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'series_id' => 0,
            'series_slug' => '',
            'button_class' => 'series-subscribe-btn',
            'subscribe_text' => __( 'Subscribe to Series', 'series-subscribe' ),
            'unsubscribe_text' => __( 'Unsubscribe from Series', 'series-subscribe' ),
            'login_text' => __( 'Login to Subscribe', 'series-subscribe' ),
            'show_count' => 'false',
        ), $atts, 'series_subscribe_button' );
        $series_id = $this->get_series_id( $atts );
        
        if ( ! $series_id ) {
            return '<p class="series-subscribe-error">' . __( 'Series not found.', 'series-subscribe' ) . '</p>';
        }

        $series = get_term( $series_id, 'series' );
        if ( ! $series || is_wp_error( $series ) ) {
            return '<p class="series-subscribe-error">' . __( 'Series not found.', 'series-subscribe' ) . '</p>';
        }

        if ( ! is_user_logged_in() ) {
            return $this->render_login_button( $atts, $series );
        }

        $user_id = get_current_user_id();
        $db = new Series_Subscribe_Database();
        $is_subscribed = $db->is_user_subscribed( $user_id, $series_id );
        $subscriber_count = $db->get_subscriber_count( $series_id );

        return $this->render_subscribe_button( $atts, $series, $is_subscribed, $subscriber_count );
    }

    /**
     * Get series ID from attributes or current context.
     *
     * @param array $atts Shortcode attributes.
     * @return int Series ID.
     */
    private function get_series_id( $atts ) {
        if ( ! empty( $atts['series_id'] ) ) {
            return intval( $atts['series_id'] );
        }
        if ( ! empty( $atts['series_slug'] ) ) {
            $series = get_term_by( 'slug', $atts['series_slug'], 'series' );
            if ( $series && ! is_wp_error( $series ) ) {
                return $series->term_id;
            }
        }

        if ( is_tax( 'series' ) ) {
            $queried_object = get_queried_object();
            if ( $queried_object && isset( $queried_object->term_id ) ) {
                return $queried_object->term_id;
            }
        }

        if ( is_single() ) {
            global $post;
            if ( $post ) {
                $series_terms = wp_get_post_terms( $post->ID, 'series' );
                if ( ! empty( $series_terms ) && ! is_wp_error( $series_terms ) ) {
                    return $series_terms[0]->term_id;
                }
            }
        }

        return 0;
    }

    /**
     * Render login button for non-logged-in users.
     *
     * @param array $atts Shortcode attributes.
     * @param WP_Term $series Series term object.
     * @return string HTML output.
     */
    private function render_login_button( $atts, $series ) {
        $login_url = wp_login_url( get_permalink() );
        
        return '<a href="' . esc_url( $login_url ) . '" class="' . esc_attr( $atts['button_class'] ) . ' series-login-btn" data-series-id="' . esc_attr( $series->term_id ) . '">' . esc_html( $atts['login_text'] ) . '</a>';
    }

    /**
     * Render subscribe/unsubscribe button for logged-in users.
     *
     * @param array $atts Shortcode attributes.
     * @param WP_Term $series Series term object.
     * @param bool $is_subscribed Whether user is subscribed.
     * @param int $subscriber_count Number of subscribers.
     * @return string HTML output.
     */
    private function render_subscribe_button( $atts, $series, $is_subscribed, $subscriber_count ) {
        $button_text = $is_subscribed ? $atts['unsubscribe_text'] : $atts['subscribe_text'];
        $button_state = $is_subscribed ? 'subscribed' : 'unsubscribed';
        
        $html = '<div class="series-subscribe-wrapper" data-series-id="' . esc_attr( $series->term_id ) . '" style="display: inline-block;">';
        $html .= '<button type="button" class="' . esc_attr( $atts['button_class'] ) . ' series-subscribe-toggle" data-state="' . esc_attr( $button_state ) . '">';
        $html .= '<span class="button-text">' . esc_html( $button_text ) . '</span>';
        $html .= '<span class="loading-text" style="display: none;">' . __( 'Loading...', 'series-subscribe' ) . '</span>';
        $html .= '</button>';
        $html .= '<div class="series-subscribe-message" style="display: none;"></div>';
        $html .= '</div>';

        return $html;
    }
}
