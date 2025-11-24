<?php
/**
 * Admin Settings Class
 *
 * @package SeriesSubscribe
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Series Subscribe Admin Class
 */
class Series_Subscribe_Admin {

    /**
     * The single instance of the class.
     *
     * @var Series_Subscribe_Admin
     */
    protected static $_instance = null;

    /**
     * Main Instance.
     *
     * @return Series_Subscribe_Admin - Main instance.
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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_featured_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_featured_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        add_action( 'series_edit_form_fields', array( $this, 'add_featured_series_field' ), 10, 2 );
        add_action( 'edited_series', array( $this, 'save_featured_series_field' ), 10, 2 );
    }

    /**
     * Add admin menu.
     */
    public function enqueue_admin_scripts( $hook ) {
        global $post;

        if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
            if ( 'post' === $post->post_type ) {
                wp_enqueue_media();
                wp_add_inline_script( 'media-upload', "
                    jQuery(document).ready(function($) {
                        var mediaUploader;

                        $('#series_featured_image_upload').on('click', function(e) {
                            e.preventDefault();

                            if (mediaUploader) {
                                mediaUploader.open();
                                return;
                            }

                            mediaUploader = wp.media({
                                title: 'Select Featured Carousel Image',
                                button: {
                                    text: 'Use this image'
                                },
                                multiple: false
                            });

                            mediaUploader.on('select', function() {
                                var attachment = mediaUploader.state().get('selection').first().toJSON();
                                $('#series_featured_image_id').val(attachment.id);
                                $('#series_featured_image_preview').html('<img src=\"' + attachment.url + '\" style=\"max-width: 100%; height: auto; display: block; margin-bottom: 5px;\" />');
                                $('#series_featured_image_upload').text('Change Image');
                                if (!$('#series_featured_image_remove').length) {
                                    $('#series_featured_image_upload').after('<button type=\"button\" class=\"button\" id=\"series_featured_image_remove\" style=\"margin-left: 5px;\">Remove</button>');
                                }
                            });

                            mediaUploader.open();
                        });

                        $(document).on('click', '#series_featured_image_remove', function(e) {
                            e.preventDefault();
                            $('#series_featured_image_id').val('');
                            $('#series_featured_image_preview').html('');
                            $('#series_featured_image_upload').text('Select Image');
                            $(this).remove();
                        });
                    });
                " );
            }
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Series Subscribe', 'series-subscribe' ),
            __( 'Series Subscribe', 'series-subscribe' ),
            'manage_options',
            'series-subscribe',
            array( $this, 'render_settings_page' ),
            'dashicons-book-alt',
            58
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting( 'series_subscribe_group', 'series_subscribe_options', array(
            'type' => 'array',
            'sanitize_callback' => array( $this, 'sanitize_options' ),
        ) );

        // Channels Settings Section
        add_settings_section(
            'series_channels_section',
            __( 'Channels Settings', 'series-subscribe' ),
            array( $this, 'render_channels_section_description' ),
            'series-subscribe'
        );

        add_settings_field(
            'channels_rows',
            __( 'Enabled Rows', 'series-subscribe' ),
            array( $this, 'render_channels_rows_field' ),
            'series-subscribe',
            'series_channels_section'
        );

        add_settings_field(
            'popularity_weights',
            __( 'Popularity Weights', 'series-subscribe' ),
            array( $this, 'render_popularity_weights_field' ),
            'series-subscribe',
            'series_channels_section'
        );

        add_settings_field(
            'tracking_window_minutes',
            __( 'View Tracking Window (minutes)', 'series-subscribe' ),
            array( $this, 'render_tracking_window_field' ),
            'series-subscribe',
            'series_channels_section'
        );

        add_settings_field(
            'popularity_lookback_days',
            __( 'Popularity Lookback Period (days)', 'series-subscribe' ),
            array( $this, 'render_lookback_days_field' ),
            'series-subscribe',
            'series_channels_section'
        );

        add_settings_field(
            'featured_carousel_limit',
            __( 'Featured Carousel Limit', 'series-subscribe' ),
            array( $this, 'render_carousel_limit_field' ),
            'series-subscribe',
            'series_channels_section'
        );

        add_settings_field(
            'carousel_rotation_speed',
            __( 'Carousel Rotation Speed', 'series-subscribe' ),
            array( $this, 'render_carousel_rotation_speed_field' ),
            'series-subscribe',
            'series_channels_section'
        );
    }

    /**
     * Sanitize options.
     *
     * @param array $options Options to sanitize.
     * @return array Sanitized options.
     */
    public function sanitize_options( $options ) {
        $clean = array();

        // Channels rows
        $clean['channels_rows'] = array(
            'featured' => ! empty( $options['channels_rows']['featured'] ) ? 1 : 0,
            'favorites' => ! empty( $options['channels_rows']['favorites'] ) ? 1 : 0,
            'recently_published' => ! empty( $options['channels_rows']['recently_published'] ) ? 1 : 0,
            'popular_articles' => ! empty( $options['channels_rows']['popular_articles'] ) ? 1 : 0,
            'popular_series' => ! empty( $options['channels_rows']['popular_series'] ) ? 1 : 0,
            'categories' => ! empty( $options['channels_rows']['categories'] ) ? 1 : 0,
        );

        // Popularity weights
        $clean['popularity_weights'] = array(
            'views' => isset( $options['popularity_weights']['views'] ) ? floatval( $options['popularity_weights']['views'] ) : 1.0,
            'comments' => isset( $options['popularity_weights']['comments'] ) ? floatval( $options['popularity_weights']['comments'] ) : 1.0,
            'subscriptions' => isset( $options['popularity_weights']['subscriptions'] ) ? floatval( $options['popularity_weights']['subscriptions'] ) : 1.0,
            'favorites' => isset( $options['popularity_weights']['favorites'] ) ? floatval( $options['popularity_weights']['favorites'] ) : 1.5,
            'recency' => isset( $options['popularity_weights']['recency'] ) ? floatval( $options['popularity_weights']['recency'] ) : 0.5,
        );

        // Tracking window
        $clean['tracking_window_minutes'] = isset( $options['tracking_window_minutes'] )
            ? max( 5, intval( $options['tracking_window_minutes'] ) )
            : 30;

        // Lookback days
        $clean['popularity_lookback_days'] = isset( $options['popularity_lookback_days'] )
            ? max( 1, intval( $options['popularity_lookback_days'] ) )
            : 120;

        // Featured carousel limit
        $clean['featured_carousel_limit'] = isset( $options['featured_carousel_limit'] )
            ? max( 1, intval( $options['featured_carousel_limit'] ) )
            : 10;

        // Carousel rotation speed (in seconds)
        $clean['carousel_rotation_speed'] = isset( $options['carousel_rotation_speed'] )
            ? max( 1, intval( $options['carousel_rotation_speed'] ) )
            : 5;

        return $clean;
    }

    /**
     * Render channels section description.
     */
    public function render_channels_section_description() {
        echo '<p>' . esc_html__( 'Configure the channels display settings and popularity scoring.', 'series-subscribe' ) . '</p>';
    }

    /**
     * Render channels rows field.
     */
    public function render_channels_rows_field() {
        $options = get_option( 'series_subscribe_options', array() );
        $rows = isset( $options['channels_rows'] ) ? $options['channels_rows'] : array();
        ?>
        <label>
            <input type="checkbox" name="series_subscribe_options[channels_rows][featured]" value="1" <?php checked( ! empty( $rows['featured'] ) ); ?> />
            <?php esc_html_e( 'Featured Posts', 'series-subscribe' ); ?>
        </label><br/>
        <label>
            <input type="checkbox" name="series_subscribe_options[channels_rows][favorites]" value="1" <?php checked( ! empty( $rows['favorites'] ) ); ?> />
            <?php esc_html_e( 'My Favorites', 'series-subscribe' ); ?>
        </label><br/>
        <label>
            <input type="checkbox" name="series_subscribe_options[channels_rows][recently_published]" value="1" <?php checked( ! empty( $rows['recently_published'] ) ); ?> />
            <?php esc_html_e( 'Recently Published', 'series-subscribe' ); ?>
        </label><br/>
        <label>
            <input type="checkbox" name="series_subscribe_options[channels_rows][popular_articles]" value="1" <?php checked( ! empty( $rows['popular_articles'] ) ); ?> />
            <?php esc_html_e( 'Most Popular Articles', 'series-subscribe' ); ?>
        </label><br/>
        <label>
            <input type="checkbox" name="series_subscribe_options[channels_rows][popular_series]" value="1" <?php checked( ! empty( $rows['popular_series'] ) ); ?> />
            <?php esc_html_e( 'Most Popular Series', 'series-subscribe' ); ?>
        </label><br/>
        <label>
            <input type="checkbox" name="series_subscribe_options[channels_rows][categories]" value="1" <?php checked( ! empty( $rows['categories'] ) ); ?> />
            <?php esc_html_e( 'Categories (Series Groups)', 'series-subscribe' ); ?>
        </label>
        <?php
    }

    /**
     * Render popularity weights field.
     */
    public function render_popularity_weights_field() {
        $options = get_option( 'series_subscribe_options', array() );
        $weights = isset( $options['popularity_weights'] ) ? $options['popularity_weights'] : array();
        $fields = array( 'views', 'comments', 'subscriptions', 'favorites', 'recency' );

        foreach ( $fields as $field ) {
            $default_value = ( $field === 'favorites' ) ? 1.5 : ( ( $field === 'recency' ) ? 0.5 : 1.0 );
            $value = isset( $weights[ $field ] ) ? $weights[ $field ] : $default_value;
            ?>
            <p>
                <label>
                    <?php echo esc_html( ucfirst( $field ) ); ?>
                    <input type="number" step="0.1" min="0" name="series_subscribe_options[popularity_weights][<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $value ); ?>" style="width: 80px;" />
                </label>
            </p>
            <?php
        }
        ?>
        <p class="description"><?php esc_html_e( 'Adjust weights to control how different factors affect popularity scoring.', 'series-subscribe' ); ?></p>
        <?php
    }

    /**
     * Render tracking window field.
     */
    public function render_tracking_window_field() {
        $options = get_option( 'series_subscribe_options', array() );
        $value = isset( $options['tracking_window_minutes'] ) ? intval( $options['tracking_window_minutes'] ) : 30;
        ?>
        <input type="number" name="series_subscribe_options[tracking_window_minutes]" min="5" step="5" value="<?php echo esc_attr( $value ); ?>" style="width: 100px;" />
        <p class="description"><?php esc_html_e( 'Minimum time between view recordings for the same post by the same user.', 'series-subscribe' ); ?></p>
        <?php
    }

    /**
     * Render lookback days field.
     */
    public function render_lookback_days_field() {
        $options = get_option( 'series_subscribe_options', array() );
        $value = isset( $options['popularity_lookback_days'] ) ? intval( $options['popularity_lookback_days'] ) : 120;
        ?>
        <input type="number" name="series_subscribe_options[popularity_lookback_days]" min="1" step="1" value="<?php echo esc_attr( $value ); ?>" style="width: 100px;" />
        <p class="description"><?php esc_html_e( 'Number of days to consider when calculating popularity scores.', 'series-subscribe' ); ?></p>
        <?php
    }

    /**
     * Render carousel limit field.
     */
    public function render_carousel_limit_field() {
        $options = get_option( 'series_subscribe_options', array() );
        $value = isset( $options['featured_carousel_limit'] ) ? intval( $options['featured_carousel_limit'] ) : 10;
        ?>
        <input type="number" name="series_subscribe_options[featured_carousel_limit]" min="1" step="1" value="<?php echo esc_attr( $value ); ?>" style="width: 100px;" />
        <p class="description"><?php esc_html_e( 'Maximum number of items to show in the featured carousel.', 'series-subscribe' ); ?></p>
        <?php
    }

    /**
     * Render carousel rotation speed field.
     */
    public function render_carousel_rotation_speed_field() {
        $options = get_option( 'series_subscribe_options', array() );
        $value = isset( $options['carousel_rotation_speed'] ) ? intval( $options['carousel_rotation_speed'] ) : 5;
        ?>
        <input type="number" name="series_subscribe_options[carousel_rotation_speed]" min="1" step="1" value="<?php echo esc_attr( $value ); ?>" style="width: 100px;" />
        <p class="description"><?php esc_html_e( 'Number of seconds between automatic carousel slides.', 'series-subscribe' ); ?></p>
        <?php
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Series Subscribe Settings', 'series-subscribe' ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'series_subscribe_group' );
                do_settings_sections( 'series-subscribe' );
                ?>

                <h2><?php esc_html_e( 'Actions', 'series-subscribe' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Rebuild Popularity Scores', 'series-subscribe' ); ?></th>
                        <td>
                            <button type="button" id="rebuild-popularity" class="button button-secondary">
                                <?php esc_html_e( 'Rebuild Now', 'series-subscribe' ); ?>
                            </button>
                            <span id="rebuild-status"></span>
                            <p class="description">
                                <?php esc_html_e( 'Manually trigger popularity score recalculation. This normally runs daily via cron.', 'series-subscribe' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <script>
            jQuery(document).ready(function($) {
                $('#rebuild-popularity').on('click', function() {
                    var $btn = $(this);
                    var $status = $('#rebuild-status');

                    $btn.prop('disabled', true);
                    $status.html('<span style="color: #999;">Rebuilding...</span>');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'series_rebuild_popularity',
                            nonce: '<?php echo wp_create_nonce( 'series_rebuild_popularity' ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $status.html('<span style="color: #46b450;">✓ Rebuilt successfully!</span>');
                            } else {
                                $status.html('<span style="color: #dc3232;">✗ Error: ' + response.data + '</span>');
                            }
                            $btn.prop('disabled', false);
                            setTimeout(function() { $status.html(''); }, 3000);
                        },
                        error: function() {
                            $status.html('<span style="color: #dc3232;">✗ Request failed</span>');
                            $btn.prop('disabled', false);
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Add featured meta box to post edit screen.
     */
    public function add_featured_meta_box() {
        add_meta_box(
            'series_featured',
            __( 'Series Channels Featured', 'series-subscribe' ),
            array( $this, 'render_featured_meta_box' ),
            'post',
            'side',
            'default'
        );
    }

    /**
     * Render featured meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_featured_meta_box( $post ) {
        wp_nonce_field( 'series_featured_nonce', 'series_featured_nonce' );
        $featured = get_post_meta( $post->ID, '_series_featured', true );
        $featured_image_id = get_post_meta( $post->ID, '_series_featured_image', true );
        $featured_image_url = $featured_image_id ? wp_get_attachment_image_url( $featured_image_id, 'large' ) : '';
        ?>
        <p>
            <label>
                <input type="checkbox" name="series_featured" value="1" <?php checked( $featured, '1' ); ?> />
                <?php esc_html_e( 'Mark as Featured Post', 'series-subscribe' ); ?>
            </label>
        </p>

        <p>
            <label style="display: block; margin-bottom: 5px;">
                <strong><?php esc_html_e( 'Carousel Image', 'series-subscribe' ); ?></strong>
            </label>
            <input type="hidden" id="series_featured_image_id" name="series_featured_image_id" value="<?php echo esc_attr( $featured_image_id ); ?>" />
            <div id="series_featured_image_preview" style="margin-bottom: 10px;">
                <?php if ( $featured_image_url ) : ?>
                    <img src="<?php echo esc_url( $featured_image_url ); ?>" style="max-width: 100%; height: auto; display: block; margin-bottom: 5px;" />
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="series_featured_image_upload">
                <?php esc_html_e( $featured_image_url ? 'Change Image' : 'Select Image', 'series-subscribe' ); ?>
            </button>
            <?php if ( $featured_image_url ) : ?>
                <button type="button" class="button" id="series_featured_image_remove" style="margin-left: 5px;">
                    <?php esc_html_e( 'Remove', 'series-subscribe' ); ?>
                </button>
            <?php endif; ?>
            <p class="description" style="margin-top: 5px;">
                <?php esc_html_e( 'This image will be used in the shortcode. If not set, the default featured image will be used as fallback.', 'series-subscribe' ); ?>
            </p>
        </p>
        <?php
    }

    /**
     * Save featured meta box.
     *
     * @param int $post_id Post ID.
     */
    public function save_featured_meta_box( $post_id ) {
        if ( ! isset( $_POST['series_featured_nonce'] ) || ! wp_verify_nonce( $_POST['series_featured_nonce'], 'series_featured_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['series_featured'] ) && $_POST['series_featured'] === '1' ) {
            update_post_meta( $post_id, '_series_featured', '1' );
        } else {
            delete_post_meta( $post_id, '_series_featured' );
        }

        if ( isset( $_POST['series_featured_image_id'] ) ) {
            $image_id = absint( $_POST['series_featured_image_id'] );
            if ( $image_id ) {
                update_post_meta( $post_id, '_series_featured_image', $image_id );
            } else {
                delete_post_meta( $post_id, '_series_featured_image' );
            }
        }
    }

    /**
     * Add featured field to series edit screen.
     *
     * @param WP_Term $term Term object.
     * @param string  $taxonomy Taxonomy slug.
     */
    public function add_featured_series_field( $term, $taxonomy ) {
        $featured = get_term_meta( $term->term_id, '_series_featured', true );
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="series_featured"><?php esc_html_e( 'Featured Series', 'series-subscribe' ); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="series_featured" id="series_featured" value="1" <?php checked( $featured, '1' ); ?> />
                    <?php esc_html_e( 'Mark as Featured Series', 'series-subscribe' ); ?>
                </label>
                <p class="description"><?php esc_html_e( 'Featured series will appear in the featured carousel.', 'series-subscribe' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save featured field for series.
     *
     * @param int $term_id Term ID.
     * @param int $tt_id Term taxonomy ID.
     */
    public function save_featured_series_field( $term_id, $tt_id ) {
        // Check if featured checkbox was set
        if ( isset( $_POST['series_featured'] ) && $_POST['series_featured'] === '1' ) {
            update_term_meta( $term_id, '_series_featured', '1' );
        } else {
            delete_term_meta( $term_id, '_series_featured' );
        }
    }
}
