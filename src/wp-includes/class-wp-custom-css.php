<?php
/**
 * WordPress Custom CSS class and associated functions
 *
 * This handles the WP Custom CSS CPT,
 * including registering the Post Type
 * as well as creating "posts" for each theme
 * and fetching the actual styles.
 *
 * To directly get the output of the styles,
 * use wp_get_custom_css().
 *
 * @todo instantiate this appropriately.
 * @todo allow developers to fetch custom css by theme.
 *
 * @package WordPress
 * @since 4.7.0
 */

/**
 * Fetch the saved WP Custom CSS content.
 *
 * Wrapper for WP_Custom_CSS::get_styles().
 *
 * The optional param can be used to fetch the CSS from
 * a specific theme.
 *
 * @see WP_Custom_CSS::get_style_post_id() for more information on theme names.
 *
 * @since 4.7.0
 *
 * @param string $theme_name Optional. A Theme stylesheet name.  Defaults to the current theme.
 *
 * @return string
 */
function wp_get_custom_css( $theme_name = '' ) {
	return WP_Custom_CSS::get_styles( $theme_name );
}

/**
 * Class WP_Custom_CSS
 *
 * Note that the Custom Post Type is called "wp_custom_css."
 *
 * @package WordPress
 * @since 4.7.0
 */
class WP_Custom_CSS {

	/**
	 * The Style CPT slug.
	 *
	 * Used as a constant here to avoid duplicating code.
	 */
	const POST_TYPE_SLUG = 'wp_custom_css';

	/**
	 * The Style posts query transient label.
	 */
	const TRANSIENT_QUERY_STYLE_POSTS = 'wp_style_posts_query';

	/**
	 * WP_Custom_CSS constructor.
	 *
	 * @since 4.7.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'after_setup_theme', array( $this, 'maybe_create_posts' ) );
		add_action( 'switch_theme', array( $this, 'maybe_create_posts' ) );
		add_action( 'trash_post', array( $this, 'maybe_clear_transient_on_removal' ) );
		add_action( 'delete_post', array( $this, 'maybe_clear_transient_on_removal' ) );
	}

	/**
	 * Register the "WP Custom CSS" post type
	 *
	 * This post type is used to store
	 * the Custom CSS.
	 *
	 * @since 4.7.0
	 */
	public function register_post_type() {
		register_post_type( self::POST_TYPE_SLUG, array(
			'labels' => array(
				'name' => __( 'WP Custom CSS' ),
				'singular_name' => __( 'WP Custom CSS' ),
				'edit_item' => __( 'Edit WP Custom CSS' ),
				'add_new_item' => __( 'Add New WP Custom CSS' ),
			),
			'public'           => true,
			'show_ui'          => false,
			'hierarchical'     => false,
			'rewrite'          => false,
			'menu_position'    => 50,
			'query_var'        => false,
			'delete_with_user' => true,
			'_builtin'         => true, /* internal use only. don't use this when registering your own post type. */
			'supports'         => array( 'title', 'revisions' ),
			'capabilities'     => array(
				'delete_posts'           => 'edit_theme_options',
				'delete_post'            => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'delete_private_posts'   => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'edit_post'              => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
				'edit_others_posts'      => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'read_post'              => 'read',
				'read_private_posts'     => 'read',
				'publish_posts'          => 'edit_theme_options',
			),
		) );
	}

	/**
	 * Ensure Style posts exist for all installed themes
	 *
	 * @since 4.7.0
	 *
	 * @return bool
	 */
	public function maybe_create_posts() {
		$updated = false;

		// Get all Style posts.
		$style_posts = self::get_style_posts();
		if ( ! is_array( $style_posts ) ) {
			return false;
		}

		// Get all themes.
		$themes = self::get_installed_theme_names();
		if ( empty( $themes ) || ! is_array( $themes ) ) {
			return false;
		}
		foreach ( $themes as $theme_name ) {
			if ( ! isset( $style_posts[ $theme_name ] ) ) {
				wp_insert_post( array(
					'post_title' => $theme_name,
					'post_status' => 'publish',
					'post_type' => self::POST_TYPE_SLUG,
				) );
				$updated = true;
			}
		}

		if ( $updated ) {
			self::clear_transient();
		}
		return true;
	}

	/**
	 * Get all posts in the "wp_custom_css" CPT.
	 *
	 * This uses a transient to store values for 12 hours.
	 *
	 * @since 4.7.0
	 *
	 * @return array Array of Post Title => Post ID pairs.
	 */
	public static function get_style_posts() {
		$posts = get_transient( self::TRANSIENT_QUERY_STYLE_POSTS );

		if ( empty( $posts ) ) {
			$posts = array();
			$query = new WP_Query( array(
				'post_type'              => self::POST_TYPE_SLUG,
				'post_status'            => 'publish',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			) );

			if ( ! empty( $query->posts ) && is_array( $query->posts ) ) {
				foreach ( $query->posts as $post ) {
					if ( empty( $post->post_title ) ) {
						continue;
					}
					$posts[ $post->post_title ] = $post->ID;
				}
			}

			set_transient( self::TRANSIENT_QUERY_STYLE_POSTS, $posts, 12 * HOUR_IN_SECONDS );
		}
		return $posts;
	}

	/**
	 * Fire clear_transient() on post removal.
	 *
	 * Checks for the Style post type.
	 * If one is being trashed or deleted, then
	 * delete the transient.
	 *
	 * @since 4.7.0
	 *
	 * @param int $post_id WP post ID.
	 *
	 * @return bool
	 */
	public function maybe_clear_transient_on_removal( $post_id ) {
		if ( self::POST_TYPE_SLUG === get_post_type( $post_id ) ) {
			self::clear_transient();
			return true;
		}
		return false;
	}

	/**
	 * Clean up
	 *
	 * Delete our transient.
	 *
	 * @since 4.7.0
	 */
	public static function clear_transient() {
		delete_transient( self::TRANSIENT_QUERY_STYLE_POSTS );
	}

	/**
	 * Find a style post's ID
	 *
	 * Defaults to finding the current theme's Style CPT post ID.
	 * Optionally, this can find a Style CPT Post ID if a stylesheet
	 * name (e.g., "twentyfifteen") is provided.
	 *
	 * @since 4.7.0
	 *
	 * @param string $theme_name Optional. A Theme stylesheet name.  Defaults to the current theme.
	 *
	 * @return mixed Int of post ID, else false if not found
	 */
	public static function get_style_post_id( $theme_name = '' ) {

		// By default, use the current theme.
		if ( empty( $theme_name ) || ! is_string( $theme_name ) ) {
			$theme = wp_get_theme();
			$theme_name = $theme->stylesheet;
		}

		$style_posts = self::get_style_posts();

		if ( ! empty( $style_posts[ $theme_name ] ) && is_numeric( $style_posts[ $theme_name ] ) ) {
			return $style_posts[ $theme_name ];
		}

		return false;
	}

	/**
	 * Get the installed theme stylesheet names
	 *
	 * To keep things simple, we'll use the slug-style
	 * stylesheet value of each theme.
	 *
	 * WP's default themes (e.g., "Twenty Fifteen" ) are named
	 * with a slug-style string (e.g., "twentyfifteen") in class WP_Theme,
	 * as opposed to all other themes, whose "Names" are usually capitalized
	 * and may contain spaces.
	 *
	 * To account for this, we're using the stylesheet names
	 * instead of the true theme Names.
	 *
	 * @see WP_Theme->default_themes
	 *
	 * @since 4.7.0
	 *
	 * @return array An array of theme names.
	 */
	public static function get_installed_theme_names() {
		$theme_names = array();
		$themes = wp_get_themes();
		foreach ( $themes as $name => $data ) {
			$theme_names[] = $data->stylesheet;
		}
		return $theme_names;
	}

	/**
	 * Get the styles
	 *
	 * Gets the content of a Style post that matches the
	 * current theme.
	 *
	 * @since 4.7.0
	 *
	 * @param string $theme_name Optional. A Theme stylesheet name.  Defaults to the current theme.
	 *
	 * @return string The Style Post content.
	 */
	public static function get_styles( $theme_name = '' ) {
		$post_id = self::get_style_post_id( $theme_name );
		$style_post = get_post( $post_id );
		return $style_post->post_content;
	}
}

/**
 * @todo instantiate this appropriately.
 */
$wp_custom_css = new WP_Custom_CSS();
