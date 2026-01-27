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
		add_action( 'admin_menu', array( $this, 'add_export_menu' ) );
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
			array( $this, 'render_export_page' )
		);
	}

	/**
	 * Render export page
	 */
	public function render_export_page() {
		$campaigns = get_posts( array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Export Donations', 'simple-fundraiser' ); ?></h1>
			
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
				</table>
				
				<p class="submit">
					<input type="submit" name="sf_export_csv" class="button button-primary" value="<?php esc_attr_e( 'Download CSV', 'simple-fundraiser' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle export
	 */
	public function handle_export() {
		if ( ! isset( $_POST['sf_export_csv'] ) ) {
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

		// Generate CSV
		$filename = 'donations-' . date( 'Y-m-d-His' ) . '.csv';
		
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// CSV header
		fputcsv( $output, array(
			__( 'ID', 'simple-fundraiser' ),
			__( 'Campaign', 'simple-fundraiser' ),
			__( 'Amount', 'simple-fundraiser' ),
			__( 'Donor Name', 'simple-fundraiser' ),
			__( 'Email', 'simple-fundraiser' ),
			__( 'Phone', 'simple-fundraiser' ),
			__( 'Message', 'simple-fundraiser' ),
			__( 'Anonymous', 'simple-fundraiser' ),
			__( 'Date', 'simple-fundraiser' ),
		) );

		// CSV rows
		foreach ( $donations as $donation ) {
			$campaign_id = get_post_meta( $donation->ID, '_sf_campaign_id', true );
			$campaign_title = $campaign_id ? get_the_title( $campaign_id ) : '';
			
			fputcsv( $output, array(
				$donation->ID,
				$campaign_title,
				get_post_meta( $donation->ID, '_sf_amount', true ),
				get_post_meta( $donation->ID, '_sf_donor_name', true ),
				get_post_meta( $donation->ID, '_sf_donor_email', true ),
				get_post_meta( $donation->ID, '_sf_donor_phone', true ),
				get_post_meta( $donation->ID, '_sf_message', true ),
				get_post_meta( $donation->ID, '_sf_anonymous', true ) === '1' ? __( 'Yes', 'simple-fundraiser' ) : __( 'No', 'simple-fundraiser' ),
				get_post_meta( $donation->ID, '_sf_date', true ),
			) );
		}

		fclose( $output );
		exit;
	}
}
