<?php
/**
 * Import functionality
 *
 * @package SimpleFundraiser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Import {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_import_menu' ) );
		add_action( 'admin_post_sf_download_sample', array( $this, 'handle_sample_download' ) );
	}

	/**
	 * Add import menu
	 */
	public function add_import_menu() {
		add_submenu_page(
			'edit.php?post_type=sf_campaign',
			__( 'Import Donations', 'simple-fundraiser' ),
			__( 'Import CSV', 'simple-fundraiser' ),
			'manage_options',
			'sf_import',
			array( $this, 'render_import_page' )
		);
	}

	/**
	 * Handle sample CSV download
	 */
	public function handle_sample_download() {
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$campaign_id = isset( $_GET['campaign_id'] ) ? intval( $_GET['campaign_id'] ) : 0;
		
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="sample-donations.csv"' );
		
		$output = fopen( 'php://output', 'w' );
		
		// Headers
		fputcsv( $output, array( 'Date', 'Donor Name', 'Amount', 'Campaign ID', 'Email', 'Message', 'Donation Type' ) );
		
		// Sample Data
		fputcsv( $output, array( 
			current_time( 'Y-m-d' ), 
			'John Doe', 
			'50000', 
			$campaign_id ? $campaign_id : '', 
			'john@example.com', 
			'Keep it up!', 
			'Sembako' 
		) );
		
		fclose( $output );
		exit;
	}

	/**
	 * Render import page
	 */
	public function render_import_page() {
		// Handle submission
		$this->process_import();

		// Get all campaigns for dropdown
		$campaigns = get_posts( array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Donations', 'simple-fundraiser' ); ?></h1>
			
			<div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'sf_import_action', 'sf_import_nonce' ); ?>
					
					<p>
						<label for="sf_default_campaign"><strong><?php esc_html_e( 'Default Campaign', 'simple-fundraiser' ); ?></strong></label><br>
						<span class="description"><?php esc_html_e( 'Assign to this campaign if "Campaign ID" column is empty in CSV.', 'simple-fundraiser' ); ?></span>
						<br>
						<div style="display: flex; gap: 10px; align-items: flex-start;">
							<select name="sf_default_campaign" id="sf_default_campaign" style="flex-grow: 1;">
								<option value=""><?php esc_html_e( '-- Select Campaign --', 'simple-fundraiser' ); ?></option>
								<?php foreach ( $campaigns as $campaign ) : ?>
									<option value="<?php echo esc_attr( $campaign->ID ); ?>"><?php echo esc_html( $campaign->post_title ); ?> (ID: <?php echo esc_html( $campaign->ID ); ?>)</option>
								<?php endforeach; ?>
							</select>
							
							<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=sf_download_sample' ) ); ?>" 
							   class="button" 
							   id="sf_download_sample" 
							   target="_blank">
								<?php esc_html_e( 'Download Sample CSV', 'simple-fundraiser' ); ?>
							</a>
						</div>
					</p>

					<p>
						<label for="sf_import_file"><strong><?php esc_html_e( 'CSV File', 'simple-fundraiser' ); ?></strong></label><br>
						<input type="file" name="sf_import_file" id="sf_import_file" accept=".csv" required>
					</p>

					<hr>
					
					<p><strong><?php esc_html_e( 'CSV Format Guide:', 'simple-fundraiser' ); ?></strong></p>
					<code style="display: block; padding: 10px; background: #f0f0f1;">
						Date, Donor Name, Amount, Campaign ID, Email, Message, Donation Type<br>
						2023-10-25, John Doe, 50000, 12, john@example.com, Keep it up, Sembako
					</code>
					<ul style="font-size: 0.9em; color: #666; list-style: disc; margin-left: 20px;">
						<li><?php esc_html_e( 'Date format: YYYY-MM-DD', 'simple-fundraiser' ); ?></li>
						<li><?php esc_html_e( 'Campaign ID: Optional (Uses default if empty)', 'simple-fundraiser' ); ?></li>
						<li><?php esc_html_e( 'Donation Type: Optional (matches types defined in campaign)', 'simple-fundraiser' ); ?></li>
					</ul>

					<p class="submit">
						<input type="submit" name="sf_import_submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Import Donations', 'simple-fundraiser' ); ?>">
					</p>
				</form>
				
				<script>
				jQuery(document).ready(function($) {
					var baseUrl = '<?php echo esc_url( admin_url( 'admin-post.php?action=sf_download_sample' ) ); ?>';
					
					$('#sf_default_campaign').on('change', function() {
						var campaignId = $(this).val();
						var downloadLink = baseUrl;
						
						if (campaignId) {
							downloadLink += '&campaign_id=' + campaignId;
						}
						
						$('#sf_download_sample').attr('href', downloadLink);
					});
				});
				</script>
			</div>
		</div>
		<?php
	}

	/**
	 * Process Import
	 */
	/**
	 * Process Import
	 */
	private function process_import() {
		if ( ! isset( $_POST['sf_import_submit'] ) ) {
			return;
		}

		if ( ! isset( $_POST['sf_import_nonce'] ) || ! wp_verify_nonce( $_POST['sf_import_nonce'], 'sf_import_action' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed.', 'simple-fundraiser' ) . '</p></div>';
			return;
		}

		if ( empty( $_FILES['sf_import_file']['tmp_name'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Please upload a CSV file.', 'simple-fundraiser' ) . '</p></div>';
			return;
		}

		$default_campaign_id = isset( $_POST['sf_default_campaign'] ) ? intval( $_POST['sf_default_campaign'] ) : 0;
		$file = $_FILES['sf_import_file']['tmp_name'];
		
		// Detect delimiter
		$handle = fopen( $file, 'r' );
		if ( false === $handle ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Could not open file.', 'simple-fundraiser' ) . '</p></div>';
			return;
		}

		$first_line = fgets( $handle );
		rewind( $handle );
		$delimiter = ( strpos( $first_line, ';' ) !== false ) ? ';' : ',';

		$row = 0;
		$success_count = 0;
		$errors = array();

		while ( ( $data = fgetcsv( $handle, 1000, $delimiter ) ) !== false ) {
			$row++;
			
			// Clean BOM from first cell if exists
			if ( $row === 1 && isset( $data[0] ) ) {
				$data[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data[0]);
			}
			
			// Skip header row
			// Check if first column is "Date" or third column is not numeric (likely "Amount")
			if ( $row === 1 ) {
				$col0 = isset( $data[0] ) ? strtolower( trim( $data[0] ) ) : '';
				$col2 = isset( $data[2] ) ? $data[2] : '';
				
				if ( $col0 === 'date' || ( ! empty( $col2 ) && ! is_numeric( $col2 ) ) ) {
					continue;
				}
			}

			// Map columns
			$date        = isset( $data[0] ) ? sanitize_text_field( $data[0] ) : current_time( 'Y-m-d' );
			$name        = isset( $data[1] ) ? sanitize_text_field( $data[1] ) : 'Anonymous';
			
			// Amount cleaning: Remove "Rp", remove "." if used as thousand separator (Indonesian format usually)
			// Logic: If contain "." and no "," -> assume dot is thousand sep. Remove it.
			// If contain "," -> replace with "." for floatval if it's decimal.
			// Simple approach: Remove everything except digits and comma/dot.
			// Then if dot exists and looks like thousand separator (e.g. 50.000), remove it.
			// Standardizing: 
			// 1. Remove non-numeric chars except . and ,
			// 2. 50.000 -> 50000
			// 3. 50,000 -> 50000
			// 4. 50.000,00 -> 50000.00
			// 5. 50,000.00 -> 50000.00
			
			$raw_amount = isset( $data[2] ) ? $data[2] : '0';
			// Remove Rp and spaces
			$clean_amount = preg_replace( '/[^0-9.,]/', '', $raw_amount );
			
			// Guess format
			if ( strpos( $clean_amount, '.' ) !== false && strpos( $clean_amount, ',' ) !== false ) {
				// Both exist. 
				// 50.000,00 (ID/EU) -> Remove dot, replace comma with dot
				if ( strrpos( $clean_amount, ',' ) > strrpos( $clean_amount, '.' ) ) {
					$clean_amount = str_replace( '.', '', $clean_amount );
					$clean_amount = str_replace( ',', '.', $clean_amount );
				} else {
					// 50,000.00 (US) -> Remove comma
					$clean_amount = str_replace( ',', '', $clean_amount );
				}
			} elseif ( strpos( $clean_amount, '.' ) !== false ) {
				// Only dot. 50.000 (ID thousand) or 50.00 (US decimal)
				// If 3 decimals, usually thousand (50.000). If 2, usually decimal (50.00).
				// But 50.000 could be 50 if interpreted as float.
				// Heuristic: If there are multiple dots, it's thousands (1.000.000).
				if ( substr_count( $clean_amount, '.' ) > 1 ) {
					$clean_amount = str_replace( '.', '', $clean_amount );
				} else {
					// Single dot. splitting hairs. 
					// Context: User is Indonesian (Sembako, Rp). 
					// Most likely dot is Thousand Separator.
					// EXCEPT if it's the sample file which uses 50000 (no chars).
					// Let's safe bet: assume dot is thousand separator if result > 1000? No.
					// Let's assume standard programming input (50000) or ID input (50.000).
					// If we strip dot, 50.000 becomes 50000. 50.00 becomes 5000.
					// Let's try to remove dots.
					$clean_amount = str_replace( '.', '', $clean_amount );
				}
			} elseif ( strpos( $clean_amount, ',' ) !== false ) {
				// Only comma. 50,000 (US thousand) or 50,00 (ID decimal)
				// If ID context, comma is decimal.
				$clean_amount = str_replace( ',', '.', $clean_amount );
			}
			
			$amount = floatval( $clean_amount );

			$camp_id     = isset( $data[3] ) && ! empty( $data[3] ) ? intval( $data[3] ) : $default_campaign_id;
			$email       = isset( $data[4] ) ? sanitize_email( $data[4] ) : '';
			$message     = isset( $data[5] ) ? sanitize_textarea_field( $data[5] ) : '';
			$type        = isset( $data[6] ) ? sanitize_text_field( $data[6] ) : '';

			if ( $amount <= 0 ) {
				$errors[] = sprintf( __( 'Row %d: Invalid amount (%s)', 'simple-fundraiser' ), $row, $raw_amount );
				continue;
			}

			if ( ! $camp_id ) {
				$errors[] = sprintf( __( 'Row %d: Missing Campaign ID', 'simple-fundraiser' ), $row );
				continue;
			}

			// Create Donation Post
			$post_data = array(
				'post_title'  => $name . ' - ' . sf_format_currency( $amount ),
				'post_type'   => 'sf_donation',
				'post_status' => 'publish',
				'post_date'   => $date . ' 12:00:00',
			);

			$donation_id = wp_insert_post( $post_data );

			if ( ! is_wp_error( $donation_id ) ) {
				update_post_meta( $donation_id, '_sf_campaign_id', $camp_id );
				update_post_meta( $donation_id, '_sf_amount', $amount );
				update_post_meta( $donation_id, '_sf_donor_name', $name );
				update_post_meta( $donation_id, '_sf_donor_email', $email );
				update_post_meta( $donation_id, '_sf_message', $message );
				update_post_meta( $donation_id, '_sf_date', $date );
				
				if ( strtolower( $name ) === 'anonymous' || strtolower( $name ) === 'hamba allah' ) {
					update_post_meta( $donation_id, '_sf_anonymous', '1' );
				}
				
				if ( $type ) {
					update_post_meta( $donation_id, '_sf_donation_type', $type );
				}
				
				$success_count++;
			} else {
				$errors[] = sprintf( __( 'Row %d: Failed to create post', 'simple-fundraiser' ), $row );
			}
		}

		fclose( $handle );

		if ( $success_count > 0 ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Imported %d donations successfully.', 'simple-fundraiser' ), $success_count ) . '</p></div>';
		}
		
		if ( ! empty( $errors ) ) {
			echo '<div class="notice notice-warning is-dismissible">';
			echo '<p><strong>' . esc_html__( 'Import Warning:', 'simple-fundraiser' ) . '</strong></p>';
			echo '<ul style="list-style: disc; margin-left: 20px;">';
			foreach ( array_slice( $errors, 0, 10 ) as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}
			if ( count( $errors ) > 10 ) {
				echo '<li>' . sprintf( esc_html__( '...and %d more errors.', 'simple-fundraiser' ), count( $errors ) - 10 ) . '</li>';
			}
			echo '</ul>';
			echo '</div>';
		}
	}
}
