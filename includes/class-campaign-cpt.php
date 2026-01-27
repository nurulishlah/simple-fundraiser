<?php
/**
 * Campaign Custom Post Type
 *
 * @package SimpleFundraiser
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Campaign_CPT {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_sf_campaign', array( $this, 'save_meta' ) );
		add_filter( 'manage_sf_campaign_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_sf_campaign_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
	}

	/**
	 * Register Campaign post type
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Campaigns', 'simple-fundraiser' ),
			'singular_name'      => __( 'Campaign', 'simple-fundraiser' ),
			'menu_name'          => __( 'Fundraiser', 'simple-fundraiser' ),
			'add_new'            => __( 'Add Campaign', 'simple-fundraiser' ),
			'add_new_item'       => __( 'Add New Campaign', 'simple-fundraiser' ),
			'edit_item'          => __( 'Edit Campaign', 'simple-fundraiser' ),
			'new_item'           => __( 'New Campaign', 'simple-fundraiser' ),
			'view_item'          => __( 'View Campaign', 'simple-fundraiser' ),
			'search_items'       => __( 'Search Campaigns', 'simple-fundraiser' ),
			'not_found'          => __( 'No campaigns found', 'simple-fundraiser' ),
			'not_found_in_trash' => __( 'No campaigns found in trash', 'simple-fundraiser' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'campaign' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-heart',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'       => true,
		);

		register_post_type( 'sf_campaign', $args );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'sf_campaign_details',
			__( 'Campaign Details', 'simple-fundraiser' ),
			array( $this, 'render_details_meta_box' ),
			'sf_campaign',
			'normal',
			'high'
		);

		add_meta_box(
			'sf_campaign_payment',
			__( 'Payment Information', 'simple-fundraiser' ),
			array( $this, 'render_payment_meta_box' ),
			'sf_campaign',
			'normal',
			'high'
		);

		add_meta_box(
			'sf_campaign_progress',
			__( 'Campaign Progress', 'simple-fundraiser' ),
			array( $this, 'render_progress_meta_box' ),
			'sf_campaign',
			'side',
			'high'
		);
	}

	/**
	 * Render campaign details meta box
	 */
	public function render_details_meta_box( $post ) {
		wp_nonce_field( 'sf_campaign_meta', 'sf_campaign_nonce' );
		
		$goal = get_post_meta( $post->ID, '_sf_goal', true );
		$deadline = get_post_meta( $post->ID, '_sf_deadline', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="sf_goal"><?php esc_html_e( 'Goal Amount (Rp)', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="number" id="sf_goal" name="sf_goal" value="<?php echo esc_attr( $goal ); ?>" class="regular-text" min="0" step="1">
				</td>
			</tr>
			<tr>
				<th><label for="sf_deadline"><?php esc_html_e( 'Deadline', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="date" id="sf_deadline" name="sf_deadline" value="<?php echo esc_attr( $deadline ); ?>" class="regular-text">
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render payment info meta box
	 */
	public function render_payment_meta_box( $post ) {
		$bank_name = get_post_meta( $post->ID, '_sf_bank_name', true );
		$account_number = get_post_meta( $post->ID, '_sf_account_number', true );
		$account_holder = get_post_meta( $post->ID, '_sf_account_holder', true );
		$contact_info = get_post_meta( $post->ID, '_sf_contact_info', true );
		$qris_image = get_post_meta( $post->ID, '_sf_qris_image', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="sf_bank_name"><?php esc_html_e( 'Bank Name', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="text" id="sf_bank_name" name="sf_bank_name" value="<?php echo esc_attr( $bank_name ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="sf_account_number"><?php esc_html_e( 'Account Number', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="text" id="sf_account_number" name="sf_account_number" value="<?php echo esc_attr( $account_number ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="sf_account_holder"><?php esc_html_e( 'Account Holder', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="text" id="sf_account_holder" name="sf_account_holder" value="<?php echo esc_attr( $account_holder ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="sf_contact_info"><?php esc_html_e( 'Contact (WhatsApp)', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="text" id="sf_contact_info" name="sf_contact_info" value="<?php echo esc_attr( $contact_info ); ?>" class="regular-text" placeholder="e.g. 628123456789">
					<p class="description"><?php esc_html_e( 'Number for donors to confirm (starts with country code, e.g. 62)', 'simple-fundraiser' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="sf_donation_types"><?php esc_html_e( 'Donation Types / Categories', 'simple-fundraiser' ); ?></label></th>
				<td>
					<textarea id="sf_donation_types" name="sf_donation_types" rows="4" class="large-text code"><?php echo esc_textarea( get_post_meta( $post->ID, '_sf_donation_types', true ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter allowed donation types, one per line. e.g., "Sembako", "Kegiatan"', 'simple-fundraiser' ); ?></p>
					
					<?php
					$types_raw = get_post_meta( $post->ID, '_sf_donation_types', true );
					$types = $types_raw ? array_map( 'trim', explode( "\n", $types_raw ) ) : array();
					$types = array_filter( $types );
					$default_type = get_post_meta( $post->ID, '_sf_default_donation_type', true );
					?>
					<div style="margin-top: 10px;">
						<label for="sf_default_donation_type"><strong><?php esc_html_e( 'Default Type:', 'simple-fundraiser' ); ?></strong></label>
						<select name="sf_default_donation_type" id="sf_default_donation_type">
							<option value=""><?php esc_html_e( '-- None --', 'simple-fundraiser' ); ?></option>
							<?php foreach ( $types as $type ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $default_type, $type ); ?>><?php echo esc_html( $type ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="description"><?php esc_html_e( 'Used when no type is selected during import or manual entry (Save to update list).', 'simple-fundraiser' ); ?></span>
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="sf_qris_image"><?php esc_html_e( 'QRIS Image', 'simple-fundraiser' ); ?></label></th>
				<td>
					<div class="sf-qris-preview">
						<?php if ( $qris_image ) : ?>
							<img src="<?php echo esc_url( wp_get_attachment_url( $qris_image ) ); ?>" style="max-width: 200px; height: auto;">
						<?php endif; ?>
					</div>
					<input type="hidden" id="sf_qris_image" name="sf_qris_image" value="<?php echo esc_attr( $qris_image ); ?>">
					<button type="button" class="button sf-upload-qris"><?php esc_html_e( 'Upload QRIS', 'simple-fundraiser' ); ?></button>
					<button type="button" class="button sf-remove-qris" <?php echo empty( $qris_image ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove', 'simple-fundraiser' ); ?></button>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render progress meta box
	 */
	public function render_progress_meta_box( $post ) {
		$goal = get_post_meta( $post->ID, '_sf_goal', true );
		$total = sf_get_campaign_total( $post->ID );
		$progress = sf_get_campaign_progress( $post->ID );
		?>
		<div class="sf-progress-box">
			<p><strong><?php esc_html_e( 'Raised:', 'simple-fundraiser' ); ?></strong> <?php echo esc_html( sf_format_currency( $total ) ); ?></p>
			<p><strong><?php esc_html_e( 'Goal:', 'simple-fundraiser' ); ?></strong> <?php echo esc_html( sf_format_currency( $goal ) ); ?></p>
			<div class="sf-admin-progress-bar">
				<div class="sf-admin-progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
			</div>
			<p><strong><?php echo esc_html( round( $progress, 1 ) ); ?>%</strong> <?php esc_html_e( 'complete', 'simple-fundraiser' ); ?></p>
		</div>
		<style>
			.sf-admin-progress-bar { background: #e0e0e0; border-radius: 4px; height: 20px; margin: 10px 0; }
			.sf-admin-progress-fill { background: #0073aa; border-radius: 4px; height: 100%; transition: width 0.3s; }
		</style>
		<?php
	}

	/**
	 * Save campaign meta
	 */
	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['sf_campaign_nonce'] ) || ! wp_verify_nonce( $_POST['sf_campaign_nonce'], 'sf_campaign_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'sf_goal'           => '_sf_goal',
			'sf_deadline'       => '_sf_deadline',
			'sf_bank_name'      => '_sf_bank_name',
			'sf_account_number' => '_sf_account_number',
			'sf_account_holder' => '_sf_account_holder',
			'sf_contact_info'   => '_sf_contact_info',
			'sf_donation_types' => '_sf_donation_types',
			'sf_default_donation_type' => '_sf_default_donation_type',
			'sf_qris_image'     => '_sf_qris_image',
		);

		foreach ( $fields as $field => $meta_key ) {
			if ( isset( $_POST[ $field ] ) ) {
				$val = $_POST[ $field ];
				if ( 'sf_donation_types' === $field ) {
					$val = sanitize_textarea_field( $val );
				} else {
					$val = sanitize_text_field( $val );
				}
				update_post_meta( $post_id, $meta_key, $val );
			}
		}
	}

	/**
	 * Add admin columns
	 */
	public function add_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['sf_goal'] = __( 'Goal', 'simple-fundraiser' );
				$new_columns['sf_raised'] = __( 'Raised', 'simple-fundraiser' );
				$new_columns['sf_progress'] = __( 'Progress', 'simple-fundraiser' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render admin columns
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'sf_goal':
				$goal = get_post_meta( $post_id, '_sf_goal', true );
				echo esc_html( sf_format_currency( $goal ) );
				break;
			case 'sf_raised':
				$total = sf_get_campaign_total( $post_id );
				echo esc_html( sf_format_currency( $total ) );
				break;
			case 'sf_progress':
				$progress = sf_get_campaign_progress( $post_id );
				echo esc_html( round( $progress, 1 ) . '%' );
				break;
		}
	}
}
