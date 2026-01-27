<?php
/**
 * Template for displaying single campaign
 *
 * @package SimpleFundraiser
 */

get_header();
?>

<div class="sf-wrap">
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
					
					<!-- Recent Donations -->
					<div class="sf-recent-donations">
						<h3><?php esc_html_e( 'Recent Donations', 'simple-fundraiser' ); ?></h3>
						<?php
						$donations = get_posts( array(
							'post_type'      => 'sf_donation',
							'posts_per_page' => 5,
							'post_status'    => 'publish',
							'meta_query'     => array(
								array(
									'key'   => '_sf_campaign_id',
									'value' => get_the_ID(),
								),
							),
							'meta_key'       => '_sf_date',
							'orderby'        => 'meta_value',
							'order'          => 'DESC',
						) );
						
						if ( $donations ) : ?>
							<ul class="sf-donations-list">
								<?php foreach ( $donations as $donation ) :
									$anonymous = get_post_meta( $donation->ID, '_sf_anonymous', true );
									$donor_name = get_post_meta( $donation->ID, '_sf_donor_name', true );
									$amount = get_post_meta( $donation->ID, '_sf_amount', true );
									$message = get_post_meta( $donation->ID, '_sf_message', true );
									$date = get_post_meta( $donation->ID, '_sf_date', true );
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
												?>
											</strong>
											<span class="sf-donation-amount"><?php echo esc_html( sf_format_currency( $amount ) ); ?></span>
										</div>
										<?php if ( $message ) : ?>
											<p class="sf-donation-message">"<?php echo esc_html( $message ); ?>"</p>
										<?php endif; ?>
										<span class="sf-donation-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="sf-no-donations"><?php esc_html_e( 'Be the first to donate!', 'simple-fundraiser' ); ?></p>
						<?php endif; ?>
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
					</div>
					
					<!-- Share -->
					<div class="sf-share-card">
						<h4><?php esc_html_e( 'Share This Campaign', 'simple-fundraiser' ); ?></h4>
						<div class="sf-share-buttons">
							<a href="https://wa.me/?text=<?php echo esc_attr( urlencode( get_the_title() . ' - ' . get_permalink() ) ); ?>" 
							   class="sf-share-wa" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'WhatsApp', 'simple-fundraiser' ); ?>
							</a>
							<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( urlencode( get_permalink() ) ); ?>" 
							   class="sf-share-fb" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Facebook', 'simple-fundraiser' ); ?>
							</a>
							<a href="https://twitter.com/intent/tweet?text=<?php echo esc_attr( urlencode( get_the_title() ) ); ?>&url=<?php echo esc_attr( urlencode( get_permalink() ) ); ?>" 
							   class="sf-share-tw" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Twitter', 'simple-fundraiser' ); ?>
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
</div>

<?php
get_footer();
