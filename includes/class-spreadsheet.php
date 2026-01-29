<?php
/**
 * Spreadsheet View for Donations
 *
 * @package SimpleFundraiser
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Spreadsheet {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add submenu page
	 */
	public function add_menu() {
		add_submenu_page(
			'edit.php?post_type=sf_campaign',
			__( 'Donations Spreadsheet', 'simple-fundraiser' ),
			__( 'Spreadsheet', 'simple-fundraiser' ),
			'manage_options',
			'sf_spreadsheet',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue assets
	 */
	public function enqueue_assets( $hook ) {
		if ( 'sf_campaign_page_sf_spreadsheet' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'sf-spreadsheet',
			SF_PLUGIN_URL . 'assets/css/spreadsheet.css',
			array(),
			SF_VERSION
		);

		wp_enqueue_script(
			'sf-spreadsheet',
			SF_PLUGIN_URL . 'assets/js/spreadsheet.js',
			array( 'jquery' ),
			SF_VERSION,
			true
		);

		wp_localize_script( 'sf-spreadsheet', 'sfSpreadsheet', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'sf_spreadsheet_nonce' ),
			'strings'   => array(
				'confirmDelete' => __( 'Are you sure you want to delete this donation?', 'simple-fundraiser' ),
				'saving'        => __( 'Saving...', 'simple-fundraiser' ),
				'saved'         => __( 'Saved!', 'simple-fundraiser' ),
				'error'         => __( 'Error saving. Please try again.', 'simple-fundraiser' ),
			),
		) );
	}

	/**
	 * Render the spreadsheet page
	 */
	public function render_page() {
		$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
		$campaigns = $this->get_campaigns();
		$donations = $this->get_donations( $campaign_id );
		$donation_types = $campaign_id ? $this->get_donation_types( $campaign_id ) : array();
		?>
		<div class="wrap sf-spreadsheet-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Donations Spreadsheet', 'simple-fundraiser' ); ?></h1>
			
			<div class="sf-spreadsheet-toolbar">
				<form method="get" class="sf-filter-form">
					<input type="hidden" name="post_type" value="sf_campaign">
					<input type="hidden" name="page" value="sf_spreadsheet">
					<label for="sf-campaign-filter"><?php esc_html_e( 'Campaign:', 'simple-fundraiser' ); ?></label>
					<select id="sf-campaign-filter" name="campaign_id">
						<option value=""><?php esc_html_e( 'All Campaigns', 'simple-fundraiser' ); ?></option>
						<?php foreach ( $campaigns as $campaign ) : ?>
							<option value="<?php echo esc_attr( $campaign->ID ); ?>" <?php selected( $campaign_id, $campaign->ID ); ?>>
								<?php echo esc_html( $campaign->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'simple-fundraiser' ); ?></button>
				</form>
				
				<button type="button" id="sf-add-row" class="button button-primary" <?php echo ! $campaign_id ? 'disabled title="' . esc_attr__( 'Select a campaign first', 'simple-fundraiser' ) . '"' : ''; ?>>
					<span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
					<?php esc_html_e( 'Add Donation', 'simple-fundraiser' ); ?>
				</button>
			</div>

			<table class="sf-spreadsheet-table widefat striped" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>">
				<thead>
					<tr>
						<th class="sf-col-id"><?php esc_html_e( 'ID', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-campaign"><?php esc_html_e( 'Campaign', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-donor"><?php esc_html_e( 'Donor Name', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-amount"><?php esc_html_e( 'Amount', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-date"><?php esc_html_e( 'Date', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-type"><?php esc_html_e( 'Type', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-anon"><?php esc_html_e( 'Anon', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-actions"><?php esc_html_e( 'Actions', 'simple-fundraiser' ); ?></th>
					</tr>
				</thead>
				<tbody id="sf-spreadsheet-body">
					<?php if ( empty( $donations ) ) : ?>
						<tr class="sf-no-data">
							<td colspan="8"><?php esc_html_e( 'No donations found. Add one using the button above.', 'simple-fundraiser' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $donations as $donation ) : ?>
							<?php $this->render_row( $donation, $campaigns ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $campaign_id ) : ?>
				<script type="text/html" id="sf-row-template">
					<?php $this->render_row( null, $campaigns, true ); ?>
				</script>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a single table row
	 */
	private function render_row( $donation = null, $campaigns = array(), $is_template = false ) {
		$is_new = is_null( $donation );
		$id = $is_new ? '{{ID}}' : $donation->ID;
		$campaign_id = $is_new ? '' : get_post_meta( $donation->ID, '_sf_campaign_id', true );
		$donor_name = $is_new ? '' : get_post_meta( $donation->ID, '_sf_donor_name', true );
		$amount = $is_new ? '' : get_post_meta( $donation->ID, '_sf_amount', true );
		$date = $is_new ? date( 'Y-m-d' ) : get_post_meta( $donation->ID, '_sf_date', true );
		$type = $is_new ? '' : get_post_meta( $donation->ID, '_sf_donation_type', true );
		$anonymous = $is_new ? '' : get_post_meta( $donation->ID, '_sf_anonymous', true );
		?>
		<tr class="sf-row <?php echo $is_new ? 'sf-new-row' : ''; ?>" data-id="<?php echo esc_attr( $id ); ?>">
			<td class="sf-col-id">
				<?php if ( $is_new ) : ?>
					<span class="sf-new-label"><?php esc_html_e( 'New', 'simple-fundraiser' ); ?></span>
				<?php else : ?>
					<?php echo esc_html( $id ); ?>
				<?php endif; ?>
			</td>
			<td class="sf-col-campaign">
				<select class="sf-cell-input" data-field="campaign_id" <?php echo $is_template ? '' : 'disabled'; ?>>
					<?php foreach ( $campaigns as $campaign ) : ?>
						<option value="<?php echo esc_attr( $campaign->ID ); ?>" <?php selected( $campaign_id, $campaign->ID ); ?>>
							<?php echo esc_html( $campaign->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<td class="sf-col-donor">
				<input type="text" class="sf-cell-input" data-field="donor_name" value="<?php echo esc_attr( $donor_name ); ?>" placeholder="<?php esc_attr_e( 'Donor name', 'simple-fundraiser' ); ?>">
			</td>
			<td class="sf-col-amount">
				<input type="number" class="sf-cell-input" data-field="amount" value="<?php echo esc_attr( $amount ); ?>" min="0" step="1" placeholder="0">
			</td>
			<td class="sf-col-date">
				<input type="date" class="sf-cell-input" data-field="date" value="<?php echo esc_attr( $date ); ?>">
			</td>
			<td class="sf-col-type">
				<input type="text" class="sf-cell-input" data-field="type" value="<?php echo esc_attr( $type ); ?>" placeholder="<?php esc_attr_e( 'Type', 'simple-fundraiser' ); ?>">
			</td>
			<td class="sf-col-anon">
				<input type="checkbox" class="sf-cell-input" data-field="anonymous" <?php checked( $anonymous, '1' ); ?>>
			</td>
			<td class="sf-col-actions">
				<?php if ( $is_new ) : ?>
					<button type="button" class="button button-small sf-save-new" title="<?php esc_attr_e( 'Save', 'simple-fundraiser' ); ?>">
						<span class="dashicons dashicons-saved"></span>
					</button>
					<button type="button" class="button button-small sf-cancel-new" title="<?php esc_attr_e( 'Cancel', 'simple-fundraiser' ); ?>">
						<span class="dashicons dashicons-no"></span>
					</button>
				<?php else : ?>
					<a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>" class="button button-small" title="<?php esc_attr_e( 'Edit', 'simple-fundraiser' ); ?>">
						<span class="dashicons dashicons-edit"></span>
					</a>
					<button type="button" class="button button-small sf-delete" title="<?php esc_attr_e( 'Delete', 'simple-fundraiser' ); ?>">
						<span class="dashicons dashicons-trash"></span>
					</button>
				<?php endif; ?>
				<span class="sf-row-status"></span>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get all campaigns
	 */
	private function get_campaigns() {
		return get_posts( array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
	}

	/**
	 * Get donations
	 */
	private function get_donations( $campaign_id = 0 ) {
		$args = array(
			'post_type'      => 'sf_donation',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);

		if ( $campaign_id ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_sf_campaign_id',
					'value' => $campaign_id,
				),
			);
		}

		return get_posts( $args );
	}

	/**
	 * Get donation types for a campaign
	 */
	private function get_donation_types( $campaign_id ) {
		$types_raw = get_post_meta( $campaign_id, '_sf_donation_types', true );
		if ( empty( $types_raw ) ) {
			return array();
		}
		return array_filter( array_map( 'trim', explode( "\n", $types_raw ) ) );
	}
}
