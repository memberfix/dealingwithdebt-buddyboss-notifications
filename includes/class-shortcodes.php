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
        add_shortcode( 'series_categories_carousel', array( $this, 'series_categories_carousel_shortcode' ) );
        add_shortcode( 'mfx_series_heading', array( $this, 'mfx_dynamic_heading_shortcode' ) );
        add_shortcode( 'series_icon_url', array( $this, 'get_series_icon_url_shortcode' ) );
        add_shortcode( 'series_channels', array( $this, 'series_channels_shortcode' ) );

        // Back to series functionality
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_back_to_series' ) );
        add_action( 'wp_footer', array( $this, 'render_back_to_series_script' ) );
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

    /**
     * Enqueue back to series script and inline data.
     */
    public function enqueue_back_to_series() {
        if ( is_singular( 'post' ) ) {
            $terms = wp_get_post_terms( get_the_ID(), 'series' );
            $series_link = '';
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $series_link = get_term_link( $terms[0] );
            }

            $inline_js = sprintf(
                'let PPS_SERIES_LINK = %s;',
                $series_link ? wp_json_encode( $series_link ) : 'null'
            );

            wp_register_script( 'pps-back-to-series', false, array(), false, true );
            wp_enqueue_script( 'pps-back-to-series' );
            wp_add_inline_script( 'pps-back-to-series', $inline_js );
        }
    }

    /**
     * Render back to series script in footer.
     */
    public function render_back_to_series_script() {
        if ( is_singular( 'post' ) ) : ?>
        <script>
        jQuery(document).ready(function($) {
          if ( PPS_SERIES_LINK ) {
            const linkHTML = `
              <p>
                <a href="${PPS_SERIES_LINK}"
                   class="back-to-series">← Back to Series</a>
              </p>`;
            $('figure.entry-media.entry-img').after(linkHTML);
          }
        });
        </script>
        <?php endif;
    }

    /**
     * Series categories carousel shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function series_categories_carousel_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'exclude' => '',
            'include' => '',
            'limit' => -1,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => true,
            'image_width' => 180,
            'image_height' => 180,
        ), $atts );

        ob_start();

        $args = array(
            'taxonomy' => 'series_group',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => $atts['hide_empty'],
        );

        if ( ! empty( $atts['include'] ) ) {
            $args['include'] = explode( ',', $atts['include'] );
        }
        if ( ! empty( $atts['exclude'] ) ) {
            $args['exclude'] = explode( ',', $atts['exclude'] );
        }

        $series_categories = get_terms( $args );

        if ( ! empty( $series_categories ) && ! is_wp_error( $series_categories ) ) {
            echo '<div class="series-categories-container">';

            foreach ( $series_categories as $category ) {
                if ( ! function_exists( 'get_series_in_group' ) ) {
                    continue;
                }

                $series_ids = get_series_in_group( $category->term_id );

                if ( ! empty( $series_ids ) ) {
                    $series_args = array(
                        'include' => $series_ids,
                        'hide_empty' => false,
                    );

                    if ( function_exists( 'ppseries_get_series_slug' ) ) {
                        $series_list = get_terms( ppseries_get_series_slug(), $series_args );
                    } else {
                        $series_list = get_terms( 'series', $series_args );
                    }

                    if ( ! empty( $series_list ) && ! is_wp_error( $series_list ) ) {
                        usort( $series_list, function ( $a, $b ) {
                            return strcasecmp( $a->name, $b->name );
                        });
                    }

                    if ( ! empty( $series_list ) && ! is_wp_error( $series_list ) ) {
                        echo '<div class="series-category-section">';
                        echo '<div class="series-category-heading">' . esc_html( $category->name ) . '</div>';

                        echo '<div class="series-carousel">';
                        echo '<div class="series-carousel-arrow prev"></div>';
                        echo '<div class="series-carousel-arrow next"></div>';
                        echo '<div class="series-carousel-inner">';

                        foreach ( $series_list as $series ) {
                            if ( function_exists( 'ppseries_get_series_slug' ) ) {
                                $series_link = get_term_link( $series, ppseries_get_series_slug() );
                            } else {
                                $series_link = get_term_link( $series, 'series' );
                            }

                            $series_image = '';
                            if ( function_exists( 'get_series_icon' ) ) {
                                $icon_params = sprintf(
                                    'fit_width=%d&fit_height=%d&series=%d&link=0&display=0&expand=true',
                                    $atts['image_width'],
                                    $atts['image_height'],
                                    $series->term_id
                                );
                                $series_image = get_series_icon( $icon_params );
                            }

                            echo '<div class="series-item">';
                            echo '<a href="' . esc_url( $series_link ) . '" class="series-link">';

                            if ( ! empty( $series_image ) ) {
                                echo '<div class="series-image">' . $series_image . '</div>';
                            } else {
                                echo '<div class="series-image-placeholder"></div>';
                            }

                            echo '<div class="series-title">' . esc_html( $series->name ) . '</div>';
                            echo '</a>';
                            echo '</div>';
                        }

                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
            }

            echo '</div>';
        } else {
            echo '<p>No series categories found.</p>';
        }

        ?>
        <style>
            .series-categories-container {
                max-width: 100%;
                margin: 0 auto;
            }

            .series-category-section {
                padding-bottom: 40px;
                border-bottom: 1px solid #ccc;
            }

             .series-category-heading {
                font-size: 22px;
                overflow: hidden;
                color: rgb(0, 42, 101);
                margin-top: 20px;
                margin-bottom: 20px;
            }

            .series-category-title {
                font-size: 24px;
                margin-bottom: 15px;
                font-weight: bold;
                color: rgb(0, 42, 101);
            }

            .series-carousel {
                position: relative;
                overflow: hidden;
            }

            .series-carousel-inner {
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 10px;
                gap: 15px;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .series-carousel-inner::-webkit-scrollbar {
                display: none;
            }

            .series-carousel-arrow {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 40px;
                height: 40px;
                background-color: rgba(255, 255, 255, 0.8);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 10;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .series-carousel:hover .series-carousel-arrow {
                opacity: 1;
            }

            .series-carousel-arrow.prev {
                left: 10px;
            }

            .series-carousel-arrow.next {
                right: 10px;
            }

            .series-carousel-arrow:before {
                content: "";
                display: block;
                width: 12px;
                height: 12px;
                border-top: 2px solid #333;
                border-right: 2px solid #333;
                transition: border-color 0.3s ease;
            }

            .series-carousel-arrow.prev:before {
                transform: rotate(-135deg);
            }

            .series-carousel-arrow.next:before {
                transform: rotate(45deg);
            }

            .series-carousel-arrow:hover:before {
                border-color: #000;
            }

            @media (max-width: 767px) {
                .series-carousel-arrow {
                    display: none !important;
                }
            }

            .series-item {
                flex: 0 0 auto;
                width: <?php echo $atts['image_width']; ?>px;
                margin-right: 5px;
                transition: transform 0.3s ease;
                box-shadow: 1px 1px 5px 1px rgba(112,112,112,0.75);
                -webkit-box-shadow: 1px 1px 5px 1px rgba(112,112,112,0.75);
                border: 1px solid #ccc;
                border-radius: 8px;
                background: white;
            }

            .series-item:hover {
                transform: translateY(-5px);
            }

            .series-link {
                display: block;
                text-decoration: none;
                color: inherit;
            }

            .series-image, .series-image-placeholder {
                width: 100%;
                height: <?php echo $atts['image_height']; ?>px;
                border-top-left-radius: 8px;
                border-top-right-radius: 8px;
                overflow: hidden;
                margin-bottom: 8px;
                position: relative;
            }

            .series-image picture,
            .series-image img {
                width: 100%;
                height: 100%;
                object-position: center;
                display: block;
            }


            .series-title {
                font-size: 14px;
                font-weight: bold;
                text-align: center;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                max-height: 40px;
            }

            @media (max-width: 768px) {
                .series-item {
                    width: 150px;
                }

                .series-image, .series-image-placeholder {
                    height: 150px;
                }
            }

            @media (max-width: 480px) {
                .series-item {
                    width: 130px;
                }

                .series-image, .series-image-placeholder {
                    height: 130px;
                }
            }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carousels = document.querySelectorAll('.series-carousel');

            carousels.forEach(carousel => {
                const scrollContainer = carousel.querySelector('.series-carousel-inner');
                const prevBtn = carousel.querySelector('.series-carousel-arrow.prev');
                const nextBtn = carousel.querySelector('.series-carousel-arrow.next');

                const checkArrows = () => {
                    if (scrollContainer.scrollWidth > scrollContainer.clientWidth) {
                        prevBtn.style.display = 'flex';
                        nextBtn.style.display = 'flex';
                    } else {
                        prevBtn.style.display = 'none';
                        nextBtn.style.display = 'none';
                    }

                    if (scrollContainer.scrollLeft <= 0) {
                        prevBtn.style.opacity = '0.5';
                    } else {
                        prevBtn.style.opacity = '1';
                    }

                    if (scrollContainer.scrollLeft + scrollContainer.clientWidth >= scrollContainer.scrollWidth - 5) {
                        nextBtn.style.opacity = '0.5';
                    } else {
                        nextBtn.style.opacity = '1';
                    }
                };

                checkArrows();
                scrollContainer.addEventListener('scroll', checkArrows);
                window.addEventListener('resize', checkArrows);
                prevBtn.addEventListener('click', () => {
                    scrollContainer.scrollBy({
                        left: -scrollContainer.clientWidth / 2,
                        behavior: 'smooth'
                    });
                });

                nextBtn.addEventListener('click', () => {
                    scrollContainer.scrollBy({
                        left: scrollContainer.clientWidth / 2,
                        behavior: 'smooth'
                    });
                });
            });
        });
        </script>
        <?php

        return ob_get_clean();
    }

    /**
     * Dynamic series heading shortcode.
     *
     * @return string HTML output.
     */
    public function mfx_dynamic_heading_shortcode() {
        $heading = 'Articles';

        if ( is_tax() && have_posts() ) {
            global $wp_query;

            foreach ( $wp_query->posts as $post ) {
                if ( has_category( 'podcast', $post ) ) {
                    $heading = 'Episodes';
                    break;
                }
            }
        }

        return '<div class="series-heading-mfx">' . esc_html( $heading ) . '</div>';
    }

    /**
     * Get series icon URL shortcode.
     *
     * @return string Series icon URL.
     */
    public function get_series_icon_url_shortcode() {
        if ( is_tax() ) {
            $series = get_queried_object();
            if ( $series && isset( $series->term_id ) ) {
                if ( function_exists( 'get_series_icon' ) ) {
                    $icon_url = get_series_icon( $series->term_id );
                    return $icon_url ? esc_url( $icon_url ) : '';
                }
            }
        }
        return '';
    }

    /**
     * Series channels shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function series_channels_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'rows' => 'featured,favorites,popular_articles,popular_series,recently_published,categories',
            'title' => 'Explore',
            'fullbleed' => '0',
        ), $atts, 'series_channels' );

        // Enqueue channels assets
        wp_enqueue_style(
            'series-channels-style',
            SERIES_SUBSCRIBE_PLUGIN_URL . 'assets/css/channels-style.css',
            array(),
            SERIES_SUBSCRIBE_VERSION
        );

        wp_enqueue_script(
            'series-channels-frontend',
            SERIES_SUBSCRIBE_PLUGIN_URL . 'assets/js/channels-frontend.js',
            array(),
            SERIES_SUBSCRIBE_VERSION,
            true
        );

        $options = get_option( 'series_subscribe_options', array() );
        $rotation_speed = isset( $options['carousel_rotation_speed'] ) ? intval( $options['carousel_rotation_speed'] ) : 5;

        wp_localize_script( 'series-channels-frontend', 'seriesChannels', array(
            'restUrl' => esc_url_raw( rest_url( 'series-subscribe/v1/' ) ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'subscribeNonce' => wp_create_nonce( 'series_subscribe_nonce' ),
            'isLoggedIn' => is_user_logged_in(),
            'carouselRotationSpeed' => $rotation_speed * 1000,
        ) );

        ob_start();
        ?>
        <div class="series-channels <?php echo ( $atts['fullbleed'] === '1' ) ? 'series-fullbleed' : ''; ?>" data-rows="<?php echo esc_attr( $atts['rows'] ); ?>">
            <div class="series-channels__tabs">
                <button class="series-channels__tab series-channels__tab--active" data-filter="series">
                    <?php esc_html_e( 'Series', 'series-subscribe' ); ?>
                </button>
                <button class="series-channels__tab" data-filter="articles">
                    <?php esc_html_e( 'Articles', 'series-subscribe' ); ?>
                </button>
            </div>
            <div class="series-channels__rows" aria-live="polite"></div>
            <noscript><?php esc_html_e( 'Enable JavaScript to browse channels.', 'series-subscribe' ); ?></noscript>
        </div>
        <?php
        return ob_get_clean();
    }
}
