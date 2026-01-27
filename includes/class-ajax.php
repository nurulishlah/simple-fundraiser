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
}
