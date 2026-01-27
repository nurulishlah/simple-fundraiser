<?php
/**
 * Widget Renderer class - Shared rendering for Classic Widget and Gutenberg Block
 *
 * @package SimpleFundraiser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Widget_Renderer {

	/**
	 * Default settings
	 *
	 * @var array
	 */
	private static $defaults = array(
		'title'              => '',
		'layout'             => 'featured-grid',
		'count'              => 3,
		'order_by'           => 'newest',
		'show_progress_bar'  => true,
		'show_goal'          => true,
		'show_donation_count'=> false,
		'status'             => 'active',
		'custom_class'       => '',
	);

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return self::$defaults;
	}

	/**
	 * Render the widget/block output
	 *
	 * @param array $settings Widget settings
	 * @return string HTML output
	 */
	public static function render( $settings ) {
		$settings = wp_parse_args( $settings, self::$defaults );
		$campaigns = self::get_campaigns( $settings );

		if ( empty( $campaigns ) ) {
			return '<p class="sf-widget-no-campaigns">' . esc_html__( 'No campaigns found.', 'simple-fundraiser' ) . '</p>';
		}

		$wrapper_class = 'sf-campaigns-widget';
		$wrapper_class .= ' sf-layout-' . esc_attr( $settings['layout'] );
		if ( ! empty( $settings['custom_class'] ) ) {
			$wrapper_class .= ' ' . esc_attr( $settings['custom_class'] );
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>">
			<?php if ( ! empty( $settings['title'] ) ) : ?>
				<h3 class="sf-widget-title"><?php echo esc_html( $settings['title'] ); ?></h3>
			<?php endif; ?>

			<?php
			switch ( $settings['layout'] ) {
				case 'carousel':
					self::render_carousel( $campaigns, $settings );
					break;
				case 'compact-list':
					self::render_compact_list( $campaigns, $settings );
					break;
				case 'hero-spotlight':
					self::render_hero_spotlight( $campaigns, $settings );
					break;
				case 'featured-grid':
				default:
					self::render_featured_grid( $campaigns, $settings );
					break;
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get campaigns based on settings
	 *
	 * @param array $settings Widget settings
	 * @return array Campaign posts
	 */
	public static function get_campaigns( $settings ) {
		$args = array(
			'post_type'      => 'sf_campaign',
			'posts_per_page' => absint( $settings['count'] ),
			'post_status'    => 'publish',
		);

		// Order by
		switch ( $settings['order_by'] ) {
			case 'oldest':
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
				break;
			case 'most_funded':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_sf_total_raised';
				$args['order']    = 'DESC';
				break;
			case 'ending_soon':
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = '_sf_end_date';
				$args['order']    = 'ASC';
				$args['meta_query'] = array(
					array(
						'key'     => '_sf_end_date',
						'value'   => current_time( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE',
					),
				);
				break;
			case 'random':
				$args['orderby'] = 'rand';
				break;
			case 'newest':
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
		}

		// Status filter
		if ( 'active' === $settings['status'] ) {
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_sf_end_date',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_sf_end_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			);
		} elseif ( 'completed' === $settings['status'] ) {
			$args['meta_query'][] = array(
				'key'     => '_sf_end_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '<',
				'type'    => 'DATE',
			);
		}

		return get_posts( $args );
	}

	/**
	 * Render Featured Grid layout
	 *
	 * @param array $campaigns Campaign posts
	 * @param array $settings  Widget settings
	 */
	private static function render_featured_grid( $campaigns, $settings ) {
		?>
		<div class="sf-campaigns-grid">
			<?php foreach ( $campaigns as $campaign ) : ?>
				<?php self::render_campaign_card( $campaign, $settings ); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render Carousel layout
	 *
	 * @param array $campaigns Campaign posts
	 * @param array $settings  Widget settings
	 */
	private static function render_carousel( $campaigns, $settings ) {
		?>
		<div class="sf-widget-carousel" data-campaign-count="<?php echo count( $campaigns ); ?>">
			<button class="sf-carousel-nav sf-carousel-prev" aria-label="<?php esc_attr_e( 'Previous', 'simple-fundraiser' ); ?>">
				<span class="dashicons dashicons-arrow-left-alt2"></span>
			</button>
			<div class="sf-carousel-track">
				<?php foreach ( $campaigns as $campaign ) : ?>
					<div class="sf-carousel-slide">
						<?php self::render_campaign_card( $campaign, $settings ); ?>
					</div>
				<?php endforeach; ?>
			</div>
			<button class="sf-carousel-nav sf-carousel-next" aria-label="<?php esc_attr_e( 'Next', 'simple-fundraiser' ); ?>">
				<span class="dashicons dashicons-arrow-right-alt2"></span>
			</button>
			<div class="sf-carousel-dots">
				<?php for ( $i = 0; $i < count( $campaigns ); $i++ ) : ?>
					<button class="sf-carousel-dot<?php echo 0 === $i ? ' active' : ''; ?>" data-index="<?php echo $i; ?>"></button>
				<?php endfor; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Compact List layout
	 *
	 * @param array $campaigns Campaign posts
	 * @param array $settings  Widget settings
	 */
	private static function render_compact_list( $campaigns, $settings ) {
		?>
		<ul class="sf-widget-list">
			<?php foreach ( $campaigns as $campaign ) : ?>
				<?php
				$campaign_id = $campaign->ID;
				$goal        = get_post_meta( $campaign_id, '_sf_goal', true );
				$total       = sf_get_campaign_total( $campaign_id );
				$progress    = sf_get_campaign_progress( $campaign_id );
				$thumbnail   = get_the_post_thumbnail_url( $campaign_id, 'thumbnail' );
				?>
				<li class="sf-widget-list-item">
					<?php if ( $thumbnail ) : ?>
						<a href="<?php echo get_permalink( $campaign_id ); ?>" class="sf-list-thumb">
							<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $campaign->post_title ); ?>">
						</a>
					<?php endif; ?>
					<div class="sf-list-content">
						<h4 class="sf-list-title">
							<a href="<?php echo get_permalink( $campaign_id ); ?>"><?php echo esc_html( $campaign->post_title ); ?></a>
						</h4>
						<?php if ( $settings['show_progress_bar'] ) : ?>
							<div class="sf-list-progress">
								<div class="sf-list-progress-bar" style="width: <?php echo esc_attr( $progress ); ?>%"></div>
							</div>
						<?php endif; ?>
						<?php if ( $settings['show_goal'] && $goal ) : ?>
							<span class="sf-list-amount"><?php echo sf_format_currency( $total ); ?> / <?php echo sf_format_currency( $goal ); ?></span>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Render Hero Spotlight layout (single featured campaign)
	 *
	 * @param array $campaigns Campaign posts
	 * @param array $settings  Widget settings
	 */
	private static function render_hero_spotlight( $campaigns, $settings ) {
		$campaign = $campaigns[0]; // Use first campaign for hero
		$campaign_id = $campaign->ID;
		$goal        = get_post_meta( $campaign_id, '_sf_goal', true );
		$total       = sf_get_campaign_total( $campaign_id );
		$progress    = sf_get_campaign_progress( $campaign_id );
		$thumbnail   = get_the_post_thumbnail_url( $campaign_id, 'large' );
		$excerpt     = get_the_excerpt( $campaign );
		$donation_count = self::get_donation_count( $campaign_id );
		?>
		<div class="sf-widget-hero">
			<?php if ( $thumbnail ) : ?>
				<div class="sf-hero-image">
					<a href="<?php echo get_permalink( $campaign_id ); ?>">
						<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $campaign->post_title ); ?>">
					</a>
				</div>
			<?php endif; ?>
			<div class="sf-hero-content">
				<h2 class="sf-hero-title">
					<a href="<?php echo get_permalink( $campaign_id ); ?>"><?php echo esc_html( $campaign->post_title ); ?></a>
				</h2>
				<?php if ( $excerpt ) : ?>
					<p class="sf-hero-excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 25 ) ); ?></p>
				<?php endif; ?>

				<div class="sf-hero-stats">
					<div class="sf-hero-stat">
						<span class="sf-hero-stat-value"><?php echo sf_format_currency( $total ); ?></span>
						<span class="sf-hero-stat-label"><?php esc_html_e( 'Raised', 'simple-fundraiser' ); ?></span>
					</div>
					<?php if ( $settings['show_goal'] && $goal ) : ?>
						<div class="sf-hero-stat">
							<span class="sf-hero-stat-value"><?php echo sf_format_currency( $goal ); ?></span>
							<span class="sf-hero-stat-label"><?php esc_html_e( 'Goal', 'simple-fundraiser' ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $settings['show_donation_count'] ) : ?>
						<div class="sf-hero-stat">
							<span class="sf-hero-stat-value"><?php echo absint( $donation_count ); ?></span>
							<span class="sf-hero-stat-label"><?php esc_html_e( 'Donors', 'simple-fundraiser' ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $settings['show_progress_bar'] ) : ?>
					<div class="sf-hero-progress">
						<div class="sf-hero-progress-bar" style="width: <?php echo esc_attr( $progress ); ?>%"></div>
					</div>
					<span class="sf-hero-progress-text"><?php echo round( $progress, 1 ); ?>%</span>
				<?php endif; ?>

				<a href="<?php echo get_permalink( $campaign_id ); ?>" class="sf-hero-button">
					<?php esc_html_e( 'Donate Now', 'simple-fundraiser' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single campaign card (reuses archive page styles)
	 *
	 * @param WP_Post $campaign Campaign post object
	 * @param array   $settings Widget settings
	 */
	private static function render_campaign_card( $campaign, $settings ) {
		$campaign_id    = $campaign->ID;
		$goal           = get_post_meta( $campaign_id, '_sf_goal', true );
		$total          = sf_get_campaign_total( $campaign_id );
		$progress       = sf_get_campaign_progress( $campaign_id );
		$deadline       = get_post_meta( $campaign_id, '_sf_deadline', true );
		$donation_count = self::get_donation_count( $campaign_id );
		?>
		<article class="sf-campaign-card">
			<?php if ( has_post_thumbnail( $campaign_id ) ) : ?>
				<div class="sf-campaign-image">
					<a href="<?php echo get_permalink( $campaign_id ); ?>">
						<?php echo get_the_post_thumbnail( $campaign_id, 'medium_large' ); ?>
					</a>
				</div>
			<?php endif; ?>
			
			<div class="sf-campaign-content">
				<h2 class="sf-campaign-title">
					<a href="<?php echo get_permalink( $campaign_id ); ?>"><?php echo esc_html( $campaign->post_title ); ?></a>
				</h2>
				
				<?php if ( $settings['show_progress_bar'] ) : ?>
					<div class="sf-campaign-progress">
						<div class="sf-progress-bar">
							<div class="sf-progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
						</div>
						<div class="sf-progress-stats">
							<span class="sf-raised"><?php echo esc_html( sf_format_currency( $total ) ); ?></span>
							<?php if ( $settings['show_goal'] && $goal ) : ?>
								<span class="sf-goal"><?php esc_html_e( 'of', 'simple-fundraiser' ); ?> <?php echo esc_html( sf_format_currency( $goal ) ); ?></span>
							<?php endif; ?>
						</div>
						<div class="sf-progress-percent">
							<strong><?php echo esc_html( round( $progress, 1 ) ); ?>%</strong>
						</div>
					</div>
				<?php endif; ?>
				
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

				<?php if ( $settings['show_donation_count'] ) : ?>
					<div class="sf-campaign-donors">
						<span class="dashicons dashicons-groups" aria-hidden="true"></span>
						<?php 
						/* translators: %d: number of donors */
						printf( esc_html__( '%d donors', 'simple-fundraiser' ), absint( $donation_count ) ); 
						?>
					</div>
				<?php endif; ?>
				
				<a href="<?php echo get_permalink( $campaign_id ); ?>" class="sf-campaign-button">
					<?php esc_html_e( 'Donate Now', 'simple-fundraiser' ); ?>
				</a>
			</div>
		</article>
		<?php
	}

	/**
	 * Get donation count for a campaign
	 *
	 * @param int $campaign_id Campaign ID
	 * @return int Donation count
	 */
	private static function get_donation_count( $campaign_id ) {
		$donations = get_posts( array(
			'post_type'      => 'sf_donation',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => '_sf_campaign_id',
					'value' => $campaign_id,
				),
			),
			'fields'         => 'ids',
		) );

		return count( $donations );
	}
}
