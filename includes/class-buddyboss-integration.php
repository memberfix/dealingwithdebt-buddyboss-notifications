<?php
/**
 * BuddyBoss Integration Class
 *
 * @package SeriesSubscribe
 */

defined( 'ABSPATH' ) || exit;

class Series_Subscribe_BuddyBoss_Integration {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		if ( ! function_exists( 'bp_is_active' ) ) {
			return;
		}

		add_action( 'bp_activity_add_user_favorite', array( $this, 'on_activity_liked' ), 10, 2 );

		if ( function_exists( 'bb_load_reaction' ) ) {
			add_action( 'bb_reaction_after_add_user_item_reaction', array( $this, 'on_activity_reacted' ), 10, 2 );
		}
	}

	public function on_activity_liked( $activity_id, $user_id ) {
		$post_id = $this->get_post_id_from_activity( $activity_id );

		if ( ! $post_id ) {
			return;
		}

		$favorites = Series_Subscribe_Favorites::instance();

		if ( $favorites->is_favorited( $user_id, $post_id, 'post' ) ) {
			return;
		}

		$result = $favorites->toggle_favorite( $user_id, $post_id, 'post' );

		if ( $result['success'] && $result['favorited'] ) {
			do_action( 'series_subscribe_activity_favorite_synced', $user_id, $post_id, $activity_id );
		}
	}

	public function on_activity_reacted( $user_reaction_id, $args ) {
		if ( empty( $args['item_type'] ) || $args['item_type'] !== 'activity' ) {
			return;
		}

		$activity_id = isset( $args['item_id'] ) ? $args['item_id'] : 0;
		$user_id = isset( $args['user_id'] ) ? $args['user_id'] : 0;

		if ( ! $activity_id || ! $user_id ) {
			return;
		}

		$post_id = $this->get_post_id_from_activity( $activity_id );

		if ( ! $post_id ) {
			return;
		}

		$favorites = Series_Subscribe_Favorites::instance();

		if ( $favorites->is_favorited( $user_id, $post_id, 'post' ) ) {
			return;
		}

		$result = $favorites->toggle_favorite( $user_id, $post_id, 'post' );

		if ( $result['success'] && $result['favorited'] ) {
			do_action( 'series_subscribe_activity_reaction_synced', $user_id, $post_id, $activity_id, $args );
		}
	}

	private function get_post_id_from_activity( $activity_id ) {
		if ( ! class_exists( 'BP_Activity_Activity' ) ) {
			return false;
		}

		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) || $activity->component !== 'blogs' ) {
			return false;
		}

		if ( $activity->type !== 'new_blog_post' && strpos( $activity->type, 'new_blog_' ) !== 0 ) {
			return false;
		}

		$post_id = absint( $activity->secondary_item_id );

		if ( ! $post_id ) {
			return false;
		}

		$blog_id = absint( $activity->item_id );

		if ( is_multisite() && $blog_id ) {
			switch_to_blog( $blog_id );
			$post = get_post( $post_id );
			restore_current_blog();
		} else {
			$post = get_post( $post_id );
		}

		return $post ? $post_id : false;
	}

	public function is_activity_favorited( $user_id, $activity_id ) {
		if ( ! function_exists( 'bp_activity_is_favorited' ) ) {
			return false;
		}

		return bp_activity_is_favorited( $activity_id, $user_id );
	}
}
