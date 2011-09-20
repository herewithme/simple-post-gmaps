<?php
class Simple_Post_Gmaps_Admin extends WP_Ajax {
	var $admin_url 	= '';
	var $admin_slug = 'simple-post-gmaps-settings';
	
	// Error management
	var $message = '';
	var $status = '';

	/**
	 * Constructor
	 *
	 * @return AdfeverAdmin
	 */
	function Simple_Post_Gmaps_Admin() {
		
		parent::__construct();
		
		// Laod the javascripts
		add_action ( 'admin_init', array (&$this, 'loadJavascript' ) );
		add_action ( 'admin_init', array (&$this, 'checkRelations' ) );
		
		add_action ( 'add_meta_boxes', 	array (&$this, 'addMetaBox' ) );
		add_action ( 'admin_menu', 		array (&$this, 'addMenu' ) );
		
		// init process for button control
		add_action( 'admin_init', array (&$this, 'addButtons' ) );
	}
	
	/**
	 * The content of the shortcode generator
	 *
	 * @param void
	 * @return void
	 * @author Nicolas Juen
	 */
	function a_spgm_shortcodePrinter(){
		// Get the syles
		global $wp_styles;
		$dir = $wp_styles->text_direction;
		$ver = md5( "$wp_styles->concat_version{$dir}" );
		
		// Make the href for the style of box
		$href = $wp_styles->base_url . "/wp-admin/load-styles.php?c={$zip}&dir={$dir}&load=media&ver=$ver";
		echo "<link rel='stylesheet' href='" . esc_attr( $href ) . "' type='text/css' media='all' />\n";
	?>
		<div id="media-upload" class=".media-upload-form">
			<h3 class="media-title"><?php _e( 'Simple Post Gmaps', 'simple-post-gmaps'); ?></h3>
			<div id="media-items" style="border-bottom:1px solid #DFDFDF;width:100%;">
				<form id="wp-body" class="sendSMPG media-upload-form type-form validate">
					<div class="media-blank" >
						<h4 class="media-sub-title" >
							<?php _e( 'Shortcode global map', 'simple-post-gmaps'); ?>
						</h4>
					<div>
					<table class="describe">
						<tbody>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft">
										<label><?php _e( 'Width', 'simple-post-gmaps'); ?></label>
									</span>
								</th>
								<td class="field">
									<input type="number" min="-1" step="1" value="-1" name="width" >
									<p class="help"> <?php _e( 'The width of your map, if you put -1 the default value will be used ( 600px ).', 'simple-post-gmaps'); ?></p>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft">
										<label><?php _e( 'Height', 'simple-post-gmaps'); ?></label>
									</span>
								</th>
								<td class="field">
									<input type="number" min="-1" step="1" value="-1" name="height" >
									<p class="help"><?php _e( ' The height of your map, if you put -1 the default value will be used ( 500px ).', 'simple-post-gmaps'); ?></p>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft">
										<label><?php _e( 'Zoom', 'simple-post-gmaps'); ?></label>
									</span>
								</th>
								<td class="field">
									<input type="number" min="-1" step="1" value="-1" name="zoom" >
									<p class="help"><?php _e( ' The intial zoom of your map, if you put -1 the default value will be used ( 5 ) .', 'simple-post-gmaps'); ?></p>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft">
										<label> 
											<?php _e( 'Post type', 'simple-post-gmaps'); ?>
										</label>
									</span>
								</th>
								<td class="field">
									<select name='post_type'>
										<option value='any'><?php _e( 'Any', 'simple-post-gmaps'); ?></option>
									<?php foreach( get_post_types( array( 'public' => true, 'show_ui' => true ), 'objects' ) as $post_type ): 
										if ( empty( $post_type->labels->name ) )
											continue;
										?>
										<option value="<?php esc_attr_e( $post_type->name ); ?>" ><?php esc_attr_e( $post_type->label ); ?></option>
									<?php endforeach; ?>
									</select>
									<p class="help"> <?php _e( 'Select the post type to use.', 'simple-post-gmaps'); ?></p>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft">
										<label> <?php _e( 'Taxonomies', 'simple-post-gmaps'); ?> </label>
									</span>
								</th>
								<td class="field">
									<select name='taxonomy'>
									<?php foreach( get_taxonomies ( array( 'public' => true, 'show_ui' => true ), 'objects' ) as $taxonomy ): ?>
										<option value="<?php esc_attr_e( $taxonomy->name ); ?>" ><?php esc_attr_e( $taxonomy->label ); ?></option>
									<?php endforeach; ?>
									</select>
									<p class="help"><?php _e( ' Select a taxonomy related to the post_type to use', 'simple-post-gmaps'); ?></p>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft">
										<label> <?php _e( 'Display filters', 'simple-post-gmaps'); ?> </label>
									</span>
								</th>
								<td class="field">
									<select name='display_taxo'>
										<option value="true" ><?php _e( 'Yes', 'simple-post-gmaps'); ?></option>
										<option value="" ><?php _e( 'No', 'simple-post-gmaps'); ?></option>
									</select>
									<p class="help"><?php _e( 'Display or not the filters by taxonomy.', 'simple-post-gmaps'); ?> </p>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft">
										<label> <?php _e( 'Display search', 'simple-post-gmaps'); ?> </label>
									</span>
								</th>
								<td class="field">
									<select name='display_search'>
										<option value="true" ><?php _e( 'Yes', 'simple-post-gmaps'); ?></option>
										<option value="" ><?php _e( 'No', 'simple-post-gmaps'); ?></option>
									</select>
									<p class="help"> <?php _e( 'Display or not the search form.', 'simple-post-gmaps'); ?> </p>
								</td>
							</tr>
							<tr>
								<th></th>
								<td>
									<input type="submit" class="button-primary" value="Insert shortcode" />
								</td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
		</div>
	<?php
	die();
	}
	/**
	 * Ajax function for gettinf the taxonomies for a post_type
	 *
	 * @param void
	 * @return void
	 * @author Nicolas Juen
	 */
	function a_post_type_taxonomy() {
		// Check the infos are given
		if( ( !isset( $_POST['post_type'] ) || !post_type_exists( $_POST['post_type'] ) ) && $_POST['post_type'] != "any" )
			die();
		
		// Get the post _type
		$post_type = $_POST['post_type'];
		if( $post_type != 'any' ) {
			$taxonomies = get_object_taxonomies( array( $post_type ), 'objects' );
		} else {
			$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'objects' );
		}
		
		// init output
		$output = array(); 
		// Build the reponse
		foreach( $taxonomies as $tax ) {
			if( $tax->public != true || $tax->show_ui != true )
				continue;
			
			$output[ $tax->name ] = $tax->label;
		}
		// Echo the result json
		echo json_encode( $output );
		// die all
		die();
	}
	
	/**
	 * Call the functions for the tiny_mce in conditions
	 *
	 * @param void
	 * @return void|false
	 * @author Nicolas Juen
	 */
	function addButtons() {
		// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) )
			return false;
		
		// If rich editing add the scripts and buttons
		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array ( &$this,'addScriptTinymce' ) );
			add_filter( 'mce_buttons', array ( &$this,'registerTheButton' ) );
		}
	}
	
	/**
	 * Add the spgm button on tinymce
	 *
	 * @param $button : the buttons of tinymce
	 * @return array
	 * @author Nicolas Juen
	 */
	function registerTheButton( $buttons ) {
		array_push( $buttons, "|", "spgm" );
		return $buttons;
	}

	/**
	 * Add javascript for button in tinymce
	 *
	 * @param $plugin_array : the array of plugins for tinymce
	 * @return array
	 * @author Nicolas Juen
	 */
	function addScriptTinymce( $plugin_array ) {
		$plugin_array['spgm'] = SGM_URL.'/inc/ressources/tinymce.min.js';
		return $plugin_array;
	}

	/**
	 * Add a page on menu for plugin settings
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function addMenu() {
		add_options_page( __( 'Simple Post Gmaps', 'simple-post-gmaps' ), __( 'Maps', 'simple-post-gmaps' ), 'manage_options', $this->admin_slug, array( &$this, 'pageManage' ) );
	}

	/**
	 * Display options on admin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function pageManage() {
		global $locale;
		
		// Display message
		$this->displayMessage();
		
		// Get settings on DB
		$current_settings = get_option( SGM_OPTION );

		// Default values for custom types
		if ( !isset( $current_settings['custom-types'] ) )
			$current_settings['custom-types'] = array();
		
		// Default values for language
		if ( !isset( $current_settings['language'] ) )
			$current_settings['language'] = substr( $locale, 0, 2 );
			
		// Default values for region
		if ( !isset( $current_settings['region'] ) )
			$current_settings['region'] = substr( $locale, 3, 2 );
			
		// Default values for the hidden coordinates
		if ( !isset( $current_settings['hidden_coordinates'] ) )
			$current_settings['hidden_coordinates'] = 0;
			
		if ( !isset( $current_settings['tooltip'] ) && empty( $current_settings['tooltip'] ) ) {
			$current_settings['tooltip'] = SGM_TOOLTIP;
		}
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e( "Simple Post Gmaps : Settings", 'simple-post-gmaps' ); ?></h2>
		
			<form action="" method="post">
				<h3><?php _e('Custom Post Types', 'simple-post-gmaps'); ?></h3>
				<div id="col-container">
					<table class="widefat fixed" cellspacing="0">
						<thead>
							<tr>
								<th scope="col" id="label" class="manage-column column-name"><?php _e('Custom type', 'simple-post-gmaps'); ?></th>
								<th scope="col" id="label" class="manage-column column-name"><?php _e('Active Maps ?', 'simple-post-gmaps'); ?></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th scope="col" id="label" class="manage-column column-name"><?php _e('Custom type', 'simple-post-gmaps'); ?></th>
								<th scope="col" id="label" class="manage-column column-name"><?php _e('Active Maps ?', 'simple-post-gmaps'); ?></th>
							</tr>
						</tfoot>
						
						<tbody id="the-list" class="list:taxonomies">
							<?php
							$class = 'alternate';
							$i = 0;
							foreach ( get_post_types( array(), 'objects' ) as $post_type ) :
								if ( !$post_type->show_ui || empty( $post_type->labels->name ) )
									continue;
								
								$i++;
								$class = ( $class == 'alternate' ) ? '' : 'alternate';
								?>
								<tr id="custom type-<?php echo $i; ?>" class="<?php echo $class; ?>">
									<th class="name column-name"><?php echo esc_html( $post_type->labels->name ); ?></th>
									<td>
										<?php
										
										echo '<input type="checkbox" name="custom-types[]" value="'.esc_attr( $post_type->name ).'" '.checked( true, in_array( $post_type->name, (array) $current_settings['custom-types'] ), false ).' />' . "\n";
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					
					<h3><?php _e( 'Google Maps', 'simple-post-gmaps' ); ?></h3>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="region"><?php _e( 'Google Maps Region', 'simple-post-gmaps' ); ?></label></th>
							<td>
								<input name="region" type="text" id="region" value="<?php echo esc_attr( $current_settings['region'] ); ?>" class="regular-text" />
								<br />
								<span class="description"><?php _e( 'You can define the default region of Google Maps for improve search results. The <code>region</code> parameter accepts <a href="http://www.unicode.org/reports/tr35/#Unicode_Language_and_Locale_Identifiers">Unicode region subtag identifiers</a> which (generally) have a one-to-one mapping to country code Top-Level Domains (ccTLDs). Most Unicode region identifiers are identical to ISO 3166-1 codes, with some notable exceptions. For example, Great Britain\'s ccTLD is "uk" (corresponding to the domain <code>.co.uk</code>) while its region identifier is "GB."', 'simple-post-gmaps' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="language"><?php _e( 'Google Maps Language', 'simple-post-gmaps' ); ?></label></th>
							<td>
								<input name="language" type="text" id="language" value="<?php echo esc_attr( $current_settings['language'] ); ?>" class="regular-text" />
								<br />
								<span class="description"><?php _e( 'You can define the language of Google Maps interface. Use <a href="http://spreadsheets.google.com/pub?key=p9pdwsai2hDMsLkXsoM05KQ&gid=1">this Google page of documentation</a> for find your code language. Example : French, put "fr"', 'simple-post-gmaps' ); ?></span>
							</td>
						</tr>
					</table>
					
					<h3><?php _e( 'Info window content (advanced usage !)', 'simple-post-gmaps' ); ?></h3>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="tooltip"><?php _e( 'Code HTML for tooltip Google Maps', 'simple-post-gmaps' ); ?></label></th>
							<td>
								<textarea cols="50" rows="10" style="width:100%" name="tooltip" type="text" id="tooltip"><?php echo esc_attr( $current_settings['tooltip'] ); ?></textarea>
							</td>
						</tr>
					</table>
					<h3><?php _e( 'End of content', 'simple-post-gmaps' ); ?></h3>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="hidden_coordinates"><?php _e( 'Do not add the hidden coordinates at the end of the article', 'simple-post-gmaps' ); ?></label></th>
							<td>
								<input name="hidden_coordinates" type="checkbox" <?php checked( true, $current_settings['hidden_coordinates'], true ) ?> id="hidden_coordinates" value="1" class="regular-text" />
							</td>
						</tr>
					</table>
					<p class="submit">
						<?php wp_nonce_field( 'save-sgm-settings' ); ?>
						<input class="button-primary" name="save-sgm" type="submit" value="<?php _e( 'Save settings', 'simple-post-gmaps' ); ?>" />
					</p>
				</form>
			</div><!-- /col-container -->
		</div>
		<?php
		return true;
	}

	/**
	 * Check $_POST datas for relations liaisons
	 *
	 * @return boolean
	 */
	function checkRelations() {
		if ( isset( $_POST['save-sgm'] ) ) {
			check_admin_referer( 'save-sgm-settings' );
			
			$new_options = array();
			$new_options['custom-types'] 	= $_POST['custom-types'];
			$new_options['region'] 			= stripslashes( $_POST['region'] );
			$new_options['language'] 		= stripslashes( $_POST['language'] );
			$new_options['tooltip'] 		= stripslashes( $_POST['tooltip'] );
			$new_options['hidden_coordinates'] = $_POST['hidden_coordinates'];

			update_option( SGM_OPTION, $new_options );
			
			$this->message = __( 'Settings updated with success !', 'simple-post-gmaps' );
		}
		return false;
	}

	/**
	 * Display WP alert
	 *
	 */
	function displayMessage() {
		if ( $this->message != '' ) {
			$message = $this->message;
			$status = $this->status;
			$this->message = $this->status = ''; // Reset
		}
		
		if ( isset( $message ) && !empty( $message ) ) {
		?>
			<div id="message" class="<?php echo ( $status != '' ) ? $status :'updated'; ?> fade">
				<p><strong><?php echo $message; ?></strong></p>
			</div>
		<?php
		}
	}
	
	/**
	 * Register javascript need for Google Maps v3
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function loadJavascript() {
		global $pagenow;
		
		if ( !in_array( $pagenow, array('post.php', 'post-new.php') ) )
			return false;
		
		// Get settings on DB
		$current_settings = get_option( SGM_OPTION );
		
		if ( isset($_GET['post']) ) { // Edition
			$post_type = get_post_type( $_GET['post'] );
		} else { // Add ?
			$post_type = ( !isset( $_GET['post_type'] ) ) ? 'post' : stripslashes( $_GET['post_type'] );
		}
		
		wp_enqueue_style ( 'simple-gm', SGM_URL . 'inc/ressources/simple.gm.css', array(), SGM_VERSION, 'all' );

		wp_enqueue_script( 'geo-location', 	SGM_URL . 'inc/ressources/geo-location.min.js', array('jquery'), 		SGM_VERSION );
		wp_enqueue_script( 'geo-gears', 	SGM_URL . 'inc/ressources/gears-init.min.js', 	array('geo-location'), 	SGM_VERSION );
		wp_enqueue_script( 'google-jsapi', 	'http://www.google.com/jsapi', 					array('geo-gears'), 	SGM_VERSION );
		wp_enqueue_script( 'simple-gm', 	SGM_URL . 'inc/ressources/simple.gm.min.js', 	array('google-jsapi'), 	SGM_VERSION );
		
		// Translate and region ?
		$current_settings = get_option( SGM_OPTION );

		$args = array( 'regionL10n' => '', 'languageL10n' => '' );
		if ( isset( $current_settings['region'] ) && !empty( $current_settings['region'] ) )
			$args['region'] = '&region='.$current_settings['region'];

		if ( isset( $current_settings['language'] ) && !empty( $current_settings['language'] ) )
			$args['language'] = '&language='.$current_settings['language'];

		wp_localize_script( 'simple-gm', 'simplegmL10n', $args );
	}
	
	/**
	 * Add meta box for allow geolocalisation...
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function addMetaBox() {
		// Get settings on DB
		$current_settings = get_option( SGM_OPTION );

		foreach ( get_post_types( array(), 'objects' ) as $post_type ) {
			if ( !in_array( $post_type->name, (array) $current_settings['custom-types'] ) )
				continue;

			add_meta_box( 'geo-location', __( 'Location', 'simple-post-gmaps' ), array( &$this, 'blockPostGeo' ), $post_type->name, 'side', 'low' );
		}
	}
	
	/**
	 * Display the HTML need for geolocalisation
	 *
	 * @param object $post
	 * @return void
	 * @author Amaury Balmer
	 */
	function blockPostGeo( $post ) {
		$geo_value = get_post_meta( $post->ID, 'geo', true );
		if ( $geo_value == false )
			$geo_value = array( 'share_post' => '', 'latitude' => '', 'longitude' => '', 'accuracy' => '', 'address' => '' );
		else 
			$geo_value['accuracy'] = (int) $geo_value['accuracy'];
			
		if ( !isset( $geo_value['share_post'] ) )
			$geo_value['share_post'] = '';
		?>
		<div class="geo-form">
			<p>
				<label for="geo-address"><?php _e('Enter address:', 'simple-post-gmaps'); ?></label>
				<img alt="<?php _e('Ico refreshing', 'simple-post-gmaps'); ?>" src="<?php echo SGM_URL; ?>/inc/ressources/wpspin_light.gif" class="geo-throbber" />
			</p>
			
			<p><input type="text" name="geo[address]" class="geo-address" id="geo-address" value="<?php echo esc_attr( $geo_value['address'] ); ?>" /></p>
			<p>
				<input type="button" class="geo-auto-detect hide-if-no-geo button alignleft"  value="<?php _e( 'Auto Detect', 'simple-post-gmaps' ); ?>"  />
				<input type="button" class="geo-address-find hide-if-no-js button alignright" value="<?php _e( 'Find Address', 'simple-post-gmaps' ); ?>" />
				<br class="clear" />
			</p>
			<div id="geo-map" class="geo-map"></div>
			
			<p class="howto"><?php _e( 'or click map to pick location', 'simple-post-gmaps' ); ?></p>
			<input type="hidden" class="latitude" 	name="geo[latitude]"  value="<?php echo esc_attr( $geo_value['latitude'] ); ?>"  />
			<input type="hidden" class="longitude" 	name="geo[longitude]" value="<?php echo esc_attr( $geo_value['longitude'] ); ?>" />
			<input type="hidden" class="accuracy" 	name="geo[accuracy]"  value="<?php echo esc_attr( $geo_value['accuracy'] ); ?>"  />
			
			<label>
				<input <?php checked( '1', $geo_value['share_post'] ); ?> type="checkbox" name="geo[share_post]" class="geo-share-post" value="1" /> 
				<?php _e( "This post's location is public", 'simple-post-gmaps' ); ?>
			</label>
			<br />
		</div>
		<?php
	}
}
?>