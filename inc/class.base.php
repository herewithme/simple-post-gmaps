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
		global $locale;
		
		$new_options = array();
		$new_options['custom-types'] 	= array('post');
		$new_options['language'] 		= substr($locale, 0, 2);
		$new_options['region'] 			= substr($locale, 3, 2);
		$new_options['tooltip'] 		= SGM_TOOLTIP;
		
		add_option( SGM_OPTION, $new_options );
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