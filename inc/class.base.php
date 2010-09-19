<?php
/**
 * Generic class for activation/deactivation...
 *
 * @package default
 * @author Amaury Balmer
 */
class Simple_Post_Gmaps_Base {
	/**
	 * Active the plugin action...
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function activate() {
		global $locale, $wpdb;
		
		// Add option ?
		$new_options = array();
		$new_options['custom-types'] 	= array( 'post' );
		$new_options['language'] 		= substr( $locale, 0, 2 );
		$new_options['region'] 			= substr( $locale, 3, 2 );
		$new_options['tooltip'] 		= SGM_TOOLTIP;
		add_option( SGM_OPTION, $new_options );
		
		// Create table ?
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";

		// Add one library admin function for next function
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Try to create the meta table
		maybe_create_table( $wpdb->simple_post_gmaps, "CREATE TABLE ".$wpdb->simple_post_gmaps." (
			`post_id` INT(20) NOT NULL ,
			`long` DECIMAL( 11,8 ) NOT NULL,
			`lat` DECIMAL( 11,8 ) NOT NULL,
			UNIQUE KEY ( `post_id` )
		) $charset_collate;" );
	}
	
	/**
	 * Deactive the plugin action
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function deactivate() {
	}
}

?>