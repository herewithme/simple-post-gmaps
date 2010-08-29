<?php
class Simple_Post_Gmaps_Client {
	/**
	 * Constructor...
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function Simple_Post_Gmaps_Client() {
		if ( !is_admin() ) {
			add_filter( 'the_content', 	array(&$this, 'addGeoMetaHtml'), 2 );
			
			add_action( 'wp_head', 		array(&$this, 'addGmapsV3Header') );
			add_action( 'wp_head', 		array(&$this, 'displayGeoMeta') );

			wp_enqueue_script( 'google-jsapi', 	'http://www.google.com/jsapi', array(), SGM_VERSION );
			wp_enqueue_script( 'geoxml3', 		SGM_URL . '/lib/geoxml3.min.js', array('google-jsapi'), SGM_VERSION );
		}
		
		add_shortcode( 'post-googlemaps', 	array(&$this, 'shortcodePostGmaps') );
		add_shortcode( 'global-googlemaps', array(&$this, 'shortcodeGlobalGmaps') );
		
		add_action( 'init', 		array(&$this, 'checkKmlPosts') );
		add_action( 'save_post', 	array(&$this, 'savePost' ) );
	
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
		}
		
		$output  = '<div id="map-post-'.$id.'" style="width: '.$width.';height: '.$height.';margin:0 auto;text-align:center;"></div>' . "\n";
		$output .= '<script type="text/javascript">' . "\n";
			$output .= '<!--' . "\n";
			$output .= '
				var myLatlng'.$id.' = new google.maps.LatLng('.$latitude.','.$longitude.');
				var myOptions'.$id.' = {
					zoom: '.$zoom.',
					center: myLatlng'.$id.',
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
				//	center: myLatlng,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					mapTypeControl: false
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
			SELECT pm.meta_value, p.post_title, p.post_excerpt, p.post_content, pm.post_id
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
						<permalink><?php echo get_permalink($post->post_id); ?></permalink>
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
			echo my_trim_excerpt( $post->post_excerpt, $word );
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
	 * @author Amaury Balmer
	 */
	function savePost( $object_ID = 0 , $object = null ) {
		$_id = ( intval($object_ID) == 0 ) ? (int) $object->ID : $object_ID;
		if ( $_id == 0 ) {
			return false;
		}
		
		if ( isset($_POST['geo']) ) { // Update geo postmeta ?
			update_post_meta( $_id, 'geo', $_POST['geo'] );
			return true;
		}
		
		return false;
	}
}
?>