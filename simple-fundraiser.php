<?php
/**
 * Plugin Name: Simple Fundraiser
 * Plugin URI: https://github.com/nurulishlah/simple-fundraiser
 * Description: A simple fundraising plugin for mosques and organizations
 * Version: 1.3.0
 * Author: Nurul Ishlah
 * Author URI: https://github.com/nurulishlah
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-fundraiser
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'SF_VERSION', '1.3.0' );
define( 'SF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once SF_PLUGIN_DIR . 'includes/class-campaign-cpt.php';
require_once SF_PLUGIN_DIR . 'includes/class-donation-cpt.php';
require_once SF_PLUGIN_DIR . 'includes/class-admin.php';
require_once SF_PLUGIN_DIR . 'includes/class-export.php';
require_once SF_PLUGIN_DIR . 'includes/class-ajax.php';
require_once SF_PLUGIN_DIR . 'includes/class-import.php';

// Excel Libraries
if ( file_exists( SF_PLUGIN_DIR . 'includes/libs/SimpleXLSX.php' ) ) {
	require_once SF_PLUGIN_DIR . 'includes/libs/SimpleXLSX.php';
}
if ( file_exists( SF_PLUGIN_DIR . 'includes/libs/SimpleXLSXGen.php' ) ) {
	require_once SF_PLUGIN_DIR . 'includes/libs/SimpleXLSXGen.php';
}

/**
 * Initialize the plugin
 */
function sf_init() {
	// Load text domain
	load_plugin_textdomain( 'simple-fundraiser', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	
	// Initialize classes
	new SF_Campaign_CPT();
	new SF_Donation_CPT();
	new SF_Admin();
	new SF_Export();
	new SF_Ajax();
	new SF_Import();
}
add_action( 'plugins_loaded', 'sf_init' );

/**
 * Activation hook
 */
function sf_activate() {
	// Register post types
	$campaign = new SF_Campaign_CPT();
	$campaign->register_post_type();
	
	$donation = new SF_Donation_CPT();
	$donation->register_post_type();
	
	// Flush rewrite rules
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sf_activate' );

/**
 * Deactivation hook
 */
function sf_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sf_deactivate' );

/**
 * Enqueue frontend styles
 */
function sf_enqueue_scripts() {
	$version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : SF_VERSION;

	wp_enqueue_style(
		'simple-fundraiser',
		SF_PLUGIN_URL . 'assets/css/frontend.css',
		array(),
		$version
	);

	wp_enqueue_script(
		'simple-fundraiser-frontend',
		SF_PLUGIN_URL . 'assets/js/frontend.js',
		array( 'jquery' ),
		$version,
		true
	);
	
	wp_enqueue_style( 'dashicons' );

	wp_localize_script( 'simple-fundraiser-frontend', 'sf_ajax_obj', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'sf_nonce' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'sf_enqueue_scripts' );

/**
 * Enqueue admin scripts
 */
function sf_admin_scripts( $hook ) {
	global $post_type;
	
	if ( in_array( $post_type, array( 'sf_campaign', 'sf_donation' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_script(
			'simple-fundraiser-admin',
			SF_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			SF_VERSION,
			true
		);
		
		// Get all campaigns and their types
		$campaign_types = array();
		$campaigns = get_posts( array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );
		
		foreach ( $campaigns as $campaign_id ) {
			$types = get_post_meta( $campaign_id, '_sf_donation_types', true );
			if ( $types ) {
				$type_list = array_map( 'trim', explode( "\n", $types ) );
				$type_list = array_filter( $type_list );
				$campaign_types[ $campaign_id ] = array_values( $type_list );
			}
		}
		
		wp_localize_script( 'simple-fundraiser-admin', 'sf_admin_data', array(
			'campaign_types' => $campaign_types
		) );
		
		wp_enqueue_style(
			'simple-fundraiser-admin',
			SF_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SF_VERSION
		);
	}
}
add_action( 'admin_enqueue_scripts', 'sf_admin_scripts' );

/**
 * Get campaign total donations
 *
 * @param int $campaign_id Campaign post ID
 * @return float Total donation amount
 */
function sf_get_campaign_total( $campaign_id ) {
	$donations = get_posts( array(
		'post_type'      => 'sf_donation',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'   => '_sf_campaign_id',
				'value' => $campaign_id,
			),
		),
		'fields'         => 'ids',
	) );
	
	$total = 0;
	foreach ( $donations as $donation_id ) {
		$amount = get_post_meta( $donation_id, '_sf_amount', true );
		$total += floatval( $amount );
	}
	
	return $total;
}

/**
 * Get campaign progress percentage
 *
 * @param int $campaign_id Campaign post ID
 * @return float Progress percentage (0-100)
 */
function sf_get_campaign_progress( $campaign_id ) {
	$goal = get_post_meta( $campaign_id, '_sf_goal', true );
	$total = sf_get_campaign_total( $campaign_id );
	
	if ( empty( $goal ) || floatval( $goal ) <= 0 ) {
		return 0;
	}
	
	$progress = ( $total / floatval( $goal ) ) * 100;
	return min( $progress, 100 );
}

/**
 * Format currency (Indonesian Rupiah)
 *
 * @param float $amount Amount to format
 * @return string Formatted amount
 */
function sf_format_currency( $amount ) {
	$options = get_option( 'sf_currency_options' );
	
	$symbol = isset( $options['symbol'] ) ? $options['symbol'] : 'Rp';
	$position = isset( $options['position'] ) ? $options['position'] : 'before';
	$thousand_sep = isset( $options['thousand_sep'] ) ? $options['thousand_sep'] : '.';
	$decimal_sep = isset( $options['decimal_sep'] ) ? $options['decimal_sep'] : ',';
	$decimals = isset( $options['decimals'] ) ? intval( $options['decimals'] ) : 0;
	
	$formatted = number_format( floatval( $amount ), $decimals, $decimal_sep, $thousand_sep );
	
	if ( 'after' === $position ) {
		return $formatted . ' ' . $symbol;
	} else {
		return $symbol . ' ' . $formatted;
	}
}

/**
 * Load custom templates for campaigns
 *
 * @param string $template Template path
 * @return string Modified template path
 */
function sf_load_templates( $template ) {
	if ( is_post_type_archive( 'sf_campaign' ) ) {
		$plugin_template = SF_PLUGIN_DIR . 'templates/archive-sf_campaign.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}
	
	if ( is_singular( 'sf_campaign' ) ) {
		$plugin_template = SF_PLUGIN_DIR . 'templates/single-sf_campaign.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}
	
	return $template;
}
add_filter( 'template_include', 'sf_load_templates' );
