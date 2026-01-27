<?php
/**
 * Template for displaying campaign archive
 *
 * @package SimpleFundraiser
 */

get_header();
?>

	<div class="sf-container">
		<header class="sf-archive-header">
			<h1 class="sf-archive-title"><?php esc_html_e( 'Fundraising Campaigns', 'simple-fundraiser' ); ?></h1>
			<p class="sf-archive-desc"><?php esc_html_e( 'Support our causes and help us make a difference.', 'simple-fundraiser' ); ?></p>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="sf-campaigns-grid">
				<?php while ( have_posts() ) : the_post(); 
					$goal = get_post_meta( get_the_ID(), '_sf_goal', true );
					$total = sf_get_campaign_total( get_the_ID() );
					$progress = sf_get_campaign_progress( get_the_ID() );
					$deadline = get_post_meta( get_the_ID(), '_sf_deadline', true );
				?>
					<article class="sf-campaign-card">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="sf-campaign-image">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail( 'medium_large' ); ?>
								</a>
							</div>
						<?php endif; ?>
						
						<div class="sf-campaign-content">
							<h2 class="sf-campaign-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>
							
							<div class="sf-campaign-progress">
								<div class="sf-progress-bar">
									<div class="sf-progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
								</div>
								<div class="sf-progress-stats">
									<span class="sf-raised"><?php echo esc_html( sf_format_currency( $total ) ); ?></span>
									<span class="sf-goal"><?php esc_html_e( 'of', 'simple-fundraiser' ); ?> <?php echo esc_html( sf_format_currency( $goal ) ); ?></span>
								</div>
								<div class="sf-progress-percent">
									<strong><?php echo esc_html( round( $progress, 1 ) ); ?>%</strong>
								</div>
							</div>
							
							<?php if ( $deadline ) : ?>
								<div class="sf-campaign-deadline">
									<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
									<?php 
									$deadline_date = strtotime( $deadline );
									$now = time();
									$days_left = ceil( ( $deadline_date - $now ) / 86400 );
									
									if ( $days_left > 0 ) {
										/* translators: %d: number of days */
										printf( esc_html__( '%d days left', 'simple-fundraiser' ), $days_left );
									} elseif ( $days_left === 0 ) {
										esc_html_e( 'Last day!', 'simple-fundraiser' );
									} else {
										esc_html_e( 'Campaign ended', 'simple-fundraiser' );
									}
									?>
								</div>
							<?php endif; ?>
							
							<a href="<?php the_permalink(); ?>" class="sf-campaign-button">
								<?php esc_html_e( 'Donate Now', 'simple-fundraiser' ); ?>
							</a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<?php the_posts_pagination( array(
				'mid_size'  => 2,
				'prev_text' => __( '&laquo; Previous', 'simple-fundraiser' ),
				'next_text' => __( 'Next &raquo;', 'simple-fundraiser' ),
			) ); ?>

		<?php else : ?>
			<p class="sf-no-campaigns"><?php esc_html_e( 'No campaigns found. Check back soon!', 'simple-fundraiser' ); ?></p>
		<?php endif; ?>
	</div>

<?php
get_footer();
