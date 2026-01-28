<?php
/**
 * Classic Widget class - WP_Widget for displaying campaigns
 *
 * @package SimpleFundraiser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SF_Campaigns_Widget extends WP_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'sf_campaigns_widget',
			__( 'Simple Fundraiser - Campaigns', 'simple-fundraiser' ),
			array(
				'description' => __( 'Display fundraising campaigns with various layouts.', 'simple-fundraiser' ),
				'classname'   => 'sf-campaigns-widget-wrapper',
			)
		);
	}

	/**
	 * Front-end display of widget
	 *
	 * @param array $args     Widget arguments
	 * @param array $instance Saved values from database
	 */
	public function widget( $args, $instance ) {
		$defaults = SF_Widget_Renderer::get_defaults();
		$instance = wp_parse_args( $instance, $defaults );

		echo $args['before_widget'];
		echo SF_Widget_Renderer::render( $instance );
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form
	 *
	 * @param array $instance Previously saved values from database
	 */
	public function form( $instance ) {
		$defaults = SF_Widget_Renderer::get_defaults();
		$instance = wp_parse_args( $instance, $defaults );

		$layouts = array(
			'featured-grid'  => __( 'Featured Grid', 'simple-fundraiser' ),
			'carousel'       => __( 'Carousel', 'simple-fundraiser' ),
			'compact-list'   => __( 'Compact List', 'simple-fundraiser' ),
			'hero-spotlight' => __( 'Hero Spotlight', 'simple-fundraiser' ),
		);

		$order_options = array(
			'newest'       => __( 'Newest', 'simple-fundraiser' ),
			'oldest'       => __( 'Oldest', 'simple-fundraiser' ),
			'most_funded'  => __( 'Most Funded', 'simple-fundraiser' ),
			'ending_soon'  => __( 'Ending Soon', 'simple-fundraiser' ),
			'random'       => __( 'Random', 'simple-fundraiser' ),
		);

		$status_options = array(
			'active'    => __( 'Active Only', 'simple-fundraiser' ),
			'completed' => __( 'Completed Only', 'simple-fundraiser' ),
			'all'       => __( 'All', 'simple-fundraiser' ),
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'simple-fundraiser' ); ?>
			</label>
			<input 
				class="widefat" 
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" 
				type="text" 
				value="<?php echo esc_attr( $instance['title'] ); ?>"
			>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>">
				<?php esc_html_e( 'Layout:', 'simple-fundraiser' ); ?>
			</label>
			<select 
				class="widefat" 
				id="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>"
			>
				<?php foreach ( $layouts as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $instance['layout'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
				<?php esc_html_e( 'Number of Campaigns:', 'simple-fundraiser' ); ?>
			</label>
			<input 
				class="tiny-text" 
				id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" 
				type="number" 
				min="1" 
				max="12" 
				step="1" 
				value="<?php echo absint( $instance['count'] ); ?>"
			>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'order_by' ) ); ?>">
				<?php esc_html_e( 'Order By:', 'simple-fundraiser' ); ?>
			</label>
			<select 
				class="widefat" 
				id="<?php echo esc_attr( $this->get_field_id( 'order_by' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'order_by' ) ); ?>"
			>
				<?php foreach ( $order_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $instance['order_by'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'status' ) ); ?>">
				<?php esc_html_e( 'Status Filter:', 'simple-fundraiser' ); ?>
			</label>
			<select 
				class="widefat" 
				id="<?php echo esc_attr( $this->get_field_id( 'status' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'status' ) ); ?>"
			>
				<?php foreach ( $status_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $instance['status'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<input 
				class="checkbox" 
				type="checkbox" 
				id="<?php echo esc_attr( $this->get_field_id( 'show_progress_bar' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'show_progress_bar' ) ); ?>"
				<?php checked( $instance['show_progress_bar'] ); ?>
			>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_progress_bar' ) ); ?>">
				<?php esc_html_e( 'Show Progress Bar', 'simple-fundraiser' ); ?>
			</label>
		</p>

		<p>
			<input 
				class="checkbox" 
				type="checkbox" 
				id="<?php echo esc_attr( $this->get_field_id( 'show_goal' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'show_goal' ) ); ?>"
				<?php checked( $instance['show_goal'] ); ?>
			>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_goal' ) ); ?>">
				<?php esc_html_e( 'Show Goal Amount', 'simple-fundraiser' ); ?>
			</label>
		</p>

		<p>
			<input 
				class="checkbox" 
				type="checkbox" 
				id="<?php echo esc_attr( $this->get_field_id( 'show_donation_count' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'show_donation_count' ) ); ?>"
				<?php checked( $instance['show_donation_count'] ); ?>
			>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_donation_count' ) ); ?>">
				<?php esc_html_e( 'Show Donation Count', 'simple-fundraiser' ); ?>
			</label>
		</p>

		<p>
			<input 
				class="checkbox" 
				type="checkbox" 
				id="<?php echo esc_attr( $this->get_field_id( 'show_nav_arrows' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'show_nav_arrows' ) ); ?>"
				<?php checked( $instance['show_nav_arrows'] ); ?>
			>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_nav_arrows' ) ); ?>">
				<?php esc_html_e( 'Show Carousel Navigation Arrows', 'simple-fundraiser' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'custom_class' ) ); ?>">
				<?php esc_html_e( 'Custom CSS Class:', 'simple-fundraiser' ); ?>
			</label>
			<input 
				class="widefat" 
				id="<?php echo esc_attr( $this->get_field_id( 'custom_class' ) ); ?>" 
				name="<?php echo esc_attr( $this->get_field_name( 'custom_class' ) ); ?>" 
				type="text" 
				value="<?php echo esc_attr( $instance['custom_class'] ); ?>"
			>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved
	 *
	 * @param array $new_instance Values just sent to be saved
	 * @param array $old_instance Previously saved values from database
	 * @return array Updated safe values to be saved
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		
		$instance['title']              = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['layout']             = sanitize_key( $new_instance['layout'] ?? 'featured-grid' );
		$instance['count']              = absint( $new_instance['count'] ?? 3 );
		$instance['order_by']           = sanitize_key( $new_instance['order_by'] ?? 'newest' );
		$instance['status']             = sanitize_key( $new_instance['status'] ?? 'active' );
		$instance['show_progress_bar']  = ! empty( $new_instance['show_progress_bar'] );
		$instance['show_goal']          = ! empty( $new_instance['show_goal'] );
		$instance['show_donation_count']= ! empty( $new_instance['show_donation_count'] );
		$instance['show_nav_arrows']    = ! empty( $new_instance['show_nav_arrows'] );
		$instance['custom_class']       = sanitize_html_class( $new_instance['custom_class'] ?? '' );

		// Validate layout
		$valid_layouts = array( 'featured-grid', 'carousel', 'compact-list', 'hero-spotlight' );
		if ( ! in_array( $instance['layout'], $valid_layouts, true ) ) {
			$instance['layout'] = 'featured-grid';
		}

		// Validate order_by
		$valid_orders = array( 'newest', 'oldest', 'most_funded', 'ending_soon', 'random' );
		if ( ! in_array( $instance['order_by'], $valid_orders, true ) ) {
			$instance['order_by'] = 'newest';
		}

		// Validate status
		$valid_statuses = array( 'active', 'completed', 'all' );
		if ( ! in_array( $instance['status'], $valid_statuses, true ) ) {
			$instance['status'] = 'active';
		}

		// Validate count
		$instance['count'] = max( 1, min( 12, $instance['count'] ) );

		return $instance;
	}
}

/**
 * Register the widget
 */
function sf_register_campaigns_widget() {
	register_widget( 'SF_Campaigns_Widget' );
}
add_action( 'widgets_init', 'sf_register_campaigns_widget' );
