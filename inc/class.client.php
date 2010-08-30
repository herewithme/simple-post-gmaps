<?php
class Simple_Post_Gmaps_Client {

	var $longitude = null;
	var $latitude = null;
	
	/**
	 * Constructor...
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function Simple_Post_Gmaps_Client() {
		// Get settings on DB
		$current_settings = get_option( SGM_OPTION );
		
		if ( !isset($current_settings['tooltip']) && empty($current_settings['tooltip']) ) {
			$current_settings['tooltip'] = SGM_TOOLTIP;
		}
		
		if ( !is_admin() ) {
			add_filter( 'the_content', 	array(&$this, 'addGeoMetaHtml'), 2 );
			
			add_action( 'wp_head', 		array(&$this, 'addGmapsV3Header') );
			add_action( 'wp_head', 		array(&$this, 'displayGeoMeta') );

			wp_enqueue_script( 'google-jsapi', 	'http://www.google.com/jsapi', array(), SGM_VERSION );
			wp_enqueue_script( 'geoxml3', 		SGM_URL . 'lib/geoxml3.min.js', array('google-jsapi'), SGM_VERSION );
			wp_localize_script( 'geoxml3', 'geoxml3L10n', array(
				'readmore' => __('Read more', 'simple-post-gmaps'),
				'tooltip' => $current_settings['tooltip']
			) );
			
			add_filter( 'query_vars', array( &$this, 'addQueryVar' ) );
			add_action( 'parse_query', array( &$this, 'parseQuery' ) );
		}
		
		add_shortcode( 'post-googlemaps', 	array(&$this, 'shortcodePostGmaps') );
		add_shortcode( 'global-googlemaps', array(&$this, 'shortcodeGlobalGmaps') );
		
		add_action( 'init', 		array(&$this, 'checkKmlPosts') );
		add_action( 'save_post', 	array(&$this, 'savePost' ) );
		add_action( 'deleted_post',	array(&$this, 'deletedPost' ) );		
	
		add_filter( 'styles_kml', 	array(&$this, 'addKmlStyles'), 2 );
	}
	
	/**
	 * Optionnaly add in HTML header the geolocalization of the post
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function displayGeoMeta() {
		global $wp_query;
		
		// For mobile ?
		echo "\n\t" . '<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />' . "\n";
		
		if ( !is_singular() )
			return false;
		
		$geo_value = get_post_meta( $wp_query->get_queried_object_id(), 'geo', true );
		if ( $geo_value == false || empty($geo_value['latitude']) ) {
			return false;
		}
		
		echo "\n\t" . '<meta name="geo.position" content="'.$geo_value['latitude'].';'.$geo_value['longitude'].'" />';
		echo "\n\t" . '<meta name="geo.placename" content="'.esc_attr($geo_value['address']).'" />';
		echo "\n\t" . '<meta name="ICBM" content="'.$geo_value['latitude'].';'.$geo_value['longitude'].'" />' . "\n";

		return true;
	}
	
	/**
	 * Add geo data at the end of the post
	 *
	 * @param string $post_content
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function addGeoMetaHtml( $post_content ) {
		global $post;
		
		$geo_value = get_post_meta( $post->ID, 'geo', true );
		if ( $geo_value == false || empty($geo_value['latitude']) ) {
			return $post_content;
		}
		
		$post_content .= "\n\t" . '<div class="geo" style="display: none">';
		$post_content .= "\n\t\t" . '<span class="latitude">'.$geo_value['latitude'].'</span>';
		$post_content .= "\n\t\t" . '<span class="longitude">'.$geo_value['longitude'].'</span>';
		$post_content .= "\n\t" . '</div>';
		
		return $post_content;
	}
	
	/**
	 * Load Google Maps v3 Javascript, use Google Loader...
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function addGmapsV3Header() {
		$current_settings = get_option( SGM_OPTION );
		
		$args = '';
		if ( isset($current_settings['region']) && !empty($current_settings['region']) )
			$args .= '&region='.$current_settings['region'];
			
		if ( isset($current_settings['language']) && !empty($current_settings['language']) )
			$args .= '&language='.$current_settings['language'];
		
		$output  = '<script type="text/javascript">' . "\n";
			$output .= '<!--' . "\n";
			$output .= 'google.load( "maps", "3", { other_params: "sensor=false'.$args.'" } );' . "\n";
			$output .= '-->' . "\n";
		$output .= '</script>' . "\n";
		
		echo $output;
	}
	
	/**
	 * Check GET data for display or not the KML datas
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function checkKmlPosts() {
		if ( isset($_GET['showposts_kml']) && $_GET['showposts_kml'] == 'true' ) {
			status_header('200');
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
			header( 'Content-Type: application/xml; charset=UTF-8' );
			
			$this->buildKmlPosts();
			exit();
		}
	}
	
	/**
	 * Shortcode for post singular
	 *
	 * @param array $atts
	 * @return string
	 * @author Amaury Balmer
	 */
	function shortcodePostGmaps($atts) {
		global $post;
		
		// Geo value exist ?
		$geo_value = get_post_meta( $post->ID, 'geo', true );
		if ( $geo_value == false || empty($geo_value['latitude']) ) {
			return '';
		}
		
		extract(shortcode_atts(array(
			'width' => '400px',
			'height' => '300px',
			'zoom' => '10',
		), $atts));
		
		return $this->buildPostGmaps( $post->ID, $width, $height, $geo_value['latitude'], $geo_value['longitude'], $zoom, get_the_title() );
	}
	
	/**
	 * Build HTML use by widget and shortcode for display the map of singular post
	 *
	 * @param integer $id
	 * @param string $width
	 * @param string $height
	 * @param string $latitude
	 * @param string $longitude
	 * @param integer $zoom
	 * @param string $title
	 * @param string $icon_url
	 * @return string
	 * @author Amaury Balmer
	 */
	function buildPostGmaps( $id = 0, $width = '400px', $height = '300px', $latitude = '', $longitude = '', $zoom = 10, $title = '', $icon_url = '' ) {
		if ( !empty($icon_url) ) {
			$image_def   = 'var image = "'.$icon_url.'";';
			$image_param = ', icon: image';
		} else {
			$image_def = $image_param = '';
		}
		
		$output  = '<div id="map-post-'.$id.'" style="width: '.$width.';height: '.$height.';margin:0 auto;text-align:center;"></div>' . "\n";
		$output .= '<script type="text/javascript">' . "\n";
			$output .= '<!--' . "\n";
			$output .= '
				var myLatlng'.$id.' = new google.maps.LatLng('.$latitude.','.$longitude.');
				var myOptions'.$id.' = {
					zoom: '.$zoom.',
					center: myLatlng'.$id.',
					mapTypeControl: false,
					mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
					navigationControl: false,
					navigationControlOptions: {style: google.maps.NavigationControlStyle.SMALL},
					mapTypeId: google.maps.MapTypeId.ROADMAP
				}
				
				'.$image_def.'
				var map'.$id.' = new google.maps.Map(document.getElementById("map-post-'.$id.'"), myOptions'.$id.');
				
				var marker'.$id.' = new google.maps.Marker({
					position: myLatlng'.$id.',
					map: map'.$id.',
					title:"'.esc_attr($title).'"
					'.$image_param.'
				});
			';
			$output .= '-->' . "\n";
		$output .= '</script>' . "\n";
		
		return $output;
	}
	
	/**
	 * Shortcode for global blog maps
	 *
	 * @param array $atts
	 * @return string
	 * @author Amaury Balmer
	 */
	function shortcodeGlobalGmaps($atts) {
		extract(shortcode_atts(array(
			'width' => '600px',
			'height' => '500px',
			'zoom' => '5',
		), $atts));
		
		return $this->buildGlobalMaps( $width, $height, $zoom );
	}
	
	/**
	 * Build the HTML for global maps with all posts.
	 *
	 * @param string $width
	 * @param string $height
	 * @param integer $zoom
	 * @return string
	 * @author Amaury Balmer
	 */
	function buildGlobalMaps( $width = '600px', $height = '500px', $zoom = 5 ) {
		$posts = $this->getKmlPosts( 'RAND()', 'LIMIT 0, 1' );
		if ( $posts == false ) {
			return '<!-- No post with geo meta datas -->';
		}
		
		$geo_value = maybe_unserialize($posts[0]->meta_value);
		
		$output  = '<div id="map-global-post" style="width: '.$width.';height: '.$height.';margin:0 auto;text-align:center;"></div>' . "\n";
		$output .= '<script type="text/javascript">' . "\n";
			$output .= '<!--' . "\n";
			$output .= '
				//var myLatlng = new google.maps.LatLng('.$geo_value['latitude'].','.$geo_value['longitude'].');
				var myOptions = {
					zoom: '.$zoom.',
					// center: myLatlng,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					mapTypeControl: true,
					//mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
					navigationControl: true
					//navigationControlOptions: {style: google.maps.NavigationControlStyle.SMALL}
				}
				var map = new google.maps.Map(document.getElementById("map-global-post"), myOptions);
				
				var geoXml = new geoXML3.parser({map:map});
				geoXml.parse("'.home_url('/').'?showposts_kml=true");
			';
			$output .= '-->' . "\n";
		$output .= '</script>' . "\n";
		
		return $output;
	}
	
	/**
	 * Make the query SQL for get posts and meta geo datas
	 *
	 * @param string $orderby
	 * @param string $limit
	 * @return array
	 * @author Amaury Balmer
	 */
	function getKmlPosts( $orderby = 'p.post_title ASC', $limit = '' ) {
		global $wpdb;
		return $wpdb->get_results("
			SELECT pm.meta_value, p.post_title, p.post_excerpt, p.post_content, pm.post_id, p.ID
			FROM $wpdb->postmeta AS pm
			INNER JOIN $wpdb->posts AS p ON pm.post_id = p.ID
			WHERE meta_key = 'geo'
			AND p.post_status = 'publish'
			AND p.post_type = 'post'
			GROUP BY post_id
			ORDER BY $orderby
			$limit
		");
	}
	
	/**
	 * Build the KML XML with all posts geolocalized
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function buildKmlPosts() {
		global $post;
		
		echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . '"?' . ">\n";
		?>
		<kml xmlns="http://www.opengis.net/kml/2.2">
			<Document>
				<name><?php _e('All posts on Gmaps', 'simple-post-gmaps'); ?></name>
				<description><![CDATA[<?php _e('All posts on Gmaps', 'simple-post-gmaps'); ?>]]></description>
				<?php echo apply_filters( 'styles_kml', '' ); ?>
				
				<?php
				$i = 0;
				foreach( (array) $this->getKmlPosts() as $post ) :
					$post->meta_value = maybe_unserialize($post->meta_value);
					
					if ( $post->meta_value['longitude'] == 0 )
						continue; // Skip post without geo data
					$i++;
					
					$term_id = $this->getFirstTerm( 'category', 'term_id' );
					?>
					<Placemark>
						<name><?php echo esc_html($post->post_title); ?></name>
						<permalink><?php echo get_permalink($post->ID); ?></permalink>
						<description><![CDATA[<?php $this->theExcerpt(35); ?>]]></description>
						<styleUrl>#<?php echo 'ico'.$term_id; ?></styleUrl>
						<Point>
							<coordinates><?php echo $post->meta_value['longitude'].','.$post->meta_value['latitude'].',0.000000'; ?></coordinates>
						</Point>
					</Placemark>
				<?php endforeach; ?>
			
			</Document>
		</kml>
		<?php
	}
	
	/**
	 * Get the first term from a post/taxonomy, allow to choose the output return...
	 *
	 * @param string $taxonomy 
	 * @param string $output 
	 * @return integer|string|object
	 * @author Amaury Balmer
	 */
	function getFirstTerm( $taxonomy = 'category', $output = '' ) {
		global $post;
		
		$terms = get_the_terms( $post->ID, $taxonomy );
		if ( $terms == false ) {
			return false;
		}
		
		$term = current($terms);
		
		switch( $output ) {
			case 'term_id' :
				return $term->term_id;
				break;
			case 'parent_id' :
				return $term->parent;
				break;
			case 'link' :
				return '<a href="'.get_term_link( $term->term_id, $taxonomy ).'">'.esc_html($term->name).'</a>';
				break;
			case 'object' :
				return $term;
				break;
		}
		
		return false;
	}
	
	/**
	 * Build the KML style with the possibility to customize each icons for each term
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function addKmlStyles() {
		$terms = get_categories( 'hide_empty=0' );
		foreach( (array) $terms as $term ) :
			if ( !is_file(TEMPLATEPATH . '/gmaps/ico-'.$term->term_id.'.png') )
				continue;
			?>
			<Style id="ico<?php echo $term->term_id; ?>">
				<IconStyle id="myico<?php echo $term->term_id; ?>">
					<Icon>
						<href><?php bloginfo('template_directory') . '/gmaps/ico-'.$term->term_id.'.png'; ?></href>
					</Icon>
				</IconStyle>
			</Style>
			<?php
		endforeach;
	}
	
	/**
	 * Allow to use custom excerpt
	 *
	 * @param integer $word
	 * @return void
	 * @author Amaury Balmer
	 */
	function theExcerpt( $word = 55 ) {
		global $post;
		
		$post->post_excerpt = trim($post->post_excerpt);
		if ( empty($post->post_excerpt) ) {
			echo $this->my_trim_excerpt( $post->post_content, $word );
		} else {
			the_excerpt();
		}
	}
	
	/**
	 * Build excerpt with custom size !
	 *
	 * @param string $text
	 * @param integer $word
	 * @return void
	 * @author Amaury Balmer
	 */
	function my_trim_excerpt( $text = '', $word = 55 ) {
		$raw_excerpt = $text;
		if ( '' == $text ) {
			$text = get_the_content('');
			
			$text = strip_shortcodes( $text );
			
			$text = apply_filters('the_content', $text);
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = strip_tags($text);
			$excerpt_length = apply_filters('excerpt_length', $word );
			$words = explode(' ', $text, $excerpt_length + 1);
			if (count($words) > $excerpt_length) {
				array_pop($words);
				array_push($words, '[...]');
				$text = implode(' ', $words);
			}
		}
		return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
	}
	
	/**
	 * During the save hook, save the geo datas...
	 *
	 * @param integer $object_ID
	 * @param object $object
	 * @return void
	 * @author Amaury Balmer, Nicolas Juen
	 */
	function savePost( $object_ID = 0 , $object = null ) {
		$_id = ( intval($object_ID) == 0 ) ? (int) $object->ID : $object_ID;
		if ( $_id == 0 )
			return false;
		
		$post = get_post( $object_ID, ARRAY_A );
		if( $post['post_status'] != 'publish' )
			return true;

		if ( isset( $_POST['geo'] ) ) { // Update geo postmeta ?
			$this->savePostMerge( $_id, $_POST['geo'] );
		}

		return false;
	}
	
	/**
	 * Save the post meta's and in the table
	 * 
	 * @access public
	 * @param mixed $post_id
	 * @param mixed $datas
	 * @return void
	 * @author Nicolas Juen
	 */
	function savePostMerge( $post_id, $datas ){
		$meta = update_post_meta( $post_id, 'geo', $datas );

		global $wpdb;
		$get = $wpdb->get_results( 'SELECT `long`,`lat` FROM `'.$wpdb->simple_post_gmaps.'` WHERE post_id='.$post_id );		

		if( empty( $get ) )
			$query = $wpdb->insert( $wpdb->simple_post_gmaps, array( 'post_id' => $post_id, 'long' => $datas['longitude'], 'lat' => $datas['latitude'] ) ,array( '%d','%f','%f' ) );

		if( rtrim( $get[0]->lat, '0' ) != rtrim( $datas['latitude'], '0' ) || rtrim( $get[0]->long, '0' ) != rtrim( $datas['longitude'], '0' ) )
			$query = $wpdb->update( $wpdb->simple_post_gmaps, array( 'long' => $datas['longitude'], 'lat' => $datas['latitude'] ), array( 'post_id' => $post_id ) , array( '%f','%f' ), array( '%d' ) );
		
		if( $datas['latitude'] == '0' && $datas['longitude'] )
			$query = $this->deletedPost( $post_id );

		if( !$meta || !$query )
			return false;

		return true;
	}
	
	/**
	 * Delete in the gmaps table when deleting the post
	 * 
	 * @access public
	 * @param int $post_id. (default: 0)
	 * @return void
	 * @author Nicolas Juen
	 */
	function deletedPost( $post_id = 0 ){
		if( $post_id == 0 )
			return false;
		
		global $wpdb;	
		return $wpdb->query( $wpdb->prepare( 'DELETE FROM '.$wpdb->simple_post_gmaps.' WHERE post_id = %d', $post_id ) );
	}
	
	/**
	 * addQueryVar function.
	 * 
	 * @access public
	 * @param mixed $wp_query_var
	 * @return void
	 * @author Nicolas Juen
	 */
	function addQueryVar( $wp_query_var ) {
		
		// Add latitude and longitude
		$wp_query_var[] = 'latitude';
		$wp_query_var[] = 'longitude';
		
		return $wp_query_var;
	}
	
	/**
	 * Add the actions if the longitude and latitude are given
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	function parseQuery( $query ) {
		
		// Get options
		$this->latitude = $query->query_vars['latitude'];
		$this->longitude = $query->query_vars['longitude'];
		
		if ( empty( $this->latitude ) || empty( $this->longitude ) || $query->query_vars['orderby'] != 'distance' )
			return $query;
			
		//Fix the query
		add_action( 'pre_get_posts', array(&$this, 'fixQueryFlags') );			
		
		//Add distance field
		add_action( 'posts_fields_request', array( &$this, 'buildQueryFields' ), 10, 2 );
		
		//Add the join part
		add_action( 'posts_join_request', array( &$this, 'buildQueryJoin' ), 10, 2 );
		
		//Add order by distance ASC
		add_action( 'posts_orderby_request', array( &$this, 'buildQueryOrder' ), 10, 2 );
	}

	/**
	 * Fix the query flags in case of fitlering
	 * 
	 * @access public
	 * @param mixed $query
	 * @return void
	 * @author Nicolas Juen
	 */
	function fixQueryFlags( $query ) {
		//Remove useless parts
		$query->is_tax = false;
		$query->is_category = false;
		$query->is_distance = true;

		if ( empty( $query->query_vars['post_type'] ) )
			$query->query_vars['post_type'] = 'any';
		$query->query_vars['category__in'] = '';
	}
	
	/**
	 * Add the join in the query
	 * 
	 * @access public
	 * @param string $join. (default: '')
	 * @param mixed $current_query
	 * @return void
	 * @author Nicolas Juen
	 */
	function buildQueryJoin( $join = '', $current_query ) {
		global  $wpdb;
		
		//Join with the GPS coordinates table
		$join .= ' INNER JOIN '.$wpdb->simple_post_gmaps.' ON ( '.$wpdb->simple_post_gmaps.'.post_id = '.$wpdb->prefix.'posts.ID )';
		
		return $join;
	}
	
	/**
	 * Add the distance calculation to the fields as distance
	 * 
	 * @access public
	 * @param string $fields. (default: '')
	 * @param mixed $current_query
	 * @return void
	 * @author Nicolas Juen
	 */
	function buildQueryFields( $fields = '', $current_query ){
		global  $wpdb;
		
		$fields .= ', round((ACOS(COS(RADIANS('.$wpdb->simple_post_gmaps.'.lat))*COS(RADIANS('.$this->latitude.'))*COS(RADIANS('.$this->longitude.')-RADIANS('.$wpdb->simple_post_gmaps.'.long))+SIN(RADIANS('.$this->latitude.'))*SIN(RADIANS('.$wpdb->simple_post_gmaps.'.lat)))*6366),2) AS `distance`';

	return $fields;	
	}
	
	/**
	 * Add ditstance to the order
	 * 
	 * @access public
	 * @param string $order_by. (default: '')
	 * @param mixed $current_query
	 * @return void
	 * @author Nicolas Juen
	 */
	function buildQueryOrder( $order_by = '', $current_query ) {
		
		//Overwrite the order_by
		$order_by = 'distance';

		return $order_by;		
	}	
		
}
?>