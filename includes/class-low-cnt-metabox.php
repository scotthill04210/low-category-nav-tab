<?php
/**
 * Per-tab card color meta box.
 *
 * @package low-category-nav-tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Color picker for `_low_cnt_card_color`.
 */
class LOW_CNT_Metabox {

	/**
	 * Register meta box, assets, and save hook.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'save_post_' . LOW_CNT_CPT, array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || LOW_CNT_CPT !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'low-cnt-admin-post',
			LOW_CNT_URL . 'assets/js/admin-post.js',
			array( 'wp-color-picker' ),
			LOW_CNT_VERSION,
			true
		);
	}

	/**
	 * Add the Card color box.
	 */
	public static function register() {
		add_meta_box(
			'low_cnt_card_color',
			__( 'Card color', 'low-category-nav-tab' ),
			array( __CLASS__, 'render' ),
			LOW_CNT_CPT,
			'side',
			'high'
		);
	}

	/**
	 * @param WP_Post $post Current post.
	 */
	public static function render( $post ) {
		wp_nonce_field( 'low_cnt_save_color', 'low_cnt_color_nonce' );

		$saved = get_post_meta( $post->ID, LOW_CNT_META_COLOR, true );
		$saved = is_string( $saved ) ? sanitize_hex_color( $saved ) : '';

		$settings = low_cnt_get_settings();
		$bg       = is_string( $saved ) ? sanitize_hex_color( $saved ) : '';
		$bg       = $bg ? $bg : $settings['default_card_color'];

		$saved_text = get_post_meta( $post->ID, LOW_CNT_META_TEXT_COLOR, true );
		$saved_text = is_string( $saved_text ) ? sanitize_hex_color( $saved_text ) : '';
		$text = $saved_text ? $saved_text : ( $saved ? low_cnt_contrast_text_color( $bg ) : $settings['default_card_text_color'] );
		?>
		<p>
			<label for="low_cnt_card_color">
				<?php esc_html_e( 'Background color', 'low-category-nav-tab' ); ?>
			</label>
		</p>
		<input
			type="text"
			id="low_cnt_card_color"
			name="low_cnt_card_color"
			value="<?php echo esc_attr( $bg ); ?>"
			class="low-cnt-color-field"
			data-default-color="<?php echo esc_attr( $settings['default_card_color'] ); ?>"
		/>
		<p>
			<label for="low_cnt_card_text_color">
				<?php esc_html_e( 'Text color', 'low-category-nav-tab' ); ?>
			</label>
		</p>
		<input
			type="text"
			id="low_cnt_card_text_color"
			name="low_cnt_card_text_color"
			value="<?php echo esc_attr( $text ); ?>"
			class="low-cnt-color-field"
			data-default-color="<?php echo esc_attr( $settings['default_card_text_color'] ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'New tabs start from the default colors in Settings. You can override both per tab.', 'low-category-nav-tab' ); ?>
		</p>
		<?php
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['low_cnt_color_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['low_cnt_color_nonce'] ) ), 'low_cnt_save_color' ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! $post || LOW_CNT_CPT !== $post->post_type ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['low_cnt_card_color'] ) && ! isset( $_POST['low_cnt_card_text_color'] ) ) {
			return;
		}

		if ( isset( $_POST['low_cnt_card_color'] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST['low_cnt_card_color'] ) );
			if ( $color ) {
				update_post_meta( $post_id, LOW_CNT_META_COLOR, $color );
			} else {
				delete_post_meta( $post_id, LOW_CNT_META_COLOR );
			}
		}

		if ( isset( $_POST['low_cnt_card_text_color'] ) ) {
			$text = sanitize_hex_color( wp_unslash( $_POST['low_cnt_card_text_color'] ) );
			if ( $text ) {
				update_post_meta( $post_id, LOW_CNT_META_TEXT_COLOR, $text );
			} else {
				delete_post_meta( $post_id, LOW_CNT_META_TEXT_COLOR );
			}
		}
	}
}
