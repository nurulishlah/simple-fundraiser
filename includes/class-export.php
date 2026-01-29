<?php
/**
 * CSV Export functionality
 *
 * @package SimpleFundraiser
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Export {

	/**
	 * Constructor
	 */
	public function __construct() {
		// add_action( 'admin_menu', array( $this, 'add_export_menu' ) ); // Moved to Settings tab
		add_action( 'admin_init', array( $this, 'handle_export' ) );
	}

	/**
	 * Add export menu
	 */
	public function add_export_menu() {
		add_submenu_page(
			'edit.php?post_type=sf_campaign',
			__( 'Export Donations', 'simple-fundraiser' ),
			__( 'Export', 'simple-fundraiser' ),
			'manage_options',
			'sf_export',
			array( $this, 'render_content' ),
			20
		);
	}

	/**
	 * Render content (for Settings tab)
	 */
	public function render_content() {
		$campaigns = get_posts( array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		?>
		<div class="wrap">
			<!-- Title handled by tabs -->
			
			<form method="post" action="">
				<?php wp_nonce_field( 'sf_export', 'sf_export_nonce' ); ?>
				
				<table class="form-table">
					<tr>
						<th><label for="sf_export_campaign"><?php esc_html_e( 'Campaign', 'simple-fundraiser' ); ?></label></th>
						<td>
							<select id="sf_export_campaign" name="sf_export_campaign">
								<option value=""><?php esc_html_e( 'All Campaigns', 'simple-fundraiser' ); ?></option>
								<?php foreach ( $campaigns as $campaign ) : ?>
									<option value="<?php echo esc_attr( $campaign->ID ); ?>">
										<?php echo esc_html( $campaign->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="sf_export_date_from"><?php esc_html_e( 'Date From', 'simple-fundraiser' ); ?></label></th>
						<td>
							<input type="date" id="sf_export_date_from" name="sf_export_date_from" class="regular-text">
						</td>
					</tr>
					<tr>
						<th><label for="sf_export_date_to"><?php esc_html_e( 'Date To', 'simple-fundraiser' ); ?></label></th>
						<td>
							<input type="date" id="sf_export_date_to" name="sf_export_date_to" class="regular-text">
						</td>
					</tr>
					<tr>
						<th><label for="sf_export_format"><?php esc_html_e( 'Format', 'simple-fundraiser' ); ?></label></th>
						<td>
							<select id="sf_export_format" name="sf_export_format">
								<option value="csv"><?php esc_html_e( 'CSV', 'simple-fundraiser' ); ?></option>
								<option value="xlsx"><?php esc_html_e( 'Excel (.xlsx)', 'simple-fundraiser' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" name="sf_export_submit" class="button button-primary" value="<?php esc_attr_e( 'Download Export', 'simple-fundraiser' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle export
	 */
	public function handle_export() {
		if ( ! isset( $_POST['sf_export_submit'] ) ) {
			return;
		}

		if ( ! isset( $_POST['sf_export_nonce'] ) || ! wp_verify_nonce( $_POST['sf_export_nonce'], 'sf_export' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Build query args
		$args = array(
			'post_type'      => 'sf_donation',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		$meta_query = array();

		// Filter by campaign
		if ( ! empty( $_POST['sf_export_campaign'] ) ) {
			$meta_query[] = array(
				'key'   => '_sf_campaign_id',
				'value' => sanitize_text_field( $_POST['sf_export_campaign'] ),
			);
		}

		// Filter by date range
		if ( ! empty( $_POST['sf_export_date_from'] ) || ! empty( $_POST['sf_export_date_to'] ) ) {
			$date_query = array(
				'key'     => '_sf_date',
				'compare' => 'BETWEEN',
			);

			$from = ! empty( $_POST['sf_export_date_from'] ) ? sanitize_text_field( $_POST['sf_export_date_from'] ) : '1970-01-01';
			$to = ! empty( $_POST['sf_export_date_to'] ) ? sanitize_text_field( $_POST['sf_export_date_to'] ) : date( 'Y-m-d' );
			
			$date_query['value'] = array( $from, $to );
			$meta_query[] = $date_query;
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$donations = get_posts( $args );
		$format    = isset( $_POST['sf_export_format'] ) ? sanitize_text_field( $_POST['sf_export_format'] ) : 'csv';

		// Prepare Data
		$data = array();
		
		// Headers
		$data[] = array(
			__( 'Date', 'simple-fundraiser' ),
			__( 'Donor Name', 'simple-fundraiser' ),
			__( 'Amount', 'simple-fundraiser' ),
			__( 'Campaign ID', 'simple-fundraiser' ),
			__( 'Email', 'simple-fundraiser' ),
			__( 'Message', 'simple-fundraiser' ),
			__( 'Donation Type', 'simple-fundraiser' ),
			__( 'Phone', 'simple-fundraiser' ),
			__( 'Anonymous', 'simple-fundraiser' ),
			__( 'Campaign Title', 'simple-fundraiser' ),
			__( 'ID', 'simple-fundraiser' ),
		);
		
		foreach ( $donations as $donation ) {
			$campaign_id = get_post_meta( $donation->ID, '_sf_campaign_id', true );
			$amount      = get_post_meta( $donation->ID, '_sf_amount', true );
			$donor_name  = get_post_meta( $donation->ID, '_sf_donor_name', true );
			$donor_email = get_post_meta( $donation->ID, '_sf_donor_email', true );
			$donor_phone = get_post_meta( $donation->ID, '_sf_donor_phone', true );
			$message     = get_post_meta( $donation->ID, '_sf_message', true );
			$anonymous   = get_post_meta( $donation->ID, '_sf_anonymous', true );
			$date        = get_post_meta( $donation->ID, '_sf_date', true );
			$type        = get_post_meta( $donation->ID, '_sf_donation_type', true );
			
			$row = array(
				$date,
				$donor_name,
				$amount,
				$campaign_id,
				$donor_email,
				$message,
				$type,
				$donor_phone,
				$anonymous === '1' ? __( 'Yes', 'simple-fundraiser' ) : __( 'No', 'simple-fundraiser' ),
				get_the_title( $campaign_id ),
				$donation->ID,
			);
			$data[] = $row;
		}

		if ( 'xlsx' === $format && class_exists( 'Shuchkin\SimpleXLSXGen' ) ) {
			$filename = 'donations-' . date( 'Y-m-d-His' ) . '.xlsx';
			$xlsx = Shuchkin\SimpleXLSXGen::fromArray( $data );
			$xlsx->downloadAs( $filename );
			exit;
		} else {
			// Default CSV
			$filename = 'donations-' . date( 'Y-m-d-His' ) . '.csv';
			
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
	
			$output = fopen( 'php://output', 'w' );
			foreach ( $data as $row ) {
				fputcsv( $output, $row );
			}
			fclose( $output );
			exit;
		}
	}
}
