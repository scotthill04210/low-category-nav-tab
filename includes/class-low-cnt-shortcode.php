<?php
/**
 * Front-end shortcode and assets.
 *
 * @package low-category-nav-tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders `[low_category_nav_tab]`.
 */
class LOW_CNT_Shortcode {

	/**
	 * Register shortcode and front-end assets.
	 */
	public static function init() {
		add_shortcode( 'low_category_nav_tab', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Register (do not print) front-end assets. Enqueued only when the shortcode runs.
	 */
	public static function register_assets() {
		wp_register_style(
			'low-cnt-frontend',
			LOW_CNT_URL . 'assets/css/frontend.css',
			array(),
			LOW_CNT_VERSION
		);
		wp_register_script(
			'low-cnt-frontend',
			LOW_CNT_URL . 'assets/js/frontend.js',
			array(),
			LOW_CNT_VERSION,
			true
		);
	}

	/**
	 * Print CSS/JS only on pages that actually render the tabs.
	 */
	private static function enqueue_assets() {
		if ( ! wp_style_is( 'low-cnt-frontend', 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_style( 'low-cnt-frontend' );
		wp_enqueue_script( 'low-cnt-frontend' );
	}

	/**
	 * One small CPT query, no term cache, no extra filters. Markup is cached.
	 *
	 * @param array|string $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render( $atts ) {
		unset( $atts );

		self::enqueue_assets();

		if ( ! is_customize_preview() ) {
			$cached = get_transient( LOW_CNT_CACHE_KEY );
			if ( is_string( $cached ) ) {
				return $cached;
			}
		}

		$posts = get_posts(
			array(
				'post_type'              => LOW_CNT_CPT,
				'post_status'            => 'publish',
				'posts_per_page'         => 20,
				'orderby'                => array(
					'menu_order' => 'ASC',
					'date'       => 'ASC',
				),
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'suppress_filters'       => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		$html = self::build_html( $posts );

		if ( ! is_customize_preview() ) {
			set_transient( LOW_CNT_CACHE_KEY, $html, DAY_IN_SECONDS );
		}

		return $html;
	}

	/**
	 * @param WP_Post[] $posts Tab posts.
	 * @return string
	 */
	private static function build_html( $posts ) {
		if ( empty( $posts ) ) {
			return '';
		}

		$settings = low_cnt_get_settings();
		$uid      = wp_unique_id( 'low-cnt-' );

		ob_start();
		?>
		<div
			class="low-cnt-wrap"
			style="<?php echo esc_attr( '--low-cnt-section-bg:' . $settings['section_bg'] . ';--low-cnt-tabbar-bg:' . $settings['tabbar_bg'] . ';--low-cnt-active-tab-bg:' . $settings['active_tab_bg'] . ';--low-cnt-active-tab-text:' . $settings['active_tab_text'] . ';--low-cnt-inactive-tab-text:' . $settings['inactive_tab_text'] . ';' ); ?>"
		>
			<div class="low-cnt-tabbar" role="tablist" aria-label="<?php echo esc_attr__( 'Membership categories', 'low-category-nav-tab' ); ?>">
				<?php foreach ( $posts as $index => $post ) : ?>
					<?php
					$selected = ( 0 === $index );
					$tab_id   = $uid . '-tab-' . (int) $post->ID;
					$panel_id = 'low-cnt-panel-' . (int) $post->ID;
					$title    = esc_html( $post->post_title );
					?>
					<button
						type="button"
						role="tab"
						id="<?php echo esc_attr( $tab_id ); ?>"
						class="low-cnt-tab<?php echo $selected ? ' is-active' : ''; ?>"
						aria-selected="<?php echo $selected ? 'true' : 'false'; ?>"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						tabindex="<?php echo $selected ? '0' : '-1'; ?>"
						data-panel="<?php echo esc_attr( (string) (int) $post->ID ); ?>"
					>
						<?php echo $title; ?>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="low-cnt-viewport">
				<div class="low-cnt-track">
					<?php foreach ( $posts as $index => $post ) : ?>
						<?php
						$active     = ( 0 === $index );
						$panel_id   = 'low-cnt-panel-' . (int) $post->ID;
						$tab_id     = $uid . '-tab-' . (int) $post->ID;
						$card_color = get_post_meta( $post->ID, LOW_CNT_META_COLOR, true );
						$card_color = sanitize_hex_color( is_string( $card_color ) ? $card_color : '' );
						if ( ! $card_color ) {
							$card_color = $settings['default_card_color'];
						}
						$text_color = low_cnt_card_text_color( (int) $post->ID, $card_color );
						?>
						<div
							role="tabpanel"
							id="<?php echo esc_attr( $panel_id ); ?>"
							class="low-cnt-panel<?php echo $active ? ' is-active' : ''; ?>"
							aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
							aria-hidden="<?php echo $active ? 'false' : 'true'; ?>"
							style="background:<?php echo esc_attr( $card_color ); ?>;color:<?php echo esc_attr( $text_color ); ?>"
						>
							<h3><?php echo esc_html( $post->post_title ); ?></h3>
							<div class="low-cnt-body">
								<?php echo wp_kses_post( wpautop( $post->post_content ) ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
