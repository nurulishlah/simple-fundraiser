<?php
/**
 * Template for displaying single campaign
 *
 * @package SimpleFundraiser
 */

get_header();
?>

	<div class="sf-container">
		<?php while ( have_posts() ) : the_post(); 
			$goal = get_post_meta( get_the_ID(), '_sf_goal', true );
			$total = sf_get_campaign_total( get_the_ID() );
			$progress = sf_get_campaign_progress( get_the_ID() );
			$deadline = get_post_meta( get_the_ID(), '_sf_deadline', true );
			$bank_name = get_post_meta( get_the_ID(), '_sf_bank_name', true );
			$account_number = get_post_meta( get_the_ID(), '_sf_account_number', true );
			$account_holder = get_post_meta( get_the_ID(), '_sf_account_holder', true );
			$qris_image = get_post_meta( get_the_ID(), '_sf_qris_image', true );
		?>
			<article class="sf-single-campaign">
				<div class="sf-single-main">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="sf-single-image">
							<?php the_post_thumbnail( 'large' ); ?>
						</div>
					<?php endif; ?>
					
					<h1 class="sf-single-title"><?php the_title(); ?></h1>
					
					<div class="sf-single-content">
						<?php the_content(); ?>
					</div>
					
					<!-- Campaign Data Tabs -->
					<div class="sf-campaign-tabs">
						<ul class="sf-tabs-nav">
							<li class="active" data-tab="sf-tab-donations">
								<?php esc_html_e( 'Donations', 'simple-fundraiser' ); ?>
								<span class="sf-tab-count"><?php echo sf_get_donation_count( get_the_ID() ); ?></span>
							</li>
							
							<?php
							// Check distribution visibility to decide if we show the tab
							$dist_visibility = get_post_meta( get_the_ID(), '_sf_dist_visibility', true );
							if ( ! $dist_visibility ) $dist_visibility = 'public';
							$show_dist_tab = ( 'private' !== $dist_visibility || current_user_can( 'edit_posts' ) );
							
							if ( $show_dist_tab ) : 
							?>
								<li data-tab="sf-tab-distribution">
									<?php esc_html_e( 'Fund Distribution', 'simple-fundraiser' ); ?>
								</li>
							<?php endif; ?>
						</ul>

						<!-- Donations Tab Content -->
						<div id="sf-tab-donations" class="sf-tab-content active">
							<!-- Recent Donations -->
							<div class="sf-recent-donations" id="sf-donations-wrapper" data-campaign-id="<?php the_ID(); ?>">
								<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e9ecef; padding-bottom: 15px; margin-bottom: 20px;">
									<h3 style="margin: 0; border: none; padding: 0;"><?php esc_html_e( 'Recent Donations', 'simple-fundraiser' ); ?></h3>
								</div>
								
								<div id="sf-donations-content">
								
								<?php
								// Params
								$paged = isset( $_GET['sf_page'] ) ? max( 1, intval( $_GET['sf_page'] ) ) : 1;
								$sort = isset( $_GET['sf_sort'] ) ? sanitize_text_field( $_GET['sf_sort'] ) : 'newest';
								
								// Sort args
								$args = array(
									'post_type'      => 'sf_donation',
									'posts_per_page' => 10,
									'paged'          => $paged,
									'post_status'    => 'publish',
									'meta_query'     => array(
										array(
											'key'   => '_sf_campaign_id',
											'value' => get_the_ID(),
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
								
								if ( $donations_query->have_posts() ) : 
								?>
								
									<!-- Sorting Controls -->
									<div class="sf-donation-controls">
										<form method="get" class="sf-sort-form">
											<label for="sf_sort"><?php esc_html_e( 'Sort by:', 'simple-fundraiser' ); ?></label>
											<select name="sf_sort" id="sf_sort">
												<option value="newest" <?php selected( $sort, 'newest' ); ?>><?php esc_html_e( 'Newest', 'simple-fundraiser' ); ?></option>
												<option value="oldest" <?php selected( $sort, 'oldest' ); ?>><?php esc_html_e( 'Oldest', 'simple-fundraiser' ); ?></option>
												<option value="amount_high" <?php selected( $sort, 'amount_high' ); ?>><?php esc_html_e( 'Highest Amount', 'simple-fundraiser' ); ?></option>
												<option value="amount_low" <?php selected( $sort, 'amount_low' ); ?>><?php esc_html_e( 'Lowest Amount', 'simple-fundraiser' ); ?></option>
											</select>
										</form>
									</div>

									<ul class="sf-donations-list">
										<?php while ( $donations_query->have_posts() ) : $donations_query->the_post();
											$donation_id = get_the_ID();
											$anonymous = get_post_meta( $donation_id, '_sf_anonymous', true );
											$donor_name = get_post_meta( $donation_id, '_sf_donor_name', true );
											$amount = get_post_meta( $donation_id, '_sf_amount', true );
											$message = get_post_meta( $donation_id, '_sf_message', true );
											$date = get_post_meta( $donation_id, '_sf_date', true );
										?>
											<li class="sf-donation-item">
												<div class="sf-donation-info">
													<strong class="sf-donor-name">
														<?php 
														if ( $anonymous === '1' ) {
															esc_html_e( 'Anonymous', 'simple-fundraiser' );
														} else {
															echo esc_html( $donor_name ? $donor_name : __( 'Someone', 'simple-fundraiser' ) );
														}
														
														// Show donation type if exists
														$d_type = get_post_meta( $donation_id, '_sf_donation_type', true );
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
										<?php endwhile; wp_reset_postdata(); ?>
									</ul>
									
									<div class="sf-pagination">
										<?php
										echo paginate_links( array(
											'base'      => add_query_arg( 'sf_page', '%#%' ),
											'format'    => '',
											'current'   => $paged,
											'total'     => $donations_query->max_num_pages,
											'prev_text' => __( '&laquo; Prev', 'simple-fundraiser' ),
											'next_text' => __( 'Next &raquo;', 'simple-fundraiser' ),
											'type'      => 'list',
											'add_args'  => array( 'sf_sort' => $sort ),
										) );
										?>
									</div>

								<?php else : ?>
									<p class="sf-no-donations"><?php esc_html_e( 'Be the first to donate!', 'simple-fundraiser' ); ?></p>
								<?php endif; ?>
								</div> <!-- #sf-donations-content -->
							</div>
						</div>

						<!-- Distribution Tab Content -->
						<?php if ( $show_dist_tab ) : 
							$total_raised = sf_get_campaign_total( get_the_ID() );
							$total_dist = SF_Distribution_CPT::get_total_distributed( get_the_ID() );
							$balance = $total_raised - $total_dist;
						?>
							<div id="sf-tab-distribution" class="sf-tab-content">
								<div class="sf-distribution-section">
									<h3><?php esc_html_e( 'Fund Distribution Report', 'simple-fundraiser' ); ?></h3>
									
									<!-- Summary Box -->
									<div class="sf-distribution-summary">
										<div class="sf-dist-summary-grid">
											<div class="sf-dist-stat">
												<span class="sf-dist-stat-label"><?php esc_html_e( 'Total Collected', 'simple-fundraiser' ); ?></span>
												<span class="sf-dist-stat-value"><?php echo esc_html( sf_format_currency( $total_raised ) ); ?></span>
											</div>
											<div class="sf-dist-stat">
												<span class="sf-dist-stat-label"><?php esc_html_e( 'Distributed', 'simple-fundraiser' ); ?></span>
												<span class="sf-dist-stat-value sf-neutral"><?php echo esc_html( sf_format_currency( $total_dist ) ); ?></span>
											</div>
											<div class="sf-dist-stat">
												<span class="sf-dist-stat-label"><?php esc_html_e( 'Remaining Balance', 'simple-fundraiser' ); ?></span>
												<span class="sf-dist-stat-value sf-positive"><?php echo esc_html( sf_format_currency( $balance ) ); ?></span>
											</div>
										</div>
									</div>
									
									<!-- Access Control -->
									<div id="sf-distribution-report-container" data-campaign-id="<?php the_ID(); ?>" class="<?php echo 'public' === $dist_visibility || current_user_can( 'edit_posts' ) ? 'sf-public-access' : 'sf-restricted-access'; ?>">
										<?php if ( 'password' === $dist_visibility && ! current_user_can( 'edit_posts' ) ) : ?>
											<div class="sf-distribution-locked" style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px;">
												<span class="dashicons dashicons-lock" style="font-size: 40px; width: 40px; height: 40px; color: #6c757d; margin-bottom: 10px;"></span>
												<p><?php esc_html_e( 'This report is password protected.', 'simple-fundraiser' ); ?></p>
												<button class="sf-view-report-btn"><?php esc_html_e( 'View Full Report', 'simple-fundraiser' ); ?></button>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<!-- Password Modal -->
					<div id="sf-password-overlay" class="sf-password-overlay">
						<div class="sf-password-modal">
							<h3><?php esc_html_e( 'Enter Password', 'simple-fundraiser' ); ?></h3>
							<p><?php esc_html_e( 'Please enter the password to view the distribution details.', 'simple-fundraiser' ); ?></p>
							<input type="password" id="sf-password-input" class="sf-password-input" placeholder="<?php esc_attr_e( 'Password', 'simple-fundraiser' ); ?>">
							<button id="sf-password-submit" class="sf-password-submit"><?php esc_html_e( 'Submit', 'simple-fundraiser' ); ?></button>
							<div class="sf-password-error"></div>
						</div>
					</div>
				</div>
				
				<aside class="sf-single-sidebar">
					<!-- Progress Card -->
					<div class="sf-progress-card">
						<div class="sf-progress-amount">
							<span class="sf-raised-amount"><?php echo esc_html( sf_format_currency( $total ) ); ?></span>
							<span class="sf-goal-text"><?php esc_html_e( 'raised of', 'simple-fundraiser' ); ?> <?php echo esc_html( sf_format_currency( $goal ) ); ?> <?php esc_html_e( 'goal', 'simple-fundraiser' ); ?></span>
						</div>
						
						<div class="sf-progress-bar sf-progress-bar-large">
							<div class="sf-progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
						</div>
						
						<div class="sf-progress-meta">
							<span class="sf-progress-percent"><?php echo esc_html( round( $progress, 1 ) ); ?>%</span>
							
							<div class="sf-campaign-dates">
								<span class="sf-date-start" title="<?php esc_attr_e( 'Campaign Started', 'simple-fundraiser' ); ?>">
									<span class="dashicons dashicons-calendar-alt"></span>
									<?php echo esc_html( get_the_date() ); ?>
								</span>
								
								<?php if ( $deadline ) : 
									$deadline_date = strtotime( $deadline );
									$days_left = ceil( ( $deadline_date - time() ) / 86400 );
								?>
									<span class="sf-days-left">
										<?php 
										if ( $days_left > 0 ) {
											/* translators: %d: number of days */
											printf( esc_html__( '%d days left', 'simple-fundraiser' ), $days_left );
										} elseif ( $days_left === 0 ) {
											esc_html_e( 'Last day!', 'simple-fundraiser' );
										} else {
											esc_html_e( 'Ended', 'simple-fundraiser' );
										}
										?>
									</span>
								<?php endif; ?>
							</div>
						</div>
						
						<?php
						// Calculate breakdown
						$type_totals = array();
						$donations = get_posts( array(
							'post_type'      => 'sf_donation',
							'posts_per_page' => -1,
							'post_status'    => 'publish',
							'meta_query'     => array(
								array(
									'key'   => '_sf_campaign_id',
									'value' => get_the_ID(),
								),
							),
							'fields'         => 'ids',
						) );
						
						foreach ( $donations as $donation_id ) {
							$d_amount = get_post_meta( $donation_id, '_sf_amount', true );
							$d_type = get_post_meta( $donation_id, '_sf_donation_type', true );
							if ( $d_type ) {
								if ( ! isset( $type_totals[ $d_type ] ) ) {
									$type_totals[ $d_type ] = 0;
								}
								$type_totals[ $d_type ] += floatval( $d_amount );
							}
						}
						
						if ( ! empty( $type_totals ) ) :
						?>
							<div class="sf-breakdown">
								<?php foreach ( $type_totals as $type_name => $type_amount ) : ?>
									<div class="sf-breakdown-item">
										<span class="sf-breakdown-label"><?php echo esc_html( $type_name ); ?></span>
										<span class="sf-breakdown-value"><?php echo esc_html( sf_format_currency( $type_amount ) ); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
							<style>
								.sf-breakdown {
									background: #f8f9fa;
									border-radius: 8px;
									padding: 15px;
									margin-top: 20px;
									font-size: 0.9rem;
								}
								.sf-breakdown-item {
									display: flex;
									justify-content: space-between;
									margin-bottom: 5px;
									padding-bottom: 5px;
									border-bottom: 1px solid #e9ecef;
								}
								.sf-breakdown-item:last-child {
									margin-bottom: 0;
									padding-bottom: 0;
									border-bottom: none;
								}
								.sf-breakdown-label {
									color: #666;
									font-weight: 500;
								}
								.sf-breakdown-value {
									color: #1a1a2e;
									font-weight: 600;
								}
							</style>
						<?php endif; ?>
					</div>
					
					<!-- Payment Info -->
					<div class="sf-payment-card">
						<h3><?php esc_html_e( 'How to Donate', 'simple-fundraiser' ); ?></h3>
						
						<?php if ( $qris_image ) : ?>
							<div class="sf-qris-section">
								<h4><?php esc_html_e( 'Scan QRIS', 'simple-fundraiser' ); ?></h4>
								<div class="sf-qris-image">
									<img src="<?php echo esc_url( wp_get_attachment_url( $qris_image ) ); ?>" alt="<?php esc_attr_e( 'QRIS Code', 'simple-fundraiser' ); ?>">
								</div>
							</div>
						<?php endif; ?>
						
						<?php if ( $bank_name && $account_number ) : ?>
							<div class="sf-bank-section">
								<h4><?php esc_html_e( 'Bank Transfer', 'simple-fundraiser' ); ?></h4>
								<div class="sf-bank-info">
									<div class="sf-bank-row">
										<span class="sf-bank-label"><?php esc_html_e( 'Bank', 'simple-fundraiser' ); ?></span>
										<span class="sf-bank-value"><?php echo esc_html( $bank_name ); ?></span>
									</div>
									<div class="sf-bank-row">
										<span class="sf-bank-label"><?php esc_html_e( 'Account No.', 'simple-fundraiser' ); ?></span>
										<span class="sf-bank-value sf-account-number"><?php echo esc_html( $account_number ); ?></span>
									</div>
									<div class="sf-bank-row">
										<span class="sf-bank-label"><?php esc_html_e( 'Holder', 'simple-fundraiser' ); ?></span>
										<span class="sf-bank-value"><?php echo esc_html( $account_holder ); ?></span>
									</div>
								</div>
							</div>
						<?php endif; ?>
						
						<p class="sf-payment-note">
							<?php esc_html_e( 'After transferring, please contact us to confirm your donation.', 'simple-fundraiser' ); ?>
						</p>
						
						<?php 
						$contact_info = get_post_meta( get_the_ID(), '_sf_contact_info', true );
						if ( $contact_info ) :
							$message = sprintf( 
								__( 'Hello, I want to confirm my donation for %s.', 'simple-fundraiser' ), 
								get_the_title() 
							);
							$wa_url = 'https://wa.me/' . esc_attr( $contact_info ) . '?text=' . urlencode( $message );
						?>
							<a href="<?php echo esc_url( $wa_url ); ?>" class="sf-confirm-button" target="_blank" rel="noopener noreferrer">
								<span class="dashicons dashicons-whatsapp" style="vertical-align: middle; margin-right: 5px;"></span>
								<?php esc_html_e( 'Confirm Donation', 'simple-fundraiser' ); ?>
							</a>
						<?php endif; ?>
					</div>
					
					<!-- Share -->
					<div class="sf-share-card">
						<h4><?php esc_html_e( 'Share This Campaign', 'simple-fundraiser' ); ?></h4>
						<div class="sf-share-buttons">
							<a href="https://wa.me/?text=<?php echo esc_attr( urlencode( get_the_title() . ' - ' . get_permalink() ) ); ?>" 
							   class="sf-share-wa" target="_blank" rel="noopener noreferrer">
								<span class="dashicons dashicons-whatsapp" style="vertical-align: middle;"></span>
								<?php esc_html_e( 'WhatsApp', 'simple-fundraiser' ); ?>
							</a>
							<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( urlencode( get_permalink() ) ); ?>" 
							   class="sf-share-fb" target="_blank" rel="noopener noreferrer">
								<span class="dashicons dashicons-facebook" style="vertical-align: middle;"></span>
								<?php esc_html_e( 'Facebook', 'simple-fundraiser' ); ?>
							</a>
							<a href="https://twitter.com/intent/tweet?text=<?php echo esc_attr( urlencode( get_the_title() ) ); ?>&url=<?php echo esc_attr( urlencode( get_permalink() ) ); ?>" 
							   class="sf-share-tw" target="_blank" rel="noopener noreferrer">
								<span class="dashicons dashicons-twitter" style="vertical-align: middle;"></span>
								<?php esc_html_e( 'X', 'simple-fundraiser' ); ?>
							</a>
						</div>
					</div>
				</aside>
			</article>
		<?php endwhile; ?>
		
		<a href="<?php echo esc_url( get_post_type_archive_link( 'sf_campaign' ) ); ?>" class="sf-back-link">
			&larr; <?php esc_html_e( 'Back to All Campaigns', 'simple-fundraiser' ); ?>
		</a>
	</div>

<?php
get_footer();
