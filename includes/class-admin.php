<?php
/**
 * Admin functionality
 *
 * @package SimpleFundraiser
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
	}

	/**
	 * Add admin menu items
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=sf_campaign',
			__( 'Fundraiser Settings', 'simple-fundraiser' ),
			__( 'Settings', 'simple-fundraiser' ),
			'manage_options',
			'sf_settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Simple Fundraiser Settings', 'simple-fundraiser' ); ?></h1>
			<p><?php esc_html_e( 'Configure your fundraising plugin settings here.', 'simple-fundraiser' ); ?></p>
			
			<h2><?php esc_html_e( 'Quick Stats', 'simple-fundraiser' ); ?></h2>
			<?php $this->render_stats(); ?>
			
			<h2><?php esc_html_e( 'Information', 'simple-fundraiser' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Archive Page URL', 'simple-fundraiser' ); ?></th>
					<td><code><?php echo esc_url( get_post_type_archive_link( 'sf_campaign' ) ); ?></code></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Plugin Version', 'simple-fundraiser' ); ?></th>
					<td><?php echo esc_html( SF_VERSION ); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render quick stats
	 */
	public function render_stats() {
		$campaigns = wp_count_posts( 'sf_campaign' );
		$donations = wp_count_posts( 'sf_donation' );
		
		// Calculate total donations
		$all_donations = get_posts( array(
			'post_type'      => 'sf_donation',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		) );
		
		$total_amount = 0;
		foreach ( $all_donations as $donation_id ) {
			$total_amount += floatval( get_post_meta( $donation_id, '_sf_amount', true ) );
		}
		?>
		<div class="sf-stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
			<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
				<h3 style="margin: 0 0 10px; color: #1e88e5;"><?php echo esc_html( $campaigns->publish ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Active Campaigns', 'simple-fundraiser' ); ?></p>
			</div>
			<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
				<h3 style="margin: 0 0 10px; color: #43a047;"><?php echo esc_html( $donations->publish ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Total Donations', 'simple-fundraiser' ); ?></p>
			</div>
			<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
				<h3 style="margin: 0 0 10px; color: #e53935;"><?php echo esc_html( sf_format_currency( $total_amount ) ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Total Raised', 'simple-fundraiser' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Add dashboard widget
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'sf_dashboard_widget',
			__( 'Fundraiser Overview', 'simple-fundraiser' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget
	 */
	public function render_dashboard_widget() {
		$campaigns = get_posts( array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => 5,
			'post_status'    => 'publish',
		) );
		
		if ( empty( $campaigns ) ) {
			echo '<p>' . esc_html__( 'No campaigns yet.', 'simple-fundraiser' ) . '</p>';
			return;
		}
		
		echo '<table style="width: 100%;">';
		echo '<thead><tr><th style="text-align: left;">' . esc_html__( 'Campaign', 'simple-fundraiser' ) . '</th><th style="text-align: right;">' . esc_html__( 'Progress', 'simple-fundraiser' ) . '</th></tr></thead>';
		echo '<tbody>';
		
		foreach ( $campaigns as $campaign ) {
			$progress = sf_get_campaign_progress( $campaign->ID );
			$total = sf_get_campaign_total( $campaign->ID );
			$goal = get_post_meta( $campaign->ID, '_sf_goal', true );
			
			echo '<tr>';
			echo '<td><a href="' . esc_url( get_edit_post_link( $campaign->ID ) ) . '">' . esc_html( $campaign->post_title ) . '</a><br>';
			echo '<small>' . esc_html( sf_format_currency( $total ) ) . ' / ' . esc_html( sf_format_currency( $goal ) ) . '</small></td>';
			echo '<td style="text-align: right;"><strong>' . esc_html( round( $progress, 1 ) ) . '%</strong></td>';
			echo '</tr>';
		}
		
		echo '</tbody></table>';
		echo '<p style="margin-top: 15px;"><a href="' . esc_url( admin_url( 'edit.php?post_type=sf_campaign' ) ) . '" class="button">' . esc_html__( 'View All Campaigns', 'simple-fundraiser' ) . '</a></p>';
	}
}
