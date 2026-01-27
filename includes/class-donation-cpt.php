<?php
/**
 * Donation Custom Post Type
 *
 * @package SimpleFundraiser
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Donation_CPT {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_sf_donation', array( $this, 'save_meta' ) );
		add_filter( 'manage_sf_donation_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_sf_donation_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
	}

	/**
	 * Register Donation post type
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Donations', 'simple-fundraiser' ),
			'singular_name'      => __( 'Donation', 'simple-fundraiser' ),
			'menu_name'          => __( 'Donations', 'simple-fundraiser' ),
			'add_new'            => __( 'Add Donation', 'simple-fundraiser' ),
			'add_new_item'       => __( 'Add New Donation', 'simple-fundraiser' ),
			'edit_item'          => __( 'Edit Donation', 'simple-fundraiser' ),
			'new_item'           => __( 'New Donation', 'simple-fundraiser' ),
			'view_item'          => __( 'View Donation', 'simple-fundraiser' ),
			'search_items'       => __( 'Search Donations', 'simple-fundraiser' ),
			'not_found'          => __( 'No donations found', 'simple-fundraiser' ),
			'not_found_in_trash' => __( 'No donations found in trash', 'simple-fundraiser' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=sf_campaign',
			'query_var'          => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'sf_donation', $args );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'sf_donation_details',
			__( 'Donation Details', 'simple-fundraiser' ),
			array( $this, 'render_details_meta_box' ),
			'sf_donation',
			'normal',
			'high'
		);

		add_meta_box(
			'sf_donor_info',
			__( 'Donor Information', 'simple-fundraiser' ),
			array( $this, 'render_donor_meta_box' ),
			'sf_donation',
			'normal',
			'high'
		);
	}

	/**
	 * Render donation details meta box
	 */
	public function render_details_meta_box( $post ) {
		wp_nonce_field( 'sf_donation_meta', 'sf_donation_nonce' );
		
		$campaign_id = get_post_meta( $post->ID, '_sf_campaign_id', true );
		$amount = get_post_meta( $post->ID, '_sf_amount', true );
		$date = get_post_meta( $post->ID, '_sf_date', true );
		
		// Get all campaigns
		$campaigns = get_posts( array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		?>
		<table class="form-table">
			<tr>
				<th><label for="sf_campaign_id"><?php esc_html_e( 'Campaign', 'simple-fundraiser' ); ?></label></th>
				<td>
					<select id="sf_campaign_id" name="sf_campaign_id" class="regular-text" required>
						<option value=""><?php esc_html_e( '— Select Campaign —', 'simple-fundraiser' ); ?></option>
						<?php foreach ( $campaigns as $campaign ) : ?>
							<option value="<?php echo esc_attr( $campaign->ID ); ?>" <?php selected( $campaign_id, $campaign->ID ); ?>>
								<?php echo esc_html( $campaign->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="sf_donation_type"><?php esc_html_e( 'Donation Type', 'simple-fundraiser' ); ?></label></th>
				<td>
					<select id="sf_donation_type" name="sf_donation_type" class="regular-text">
						<option value=""><?php esc_html_e( '— Select Type —', 'simple-fundraiser' ); ?></option>
						<?php 
						$type = get_post_meta( $post->ID, '_sf_donation_type', true );
						if ( $type ) {
							echo '<option value="' . esc_attr( $type ) . '" selected>' . esc_html( $type ) . '</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="sf_amount"><?php esc_html_e( 'Amount (Rp)', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="number" id="sf_amount" name="sf_amount" value="<?php echo esc_attr( $amount ); ?>" class="regular-text" min="0" step="1" required>
				</td>
			</tr>
			<tr>
				<th><label for="sf_date"><?php esc_html_e( 'Date', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="date" id="sf_date" name="sf_date" value="<?php echo esc_attr( $date ? $date : date( 'Y-m-d' ) ); ?>" class="regular-text" required>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render donor info meta box
	 */
	public function render_donor_meta_box( $post ) {
		$donor_name = get_post_meta( $post->ID, '_sf_donor_name', true );
		$donor_email = get_post_meta( $post->ID, '_sf_donor_email', true );
		$donor_phone = get_post_meta( $post->ID, '_sf_donor_phone', true );
		$message = get_post_meta( $post->ID, '_sf_message', true );
		$anonymous = get_post_meta( $post->ID, '_sf_anonymous', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="sf_donor_name"><?php esc_html_e( 'Donor Name', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="text" id="sf_donor_name" name="sf_donor_name" value="<?php echo esc_attr( $donor_name ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="sf_donor_email"><?php esc_html_e( 'Email', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="email" id="sf_donor_email" name="sf_donor_email" value="<?php echo esc_attr( $donor_email ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="sf_donor_phone"><?php esc_html_e( 'Phone', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="tel" id="sf_donor_phone" name="sf_donor_phone" value="<?php echo esc_attr( $donor_phone ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="sf_message"><?php esc_html_e( 'Message', 'simple-fundraiser' ); ?></label></th>
				<td>
					<textarea id="sf_message" name="sf_message" rows="3" class="large-text"><?php echo esc_textarea( $message ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="sf_anonymous"><?php esc_html_e( 'Anonymous', 'simple-fundraiser' ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" id="sf_anonymous" name="sf_anonymous" value="1" <?php checked( $anonymous, '1' ); ?>>
						<?php esc_html_e( 'Hide donor name on frontend', 'simple-fundraiser' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save donation meta
	 */
	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['sf_donation_nonce'] ) || ! wp_verify_nonce( $_POST['sf_donation_nonce'], 'sf_donation_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$text_fields = array(
			'sf_campaign_id'  => '_sf_campaign_id',
			'sf_amount'       => '_sf_amount',
			'sf_date'         => '_sf_date',
			'sf_donation_type'=> '_sf_donation_type',
			'sf_donor_name'   => '_sf_donor_name',
			'sf_donor_email'  => '_sf_donor_email',
			'sf_donor_phone'  => '_sf_donor_phone',
		);

		foreach ( $text_fields as $field => $meta_key ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field ] ) );
			}
		}

		// Handle message textarea
		if ( isset( $_POST['sf_message'] ) ) {
			update_post_meta( $post_id, '_sf_message', sanitize_textarea_field( $_POST['sf_message'] ) );
		}

		// Handle anonymous checkbox
		$anonymous = isset( $_POST['sf_anonymous'] ) ? '1' : '0';
		update_post_meta( $post_id, '_sf_anonymous', $anonymous );
	}

	/**
	 * Add admin columns
	 */
	public function add_columns( $columns ) {
		$new_columns = array(
			'cb'          => $columns['cb'],
			'title'       => $columns['title'],
			'sf_campaign' => __( 'Campaign', 'simple-fundraiser' ),
			'sf_amount'   => __( 'Amount', 'simple-fundraiser' ),
			'sf_donor'    => __( 'Donor', 'simple-fundraiser' ),
			'sf_date'     => __( 'Date', 'simple-fundraiser' ),
		);
		return $new_columns;
	}

	/**
	 * Render admin columns
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'sf_campaign':
				$campaign_id = get_post_meta( $post_id, '_sf_campaign_id', true );
				if ( $campaign_id ) {
					echo esc_html( get_the_title( $campaign_id ) );
				} else {
					echo '—';
				}
				break;
			case 'sf_amount':
				$amount = get_post_meta( $post_id, '_sf_amount', true );
				echo esc_html( sf_format_currency( $amount ) );
				break;
			case 'sf_donor':
				$anonymous = get_post_meta( $post_id, '_sf_anonymous', true );
				$donor_name = get_post_meta( $post_id, '_sf_donor_name', true );
				if ( $anonymous === '1' ) {
					echo '<em>' . esc_html__( 'Anonymous', 'simple-fundraiser' ) . '</em>';
				} else {
					echo esc_html( $donor_name ? $donor_name : '—' );
				}
				break;
			case 'sf_date':
				$date = get_post_meta( $post_id, '_sf_date', true );
				if ( $date ) {
					echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) );
				} else {
					echo '—';
				}
				break;
		}
	}
}
