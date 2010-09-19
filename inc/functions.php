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

/**
 * Call a external webservice for find the GPS coordinates of the current user, from the IP.
 *
 * @return void
 * @author Amaury Balmer
 */
function get_current_coordinates() {
	$result = wp_remote_get( "http://geoiptool.com/data.php?IP=".$_SERVER['REMOTE_ADDR'] . $hostname, array( 'timeout' => 5, 'httpversion' => '1.1' ) );
	if ( is_wp_error( $result ) )
		return false;

	// Test the result
	if( wp_remote_retrieve_body($result) == '' )
		return false;

	// Transform the result into an xml object
	$data = simplexml_load_string( wp_remote_retrieve_body($result) );
	if( $data == false )
		return false;

	// Get the latitude and longitude data's
	$lat = (string) $data->marker[0]->attributes()->lat;
	$lng = (string) $data->marker[0]->attributes()->lng;
	
	// If one of the data's is empty return false
	if( empty($lat) || empty($lng) )
		return false;
	
	// Return the results
	return array( 'latitude' => $lat, 'longitude' => $lng );
}
?>