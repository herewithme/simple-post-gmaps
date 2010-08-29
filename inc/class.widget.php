<?php
/**
 * Simple_Post_Gmaps_Widget class
 *
 * @package default
 * @author Amaury Balmer
 */
class Simple_Post_Gmaps_Widget extends WP_Widget {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function Simple_Post_Gmaps_Widget() {
		$this->WP_Widget('simple-post-gmaps', __('Related Maps for Post', 'simple-post-gmaps'), array( 'classname' => 'widget_spgm', 'description' => __( "Display the google maps of relative post on singular view.", 'simple-post-gmaps' ) ));
	}
	
	/**
	 * Method call on cliend side
	 *
	 * @param array $args 
	 * @param array $instance 
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function widget( $args, $instance ) {
		global $wp_query;
		
		extract( $args );
		
		if ( !is_singular() )
			return false;
			
		// Geo value exist ?
		$geo_value = get_post_meta( $wp_query->get_queried_object_id(), 'geo', true );
		if ( $geo_value == false || empty($geo_value['latitude']) ) {
			return false;
		}

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? '' : $instance['title']);

		echo $before_widget;
			if ( $title )
				echo $before_title . $title . $after_title;
			
			the_post_googlemaps( $wp_query->get_queried_object_id(), $instance['width'], $instance['height'], $geo_value['latitude'], $geo_value['longitude'], $instance['zoom'], get_the_title($wp_query->get_queried_object_id()) );
		echo $after_widget;
		
		return true;
	}

	/**
	 * Method call for save widget settings
	 *
	 * @param array $new_instance 
	 * @param array $old_instance 
	 * @return void
	 * @author Amaury Balmer
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['title'] 	= strip_tags($new_instance['title']);
		$instance['width'] 	= strip_tags($new_instance['width']);
		$instance['height'] = strip_tags($new_instance['height']);
		$instance['zoom'] 	= strip_tags($new_instance['zoom']);
		
		return $instance;
	}

	/**
	 * Method call for build the admin form widgets
	 *
	 * @param array $instance 
	 * @return void
	 * @author Amaury Balmer
	 */
	function form( $instance ) {
		// Defaults settings
		$instance 	= wp_parse_args( (array) $instance, array( 'title' => '', 'width' => '300px', 'height' => '200px', 'zoom' => 10 ) );
		
		$title 		= esc_attr( $instance['title'] );
		$width 		= esc_attr( $instance['width'] );
		$height 	= esc_attr( $instance['height'] );
		$zoom 		= intval( $instance['zoom'] );
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'simple-post-gmaps' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('width'); ?>"><?php _e( 'Width:', 'simple-post-gmaps' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $width; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('height'); ?>"><?php _e( 'Height:', 'simple-post-gmaps' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo $height; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('zoom'); ?>"><?php _e( 'Zoom:', 'simple-post-gmaps' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('zoom'); ?>" name="<?php echo $this->get_field_name('zoom'); ?>" type="text" value="<?php echo $zoom; ?>" />
		</p>
		<?php
	}

}
?>