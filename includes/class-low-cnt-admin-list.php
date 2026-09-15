<?php
/**
 * Drag-to-reorder on the Category Tabs list table.
 *
 * @package low-category-nav-tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * menu_order column + AJAX save.
 */
class LOW_CNT_Admin_List {

	/**
	 * Register list-table hooks.
	 */
	public static function init() {
		add_filter( 'manage_' . LOW_CNT_CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . LOW_CNT_CPT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . LOW_CNT_CPT . '_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'admin_order' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_low_cnt_reorder', array( __CLASS__, 'ajax_reorder' ) );
	}

	/**
	 * Default admin list to menu_order.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function admin_order( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( LOW_CNT_CPT !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}

	/**
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['low_cnt_order'] = __( 'Order', 'low-category-nav-tab' );
			}
			$new[ $key ] = $label;
		}
		return $new;
	}

	/**
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public static function sortable( $columns ) {
		$columns['low_cnt_order'] = 'menu_order';
		return $columns;
	}

	/**
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'low_cnt_order' !== $column ) {
			return;
		}
		$post = get_post( $post_id );
		echo '<span class="low-cnt-order-handle dashicons dashicons-menu" title="' . esc_attr__( 'Drag to reorder', 'low-category-nav-tab' ) . '"></span> ';
		echo '<span class="low-cnt-order-value">' . esc_html( (string) ( $post ? $post->menu_order : 0 ) ) . '</span>';
	}

	/**
	 * @param string $hook Admin hook.
	 */
	public static function enqueue( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || LOW_CNT_CPT !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style(
			'low-cnt-admin-list',
			LOW_CNT_URL . 'assets/css/admin.css',
			array(),
			LOW_CNT_VERSION
		);
		wp_enqueue_script(
			'low-cnt-admin-list',
			LOW_CNT_URL . 'assets/js/admin-list.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			LOW_CNT_VERSION,
			true
		);
		wp_localize_script(
			'low-cnt-admin-list',
			'lowCntList',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'low_cnt_reorder' ),
			)
		);
	}

	/**
	 * Save drag order as menu_order.
	 */
	public static function ajax_reorder() {
		check_ajax_referer( 'low_cnt_reorder', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$ids = isset( $_POST['ids'] ) ? wp_unslash( $_POST['ids'] ) : array();
		if ( ! is_array( $ids ) ) {
			wp_send_json_error( array( 'message' => 'bad ids' ), 400 );
		}

		$ids = array_slice( array_map( 'absint', $ids ), 0, 20 );
		$ids = array_values( array_filter( $ids ) );
		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'bad ids' ), 400 );
		}

		global $wpdb;

		foreach ( $ids as $index => $id ) {
			if ( LOW_CNT_CPT !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}

			$wpdb->update(
				$wpdb->posts,
				array( 'menu_order' => (int) $index ),
				array(
					'ID'        => $id,
					'post_type' => LOW_CNT_CPT,
				),
				array( '%d' ),
				array( '%d', '%s' )
			);
			clean_post_cache( $id );
		}

		low_cnt_flush_cache();
		wp_send_json_success();
	}
}
