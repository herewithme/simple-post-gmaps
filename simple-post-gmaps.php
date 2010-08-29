<?php
/*
Plugin Name: Simple Post GMaps
Plugin URI: http://www.beapi.fr
Description: Allow to geolocalise post with Google Maps (API in v3). No google maps key are required. You can choose with the map the position of the post on admin. You can use shortcode for display the map, or the widget. You can also display a maps with each posts localized on the same maps !
Author: Be API
Author URI: http://www.beapi.fr
Version: 3.0.5
Text Domain: simple-post-gmaps
Domain Path: /languages/
Network: false

Copyright 2010 Amaury BALMER (amaury@beapi.fr) - Be-API

This plugin is not free to usage, not open-source, not GPL.
You can't use and modify this plugin without the permission of Be-API. (amaury@be-api.fr)

Todo :
	Options :
		* Region used by Gmaps.
*/

// Folder name
define ( 'SGM_VERSION', '3.0.5' );
define ( 'SGM_OPTION',  'simple-post-gmaps' );

define ( 'SGM_URL', plugins_url('/', __FILE__) );
define ( 'SGM_DIR', dirname(__FILE__) );

// Fonctions
require (SGM_DIR . '/inc/functions.php');
require (SGM_DIR . '/inc/class.widget.php');

// Class
require (SGM_DIR . '/inc/class.base.php');
require (SGM_DIR . '/inc/class.client.php');

// Activate/Desactive Simple Post Gmaps
register_activation_hook  ( __FILE__, array('Simple_Post_Gmaps_Base', 'activate') );
register_deactivation_hook( __FILE__, array('Simple_Post_Gmaps_Base', 'deactivate') );

// init Simple Post Gmaps when all plugins are loaded !
add_action ( 'plugins_loaded', 'init_simple_post_gmaps' );
function init_simple_post_gmaps() {
	// Load translations
	load_plugin_textdomain( 'simple-post-gmaps', false, basename(rtrim(dirname(__FILE__), '/')) . '/languages' );
	
	// Load client
	global $spgm_obj;
	$spgm_obj['client'] = new Simple_Post_Gmaps_Client();
	
	// Load admin
	if ( is_admin() ) {
		require (SGM_DIR . '/inc/class.admin.php');
		$spgm_obj['admin'] = new Simple_Post_Gmaps_Admin();
	}
	
	// Widget
	add_action( 'widgets_init', create_function('', 'return register_widget("Simple_Post_Gmaps_Widget");') );
}
?>