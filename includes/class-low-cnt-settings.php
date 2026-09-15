<?php
/**
 * Plugin color settings (Settings API).
 *
 * @package low-category-nav-tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submenu under Category Tabs.
 */
class LOW_CNT_Settings {

	const PAGE = 'low-cnt-settings';

	/**
	 * Register menu, settings, and color-picker assets.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * @param string $hook Current admin hook.
	 */
	public static function enqueue( $hook ) {
		if ( false === strpos( (string) $hook, self::PAGE ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'low-cnt-admin-settings',
			LOW_CNT_URL . 'assets/js/admin-post.js',
			array( 'wp-color-picker' ),
			LOW_CNT_VERSION,
			true
		);
	}

	/**
	 * Settings submenu on the CPT menu.
	 */
	public static function add_page() {
		add_submenu_page(
			'edit.php?post_type=' . LOW_CNT_CPT,
			__( 'Category Tab Settings', 'low-category-nav-tab' ),
			__( 'Settings', 'low-category-nav-tab' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register the option group and color fields.
	 */
	public static function register() {
		register_setting(
			'low_cnt_settings_group',
			LOW_CNT_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => low_cnt_default_settings(),
			)
		);

		add_settings_section(
			'low_cnt_colors',
			__( 'Colors', 'low-category-nav-tab' ),
			array( __CLASS__, 'render_section' ),
			self::PAGE
		);

		$fields = array(
			'section_bg'         => __( 'Section background', 'low-category-nav-tab' ),
			'tabbar_bg'          => __( 'Tab bar background', 'low-category-nav-tab' ),
			'active_tab_bg'      => __( 'Active tab pill background', 'low-category-nav-tab' ),
			'active_tab_text'    => __( 'Active tab text color', 'low-category-nav-tab' ),
			'inactive_tab_text'  => __( 'Inactive tab text color', 'low-category-nav-tab' ),
			'default_card_color'      => __( 'Default new-post card color', 'low-category-nav-tab' ),
			'default_card_text_color' => __( 'Default new-post card text color', 'low-category-nav-tab' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				$key,
				$label,
				array( __CLASS__, 'render_color_field' ),
				self::PAGE,
				'low_cnt_colors',
				array(
					'key'   => $key,
					'label' => $label,
				)
			);
		}
	}

	/**
	 * Sanitize the option array.
	 *
	 * @param mixed $input Raw POST.
	 * @return array<string, string>
	 */
	public static function sanitize( $input ) {
		$defaults = low_cnt_default_settings();
		$out      = $defaults;

		if ( ! is_array( $input ) ) {
			return $out;
		}

		foreach ( $defaults as $key => $default ) {
			$hex         = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$out[ $key ] = $hex ? $hex : $default;
		}

		return $out;
	}

	/**
	 * Section intro.
	 */
	public static function render_section() {
		echo '<p>' . esc_html__( 'These colors apply to the shortcode layout. Each tab can still override its card background and text colors on the edit screen.', 'low-category-nav-tab' ) . '</p>';
	}

	/**
	 * @param array $args Field args with key.
	 */
	public static function render_color_field( $args ) {
		$settings = low_cnt_get_settings();
		$key      = isset( $args['key'] ) ? $args['key'] : '';
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		$defaults = low_cnt_default_settings();
		$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '#000000';
		?>
		<input
			type="text"
			class="low-cnt-color-field"
			name="<?php echo esc_attr( LOW_CNT_OPTION . '[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			data-default-color="<?php echo esc_attr( $default ); ?>"
		/>
		<?php
	}

	/**
	 * Settings form.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Category Tab Settings', 'low-category-nav-tab' ); ?></h1>
			<p>
				<?php
				echo esc_html__( 'Place the tabs on any page with the shortcode', 'low-category-nav-tab' );
				echo ' <code>[low_category_nav_tab]</code>.';
				?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'low_cnt_settings_group' );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
