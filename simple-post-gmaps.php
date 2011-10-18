<?php
/*
Plugin Name: Simple Post GMaps
Plugin URI: http://www.beapi.fr
Description: Allow to geolocalise post with Google Maps (API in v3). No google maps key are required. You can choose with the map the position of the post on admin. You can use shortcode for display the map, or the widget. You can also display a maps with each posts localized on the same maps !
Author: BeAPI
Author URI: http://www.beapi.fr
Version: 3.2
Text Domain: simple-post-gmaps
Domain Path: /languages/
Network: false

----

Copyright 2011 Amaury Balmer (amaury@beapi.fr) - Be-API

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

global $wpdb;
$wpdb->simple_post_gmaps = $wpdb->prefix . 'simple_post_gmaps';
$wpdb->tables[] = 'simple_post_gmaps';

// Constants
define ( 'SGM_VERSION', '3.2' );
define ( 'SGM_OPTION',  'simple-post-gmaps' );

define ( 'SGM_URL',  plugins_url('/', __FILE__) );
define ( 'SGM_DIR',  dirname(__FILE__) );
define ( 'SGM_SLUG', 'maps' );

define ( 'SGM_TOOLTIP', '<div class="infotool-gmap" style="font-size:11px;">
	<h6 style="font-size:13px;text-align:left;margin:0;"><a style="text-decoration:none;" href="%permalink%">%title%</a></h6>
	<p style="margin:0;padding:0;text-align:left;">%description% <a href="%permalink%">%readmore%</a></p>
</div>' );

// Fonctions
require (SGM_DIR . '/inc/functions.php');
require (SGM_DIR . '/inc/class.widget.php');

// Class
require (SGM_DIR . '/inc/class.rewrite.php');
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
	
	global $spgm_obj;
	$spgm_obj['rewrite'] = new Simple_Post_Gmaps_Rewrite();
	$spgm_obj['client']  = new Simple_Post_Gmaps_Client();

	// Load admin
	if ( is_admin() ) {
		if( !class_exists( 'WP_Ajax' ) ) {
			require (SGM_DIR . '/inc/class.ajax.php');
		}
		require (SGM_DIR . '/inc/class.admin.php');
		$spgm_obj['admin'] = new Simple_Post_Gmaps_Admin();
	}
	
	// Widget
	add_action( 'widgets_init', create_function('', 'return register_widget("Simple_Post_Gmaps_Widget");') );
}
?>