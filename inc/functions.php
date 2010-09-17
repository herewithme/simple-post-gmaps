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

function getCurrentCoords(){
	$data = "http://geoiptool.com/data.php?IP=".getCurrentIp();
	
	//Get the xml result
    $ch = curl_init();
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_URL, $data );
	$result = curl_exec( $ch );
	curl_close( $ch );
	
	// Test the result
	if( empty( $result ) )
		return false;

	//Transform the result into an xml object
	$data = simplexml_load_string( $result );
	if( empty( $data ) )
		return false;
	
	//Get the latitude and longitude data's
	$lat = (string)$data->marker[0]->attributes()->lat;
	$lng = (string)$data->marker[0]->attributes()->lng;
	
	//If one of the data's is empty return false
	if( empty( $lat ) || empty( $lng ) )
		return false;
	
	//Return the results
	return array( 'latitude' => $lat, 'longitude' => $lng );
	
}

function getCurrentIp(){
	return $_SERVER['REMOTE_ADDR'];
}
?>