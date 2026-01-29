<?php
/**
 * Spreadsheet View for Distributions
 *
 * @package SimpleFundraiser
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Distribution_Spreadsheet {

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
			__( 'Distribution Spreadsheet', 'simple-fundraiser' ),
			__( 'Distribution Spreadsheet', 'simple-fundraiser' ),
			'manage_options',
			'sf_distribution_spreadsheet',
			array( $this, 'render_page' ),
			13
		);
	}

	/**
	 * Enqueue assets
	 */
	public function enqueue_assets( $hook ) {
		if ( 'sf_campaign_page_sf_distribution_spreadsheet' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'sf-spreadsheet',
			SF_PLUGIN_URL . 'assets/css/spreadsheet.css',
			array(),
			SF_VERSION
		);

		wp_enqueue_media();

		wp_enqueue_script(
			'sf-distribution-spreadsheet',
			SF_PLUGIN_URL . 'assets/js/distribution-spreadsheet.js',
			array( 'jquery' ),
			SF_VERSION,
			true
		);

		wp_localize_script( 'sf-distribution-spreadsheet', 'sfDistSpreadsheet', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'sf_dist_spreadsheet_nonce' ),
			'strings'   => array(
				'confirmDelete'     => __( 'Are you sure you want to delete this distribution?', 'simple-fundraiser' ),
				'confirmBulkDelete' => __( 'Are you sure you want to delete %d distributions?', 'simple-fundraiser' ),
				'saving'            => __( 'Saving...', 'simple-fundraiser' ),
				'saved'             => __( 'Saved!', 'simple-fundraiser' ),
				'error'             => __( 'Error saving. Please try again.', 'simple-fundraiser' ),
				'selected'          => __( 'selected', 'simple-fundraiser' ),
			),
		) );
	}

	/**
	 * Render the spreadsheet page
	 */
	public function render_page() {
		$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
		$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page = 50;
		
		$campaigns = $this->get_campaigns();
		$query = $this->get_items_query( $campaign_id, $paged, $per_page );
		$items = $query->posts;
		$total_pages = $query->max_num_pages;
		$total_items = $query->found_posts;
		$types = $campaign_id ? $this->get_types( $campaign_id ) : array();
		?>
		<div class="wrap sf-spreadsheet-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Distributions Spreadsheet', 'simple-fundraiser' ); ?></h1>
			
			<div class="sf-spreadsheet-toolbar">
				<form method="get" class="sf-filter-form">
					<input type="hidden" name="post_type" value="sf_campaign">
					<input type="hidden" name="page" value="sf_distribution_spreadsheet">
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
					<?php esc_html_e( 'Add Distribution', 'simple-fundraiser' ); ?>
				</button>
			</div>

			<div class="sf-bulk-actions" style="display: none;">
				<span class="sf-selected-count">0 <?php esc_html_e( 'selected', 'simple-fundraiser' ); ?></span>
				<select id="sf-bulk-action">
					<option value=""><?php esc_html_e( 'Bulk Actions', 'simple-fundraiser' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'simple-fundraiser' ); ?></option>
					<?php if ( $campaign_id && ! empty( $types ) ) : ?>
						<option value="change_type"><?php esc_html_e( 'Change Type', 'simple-fundraiser' ); ?></option>
					<?php endif; ?>
				</select>
				<?php if ( $campaign_id && ! empty( $types ) ) : ?>
					<select id="sf-bulk-type" style="display: none;">
						<option value=""><?php esc_html_e( '— Select Type —', 'simple-fundraiser' ); ?></option>
						<?php foreach ( $types as $type ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
				<button type="button" id="sf-apply-bulk" class="button"><?php esc_html_e( 'Apply', 'simple-fundraiser' ); ?></button>
				<button type="button" id="sf-clear-selection" class="button"><?php esc_html_e( 'Clear Selection', 'simple-fundraiser' ); ?></button>
			</div>

			<table class="sf-spreadsheet-table widefat striped" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>">
				<thead>
					<tr>
						<th class="sf-col-check"><input type="checkbox" id="sf-select-all" title="<?php esc_attr_e( 'Select All', 'simple-fundraiser' ); ?>"></th>
						<th class="sf-col-id"><?php esc_html_e( 'ID', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-campaign"><?php esc_html_e( 'Campaign', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-date"><?php esc_html_e( 'Date', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-recipient"><?php esc_html_e( 'Recipient', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-desc"><?php esc_html_e( 'Description', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-amount"><?php esc_html_e( 'Amount', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-type"><?php esc_html_e( 'Type', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-proof"><?php esc_html_e( 'Proof', 'simple-fundraiser' ); ?></th>
						<th class="sf-col-actions"><?php esc_html_e( 'Actions', 'simple-fundraiser' ); ?></th>
					</tr>
				</thead>
				<tbody id="sf-spreadsheet-body">
					<?php if ( empty( $items ) ) : ?>
						<tr class="sf-no-data">
							<td colspan="10"><?php esc_html_e( 'No distributions found. Add one using the button above.', 'simple-fundraiser' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $items as $item ) : ?>
							<?php 
							$row_campaign_id = get_post_meta( $item->ID, '_sf_campaign_id', true );
							$row_types = $row_campaign_id ? $this->get_types( $row_campaign_id ) : array();
							$this->render_row( $item, $campaigns, false, $row_types ); 
							?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $campaign_id ) : ?>
				<script type="text/html" id="sf-row-template">
					<?php $this->render_row( null, $campaigns, true, $types ); ?>
				</script>
			<?php endif; ?>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="sf-pagination">
					<span class="sf-pagination-info">
						<?php 
						printf( 
							esc_html__( 'Page %1$d of %2$d (%3$d items)', 'simple-fundraiser' ), 
							$paged, 
							$total_pages, 
							$total_items 
						); 
						?>
					</span>
					<span class="sf-pagination-links">
						<?php
						$base_url = admin_url( 'edit.php?post_type=sf_campaign&page=sf_distribution_spreadsheet' );
						if ( $campaign_id ) {
							$base_url .= '&campaign_id=' . $campaign_id;
						}
						
						if ( $paged > 1 ) :
						?>
							<a href="<?php echo esc_url( $base_url . '&paged=' . ( $paged - 1 ) ); ?>" class="button">&laquo; <?php esc_html_e( 'Previous', 'simple-fundraiser' ); ?></a>
						<?php endif; ?>
						
						<?php if ( $paged < $total_pages ) : ?>
							<a href="<?php echo esc_url( $base_url . '&paged=' . ( $paged + 1 ) ); ?>" class="button"><?php esc_html_e( 'Next', 'simple-fundraiser' ); ?> &raquo;</a>
						<?php endif; ?>
					</span>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a single table row
	 */
	private function render_row( $post = null, $campaigns = array(), $is_template = false, $types = array() ) {
		$is_new = is_null( $post );
		$id = $is_new ? '{{ID}}' : $post->ID;
		$campaign_id = $is_new ? '' : get_post_meta( $post->ID, '_sf_campaign_id', true );
		$amount      = $is_new ? '' : get_post_meta( $post->ID, '_sf_dist_amount', true );
		$date        = $is_new ? date( 'Y-m-d' ) : get_post_meta( $post->ID, '_sf_dist_date', true );
		$type        = $is_new ? '' : get_post_meta( $post->ID, '_sf_dist_type', true );
		$recipient   = $is_new ? '' : get_post_meta( $post->ID, '_sf_dist_recipient', true );
		$description = $is_new ? '' : get_post_meta( $post->ID, '_sf_dist_description', true );
		$proof_id    = $is_new ? '' : get_post_meta( $post->ID, '_sf_dist_proof', true );
		$proof_url   = $proof_id ? wp_get_attachment_url( $proof_id ) : '';
		?>
		<tr class="sf-row <?php echo $is_new ? 'sf-new-row' : ''; ?>" data-id="<?php echo esc_attr( $id ); ?>">
			<td class="sf-col-check">
				<?php if ( ! $is_new ) : ?>
					<input type="checkbox" class="sf-row-select" value="<?php echo esc_attr( $id ); ?>">
				<?php endif; ?>
			</td>
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
			<td class="sf-col-date">
				<input type="date" class="sf-cell-input" data-field="date" value="<?php echo esc_attr( $date ); ?>">
			</td>
			<td class="sf-col-recipient">
				<input type="text" class="sf-cell-input" data-field="recipient" value="<?php echo esc_attr( $recipient ); ?>" placeholder="<?php esc_attr_e( 'Recipient', 'simple-fundraiser' ); ?>">
			</td>
			<td class="sf-col-desc">
				<input type="text" class="sf-cell-input" data-field="description" value="<?php echo esc_attr( $description ); ?>" placeholder="<?php esc_attr_e( 'Description', 'simple-fundraiser' ); ?>">
			</td>
			<td class="sf-col-amount">
				<input type="number" class="sf-cell-input" data-field="amount" value="<?php echo esc_attr( $amount ); ?>" min="0" step="1" placeholder="0">
			</td>
			<td class="sf-col-type">
				<?php if ( ! empty( $types ) ) : ?>
					<select class="sf-cell-input" data-field="type">
						<option value=""><?php esc_html_e( '— Select —', 'simple-fundraiser' ); ?></option>
						<?php foreach ( $types as $dtype ) : ?>
							<option value="<?php echo esc_attr( $dtype ); ?>" <?php selected( $type, $dtype ); ?>>
								<?php echo esc_html( $dtype ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input type="text" class="sf-cell-input" data-field="type" value="<?php echo esc_attr( $type ); ?>" placeholder="<?php esc_attr_e( 'Type', 'simple-fundraiser' ); ?>">
				<?php endif; ?>
			</td>
			<td class="sf-col-proof">
				<div class="sf-proof-wrapper">
					<input type="hidden" class="sf-cell-input" data-field="proof_id" value="<?php echo esc_attr( $proof_id ); ?>">
					<?php if ( $proof_url ) : ?>
						<a href="<?php echo esc_url( $proof_url ); ?>" target="_blank" class="sf-proof-link"><span class="dashicons dashicons-media-default"></span></a>
					<?php endif; ?>
					<button type="button" class="button button-small sf-upload-proof" title="<?php esc_attr_e( 'Upload/Select Proof', 'simple-fundraiser' ); ?>">
						<span class="dashicons dashicons-upload"></span>
					</button>
				</div>
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
	 * Get items query
	 */
	private function get_items_query( $campaign_id = 0, $paged = 1, $per_page = 50 ) {
		$args = array(
			'post_type'      => 'sf_distribution',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
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

		return new WP_Query( $args );
	}

	/**
	 * Get types for a campaign
	 */
	private function get_types( $campaign_id ) {
		$types = array();
		
		// Dist types
		$types_raw = get_post_meta( $campaign_id, '_sf_distribution_types', true );
		if ( ! empty( $types_raw ) ) {
			$types = array_filter( array_map( 'trim', explode( "\n", $types_raw ) ) );
		}
		
		// Donation types
		$d_types = get_post_meta( $campaign_id, '_sf_donation_types', true );
		if ( ! empty( $d_types ) ) {
			$d_arr = array_filter( array_map( 'trim', explode( "\n", $d_types ) ) );
			$types = array_merge( $types, $d_arr );
		}
		
		return array_unique( $types );
	}
}
