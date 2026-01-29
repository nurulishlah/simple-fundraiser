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
		// add_action( 'admin_menu', array( $this, 'add_import_menu' ) ); // Moved to Settings tab
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
			array( $this, 'render_import_page' ),
			25
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
		fputcsv( $output, array( 'Date', 'Donor Name', 'Amount', 'Campaign ID', 'Email', 'Message', 'Donation Type', 'Phone', 'Anonymous' ) );
		
		// Sample Data
		fputcsv( $output, array( 
			current_time( 'Y-m-d' ), 
			'John Doe', 
			'50000', 
			$campaign_id ? $campaign_id : '', 
			'john@example.com', 
			'Keep it up!', 
			'Sembako',
			'08123456789',
			'No'
		) );
		
		fclose( $output );
		exit;
	}

	/**
	 * Render import page
	 */
	/**
	 * Render content (for Settings tab)
	 */
	public function render_content() {
		// Handle submission
		$this->process_import();

		// Get all campaigns for dropdown
		$campaigns = get_posts( array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );
		?>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'sf_import_action', 'sf_import_nonce' ); ?>
			
			<table class="form-table">
				<tr>
					<th><label for="sf_default_campaign"><?php esc_html_e( 'Default Campaign', 'simple-fundraiser' ); ?></label></th>
					<td>
						<select name="sf_default_campaign" id="sf_default_campaign" class="regular-text">
							<option value=""><?php esc_html_e( '-- Select Campaign --', 'simple-fundraiser' ); ?></option>
							<?php foreach ( $campaigns as $campaign ) : ?>
								<option value="<?php echo esc_attr( $campaign->ID ); ?>"><?php echo esc_html( $campaign->post_title ); ?> (ID: <?php echo esc_html( $campaign->ID ); ?>)</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Assign to this campaign if "Campaign ID" column is empty in CSV.', 'simple-fundraiser' ); ?>
						</p>
						<br>
						<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=sf_download_sample' ) ); ?>" 
						   id="sf_download_sample" 
						   target="_blank">
							<?php esc_html_e( 'Download Sample CSV', 'simple-fundraiser' ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<th><label for="sf_import_file"><?php esc_html_e( 'File (CSV or Excel)', 'simple-fundraiser' ); ?></label></th>
					<td>
						<input type="file" name="sf_import_file" id="sf_import_file" accept=".csv, .xlsx" required>
					</td>
				</tr>
			</table>
			
			<hr>
			
			<h3><?php esc_html_e( 'CSV Format Guide', 'simple-fundraiser' ); ?></h3>
			<p><?php esc_html_e( 'Your CSV should follow this structure:', 'simple-fundraiser' ); ?></p>
			
			<code style="display: block; padding: 10px; background: #f0f0f1; margin: 10px 0;">
				Date, Donor Name, Amount, Campaign ID, Email, Message, Donation Type, Phone, Anonymous<br>
				2023-10-25, John Doe, 50000, 12, john@example.com, Keep it up, Sembako, 0812xx, No
			</code>
			
			<ul style="font-size: 0.9em; color: #666; list-style: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Date format: YYYY-MM-DD', 'simple-fundraiser' ); ?></li>
				<li><?php esc_html_e( 'Campaign ID: Optional (Uses default if empty)', 'simple-fundraiser' ); ?></li>
				<li><?php esc_html_e( 'Phone & Anonymous: Optional', 'simple-fundraiser' ); ?></li>
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
		<?php
	}

	/**
	 * Process Import
	 */
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
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Please upload a file.', 'simple-fundraiser' ) . '</p></div>';
			return;
		}

		$default_campaign_id = isset( $_POST['sf_default_campaign'] ) ? intval( $_POST['sf_default_campaign'] ) : 0;
		$file = $_FILES['sf_import_file']['tmp_name'];
		$filename = $_FILES['sf_import_file']['name'];

		// Secure File Upload Check
		$file_info = wp_check_filetype_and_ext( $file, $filename );
		$ext       = strtolower( $file_info['ext'] );
		$type      = $file_info['type'];
		
		$allowed_mimes = array( 
			'csv'  => 'text/csv', 
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
		);

		// Fallback for CSV mime types which can vary
		if ( 'csv' === $ext && empty( $type ) ) {
			// Check if it's really a text file
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$real_mime = finfo_file( $finfo, $file );
			finfo_close( $finfo );
			if ( in_array( $real_mime, array( 'text/plain', 'text/csv', 'text/x-csv', 'application/vnd.ms-excel', 'application/csv' ) ) ) {
				$type = 'text/csv';
			}
		}

		if ( ! in_array( $ext, array( 'csv', 'xlsx' ) ) || empty( $type ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid file type. Please upload a valid CSV or Excel file.', 'simple-fundraiser' ) . '</p></div>';
			return;
		}

		$row_count = 0;
		$success_count = 0;
		$errors = array();
		$header_map = array();

		// EXCEL IMPORT
		if ( 'xlsx' === $ext ) {
			if ( ! class_exists( 'Shuchkin\SimpleXLSX' ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Excel library not found.', 'simple-fundraiser' ) . '</p></div>';
				return;
			}

			if ( $xlsx = Shuchkin\SimpleXLSX::parse( $file ) ) {
				foreach ( $xlsx->rows() as $index => $data ) {
					$row_count++;
					
					// Header Row
					if ( $index === 0 ) {
						foreach ( $data as $key => $col_name ) {
							$col_name = strtolower( trim( $col_name ) );
							if ( empty( $col_name ) ) continue;
							if ( strpos( $col_name, 'date' ) !== false || strpos( $col_name, 'tanggal' ) !== false ) $header_map['date'] = $key;
							elseif ( strpos( $col_name, 'name' ) !== false || strpos( $col_name, 'nama' ) !== false ) $header_map['name'] = $key;
							elseif ( strpos( $col_name, 'amount' ) !== false || strpos( $col_name, 'jumlah' ) !== false ) $header_map['amount'] = $key;
							elseif ( strpos( $col_name, 'campaign id' ) !== false || strpos( $col_name, 'id kampanye' ) !== false ) $header_map['campaign_id'] = $key;
							elseif ( strpos( $col_name, 'email' ) !== false ) $header_map['email'] = $key;
							elseif ( strpos( $col_name, 'message' ) !== false || strpos( $col_name, 'pesan' ) !== false ) $header_map['message'] = $key;
							elseif ( strpos( $col_name, 'type' ) !== false || strpos( $col_name, 'tipe' ) !== false ) $header_map['type'] = $key;
							elseif ( strpos( $col_name, 'phone' ) !== false || strpos( $col_name, 'telepon' ) !== false ) $header_map['phone'] = $key;
							elseif ( strpos( $col_name, 'anonymous' ) !== false || strpos( $col_name, 'hamba allah' ) !== false ) $header_map['anonymous'] = $key;
							elseif ( $col_name === 'id' || $col_name === 'donation id' ) $header_map['id'] = $key;
						}
						
						// Default map if empty
						if ( empty( $header_map ) ) {
							$header_map = array( 'date' => 0, 'name' => 1, 'amount' => 2, 'campaign_id' => 3, 'email' => 4, 'message' => 5, 'type' => 6 );
						}
						continue;
					}

					// Process Row
					$result = $this->process_single_row( $data, $header_map, $row_count, $default_campaign_id );
					if ( true === $result ) {
						$success_count++;
					} else {
						$errors[] = $result['error'];
					}
				}
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( Shuchkin\SimpleXLSX::parseError() ) . '</p></div>';
				return;
			}

		} else {
			// CSV IMPORT
			$handle = fopen( $file, 'r' );
			if ( false === $handle ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Could not open file.', 'simple-fundraiser' ) . '</p></div>';
				return;
			}

			// Detect delimiter
			$first_line = fgets( $handle );
			rewind( $handle );
			$delimiter = ( strpos( $first_line, ';' ) !== false ) ? ';' : ',';

			while ( ( $data = fgetcsv( $handle, 1000, $delimiter ) ) !== false ) {
				$row_count++;
				
				// BOM Fix
				if ( $row_count === 1 && isset( $data[0] ) ) {
					$data[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data[0]);
				}

				// Header Row
				if ( $row_count === 1 ) {
					foreach ( $data as $key => $col_name ) {
						$col_name = strtolower( trim( $col_name ) );
						if ( empty( $col_name ) ) continue;
						if ( strpos( $col_name, 'date' ) !== false || strpos( $col_name, 'tanggal' ) !== false ) $header_map['date'] = $key;
						elseif ( strpos( $col_name, 'name' ) !== false || strpos( $col_name, 'nama' ) !== false ) $header_map['name'] = $key;
						elseif ( strpos( $col_name, 'amount' ) !== false || strpos( $col_name, 'jumlah' ) !== false ) $header_map['amount'] = $key;
						elseif ( strpos( $col_name, 'campaign id' ) !== false || strpos( $col_name, 'id kampanye' ) !== false ) $header_map['campaign_id'] = $key;
						elseif ( strpos( $col_name, 'email' ) !== false ) $header_map['email'] = $key;
						elseif ( strpos( $col_name, 'message' ) !== false || strpos( $col_name, 'pesan' ) !== false ) $header_map['message'] = $key;
						elseif ( strpos( $col_name, 'type' ) !== false || strpos( $col_name, 'tipe' ) !== false ) $header_map['type'] = $key;
						elseif ( strpos( $col_name, 'phone' ) !== false || strpos( $col_name, 'telepon' ) !== false ) $header_map['phone'] = $key;
						elseif ( strpos( $col_name, 'anonymous' ) !== false || strpos( $col_name, 'hamba allah' ) !== false ) $header_map['anonymous'] = $key;
						elseif ( $col_name === 'id' || $col_name === 'donation id' ) $header_map['id'] = $key;
					}
					
					if ( empty( $header_map ) ) {
						 $header_map = array( 'date' => 0, 'name' => 1, 'amount' => 2, 'campaign_id' => 3, 'email' => 4, 'message' => 5, 'type' => 6 );
						 if ( isset( $data[2] ) && is_numeric( preg_replace( '/[^0-9]/', '', $data[2] ) ) ) {
							 // Row 1 is data
							 $row_count = 0; 
							 rewind( $handle );
						 } else {
							 continue;
						 }
					} else {
						continue;
					}
				}

				// Process Row
				$result = $this->process_single_row( $data, $header_map, $row_count, $default_campaign_id );
				if ( true === $result ) {
					$success_count++;
				} else {
					$errors[] = $result['error'];
				}
			}
			fclose( $handle );
		}

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

	/**
	 * Process a single row
	 *
	 * @param array $data Row data
	 * @param array $header_map Header mapping
	 * @param int $row_num Row number for error reporting
	 * @param int $default_campaign_id Default campaign ID
	 * @return array|bool True on success, array with error string on failure
	 */
	private function process_single_row( $data, $header_map, $row_num, $default_campaign_id ) {
		// Extract Data using Map
		$get_col = function( $key ) use ( $data, $header_map ) {
			return ( isset( $header_map[ $key ] ) && isset( $data[ $header_map[ $key ] ] ) ) ? $data[ $header_map[ $key ] ] : '';
		};
		
		$date        = $get_col('date') ? sanitize_text_field( $get_col('date') ) : current_time( 'Y-m-d' );
		$name        = $get_col('name') ? sanitize_text_field( $get_col('name') ) : 'Anonymous';
		
		$raw_amount = $get_col('amount') ? $get_col('amount') : '0';
		$clean_amount = preg_replace( '/[^0-9.,]/', '', $raw_amount );
		
		// Amount Cleaning Logic
		if ( strpos( $clean_amount, '.' ) !== false && strpos( $clean_amount, ',' ) !== false ) {
			if ( strrpos( $clean_amount, ',' ) > strrpos( $clean_amount, '.' ) ) {
				$clean_amount = str_replace( '.', '', $clean_amount );
				$clean_amount = str_replace( ',', '.', $clean_amount );
			} else {
				$clean_amount = str_replace( ',', '', $clean_amount );
			}
		} elseif ( strpos( $clean_amount, '.' ) !== false ) {
			if ( substr_count( $clean_amount, '.' ) > 1 ) {
				$clean_amount = str_replace( '.', '', $clean_amount );
			} else {
				$clean_amount = str_replace( '.', '', $clean_amount );
			}
		} elseif ( strpos( $clean_amount, ',' ) !== false ) {
			$clean_amount = str_replace( ',', '.', $clean_amount );
		}
		
		$amount = floatval( $clean_amount );
		
		$camp_id_raw = $get_col('campaign_id');
		$camp_id     = ! empty( $camp_id_raw ) ? intval( $camp_id_raw ) : $default_campaign_id;
		
		$email       = sanitize_email( $get_col('email') );
		$message     = sanitize_textarea_field( $get_col('message') );
		$type        = sanitize_text_field( $get_col('type') );
		$phone       = sanitize_text_field( $get_col('phone') );
		$anon_val    = strtolower( trim( $get_col('anonymous') ) );
		$csv_id      = isset( $header_map['id'] ) ? intval( $get_col('id') ) : 0;
		
		// Default Donation Type Fallback
		if ( empty( $type ) && $camp_id ) {
			$default_type = get_post_meta( $camp_id, '_sf_default_donation_type', true );
			if ( $default_type ) {
				$type = $default_type;
			}
		}
		
		if ( $amount <= 0 ) {
			return array( 'error' => sprintf( __( 'Row %d: Invalid amount (%s)', 'simple-fundraiser' ), $row_num, $raw_amount ) );
		}
		
		if ( ! $camp_id ) {
			return array( 'error' => sprintf( __( 'Row %d: Missing Campaign ID', 'simple-fundraiser' ), $row_num ) );
		}
		
		// Duplicate Check (ID based)
		if ( $csv_id > 0 && get_post( $csv_id ) ) {
			return array( 'error' => sprintf( __( 'Row %d: Duplicate skipped (ID %d already exists)', 'simple-fundraiser' ), $row_num, $csv_id ) );
		}
		
		// Create Donation Post
		$post_data = array(
			'post_title'  => $name . ' - ' . sf_format_currency( $amount ),
			'post_type'   => 'sf_donation',
			'post_status' => 'publish',
			'post_date'   => $date . ' 12:00:00',
		);
		
		if ( $csv_id > 0 ) {
			$post_data['import_id'] = $csv_id; // Store import ID if needed, or maybe try to force ID? WP rarely allows forcing ID easily without filter.
			// Actually WP insert post doesn't accept ID easily.
			// Users usually import to restore or migrate.
			// For now, ignoring preserving ID as post_ID, just skipping duplicates.
		}
		
		$donation_id = wp_insert_post( $post_data );
		
		if ( ! is_wp_error( $donation_id ) ) {
			update_post_meta( $donation_id, '_sf_campaign_id', $camp_id );
			update_post_meta( $donation_id, '_sf_amount', $amount );
			update_post_meta( $donation_id, '_sf_donor_name', $name );
			update_post_meta( $donation_id, '_sf_donor_email', $email );
			update_post_meta( $donation_id, '_sf_message', $message );
			update_post_meta( $donation_id, '_sf_date', $date );
			
			if ( $phone ) {
				update_post_meta( $donation_id, '_sf_donor_phone', $phone );
			}
			
			// Anonymous Check
			if ( $anon_val === 'yes' || $anon_val === 'y' || $anon_val === 'ya' || $anon_val === '1' || strtolower( $name ) === 'anonymous' || strtolower( $name ) === 'hamba allah' ) {
				update_post_meta( $donation_id, '_sf_anonymous', '1' );
			}
			
			if ( $type ) {
				update_post_meta( $donation_id, '_sf_donation_type', $type );
			}
			
			return true;
		} else {
			return array( 'error' => sprintf( __( 'Row %d: Failed to create post', 'simple-fundraiser' ), $row_num ) );
		}
	}
}
