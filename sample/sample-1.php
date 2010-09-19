<?php 
//In single view make this to get all the posts by distance by this post coordinates and exclude th current post from the results :
$coords = get_post_meta( $post->ID , 'geo', false );
if( !empty( $coords[0] ) && !empty( $coords[0]['latitude'] ) && !empty( $coords[0]['longitude'] ) ){
	$query = new WP_Query( array(
		'orderby' 	=> 'distance',
		'latitude' 	=> $coords[0]['latitude'],
		'longitude' => $coords[0]['longitude'],
		'post__not_in' 	=> array( $post->ID )
	) );
}
?>