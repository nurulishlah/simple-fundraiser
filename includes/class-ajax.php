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
		add_action( 'wp_ajax_sf_spreadsheet_delete', array( $this, 'spreadsheet_delete' ) );
		add_action( 'wp_ajax_sf_spreadsheet_bulk_delete', array( $this, 'spreadsheet_bulk_delete' ) );
		add_action( 'wp_ajax_sf_spreadsheet_bulk_update', array( $this, 'spreadsheet_bulk_update' ) );
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
