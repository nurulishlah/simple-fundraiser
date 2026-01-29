<?php
/**
 * AJAX Handler class
 *
 * @package SimpleFundraiser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_sf_get_donations', array( $this, 'get_donations' ) );
		add_action( 'wp_ajax_nopriv_sf_get_donations', array( $this, 'get_donations' ) );
		
		// Spreadsheet AJAX handlers
		add_action( 'wp_ajax_sf_spreadsheet_save', array( $this, 'spreadsheet_save' ) );
		add_action( 'wp_ajax_sf_spreadsheet_add', array( $this, 'spreadsheet_add' ) );
		add_action( 'wp_ajax_sf_spreadsheet_bulk_delete', array( $this, 'spreadsheet_bulk_delete' ) );
		add_action( 'wp_ajax_sf_spreadsheet_bulk_update', array( $this, 'spreadsheet_bulk_update' ) );
		
		// Campaign types AJAX
		add_action( 'wp_ajax_sf_get_campaign_types', array( $this, 'get_campaign_types' ) );
		
		// Distribution Report AJAX
		add_action( 'wp_ajax_sf_get_distributions', array( $this, 'get_distributions' ) );
		add_action( 'wp_ajax_nopriv_sf_get_distributions', array( $this, 'get_distributions' ) );
		add_action( 'wp_ajax_sf_verify_dist_password', array( $this, 'verify_dist_password' ) );
		add_action( 'wp_ajax_nopriv_sf_verify_dist_password', array( $this, 'verify_dist_password' ) );
	}

	/**
	 * Verify distribution password
	 */
	public function verify_dist_password() {
		check_ajax_referer( 'sf_nonce', 'nonce' );

		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : 0;
		$password    = isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';

		if ( ! $campaign_id || ! $password ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request', 'simple-fundraiser' ) ) );
		}

		$real_password = get_post_meta( $campaign_id, '_sf_dist_password', true );

		if ( $password === $real_password ) {
			// Generate a simple token (in real app, use transient or session)
			$token = wp_hash( $campaign_id . $password . 'sf_dist_auth' );
			set_transient( 'sf_dist_auth_' . $token, $campaign_id, HOUR_IN_SECONDS );
			
			wp_send_json_success( array(
				'token' => $token
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Incorrect password', 'simple-fundraiser' ) ) );
		}
	}

	/**
	 * Get distributions HTML
	 */
	public function get_distributions() {
		check_ajax_referer( 'sf_nonce', 'nonce' );

		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : 0;
		$page        = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$token       = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign', 'simple-fundraiser' ) ) );
		}

		// Check visibility settings
		$visibility = get_post_meta( $campaign_id, '_sf_dist_visibility', true );
		if ( ! $visibility ) {
			$visibility = 'public';
		}

		if ( 'private' === $visibility && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'This report is private.', 'simple-fundraiser' ) ) );
		}

		if ( 'password' === $visibility ) {
			// specific check
			$valid_token = get_transient( 'sf_dist_auth_' . $token );
			if ( $valid_token != $campaign_id ) {
				wp_send_json_error( array( 'message' => __( 'Authentication required.', 'simple-fundraiser' ) ) );
			}
		}

		// Fetch distributions
		$args = array(
			'posts_per_page' => 10,
			'paged'          => $page,
		);
		$query = SF_Distribution_CPT::get_distributions( $campaign_id, $args );

		if ( $query->have_posts() ) {
			ob_start();
			?>
			<div class="sf-distribution-list">
				<table class="sf-dist-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'simple-fundraiser' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'simple-fundraiser' ); ?></th>
							<th><?php esc_html_e( 'Type', 'simple-fundraiser' ); ?></th>
							<th><?php esc_html_e( 'Recipient', 'simple-fundraiser' ); ?></th>
							<th><?php esc_html_e( 'Description', 'simple-fundraiser' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php while ( $query->have_posts() ) : $query->the_post(); 
							$amount = get_post_meta( get_the_ID(), '_sf_dist_amount', true );
							$date = get_post_meta( get_the_ID(), '_sf_dist_date', true );
							$type = get_post_meta( get_the_ID(), '_sf_dist_type', true );
							$recipient = get_post_meta( get_the_ID(), '_sf_dist_recipient', true );
							$desc = get_post_meta( get_the_ID(), '_sf_dist_description', true );
							$proof_id = get_post_meta( get_the_ID(), '_sf_dist_proof', true );
						?>
							<tr>
								<td class="sf-dist-date" data-label="<?php esc_attr_e( 'Date', 'simple-fundraiser' ); ?>">
									<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?>
								</td>
								<td class="sf-dist-amount" data-label="<?php esc_attr_e( 'Amount', 'simple-fundraiser' ); ?>">
									<?php echo esc_html( sf_format_currency( $amount ) ); ?>
								</td>
								<td data-label="<?php esc_attr_e( 'Type', 'simple-fundraiser' ); ?>">
									<?php if ( $type ) : ?>
										<span class="sf-dist-type"><?php echo esc_html( $type ); ?></span>
									<?php else : ?>
										-
									<?php endif; ?>
								</td>
								<td data-label="<?php esc_attr_e( 'Recipient', 'simple-fundraiser' ); ?>">
									<?php echo $recipient ? esc_html( $recipient ) : '-'; ?>
								</td>
								<td class="sf-dist-desc" data-label="<?php esc_attr_e( 'Description', 'simple-fundraiser' ); ?>">
									<?php echo $desc ? esc_html( $desc ) : ''; ?>
									<?php if ( $proof_id ) : 
										$proof_url = wp_get_attachment_url( $proof_id );
									?>
										<br><a href="<?php echo esc_url( $proof_url ); ?>" target="_blank" style="font-size: 0.85em;">
											<span class="dashicons dashicons-media-default" style="vertical-align: middle; font-size: 14px;"></span> 
											<?php esc_html_e( 'View Receipt', 'simple-fundraiser' ); ?>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>

				<?php if ( $query->max_num_pages > 1 ) : ?>
					<div class="sf-dist-pagination">
						<?php
						echo paginate_links( array(
							'base'      => '#%#%',
							'format'    => '?sf_dpage=%#%',
							'current'   => $page,
							'total'     => $query->max_num_pages,
							'prev_text' => __( '&laquo;', 'simple-fundraiser' ),
							'next_text' => __( '&raquo;', 'simple-fundraiser' ),
							'mid_size'  => 1
						) );
						?>
					</div>
				<?php endif; ?>
			</div>
			<?php
			$html = ob_get_clean();
			wp_send_json_success( array( 'html' => $html ) );
		} else {
			wp_send_json_success( array( 'html' => '<p class="sf-no-data">' . __( 'No distributions found yet.', 'simple-fundraiser' ) . '</p>' ) );
		}
	}

	/**
	 * Get campaign types (donation + distribution)
	 */
	public function get_campaign_types() {
		check_ajax_referer( 'sf_nonce', 'nonce' );

		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : 0;
		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => 'Invalid campaign ID' ) );
		}

		// Get distribution types
		$distribution_types = array();
		$dist_types_raw = get_post_meta( $campaign_id, '_sf_distribution_types', true );
		if ( ! empty( $dist_types_raw ) ) {
			$distribution_types = array_filter( array_map( 'trim', explode( "\n", $dist_types_raw ) ) );
		}

		// Get donation types
		$donation_types = array();
		$don_types_raw = get_post_meta( $campaign_id, '_sf_donation_types', true );
		if ( ! empty( $don_types_raw ) ) {
			$donation_types = array_filter( array_map( 'trim', explode( "\n", $don_types_raw ) ) );
		}

		// Merge and deduplicate
		$all_types = array_unique( array_merge( $distribution_types, $donation_types ) );
		sort( $all_types );

		wp_send_json_success( array(
			'types' => array_values( $all_types )
		) );
	}

	/**
	 * Get donations via AJAX
	 */
	public function get_donations() {
		check_ajax_referer( 'sf_nonce', 'nonce' );

		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : 0;
		$page        = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$sort        = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'newest';

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => 'Invalid campaign ID' ) );
		}

		// Sort args
		$args = array(
			'post_type'      => 'sf_donation',
			'posts_per_page' => 10,
			'paged'          => $page,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => '_sf_campaign_id',
					'value' => $campaign_id,
				),
			),
		);

		switch ( $sort ) {
			case 'oldest':
				$args['meta_key'] = '_sf_date';
				$args['orderby']  = array( 'meta_value' => 'ASC', 'ID' => 'ASC' );
				break;
			case 'amount_high':
				$args['meta_key'] = '_sf_amount';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;
			case 'amount_low':
				$args['meta_key'] = '_sf_amount';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'ASC';
				break;
			case 'newest':
			default:
				$args['meta_key'] = '_sf_date';
				$args['orderby']  = array( 'meta_value' => 'DESC', 'ID' => 'DESC' );
				break;
		}

		$donations_query = new WP_Query( $args );
		$html            = '';

		if ( $donations_query->have_posts() ) {
			ob_start();
			?>
			<ul class="sf-donations-list">
				<?php
				while ( $donations_query->have_posts() ) :
					$donations_query->the_post();
					$donation_id = get_the_ID();
					$anonymous   = get_post_meta( $donation_id, '_sf_anonymous', true );
					$donor_name  = get_post_meta( $donation_id, '_sf_donor_name', true );
					$amount      = get_post_meta( $donation_id, '_sf_amount', true );
					$message     = get_post_meta( $donation_id, '_sf_message', true );
					$date        = get_post_meta( $donation_id, '_sf_date', true );
					$d_type      = get_post_meta( $donation_id, '_sf_donation_type', true );
					?>
					<li class="sf-donation-item">
						<div class="sf-donation-info">
							<strong class="sf-donor-name">
								<?php
								if ( '1' === $anonymous ) {
									esc_html_e( 'Anonymous', 'simple-fundraiser' );
								} else {
									echo esc_html( $donor_name ? $donor_name : __( 'Someone', 'simple-fundraiser' ) );
								}

								if ( $d_type ) {
									echo ' <span class="sf-donation-type-badge">' . esc_html( $d_type ) . '</span>';
								}
								?>
							</strong>
							<span class="sf-donation-amount"><?php echo esc_html( sf_format_currency( $amount ) ); ?></span>
						</div>
						<?php if ( $message ) : ?>
							<p class="sf-donation-message">"<?php echo esc_html( $message ); ?>"</p>
						<?php endif; ?>
						<span class="sf-donation-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></span>
					</li>
				<?php endwhile; ?>
			</ul>
			<?php
			$html = ob_get_clean();
		} else {
			$html = '<p class="sf-no-donations">' . esc_html__( 'No donations found.', 'simple-fundraiser' ) . '</p>';
		}
		
		wp_reset_postdata();

		// Pagination
		$pagination = '';

		if ( $donations_query->max_num_pages > 1 ) {
			$pagination = paginate_links( array(
				'base'      => '#%#%',
				'format'    => '',
				'current'   => $page,
				'total'     => $donations_query->max_num_pages,
				'prev_text' => __( '&laquo; Prev', 'simple-fundraiser' ),
				'next_text' => __( 'Next &raquo;', 'simple-fundraiser' ),
				'type'      => 'list',
			) );
		}

		wp_send_json_success( array(
			'html'       => $html,
			'pagination' => $pagination,
		) );
	}

	/**
	 * Save a donation via spreadsheet
	 */
	public function spreadsheet_save() {
		check_ajax_referer( 'sf_spreadsheet_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$donation_id = isset( $_POST['donation_id'] ) ? absint( $_POST['donation_id'] ) : 0;
		$data = isset( $_POST['data'] ) ? $_POST['data'] : array();

		if ( ! $donation_id || empty( $data ) ) {
			wp_send_json_error( array( 'message' => 'Missing data' ) );
		}

		// Update post meta
		if ( isset( $data['donor_name'] ) ) {
			update_post_meta( $donation_id, '_sf_donor_name', sanitize_text_field( $data['donor_name'] ) );
		}
		if ( isset( $data['amount'] ) ) {
			update_post_meta( $donation_id, '_sf_amount', sanitize_text_field( $data['amount'] ) );
		}
		if ( isset( $data['date'] ) ) {
			update_post_meta( $donation_id, '_sf_date', sanitize_text_field( $data['date'] ) );
		}
		if ( isset( $data['type'] ) ) {
			update_post_meta( $donation_id, '_sf_donation_type', sanitize_text_field( $data['type'] ) );
		}
		if ( isset( $data['anonymous'] ) ) {
			update_post_meta( $donation_id, '_sf_anonymous', $data['anonymous'] === '1' ? '1' : '0' );
		}

		wp_send_json_success( array( 'message' => 'Saved' ) );
	}

	/**
	 * Add a new donation via spreadsheet
	 */
	public function spreadsheet_add() {
		check_ajax_referer( 'sf_spreadsheet_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$data = isset( $_POST['data'] ) ? $_POST['data'] : array();
		
		if ( empty( $data['campaign_id'] ) || empty( $data['amount'] ) ) {
			wp_send_json_error( array( 'message' => 'Campaign and Amount are required' ) );
		}

		// Create new donation post
		$donor_name = isset( $data['donor_name'] ) ? sanitize_text_field( $data['donor_name'] ) : '';
		$title = $donor_name ? $donor_name : __( 'Anonymous Donation', 'simple-fundraiser' );

		$post_id = wp_insert_post( array(
			'post_type'   => 'sf_donation',
			'post_title'  => $title,
			'post_status' => 'publish',
		) );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Failed to create donation' ) );
		}

		// Save meta
		update_post_meta( $post_id, '_sf_campaign_id', absint( $data['campaign_id'] ) );
		update_post_meta( $post_id, '_sf_donor_name', $donor_name );
		update_post_meta( $post_id, '_sf_amount', sanitize_text_field( $data['amount'] ) );
		update_post_meta( $post_id, '_sf_date', isset( $data['date'] ) ? sanitize_text_field( $data['date'] ) : date( 'Y-m-d' ) );
		update_post_meta( $post_id, '_sf_donation_type', isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : '' );
		update_post_meta( $post_id, '_sf_anonymous', isset( $data['anonymous'] ) && $data['anonymous'] === '1' ? '1' : '0' );

		wp_send_json_success( array(
			'id'       => $post_id,
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
		) );
	}

	/**
	 * Delete a donation via spreadsheet
	 */
	public function spreadsheet_delete() {
		check_ajax_referer( 'sf_spreadsheet_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$donation_id = isset( $_POST['donation_id'] ) ? absint( $_POST['donation_id'] ) : 0;

		if ( ! $donation_id ) {
			wp_send_json_error( array( 'message' => 'Invalid donation ID' ) );
		}

		$result = wp_delete_post( $donation_id, true );

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Deleted' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to delete' ) );
		}
	}

	/**
	 * Bulk delete donations via spreadsheet
	 */
	public function spreadsheet_bulk_delete() {
		check_ajax_referer( 'sf_spreadsheet_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$ids = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'No IDs provided' ) );
		}

		$deleted = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_post( $id, true ) ) {
				$deleted++;
			}
		}

		wp_send_json_success( array(
			'message' => sprintf( '%d donations deleted', $deleted ),
			'deleted' => $deleted,
		) );
	}

	/**
	 * Bulk update a field for donations via spreadsheet
	 */
	public function spreadsheet_bulk_update() {
		check_ajax_referer( 'sf_spreadsheet_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$ids = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();
		$field = isset( $_POST['field'] ) ? sanitize_text_field( $_POST['field'] ) : '';
		$value = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : '';

		if ( empty( $ids ) || empty( $field ) ) {
			wp_send_json_error( array( 'message' => 'Missing data' ) );
		}

		// Map field to meta key
		$field_map = array(
			'anonymous' => '_sf_anonymous',
			'type'      => '_sf_donation_type',
		);

		if ( ! isset( $field_map[ $field ] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid field' ) );
		}

		$meta_key = $field_map[ $field ];
		$updated = 0;

		foreach ( $ids as $id ) {
			if ( update_post_meta( $id, $meta_key, $value ) ) {
				$updated++;
			}
		}

		wp_send_json_success( array(
			'message' => sprintf( '%d donations updated', $updated ),
			'updated' => $updated,
		) );
	}
}
