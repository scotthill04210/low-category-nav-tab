<?php
/**
 * Plugin Name: LOW Category Nav Tab
 * Description: Tabbed membership category selector with a swapping content card.
 * Version: 1.1.0
 * Author: Scott Hill
 * License: GPL-2.0-or-later
 * Text Domain: low-category-nav-tab
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LOW_CNT_VERSION', '1.1.0' );
define( 'LOW_CNT_FILE', __FILE__ );
define( 'LOW_CNT_PATH', plugin_dir_path( __FILE__ ) );
define( 'LOW_CNT_URL', plugin_dir_url( __FILE__ ) );
define( 'LOW_CNT_CPT', 'low_cat_tab' );
define( 'LOW_CNT_OPTION', 'low_cnt_settings' );
define( 'LOW_CNT_META_COLOR', '_low_cnt_card_color' );
define( 'LOW_CNT_META_TEXT_COLOR', '_low_cnt_card_text_color' );
define( 'LOW_CNT_CACHE_KEY', 'low_cnt_markup_1_1_0' );

require_once LOW_CNT_PATH . 'includes/class-low-cnt-cpt.php';
require_once LOW_CNT_PATH . 'includes/class-low-cnt-metabox.php';
require_once LOW_CNT_PATH . 'includes/class-low-cnt-settings.php';
require_once LOW_CNT_PATH . 'includes/class-low-cnt-shortcode.php';
require_once LOW_CNT_PATH . 'includes/class-low-cnt-admin-list.php';

/**
 * Default color settings matching the reference mockup.
 *
 * @return array<string, string>
 */
function low_cnt_default_settings() {
	return array(
		'section_bg'          => '#EFEAE0',
		'tabbar_bg'           => '#FFFFFF',
		'active_tab_bg'       => '#EDECE7',
		'active_tab_text'     => '#1A1A1A',
		'inactive_tab_text'   => '#767676',
		'default_card_color'      => '#1D5C8B',
		'default_card_text_color' => '#FFFFFF',
	);
}

/**
 * Saved settings merged over defaults, with hex sanitization.
 *
 * @return array<string, string>
 */
function low_cnt_get_settings() {
	$saved    = get_option( LOW_CNT_OPTION, array() );
	$defaults = low_cnt_default_settings();

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	$out = array();
	foreach ( $defaults as $key => $default ) {
		$hex           = isset( $saved[ $key ] ) ? sanitize_hex_color( $saved[ $key ] ) : '';
		$out[ $key ]   = $hex ? $hex : $default;
	}

	return $out;
}

/**
 * Relative luminance of a hex color (sRGB, WCAG).
 *
 * @param string $hex Hex color.
 * @return float 0–1
 */
function low_cnt_relative_luminance( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return 0.0;
	}

	$channels = array(
		hexdec( substr( $hex, 0, 2 ) ) / 255,
		hexdec( substr( $hex, 2, 2 ) ) / 255,
		hexdec( substr( $hex, 4, 2 ) ) / 255,
	);

	foreach ( $channels as $i => $channel ) {
		$channels[ $i ] = ( $channel <= 0.03928 )
			? $channel / 12.92
			: pow( ( $channel + 0.055 ) / 1.055, 2.4 );
	}

	return ( 0.2126 * $channels[0] ) + ( 0.7152 * $channels[1] ) + ( 0.0722 * $channels[2] );
}

/**
 * Choose white or near-black text for contrast against a card color.
 *
 * @param string $hex Card background hex.
 * @return string
 */
function low_cnt_contrast_text_color( $hex ) {
	return ( low_cnt_relative_luminance( $hex ) > 0.55 ) ? '#141414' : '#FFFFFF';
}

/**
 * Card title/body color: saved per-tab value, else auto-contrast from the card background.
 *
 * @param int    $post_id    Tab post ID.
 * @param string $card_color Card background hex.
 * @return string
 */
function low_cnt_card_text_color( $post_id, $card_color ) {
	$saved = get_post_meta( $post_id, LOW_CNT_META_TEXT_COLOR, true );
	$saved = is_string( $saved ) ? sanitize_hex_color( $saved ) : '';
	if ( $saved ) {
		return $saved;
	}

	return low_cnt_contrast_text_color( $card_color );
}

/**
 * Boot CPT, shortcode, and admin UI.
 */
function low_cnt_init() {
	LOW_CNT_Cpt::init();
	LOW_CNT_Shortcode::init();
	if ( is_admin() ) {
		LOW_CNT_Metabox::init();
		LOW_CNT_Settings::init();
		LOW_CNT_Admin_List::init();
	}
}
add_action( 'plugins_loaded', 'low_cnt_init' );

/**
 * Drop the shortcode HTML cache after tabs or colors change.
 */
function low_cnt_flush_cache() {
	delete_transient( LOW_CNT_CACHE_KEY );
}
add_action( 'save_post_' . LOW_CNT_CPT, 'low_cnt_flush_cache' );
add_action( 'update_option_' . LOW_CNT_OPTION, 'low_cnt_flush_cache' );
add_action( 'add_option_' . LOW_CNT_OPTION, 'low_cnt_flush_cache' );

/**
 * Flush cache only when a Category Tab post is deleted/trashed.
 *
 * @param int $post_id Post ID.
 */
function low_cnt_flush_cache_for_post( $post_id ) {
	if ( LOW_CNT_CPT === get_post_type( $post_id ) ) {
		low_cnt_flush_cache();
	}
}
add_action( 'before_delete_post', 'low_cnt_flush_cache_for_post' );
add_action( 'trashed_post', 'low_cnt_flush_cache_for_post' );
add_action( 'untrashed_post', 'low_cnt_flush_cache_for_post' );
