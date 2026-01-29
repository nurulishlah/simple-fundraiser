<?php
/**
 * Distribution Custom Post Type
 *
 * @package SimpleFundraiser
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Distribution_CPT {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_sf_distribution', array( $this, 'save_meta' ) );
		add_filter( 'manage_sf_distribution_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_sf_distribution_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_filter( 'manage_edit-sf_distribution_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_columns' ) );
	}

	/**
	 * Register the Distribution CPT
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Distributions', 'simple-fundraiser' ),
			'singular_name'      => __( 'Distribution', 'simple-fundraiser' ),
			'menu_name'          => __( 'Distributions', 'simple-fundraiser' ),
			'add_new'            => __( 'Add Distribution', 'simple-fundraiser' ),
			'add_new_item'       => __( 'Add New Distribution', 'simple-fundraiser' ),
			'edit_item'          => __( 'Edit Distribution', 'simple-fundraiser' ),
			'new_item'           => __( 'New Distribution', 'simple-fundraiser' ),
			'view_item'          => __( 'View Distribution', 'simple-fundraiser' ),
			'search_items'       => __( 'Search Distributions', 'simple-fundraiser' ),
			'not_found'          => __( 'No distributions found', 'simple-fundraiser' ),
			'not_found_in_trash' => __( 'No distributions found in trash', 'simple-fundraiser' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=sf_campaign',
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title' ),
			'show_in_rest'        => false,
		);

		register_post_type( 'sf_distribution', $args );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'sf_distribution_details',
			__( 'Distribution Details', 'simple-fundraiser' ),
			array( $this, 'render_details_meta_box' ),
			'sf_distribution',
			'normal',
			'high'
		);

		add_meta_box(
			'sf_distribution_proof',
			__( 'Receipt / Proof', 'simple-fundraiser' ),
			array( $this, 'render_proof_meta_box' ),
			'sf_distribution',
			'side',
			'default'
		);
	}

	/**
	 * Render details meta box
	 */
	public function render_details_meta_box( $post ) {
		wp_nonce_field( 'sf_distribution_meta', 'sf_distribution_nonce' );

		$campaign_id = get_post_meta( $post->ID, '_sf_campaign_id', true );
		$amount      = get_post_meta( $post->ID, '_sf_dist_amount', true );
		$date        = get_post_meta( $post->ID, '_sf_dist_date', true );
		$type        = get_post_meta( $post->ID, '_sf_dist_type', true );
		$recipient   = get_post_meta( $post->ID, '_sf_dist_recipient', true );
		$description = get_post_meta( $post->ID, '_sf_dist_description', true );

		// Get campaigns
		$campaigns = get_posts( array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		// Get distribution types from selected campaign
		$distribution_types = array();
		if ( $campaign_id ) {
			$types_raw = get_post_meta( $campaign_id, '_sf_distribution_types', true );
			if ( ! empty( $types_raw ) ) {
				$distribution_types = array_filter( array_map( 'trim', explode( "\n", $types_raw ) ) );
			}
			// Also include donation types as options
			$donation_types_raw = get_post_meta( $campaign_id, '_sf_donation_types', true );
			if ( ! empty( $donation_types_raw ) ) {
				$donation_types = array_filter( array_map( 'trim', explode( "\n", $donation_types_raw ) ) );
				$distribution_types = array_unique( array_merge( $distribution_types, $donation_types ) );
			}
		}
		?>
		<table class="form-table">
			<tr>
				<th><label for="sf_campaign_id"><?php esc_html_e( 'Campaign', 'simple-fundraiser' ); ?></label></th>
				<td>
					<select name="sf_campaign_id" id="sf_campaign_id" class="regular-text" required>
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
				<th><label for="sf_dist_amount"><?php esc_html_e( 'Amount (Rp)', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="number" name="sf_dist_amount" id="sf_dist_amount" class="regular-text" 
						   value="<?php echo esc_attr( $amount ); ?>" min="0" step="1" required>
				</td>
			</tr>
			<tr>
				<th><label for="sf_dist_date"><?php esc_html_e( 'Date', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="date" name="sf_dist_date" id="sf_dist_date" class="regular-text" 
						   value="<?php echo esc_attr( $date ? $date : date( 'Y-m-d' ) ); ?>" required>
				</td>
			</tr>
			<tr>
				<th><label for="sf_dist_type"><?php esc_html_e( 'Type / Category', 'simple-fundraiser' ); ?></label></th>
				<td>
					<select name="sf_dist_type" id="sf_dist_type" class="regular-text" <?php echo empty( $distribution_types ) ? 'style="display:none;"' : ''; ?>>
						<option value=""><?php esc_html_e( '— Select Type —', 'simple-fundraiser' ); ?></option>
						<?php foreach ( $distribution_types as $dtype ) : ?>
							<option value="<?php echo esc_attr( $dtype ); ?>" <?php selected( $type, $dtype ); ?>>
								<?php echo esc_html( $dtype ); ?>
							</option>
						<?php endforeach; ?>
						<option value="__custom__" <?php selected( $type, '__custom__' ); ?>><?php esc_html_e( 'Other (custom)', 'simple-fundraiser' ); ?></option>
					</select>
					
					<input type="text" name="sf_dist_type_custom" id="sf_dist_type_custom" class="regular-text" 
						   value="<?php echo ( $type && ! in_array( $type, $distribution_types ) ) ? esc_attr( $type ) : ''; ?>"
						   placeholder="<?php esc_attr_e( 'Enter custom type', 'simple-fundraiser' ); ?>"
						   style="<?php echo ( $type && ! in_array( $type, $distribution_types ) ) || $type === '__custom__' || empty( $distribution_types ) ? '' : 'display: none;'; ?> margin-top: 8px;">
					
					<p class="description sf-type-hint" <?php echo ! empty( $distribution_types ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Select a campaign to see predefined types.', 'simple-fundraiser' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="sf_dist_recipient"><?php esc_html_e( 'Recipient', 'simple-fundraiser' ); ?></label></th>
				<td>
					<input type="text" name="sf_dist_recipient" id="sf_dist_recipient" class="regular-text" 
						   value="<?php echo esc_attr( $recipient ); ?>" 
						   placeholder="<?php esc_attr_e( 'Name of recipient', 'simple-fundraiser' ); ?>">
				</td>
			</tr>
			<tr>
				<th><label for="sf_dist_description"><?php esc_html_e( 'Description', 'simple-fundraiser' ); ?></label></th>
				<td>
					<textarea name="sf_dist_description" id="sf_dist_description" class="large-text" rows="4"
							  placeholder="<?php esc_attr_e( 'Details about this distribution...', 'simple-fundraiser' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
				</td>
			</tr>
		</table>

		<script>
		jQuery(document).ready(function($) {
			
			// Show custom type input if "Other" is selected or if existing type is custom
			$('#sf_dist_type').on('change', function() {
				if ($(this).val() === '__custom__') {
					$('#sf_dist_type_custom').show().focus();
				} else {
					$('#sf_dist_type_custom').hide().val('');
				}
			});

			// Reactive campaign types
			$('#sf_campaign_id').on('change', function() {
				var campaignId = $(this).val();
				var $typeSelect = $('#sf_dist_type');
				var $customInput = $('#sf_dist_type_custom');
				var $hint = $('.sf-type-hint');

				if (!campaignId) {
					$typeSelect.hide();
					$customInput.show();
					$hint.show();
					return;
				}

				// AJAX call to get types
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'sf_get_campaign_types',
						campaign_id: campaignId,
						nonce: '<?php echo wp_create_nonce( "sf_nonce" ); ?>'
					},
					success: function(response) {
						if (response.success && response.data.types.length > 0) {
							// Populate select
							$typeSelect.find('option:not(:first):not([value="__custom__"])').remove();
							
							$.each(response.data.types, function(i, type) {
								$typeSelect.find('option[value="__custom__"]').before('<option value="' + type + '">' + type + '</option>');
							});
							
							$typeSelect.show();
							$customInput.hide().val('');
							$hint.hide();
							
							// Reset selection to default
							$typeSelect.val('');
						} else {
							// No types found, fallback to custom input
							$typeSelect.hide();
							$customInput.show();
							$hint.hide(); // Hide hint as we are just forcing custom input
						}
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render proof meta box
	 */
	public function render_proof_meta_box( $post ) {
		$proof_id  = get_post_meta( $post->ID, '_sf_dist_proof', true );
		$proof_url = $proof_id ? wp_get_attachment_image_url( $proof_id, 'medium' ) : '';
		?>
		<div class="sf-proof-upload">
			<div class="sf-proof-preview" style="margin-bottom: 10px;">
				<?php if ( $proof_url ) : ?>
					<img src="<?php echo esc_url( $proof_url ); ?>" style="max-width: 100%; height: auto;">
				<?php endif; ?>
			</div>
			<input type="hidden" name="sf_dist_proof" id="sf_dist_proof" value="<?php echo esc_attr( $proof_id ); ?>">
			<button type="button" class="button sf-upload-proof"><?php esc_html_e( 'Upload Receipt', 'simple-fundraiser' ); ?></button>
			<?php if ( $proof_id ) : ?>
				<button type="button" class="button sf-remove-proof" style="color: #a00;"><?php esc_html_e( 'Remove', 'simple-fundraiser' ); ?></button>
			<?php endif; ?>
		</div>

		<script>
		jQuery(document).ready(function($) {
			var frame;

			$('.sf-upload-proof').on('click', function(e) {
				e.preventDefault();

				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: '<?php esc_html_e( 'Select Receipt Image', 'simple-fundraiser' ); ?>',
					button: { text: '<?php esc_html_e( 'Use Image', 'simple-fundraiser' ); ?>' },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#sf_dist_proof').val(attachment.id);
					$('.sf-proof-preview').html('<img src="' + attachment.sizes.medium.url + '" style="max-width: 100%; height: auto;">');
					if ($('.sf-remove-proof').length === 0) {
						$('.sf-upload-proof').after('<button type="button" class="button sf-remove-proof" style="color: #a00;"><?php esc_html_e( 'Remove', 'simple-fundraiser' ); ?></button>');
					}
				});

				frame.open();
			});

			$(document).on('click', '.sf-remove-proof', function(e) {
				e.preventDefault();
				$('#sf_dist_proof').val('');
				$('.sf-proof-preview').html('');
				$(this).remove();
			});
		});
		</script>
		<?php
	}

	/**
	 * Save meta data
	 */
	public function save_meta( $post_id ) {
		// Security checks
		if ( ! isset( $_POST['sf_distribution_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['sf_distribution_nonce'], 'sf_distribution_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save campaign ID
		if ( isset( $_POST['sf_campaign_id'] ) ) {
			update_post_meta( $post_id, '_sf_campaign_id', absint( $_POST['sf_campaign_id'] ) );
		}

		// Save amount
		if ( isset( $_POST['sf_dist_amount'] ) ) {
			update_post_meta( $post_id, '_sf_dist_amount', floatval( $_POST['sf_dist_amount'] ) );
		}

		// Save date
		if ( isset( $_POST['sf_dist_date'] ) ) {
			update_post_meta( $post_id, '_sf_dist_date', sanitize_text_field( $_POST['sf_dist_date'] ) );
		}

		// Save type (handle custom)
		if ( isset( $_POST['sf_dist_type'] ) ) {
			$type = sanitize_text_field( $_POST['sf_dist_type'] );
			if ( $type === '__custom__' && isset( $_POST['sf_dist_type_custom'] ) ) {
				$type = sanitize_text_field( $_POST['sf_dist_type_custom'] );
			}
			update_post_meta( $post_id, '_sf_dist_type', $type );
		}

		// Save recipient
		if ( isset( $_POST['sf_dist_recipient'] ) ) {
			update_post_meta( $post_id, '_sf_dist_recipient', sanitize_text_field( $_POST['sf_dist_recipient'] ) );
		}

		// Save description
		if ( isset( $_POST['sf_dist_description'] ) ) {
			update_post_meta( $post_id, '_sf_dist_description', sanitize_textarea_field( $_POST['sf_dist_description'] ) );
		}

		// Save proof
		if ( isset( $_POST['sf_dist_proof'] ) ) {
			update_post_meta( $post_id, '_sf_dist_proof', absint( $_POST['sf_dist_proof'] ) );
		}
	}

	/**
	 * Add admin columns
	 */
	public function add_columns( $columns ) {
		$new_columns = array();
		$new_columns['cb'] = $columns['cb'];
		$new_columns['title'] = $columns['title'];
		$new_columns['campaign'] = __( 'Campaign', 'simple-fundraiser' );
		$new_columns['amount'] = __( 'Amount', 'simple-fundraiser' );
		$new_columns['date'] = __( 'Date', 'simple-fundraiser' );
		$new_columns['type'] = __( 'Type', 'simple-fundraiser' );
		$new_columns['recipient'] = __( 'Recipient', 'simple-fundraiser' );

		return $new_columns;
	}

	/**
	 * Render admin columns
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'campaign':
				$campaign_id = get_post_meta( $post_id, '_sf_campaign_id', true );
				if ( $campaign_id ) {
					$campaign = get_post( $campaign_id );
					if ( $campaign ) {
						echo '<a href="' . esc_url( get_edit_post_link( $campaign_id ) ) . '">';
						echo esc_html( $campaign->post_title );
						echo '</a>';
					}
				} else {
					echo '—';
				}
				break;

			case 'amount':
				$amount = get_post_meta( $post_id, '_sf_dist_amount', true );
				echo 'Rp ' . number_format( floatval( $amount ), 0, ',', '.' );
				break;

			case 'date':
				$date = get_post_meta( $post_id, '_sf_dist_date', true );
				echo $date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ) : '—';
				break;

			case 'type':
				$type = get_post_meta( $post_id, '_sf_dist_type', true );
				echo $type ? esc_html( $type ) : '—';
				break;

			case 'recipient':
				$recipient = get_post_meta( $post_id, '_sf_dist_recipient', true );
				echo $recipient ? esc_html( $recipient ) : '—';
				break;
		}
	}

	/**
	 * Define sortable columns
	 */
	public function sortable_columns( $columns ) {
		$columns['amount'] = 'amount';
		$columns['date'] = 'date';
		return $columns;
	}

	/**
	 * Handle column sorting
	 */
	public function sort_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'sf_distribution' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'amount' === $orderby ) {
			$query->set( 'meta_key', '_sf_dist_amount' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'date' === $orderby ) {
			$query->set( 'meta_key', '_sf_dist_date' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Get total distributed for a campaign
	 */
	public static function get_total_distributed( $campaign_id ) {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(pm.meta_value) 
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
			WHERE pm.meta_key = '_sf_dist_amount'
			AND pm2.meta_key = '_sf_campaign_id'
			AND pm2.meta_value = %d
			AND p.post_type = 'sf_distribution'
			AND p.post_status = 'publish'",
			$campaign_id
		) );

		return $total ? floatval( $total ) : 0;
	}

	/**
	 * Get distributions for a campaign
	 */
	public static function get_distributions( $campaign_id, $args = array() ) {
		$defaults = array(
			'posts_per_page' => 10,
			'paged'          => 1,
			'orderby'        => 'meta_value',
			'meta_key'       => '_sf_dist_date',
			'order'          => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'      => 'sf_distribution',
			'post_status'    => 'publish',
			'posts_per_page' => $args['posts_per_page'],
			'paged'          => $args['paged'],
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
			'meta_query'     => array(
				array(
					'key'   => '_sf_campaign_id',
					'value' => $campaign_id,
				),
			),
		);

		if ( isset( $args['meta_key'] ) ) {
			$query_args['meta_key'] = $args['meta_key'];
		}

		return new WP_Query( $query_args );
	}
}
