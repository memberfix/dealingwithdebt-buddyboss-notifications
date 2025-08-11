<?php
/**
 * Series Subscribe Notifications
 *
 * @package SeriesSubscribe
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Series Subscribe Notifications Class
 * Extends BuddyBoss notification system for series subscriptions
 */
class Series_Subscribe_Notifications extends BP_Core_Notification_Abstract {

    /**
     * Instance of this class.
     *
     * @var object
     */
    private static $instance = null;

    /**
     * Get the instance of this class.
     *
     * @return Series_Subscribe_Notifications
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        if ( class_exists( 'BP_Core_Notification_Abstract' ) ) {
            $this->start();
        }
    }

    /**
     * Initialize all methods inside it.
     *
     * @return mixed|void
     */
    public function load() {
        $this->register_notification_group(
            'series_subscribe',
            esc_html__( 'Series Subscriptions', 'series-subscribe' ),
            esc_html__( 'Series Subscription Notifications Admin', 'series-subscribe' )
        );

        $this->register_series_notifications();
    }

    /**
     * Register series subscription notification types.
     */
    public function register_series_notifications() {
        $this->register_notification_type(
            'series_new_post_notification',
            esc_html__( 'New Article in Subscribed Series', 'series-subscribe' ),
            esc_html__( 'New Article in Subscribed Series Notifications', 'series-subscribe' ),
            'series_subscribe'
        );

        // Register author follow notification type
        $this->register_notification_type(
            'author_new_post_notification',
            esc_html__( 'New Article by Followed Author', 'series-subscribe' ),
            esc_html__( 'New Article by Followed Author Notifications', 'series-subscribe' ),
            'series_subscribe'
        );

        $this->register_email_type(
            'series-new-post-published',
            array(
                'email_title'         => __( 'New Article in {{series.name}}: {{post.title}}', 'series-subscribe' ),
                'email_content'       => __( 'Hi {{recipient.name}},<br><br>{{author.name}} has published a new article in the series "<strong>{{series.name}}</strong>" that you are subscribed to:<br><br><strong>{{post.title}}</strong><br><br>{{post.excerpt}}<br><br><a href="{{post.url}}">Read the full post</a><br><br>You can manage your series subscriptions in your <a href="{{profile.url}}">profile settings</a>.', 'series-subscribe' ),
                'email_plain_content' => __( 'Hi {{recipient.name}}, {{author.name}} has published a new article in the series "{{series.name}}" that you are subscribed to: {{post.title}}. {{post.excerpt}} Read the full post: {{post.url}}', 'series-subscribe' ),
                'situation_label'     => __( 'A user publishes a new article in a series you are subscribed to', 'series-subscribe' ),
                'unsubscribe_text'    => __( 'You will no longer receive emails when new articles are published in your subscribed series.', 'series-subscribe' ),
            ),
            'series_new_post_notification'
        );

        // Register author follow email type
        $this->register_email_type(
            'author-new-post-published',
            array(
                'email_title'         => __( 'New Article by {{author.name}}: {{post.title}}', 'series-subscribe' ),
                'email_content'       => __( 'Hi {{recipient.name}},<br><br>{{author.name}}, whom you follow, has published a new article:<br><br><strong>{{post.title}}</strong><br><br>{{post.excerpt}}<br><br><a href="{{post.url}}">Read the full post</a><br><br>You can manage who you follow in your <a href="{{profile.url}}">profile settings</a>.', 'series-subscribe' ),
                'email_plain_content' => __( 'Hi {{recipient.name}}, {{author.name}}, whom you follow, has published a new article: {{post.title}}. {{post.excerpt}} Read the full post: {{post.url}}', 'series-subscribe' ),
                'situation_label'     => __( 'A user you follow publishes a new article', 'series-subscribe' ),
                'unsubscribe_text'    => __( 'You will no longer receive emails when authors you follow publish new articles.', 'series-subscribe' ),
            ),
            'author_new_post_notification'
        );

        $this->register_notification(
            'series_subscribe',
            'series_new_post_published',
            'series_new_post_notification'
        );

        // Register author follow notification
        $this->register_notification(
            'series_subscribe',
            'author_new_post_published',
            'author_new_post_notification'
        );

        $this->register_notification_filter(
            __( 'Series Subscription Notifications', 'series-subscribe' ),
            array( 'series_new_post_notification' ),
            6
        );

        // Register author follow notification filter
        $this->register_notification_filter(
            __( 'Author Follow Notifications', 'series-subscribe' ),
            array( 'author_new_post_notification' ),
            7
        );
    }

    /**
     * Send notifications to subscribed users when a new post is published in a series.
     *
     * @param WP_Post $post The published post object.
     * @param WP_Term $series The series term object.
     */
    public function send_series_post_notifications( $post, $series ) {
        if ( ! function_exists( 'bp_notifications_add_notification' ) ) {
            return;
        }
        
        if ( ! function_exists( 'bp_core_current_time' ) ) {
            return;
        }
        
        $db = new Series_Subscribe_Database();
        $subscriber_ids = $db->get_series_subscribers( $series->term_id );

        if ( empty( $subscriber_ids ) ) {
            return;
        }

        foreach ( $subscriber_ids as $user_id ) {
            $user = get_user_by( 'ID', $user_id );
            if ( ! $user ) {
                continue;
            }

            bp_notifications_add_notification( array(
                'user_id'           => $user_id,
                'item_id'           => $post->ID,
                'secondary_item_id' => $series->term_id,
                'component_name'    => 'series_subscribe',
                'component_action'  => 'series_new_post_published',
                'date_notified'     => bp_core_current_time(),
            ) );
        }

        // Send notifications to followers of the author
        $this->send_author_post_notifications( $post, $subscriber_ids );
    }

    /**
     * Send notifications to followers when an author publishes a new post.
     *
     * @param WP_Post $post The published post object.
     * @param array $series_subscriber_ids Array of user IDs who are subscribed to series (to avoid duplicate notifications).
     */
    public function send_author_post_notifications( $post, $series_subscriber_ids = array() ) {
        if ( ! function_exists( 'bp_notifications_add_notification' ) ) {
            return;
        }
        
        if ( ! function_exists( 'bp_core_current_time' ) ) {
            return;
        }

        if ( ! function_exists( 'bp_is_activity_follow_active' ) || ! bp_is_activity_follow_active() ) {
            return;
        }

        if ( ! function_exists( 'bp_get_follower_ids' ) ) {
            return;
        }
        
        $author_id = $post->post_author;
        
        $follower_ids_string = bp_get_follower_ids( array( 'user_id' => $author_id ) );
        
        if ( empty( $follower_ids_string ) || $follower_ids_string == '0' ) {
            return;
        }
        
        $follower_ids = explode( ',', $follower_ids_string );
        $follower_ids = array_map( 'intval', $follower_ids );
        $follower_ids = array_filter( $follower_ids );
        
        $follower_ids = array_diff( $follower_ids, $series_subscriber_ids );
        
        foreach ( $follower_ids as $user_id ) {
            bp_notifications_add_notification( array(
                'user_id'           => $user_id,
                'item_id'           => $post->ID,
                'secondary_item_id' => $author_id,
                'component_name'    => 'series_subscribe',
                'component_action'  => 'author_new_post_published',
                'date_notified'     => bp_core_current_time(),
            ) );
        }
    }

    /**
     * Format the notifications.
     *
     * @param string $content               Notification content.
     * @param int    $item_id               Notification item ID (Post ID).
     * @param int    $secondary_item_id     Notification secondary item ID (Series ID or Author ID).
     * @param int    $action_item_count     Notification action count.
     * @param string $component_action_name Canonical notification action.
     * @param string $component_name        Notification component ID.
     * @param int    $notification_id       Notification ID.
     * @param string $screen                Notification Screen type.
     *
     * @return array
     */
    public function format_notification( $content, $item_id, $secondary_item_id, $action_item_count, $component_action_name, $component_name, $notification_id, $screen ) {
        
        if ( 'series_subscribe' === $component_name && 'series_new_post_published' === $component_action_name ) {
            $post = get_post( $item_id );
            $series = get_term( $secondary_item_id, 'series' );
            $author = get_user_by( 'ID', $post->post_author );
            
            if ( ! $post || ! $series || ! $author ) {
                return $content;
            }
            
            $text = sprintf(
                esc_html__( '%s published a new article in "%s": %s', 'series-subscribe' ),
                $author->display_name,
                $series->name,
                $post->post_title
            );
            
            $link = get_permalink( $item_id );
            
            if ( $screen == "app_push" || $screen == "web_push" ) {
                $text = sprintf(
                    esc_html__( 'New Article in %s: %s', 'series-subscribe' ),
                    $series->name,
                    $post->post_title
                );
            }
            
            return array(
                'title' => sprintf( __( 'New Article in %s', 'series-subscribe' ), $series->name ),
                'text' => $text,
                'link' => $link,
            );
        }

        // Handle author follow notifications
        if ( 'series_subscribe' === $component_name && 'author_new_post_published' === $component_action_name ) {
            $post = get_post( $item_id );
            $author = get_user_by( 'ID', $secondary_item_id );
            
            if ( ! $post || ! $author ) {
                return $content;
            }
            
            $text = sprintf(
                esc_html__( 'An article titled %s has been published by %s', 'series-subscribe' ),
                $post->post_title,
                $author->display_name
            );
            
            $link = get_permalink( $item_id );
            
            if ( $screen == "app_push" || $screen == "web_push" ) {
                $text = sprintf(
                    esc_html__( 'New Article: %s', 'series-subscribe' ),
                    $post->post_title
                );
            }
            
            return array(
                'title' => sprintf( __( '%s published a new article', 'series-subscribe' ), $author->display_name ),
                'text' => $text,
                'link' => $link,
            );
        }

        return $content;
    }

    /**
     * Get email template tokens for series notifications.
     *
     * @param array $tokens Email tokens.
     * @param int $item_id Post ID.
     * @param int $secondary_item_id Series ID or Author ID.
     * @param int $user_id Recipient user ID.
     * @return array Modified tokens.
     */
    public function get_email_tokens( $tokens, $item_id, $secondary_item_id, $user_id ) {
        $post = get_post( $item_id );
        $recipient = get_user_by( 'ID', $user_id );

        if ( $post && $recipient ) {
            $author = get_user_by( 'ID', $post->post_author );
            
            if ( $author ) {
                $tokens['post.title'] = $post->post_title;
                $tokens['post.url'] = get_permalink( $post->ID );
                $tokens['post.excerpt'] = wp_trim_words( $post->post_excerpt ?: $post->post_content, 30, '...' );
                $tokens['author.name'] = $author->display_name;
                $tokens['author.url'] = bp_core_get_user_domain( $author->ID );
                $tokens['recipient.name'] = $recipient->display_name;
                $tokens['profile.url'] = bp_core_get_user_domain( $recipient->ID );
                
                // For series notifications, also include series tokens
                $series = get_term( $secondary_item_id, 'series' );
                if ( $series && ! is_wp_error( $series ) ) {
                    $tokens['series.name'] = $series->name;
                    $tokens['series.url'] = get_term_link( $series );
                }
            }
        }

        return $tokens;
    }

    /**
     * Send test notification (for debugging).
     *
     * @param int $user_id User ID to send test notification to.
     * @param int $post_id Post ID for test.
     * @param int $series_id Series ID for test.
     */
    public function send_test_notification( $user_id, $post_id, $series_id ) {
        $post = get_post( $post_id );
        $series = get_term( $series_id, 'series' );

        if ( ! $post || ! $series ) {
            return false;
        }

        bp_notifications_add_notification( array(
            'user_id'           => $user_id,
            'item_id'           => $post_id,
            'secondary_item_id' => $series_id,
            'component_name'    => 'series_subscribe',
            'component_action'  => 'series_new_post_published',
            'date_notified'     => bp_core_current_time(),
        ) );

        return true;
    }
}
