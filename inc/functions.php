<?php
/**
 * display a google maps, just call get_post_googlemaps() function
 *
 * @param string $id 
 * @param string $width 
 * @param string $height 
 * @param string $latitude 
 * @param string $longitude 
 * @param string $zoom 
 * @param string $title 
 * @return void
 * @author Amaury Balmer
 */
function the_post_googlemaps( $id = 0, $width = '400px', $height = '300px', $latitude = '', $longitude = '', $zoom = 10, $title = '') {
	echo get_post_googlemaps( $id, $width, $height, $latitude, $longitude, $zoom, $title );
}

/**
 * Build HTML for display the post google maps.
 *
 * @param string $id 
 * @param string $width 
 * @param string $height 
 * @param string $latitude 
 * @param string $longitude 
 * @param string $zoom 
 * @param string $title 
 * @return void
 * @author Amaury Balmer
 */
function get_post_googlemaps( $id = 0, $width = '400px', $height = '300px', $latitude = '', $longitude = '', $zoom = 10, $title = '' ) {
	global $spgm_obj;
	return $spgm_obj['client']->buildPostGmaps( $id, $width, $height, $latitude, $longitude, $zoom, $title );
}
?>