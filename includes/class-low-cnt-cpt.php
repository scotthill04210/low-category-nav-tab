<?php
/**
 * Category Tab custom post type and sitemap exclusions.
 *
 * @package low-category-nav-tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers `low_cat_tab` as a UI-only, non-public post type.
 */
class LOW_CNT_Cpt {

	/**
	 * Hook registration and SEO sitemap filters.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'register_seo_exclusions' ), 20 );
		add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'exclude_core_sitemap' ) );
	}

	/**
	 * Register the CPT. No front-end URLs, archives, or search results.
	 */
	public static function register() {
		register_post_type(
			LOW_CNT_CPT,
			array(
				'labels'              => array(
					'name'               => __( 'Category Tabs', 'low-category-nav-tab' ),
					'singular_name'      => __( 'Category Tab', 'low-category-nav-tab' ),
					'add_new'            => __( 'Add New', 'low-category-nav-tab' ),
					'add_new_item'       => __( 'Add New Category Tab', 'low-category-nav-tab' ),
					'edit_item'          => __( 'Edit Category Tab', 'low-category-nav-tab' ),
					'new_item'           => __( 'New Category Tab', 'low-category-nav-tab' ),
					'view_item'          => __( 'View Category Tab', 'low-category-nav-tab' ),
					'search_items'       => __( 'Search Category Tabs', 'low-category-nav-tab' ),
					'not_found'          => __( 'No category tabs found.', 'low-category-nav-tab' ),
					'not_found_in_trash' => __( 'No category tabs found in Trash.', 'low-category-nav-tab' ),
					'menu_name'          => __( 'Category Tabs', 'low-category-nav-tab' ),
					'all_items'          => __( 'All Tabs', 'low-category-nav-tab' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'menu_icon'           => 'dashicons-networking',
				'supports'            => array( 'title', 'editor', 'page-attributes' ),
			)
		);
	}

	/**
	 * Remove this CPT from the core WordPress sitemap.
	 *
	 * @param WP_Post_Type[] $post_types Post types included in sitemaps.
	 * @return WP_Post_Type[]
	 */
	public static function exclude_core_sitemap( $post_types ) {
		unset( $post_types[ LOW_CNT_CPT ] );
		return $post_types;
	}

	/**
	 * Hook only the SEO sitemap filters whose plugins are actually loaded.
	 */
	public static function register_seo_exclusions() {
		if ( defined( 'WPSEO_VERSION' ) || function_exists( 'wpseo_init' ) || class_exists( 'WPSEO_Sitemaps', false ) ) {
			add_filter( 'wpseo_sitemap_exclude_post_type', array( __CLASS__, 'yoast_exclude' ), 10, 2 );
		}

		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath', false ) ) {
			add_filter( 'rank_math/sitemap/exclude_post_types', array( __CLASS__, 'rankmath_exclude_types' ) );
			add_filter( 'rank_math/sitemap/exclude_post_type', array( __CLASS__, 'rankmath_exclude_type' ), 10, 2 );
		}

		if ( defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' ) || class_exists( 'AIOSEO\\Plugin\\AIOSEO', false ) ) {
			add_filter( 'aioseo_sitemap_exclude_post_types', array( __CLASS__, 'aioseo_exclude_types' ) );
			add_filter( 'aioseo_sitemap_post_types', array( __CLASS__, 'aioseo_strip_type' ) );
		}
	}

	/**
	 * Yoast: exclude this post type from XML sitemaps.
	 *
	 * @param bool   $excluded  Whether already excluded.
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public static function yoast_exclude( $excluded, $post_type ) {
		return ( LOW_CNT_CPT === $post_type ) ? true : $excluded;
	}

	/**
	 * Rank Math: list of post types to exclude.
	 *
	 * @param string[] $types Post type slugs.
	 * @return string[]
	 */
	public static function rankmath_exclude_types( $types ) {
		if ( ! is_array( $types ) ) {
			$types = array();
		}
		$types[] = LOW_CNT_CPT;
		return array_values( array_unique( $types ) );
	}

	/**
	 * Rank Math: per-type exclude flag.
	 *
	 * @param bool   $exclude   Whether to exclude.
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public static function rankmath_exclude_type( $exclude, $post_type ) {
		return ( LOW_CNT_CPT === $post_type ) ? true : $exclude;
	}

	/**
	 * AIOSEO: add this CPT to the excluded types list.
	 *
	 * @param string[] $types Post type slugs.
	 * @return string[]
	 */
	public static function aioseo_exclude_types( $types ) {
		if ( ! is_array( $types ) ) {
			$types = array();
		}
		$types[] = LOW_CNT_CPT;
		return array_values( array_unique( $types ) );
	}

	/**
	 * AIOSEO: remove this CPT from included sitemap post types.
	 *
	 * @param mixed $types Post types as array or objects.
	 * @return mixed
	 */
	public static function aioseo_strip_type( $types ) {
		if ( is_array( $types ) ) {
			unset( $types[ LOW_CNT_CPT ] );
			$types = array_values( array_filter( $types, array( __CLASS__, 'aioseo_not_this_type' ) ) );
		}
		return $types;
	}

	/**
	 * @param mixed $type A post type slug or object.
	 * @return bool
	 */
	public static function aioseo_not_this_type( $type ) {
		if ( is_string( $type ) ) {
			return LOW_CNT_CPT !== $type;
		}
		if ( is_object( $type ) && isset( $type->name ) ) {
			return LOW_CNT_CPT !== $type->name;
		}
		return true;
	}
}
