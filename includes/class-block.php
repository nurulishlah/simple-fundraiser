<?php
/**
 * Gutenberg Block class - Block registration for displaying campaigns
 *
 * @package SimpleFundraiser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Campaigns_Block {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Register the Gutenberg block
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type( 'simple-fundraiser/campaigns', array(
			'editor_script'   => 'sf-block-editor',
			'editor_style'    => 'sf-block-editor-style',
			'render_callback' => array( $this, 'render_block' ),
			'attributes'      => array(
				'title' => array(
					'type'    => 'string',
					'default' => '',
				),
				'layout' => array(
					'type'    => 'string',
					'default' => 'featured-grid',
				),
				'count' => array(
					'type'    => 'number',
					'default' => 3,
				),
				'orderBy' => array(
					'type'    => 'string',
					'default' => 'newest',
				),
				'showProgressBar' => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showGoal' => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showDonationCount' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'showNavArrows' => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'status' => array(
					'type'    => 'string',
					'default' => 'active',
				),
				'campaignId' => array(
					'type'    => 'number',
					'default' => 0,
				),
				'customClass' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		) );
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_editor_assets() {
		$version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : SF_VERSION;

		wp_enqueue_script(
			'sf-block-editor',
			SF_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			$version,
			true
		);

		wp_enqueue_style(
			'sf-block-editor-style',
			SF_PLUGIN_URL . 'assets/css/block-editor.css',
			array( 'wp-edit-blocks' ),
			$version
		);

		// Localize script with translations
		wp_localize_script( 'sf-block-editor', 'sfBlockData', array(
			'layouts' => array(
				array( 'value' => 'featured-grid', 'label' => __( 'Featured Grid', 'simple-fundraiser' ) ),
				array( 'value' => 'carousel', 'label' => __( 'Carousel', 'simple-fundraiser' ) ),
				array( 'value' => 'compact-list', 'label' => __( 'Compact List', 'simple-fundraiser' ) ),
				array( 'value' => 'hero-spotlight', 'label' => __( 'Hero Spotlight', 'simple-fundraiser' ) ),
			),
			'orderOptions' => array(
				array( 'value' => 'newest', 'label' => __( 'Newest', 'simple-fundraiser' ) ),
				array( 'value' => 'oldest', 'label' => __( 'Oldest', 'simple-fundraiser' ) ),
				array( 'value' => 'most_funded', 'label' => __( 'Most Funded', 'simple-fundraiser' ) ),
				array( 'value' => 'ending_soon', 'label' => __( 'Ending Soon', 'simple-fundraiser' ) ),
				array( 'value' => 'random', 'label' => __( 'Random', 'simple-fundraiser' ) ),
			),
			'statusOptions' => array(
				array( 'value' => 'active', 'label' => __( 'Active Only', 'simple-fundraiser' ) ),
				array( 'value' => 'completed', 'label' => __( 'Completed Only', 'simple-fundraiser' ) ),
				array( 'value' => 'all', 'label' => __( 'All', 'simple-fundraiser' ) ),
			),
			'campaignOptions' => SF_Widget_Renderer::get_campaign_options(),
		) );
	}

	/**
	 * Render block on frontend
	 *
	 * @param array $attributes Block attributes
	 * @return string Rendered HTML
	 */
	public function render_block( $attributes ) {
		// Convert camelCase attribute names to snake_case for renderer
		$settings = array(
			'title'               => $attributes['title'] ?? '',
			'layout'              => $attributes['layout'] ?? 'featured-grid',
			'count'               => $attributes['count'] ?? 3,
			'order_by'            => $attributes['orderBy'] ?? 'newest',
			'show_progress_bar'   => $attributes['showProgressBar'] ?? true,
			'show_goal'           => $attributes['showGoal'] ?? true,
			'show_donation_count' => $attributes['showDonationCount'] ?? false,
			'show_nav_arrows'     => $attributes['showNavArrows'] ?? true,
			'status'              => $attributes['status'] ?? 'active',
			'campaign_id'         => $attributes['campaignId'] ?? 0,
			'custom_class'        => $attributes['customClass'] ?? '',
		);

		return SF_Widget_Renderer::render( $settings );
	}
}
