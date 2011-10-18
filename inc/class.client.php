<?php
class Simple_Post_Gmaps_Client {
	var $longitude = null;
	var $latitude  = null;
	
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
			
			add_action( 'template_redirect', array(&$this, 'addScripts'), 9 );
			
			add_filter( 'query_vars', array( &$this, 'addQueryVar' ) );
			add_action( 'parse_query', array( &$this, 'parseQuery' ) );
		}
		
		// Shortcodes
		add_shortcode( 'post-googlemaps', 	array(&$this, 'shortcodePostGmaps') );
		add_shortcode( 'global-googlemaps', array(&$this, 'shortcodeGlobalGmaps') );
		
		add_action( 'init', array(&$this, 'checkKmlPosts') );
		
		// Keep update geo table
		add_action( 'save_post', 				array(&$this, 'savePost' ) );
		add_action( 'deleted_post',				array(&$this, 'deletedPost' ) );
		add_action( 'publish_to_draft',			array(&$this, 'deletedPost' ) );
		add_action( 'publish_to_private',		array(&$this, 'deletedPost' ) );
		add_action( 'publish_to_future',		array(&$this, 'deletedPost' ) );
		add_action( 'publish_to_pending',		array(&$this, 'deletedPost' ) );
		add_action( 'publish_to_new',			array(&$this, 'deletedPost' ) );
		
		add_filter( 'styles_kml', 				array(&$this, 'addKmlStyles'), 2 );
		add_filter( 'list_terms_exclusions',	array( &$this, 'hideEmpty'), 10, 2);
	}

	/**
	 * Add optionnaly the css/javascript for the gmaps
	 *
	 * @param void
	 * @return void
	 * @author Nicolas Juen
	 */
	function addScripts() {
		// Get settings on DB
		$current_settings = get_option( SGM_OPTION );
		
		// Check post_type
		if( !isset( $current_settings['custom-types'] ) || empty( $current_settings['custom-types'] ) )
			return false;
		
		// Check if this singular is registered
		if( is_single( ) || is_archive() || is_post_type_archive() || is_page() ) {

			// Set default tooltip
			if ( !isset( $current_settings['tooltip'] ) && empty( $current_settings['tooltip'] ) ) {
				$current_settings['tooltip'] = SGM_TOOLTIP;
			}
	
			// Enqueue scripts
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'google-jsapi', 	'http://maps.google.com/maps/api/js?libraries=places&sensor=false', array(), SGM_VERSION );
			wp_enqueue_script( 'geoxml3', 		SGM_URL . 'lib/geoxml3.js', array('google-jsapi'), SGM_VERSION );
			wp_enqueue_script( 'spgm-map', 		SGM_URL . 'inc/ressources/spgm-map.min.js', array( 'jquery', 'google-jsapi' , 'geoxml3' ), SGM_VERSION );
			
			wp_localize_script( 'geoxml3', 'geoxml3L10n', array(
				'readmore' => __('Read more', 'simple-post-gmaps'),
				'tooltip' => $current_settings['tooltip'],
				'kml_url' => home_url( '?showposts_kml=true' )
			) );
	
			// Enqueue CSS if needed, correct bug with twenty eleven
			add_action( 'wp_head', array(&$this, 'addCss') );
		}
	}

	/**
	 * Correct bug with twenty eleven
	 *
	 * @param void
	 * @return void
	 * @author Nicolas Juen
	 */
	function addCss(){
	?>
	<style>	
		.gmaps img{
			max-width:none;
		}
	</style>
	<?php
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
		if ( $geo_value == false || empty( $geo_value['latitude'] ) ) {
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
		// Get settings on DB
		$current_settings = get_option( SGM_OPTION );
		if ( isset($current_settings['hidden_coordinates']) && $current_settings['hidden_coordinates'] == 1 )
			return $post_content;
		
		global $post;
		$geo_value = get_post_meta( $post->ID, 'geo', true );
		if ( $geo_value == false || empty( $geo_value['latitude'] ) ) {
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
		if ( isset( $current_settings['region'] ) && !empty( $current_settings['region'] ) )
			$args .= '&region='.$current_settings['region'];
		
		if ( isset( $current_settings['language'] ) && !empty( $current_settings['language'] ) )
			$args .= '&language='.$current_settings['language'];
		
		echo $output;
	}
	
	/**
	 * Check GET data for display or not the KML datas
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function checkKmlPosts() {
		if ( isset( $_GET['showposts_kml'] ) && $_GET['showposts_kml'] == 'true' ) {
			status_header( '200' );
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
		extract( shortcode_atts( array(
			'width' => '400px',
			'height' => '300px',
			'zoom' => '10',
			'post_id' => 0
		), $atts ) );
		
		if ( (int) $post_id == 0 ) {
			global $post;
			$post_id = $post->ID;
		}
		
		// Geo value exist ?
		$geo_value = get_post_meta( $post_id, 'geo', true );
		if ( $geo_value == false || empty( $geo_value['latitude'] ) ) {
			return '';
		}
		
		return $this->buildPostGmaps( $post_id, $width, $height, $geo_value['latitude'], $geo_value['longitude'], $zoom, get_the_title($post_id) );
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
	function buildPostGmaps( $id = 0, $width = '400px', $height = '300px', $latitude = '', $longitude = '', $zoom = 10, $title = '', $icon_url = '', $iframe = false, $iframe_adress = '' ) {
		if ( $iframe == true ) {
			return '<iframe width="'.$width.'" height="'.$height.'" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.fr/maps?f=q&amp;source=s_q&amp;hl=fr&amp;geocode=&amp;q='.$iframe_adress.'&amp;sll='.$latitude.','.$longitude.'&amp;ie=UTF8&amp;hq=&amp;z='.$zoom.'&amp;output=embed"></iframe>' . "\n";
		}
		
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
					title:"'.esc_attr( $title ).'"
					'.$image_param.'
				});
			';
			$output .= '-->' . "\n";
		$output .= '</script>' . "\n";
		
		return apply_filters( 'buildPostGmaps', $output );
	}
	
	/**
	 * Shortcode for global blog maps
	 *
	 * @param array $atts
	 * @return string
	 * @author Amaury Balmer
	 */
	function shortcodeGlobalGmaps( $atts ) {
		extract( shortcode_atts( array(
			'width' 			=> '600px',
			'height' 			=> '500px',
			'zoom' 				=> '5',
			'display_taxo' 		=> false,
			'taxonomy' 			=> '',
			'post_type' 		=> '',
			'term'				=> '',
			'search'			=> false
		), $atts ) );

		return $this->buildGlobalMaps( $width, $height, $zoom, $display_taxo, $taxonomy, $term , $post_type, $search );
	}

	/**
	 * Build the HTML for global maps with all posts.
	 *
	 * @param string $width
	 * @param string $height
	 * @param integer $zoom
	 * @return string
	 * @author Amaury Balmer & Nicolas Juen
	 */
	function buildGlobalMaps(  $width = '600px', $height = '500px', $zoom = 5, $display_taxo = false, $taxonomy = '', $term = '', $post_type ='', $search = false ) {
		global $map_id;
		
		// Init output
		$output = '';
		
		// For multiple globalmaps
		if( !isset( $map_id ) )
			$map_id = 0;
		else
			$map_id++;
		
		// Check if px at the end, add if needed
		if( !strpos( $width, 'px' ) )
			$width = $width.'px';
		
		// Check if px at the end, add if needed
		if( !strpos( $height, 'px' ) )
			$height = $height.'px';

		$url_request_taxo = '';
		$taxonomies = array();
		$firstTaxonomy = '';
		
		if ( $display_taxo == 'true' ) {
			// Get the taxonomies in the short code if needed
			if ( empty( $taxonomy ) )
				$taxonomies =  get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'objects' );
			elseif ( !is_array( $taxonomy ) )
				if ( taxonomy_exists( $taxonomy ) )
					$taxonomies[] = get_taxonomy( $taxonomy );
			else {
				//Get the taxonomies with the explode
				$taxos = explode( ',' , $taxonomy );
				//If the array is not empty fill the taxonomy array with the taxonomy name
				if ( !empty( $taxos ) ) {
					foreach( $taxos  as $tax ){
						if ( taxonomy_exists( $tax ) ){
							$taxonomies[] = get_taxonomy( $tax );
						}
					}
				}
			}
			
			if ( !empty($taxonomies) && is_array($taxonomies) ) { // Get the first taxonomy for the icons
				$firstTaxonomy = current($taxonomies)->name;
				$url_request_taxo = '&taxonomiesFilter[]='.$firstTaxonomy;
			}
		}
		
		// Check term given
		if( $term != '' ) {
			// if taxonomy given add the taxonomy
			if ( !empty( $taxonomy ) && !is_array( $taxonomy ) && taxonomy_exists( $taxonomy ) ) { // Get the first taxonomy for the icons
				$firstTaxonomy = '&taxonomiesFilter[]='.$taxonomy;
			}
		}
		
		// Make the wrapper for the taxonomies and the filters and search
		$output .= '<div class="spgmWrapper">'."\n"; 
			$output .= '<form method="post" class="spgmSettings">'."\n"; 
				$output .= '<input type="hidden" name="spgm_post_type" value="'.esc_attr( $post_type ).'" />'."\n"; 
				$output .= '<input type="hidden" name="spgm_requestTaxo" value="'.esc_attr( $url_request_taxo ).'"/>'."\n";
				$output .= '<input type="hidden" name="spgm_requestTerm" value="'.esc_attr( $term ).'"/>'."\n"; 
				$output .= '<input type="hidden" name="spgm_firstTaxo" value="'.esc_attr( $firstTaxonomy ).'"/>'."\n"; 
				$output .= '<input type="hidden" name="spgm_zoom" value="'.esc_attr( $zoom ).'"/>'."\n"; 
			$output .= '</form>'."\n"; 
		$output .= '<div class="gmaps" id="map-global-post-'.esc_attr( $map_id ).'" style="width: '.esc_attr( $width ).';height: '.esc_attr( $height ).';"></div>'."\n";
			if( $search == true ) {
				$output .= '<form class="spgmSearch">'."\n"; 
					$output .= '<input type="text" name="spgm_search" />'."\n"; 
					$output .= '<input type="submit" name="spgm_ok" value="'.esc_attr__( 'Search', '' ).'" />'."\n"; 
				$output .= '</form>' . "\n";
			}
		
		// If no taxonomies just display the ap with filter
		if ( empty( $taxonomies ) )
			return apply_filters( 'buildGlobalMaps', $output.'</div>' );
		
		// Get the restults
		$results = $this->getPostsWithGeo( $post_type );
		
		if( $display_taxo == true ) {
			// Filter terms
			$output .= '<div class="termsFiltering" id="termsFiltering-'.$map_id.'">';
				foreach( $taxonomies as $taxonomy ) {
					//Get the terms with posts with coordinates
					$terms = get_terms( $taxonomy->name, array( 'hide_empty' => true, 'gmaps' => $taxonomy->name ) );
				
					//Go to the next taxonomy if no terms
					if ( empty( $terms ) )
						continue;
				
					$output .= '<div class="'.$taxonomy->name.'" id="'.$taxonomy->name.'">';
						$output .= '<h3>'.apply_filters('taxonomy_name', $taxonomy->labels->name ).'</h3>';
				
						//Display a label with the checkbox
						foreach( $terms as $term ) {
							// Get the objects in the current term
							$objects = get_objects_in_term( $term->term_id, $taxonomy->name );
							
							// Intersect the geolocalized posts and the term objects
							$intersect = array_intersect( $results, $objects );		
							
							// Continue if no posts	
							if( empty( $intersect ) )
								continue;
							
							$output .= '<p>' . "\n";
								$output .= '<input type="checkbox" id="'.esc_attr( $map_id.'-'.$term->term_id ).'" value="'.esc_attr( $term->term_id ).'" />' . "\n";
								
								//Display the legend icon if present
								if( is_file(TEMPLATEPATH . '/gmaps/ico-legend-'.$term->taxonomy.'-'.$term->term_id.'.png') )
									$output .= '<img src="'.get_bloginfo( 'template_url' ) . '/gmaps/ico-legend-'.esc_attr( $term->taxonomy.'-'.$term->term_id ).'.png'.'" />';
									
								$output .= '<label for="'.esc_attr( $map_id.'-'.$term->term_id ).'">'.esc_html( $term->name ).'</label>' . "\n";
							$output .='</p>' . "\n";
						}
					$output .= '</div>' . "\n";
				}
				$output .= '</div>'. "\n";
			$output .=	'</div>' . "\n";
		}
		
		//Return with filters
		return apply_filters( 'buildGlobalMaps', $output );
	}
	
	/**
	 * Get all the posts in the coordinates table
	 *
	 * @return array
	 * @author Amaury Balmer
	 */
	function getPostsWithGeo( $post_type = '' ) {
		global $wpdb;
		$where = '';
		
		if( isset( $post_type ) && !empty( $post_type ) && post_type_exists( $post_type ) )
			$where = "AND post_type=".$post_type ;
		
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts JOIN $wpdb->postmeta ON post_id = ID WHERE meta_key = 'geo' %s", array( $where ) ) );
		
		if ( $results == false )
			return array();
		
		return $results;
	}
	
	/**
	 * Build the KML XML with all posts geolocalized
	 *
	 * @return void
	 * @author Amaury Balmer & Nicolas Juen
	 */
	function buildKmlPosts() {
		global $post;
		
		// Get different filters
		$taxonomies = isset( $_GET['taxonomiesFilter'] ) && !empty( $_GET['taxonomiesFilter'] ) ? $_GET['taxonomiesFilter'] : '';
		$terms 		= isset( $_GET['termsFilter'] ) && !empty( $_GET['termsFilter'] ) 			? $_GET['termsFilter'] 		: '';
		$post_type 	= isset( $_GET['post_type'] ) && !empty( $_GET['post_type'] ) 				? $_GET['post_type'] 		: 'any';
		
		// Get all posts
		$post_ids = $this->getPostsWithGeo( $post_type );
		
		
		// Remove IDs
		if ( !empty($taxonomies) ) {
			$taxonomies = array_unique( $taxonomies );
			foreach( $taxonomies as $key => $taxonomy )
				if ( !taxonomy_exists( $taxonomy ) )
					unset($taxonomies[$key]);

			// Get the posts in the taxonomy and terms
			$postIn = array();
			if ( !empty( $terms  ) && !empty( $taxonomies ) )
				$postIn = get_objects_in_term( $terms, $taxonomies );
			
			$post_ids = array_intersect( $post_ids, $postIn );
		}
		
		// Make the query
		$query_posts = new WP_Query( array( 'post__in' => $post_ids, 'post_type' => $post_type, 'nopaging' => true, 'status' => 'publish', 'posts_per_page' => -1 ) );
		
		echo '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . '"?' . ">\n";
		?>
		<kml xmlns="http://www.opengis.net/kml/2.2">
			<Document>
				<name><?php _e( 'All posts on Gmaps', 'simple-post-gmaps' ); ?></name>
				<description><![CDATA[<?php _e( 'All posts on Gmaps', 'simple-post-gmaps' ); ?>]]></description>
				<?php echo apply_filters( 'styles_kml', '' ); ?>
				
				<?php
				foreach( (array) $query_posts->posts as $post ) :
					setup_postdata( $post );
					
					//Get the post meta for the geolocalisation, continue if no metas
					$meta = '';
					$meta = get_post_meta( $post->ID, 'geo', true );
					if ( empty( $meta ) || empty( $meta['longitude'] )  )
						continue;
					
					// Get the first term id for the icon style
					$style_url = '<styleUrl></styleUrl>' . "\n";
					if ( !empty( $taxonomies ) ) {
						$term_id = $this->getFirstTerm( $post->ID, $taxonomies[0], 'term_id' );
						$style_url = '<styleUrl>#ico-'.$taxonomies[0].'-'.$term_id.'</styleUrl>' . "\n";
					}
					?>
					<Placemark>
						<name><?php echo esc_html( $post->post_title ); ?></name>
						<permalink><?php echo get_permalink( $post ); ?></permalink>
						<description><![CDATA[<?php $this->theExcerpt( 35 ); ?>]]></description>
						<?php echo $style_url; ?>
						<Point>
							<coordinates><?php echo $meta['longitude'].','.$meta['latitude'].',0.00000000'; ?></coordinates>
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
	function getFirstTerm( $post_id = '' , $taxonomy = 'category', $output = '' ) {
		if ( !is_numeric( $post_id ) || empty( $post_id ) )
			return false;
		
		// Get the terms of the taxonomy
		$terms = get_the_terms( $post_id, $taxonomy );
		
		if ( $terms == false )
			return false;
		
		// Get the first term
		$term = current( $terms );
		
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
		$terms = get_terms( get_taxonomies(), array( 'hide_empty'=> true ) );
		
		foreach( (array) $terms as $term ) :
			
			if ( !is_file( TEMPLATEPATH . '/gmaps/ico-'.$term->taxonomy.'-'.$term->term_id.'.png' ) )
				continue;
			?>
			<Style id="ico-<?php echo $term->taxonomy.'-'.$term->term_id; ?>">
				<IconStyle id="myico<?php echo $term->term_id; ?>">
					<Icon>
						<href><?php echo get_bloginfo('template_directory') . '/gmaps/ico-'.$term->taxonomy.'-'.$term->term_id.'.png'; ?></href>
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
		
		$post->post_excerpt = trim( $post->post_excerpt );
		
		if ( empty($post->post_excerpt) && !empty($post->post_content) ) {
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
	function my_trim_excerpt( $content = '', $word = 55 ) {
		$text = strip_shortcodes( $content );
		
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = apply_filters('excerpt_length', $word );
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '[...]');
			$text = implode(' ', $words);
		}
	
		return apply_filters('wp_trim_excerpt', $text, $content);
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

		if ( isset( $_POST['geo'] ) ) {
			// Save data in meta
			update_post_meta( $_id, 'geo', $_POST['geo'] );
			
			$post = get_post( $_id );
			if ( $post->post_status == 'publish' )
				$this->savePostMerge( $_id, $_POST['geo'] );
			else 
				$this->deletedPost( $_id );
		}
		
		return false;
	}
	
	/**
	 * Save the geo datas in the table
	 *
	 * @access public
	 * @param integer $post_id
	 * @param array $datas
	 * @return boolean|integer
	 * @author Nicolas Juen
	 */
	function savePostMerge( $post_id, $datas ) {
		global $wpdb;

		// Not Valid GPS ? Delete it from table
		if ( $datas['latitude'] == '0' && $datas['longitude'] == 0 ) {
			return $this->deletedPost( $post_id );
		} else {
			if ( $wpdb->get_var( $wpdb->prepare("SELECT long FROM $wpdb->simple_post_gmaps WHERE post_id = %d", $post_id) ) == false ) // Insert
				return $wpdb->insert( $wpdb->simple_post_gmaps, array('post_id' => $post_id, 'long' => $datas['longitude'], 'lat' => $datas['latitude']), array('%d','%f','%f') );
			else // update
				return $wpdb->update( $wpdb->simple_post_gmaps, array( 'long' => $datas['longitude'], 'lat' => $datas['latitude'] ), array( 'post_id' => $post_id ) , array('%f','%f'), array('%d') );
		}
	}
	
	/**
	 * Delete in the gmaps table when deleting the post
	 *
	 * @access public
	 * @param int $post_id. (default: 0)
	 * @return void
	 * @author Nicolas Juen
	 */
	function deletedPost( $post_id = 0 ) {
		global $wpdb;
		
		if ( $post_id == 0 )
			return false;
			
		return $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->simple_post_gmaps WHERE post_id = %d", $post_id ) );
	}
	
	/**
	 * Add latitude and longitude
	 *
	 * @access public
	 * @param array $query_vars
	 * @return array
	 * @author Nicolas Juen
	 */
	function addQueryVar( $query_vars ) {
		$query_vars[] = 'latitude';
		$query_vars[] = 'longitude';
		
		return $query_vars;
	}
	
	/**
	 * Add the actions if the longitude and latitude are given
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	function parseQuery( $query ) {
		if ( !isset($query->query_vars['latitude']) || !isset($query->query_vars['longitude']) || empty($query->query_vars['latitude']) || empty($query->query_vars['longitude']) || $query->query_vars['orderby'] != 'distance' )
			return $query;
			
		$this->latitude  = $query->query_vars['latitude'];
		$this->longitude = $query->query_vars['longitude'];

		//Fix the query
		add_action( 'pre_get_posts', array(&$this, 'fixQueryFlags') );
		
		//Add distance field
		add_action( 'posts_fields_request', array( &$this, 'buildQueryFields' ), 10, 2 );
		
		//Add the join part
		add_action( 'posts_join_request', array( &$this, 'buildQueryJoin' ), 10, 2 );
		
		//Add order by distance ASC
		add_action( 'posts_orderby_request', array( &$this, 'buildQueryOrder' ), 10, 2 );
		
		return $query;
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
		// Remove useless parts
		$query->is_tax = false;
		$query->is_category = false;
		$query->is_distance = true;
		
		if ( empty( $query->query_vars['post_type'] ) )
			$query->query_vars['post_type'] = 'any';
			
		$query->query_vars['category__in'] = '';
	}
	
	/**
	 * Join with the GPS coordinates table
	 *
	 * @access public
	 * @param string $join
	 * @param mixed $current_query
	 * @return string
	 * @author Nicolas Juen
	 */
	function buildQueryJoin( $join = '', $current_query ) {
		global $wpdb;
		
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
	function buildQueryFields( $fields = '', $current_query ) {
		global $wpdb;
		
		$fields .= ', round((ACOS(COS(RADIANS('.$wpdb->simple_post_gmaps.'.lat))*COS(RADIANS('.$this->latitude.'))*COS(RADIANS('.$this->longitude.')-RADIANS('.$wpdb->simple_post_gmaps.'.long))+SIN(RADIANS('.$this->latitude.'))*SIN(RADIANS('.$wpdb->simple_post_gmaps.'.lat)))*6366),2) AS `distance`';
		return $fields;
	}
	
	/**
	 * Overwrite the order_by
	 *
	 * @access public
	 * @param string $order_by. (default: '')
	 * @param mixed $current_query
	 * @return string
	 * @author Nicolas Juen
	 */
	function buildQueryOrder( $order_by = '', $current_query ) {
		$order_by = 'distance';
		return $order_by;
	}
	
	/**
	 * Hide terms without posts with gmaps
	 *
	 * @access public
	 * @param mixed $exclusion
	 * @param mixed $_args
	 * @return void
	 * @author Julien Guilmont & Nicolas Juen
	 */
	function hideEmpty( $exclusion, $_args ) {
		if ( !isset($_args['gmaps']) )
			return $exclusion;

		// Posts with geo ?
		$posts_id = $this->getPostsWithGeo();
		if ( empty($posts_id) )
			return $exclusion;
			
		$exclusion_cat = '';
		
		// Get all terms...
		$terms = get_terms($_args['gmaps']);
		foreach( $terms as $term ){
			if ( count(array_diff($posts_id, get_objects_in_term($term->term_id, $_args['gmaps']))) == count($posts_id) ) {
				if ( !empty($exclusion_cat) )
					$exclusion_cat .= ' AND t.term_id <> ' . $term->term_id;
				else
					$exclusion_cat .= ' AND ( t.term_id <> ' . $term->term_id;
			}
		}
		
		if ( !empty($exclusion_cat) )
			$exclusion_cat .= ' )';
		
		return $exclusion . $exclusion_cat;
	}
}
?>