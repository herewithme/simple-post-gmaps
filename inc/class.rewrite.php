<?php
class Simple_Post_Gmaps_Rewrite {

	function Simple_Post_Gmaps_Rewrite() {
		//Generate new rewrite rules
		add_action( 'generate_rewrite_rules', array( &$this, 'createRewriteRules' ) );
		
		//Add the endpoint to the query
		add_filter( 'query_vars', array( &$this, 'addQueryVar' ) );
		
		//Add the template redirect
		add_action( 'template_redirect', array( &$this, 'include_template' ), 5 );
	}
	
	/**
	 * Create rewrite rules for the /maps slug
	 * 
	 * @access public
	 * @param mixed $wp_rewrite
	 * @return void
	 * @author Nicolas Juen
	 */
	function createRewriteRules( $wp_rewrite ) {
		$maps_link_text = (substr( get_permalink(), -1, 1 ) != '/' && substr( $wp_rewrite->permalink_structure, -1, 1 ) != '/' ) ? '/'.SGM_SLUG : SGM_SLUG;
		
		// Base on WP-EMail Standalone Post Rules
		$rewrite_rules = $wp_rewrite->generate_rewrite_rule( $wp_rewrite->permalink_structure.$maps_link_text, EP_PERMALINK );
		$rewrite_rules = array_slice( $rewrite_rules, 5, 1 );
		$r_rule = array_keys( $rewrite_rules );
		$r_rule = array_shift( $r_rule );
		$r_rule = str_replace( '/trackback', '', $r_rule );
		$r_link = array_values( $rewrite_rules );
		$r_link = array_shift( $r_link );
		$r_link = str_replace( 'tb=1', SGM_SLUG.'=1', $r_link );
		$wp_rewrite->rules = array_merge( array( $r_rule => $r_link ), $wp_rewrite->rules );
		
		$page_uris = $wp_rewrite->page_uri_index();
		if( is_array( $page_uris[0] ) ) {
			$maps_page_rules = array();
			foreach( $page_uris[0] as $uri => $pagename ) {
				$wp_rewrite->add_rewrite_tag( '%pagename%', '($uri)', 'pagename=' );
				$rewrite_rules = $wp_rewrite->generate_rewrite_rules( $wp_rewrite->get_page_permastruct().'/'.SGM_SLUG, EP_PAGES );
				$rewrite_rules = array_slice( $rewrite_rules, 5, 1 );
				$r_rule = array_keys( $rewrite_rules );
				$r_rule = array_shift( $r_rule );
				$r_rule = str_replace( '/trackback', '', $r_rule );
				$r_link = array_values( $rewrite_rules );
				$r_link = array_shift( $r_link );
				$r_link = str_replace( 'tb=1', SGM_SLUG.'=1', $r_link );
				$maps_page_rules = array_merge( $maps_page_rules, array( $r_rule => $r_link ) );
			}
			$wp_rewrite->rules = array_merge( $maps_page_rules, $wp_rewrite->rules );
		}

		// Add rewrite rules for the custom types checked
		
		// Get options
		$options = get_option( SGM_OPTION );
		if( !empty( $options['custom-types'] ) ) {
			foreach( $options['custom-types'] as $post_type ) {
				if( $post_type == 'post' || $post_type == 'page' ) //Remove pages and page
					continue;
					
				$new_rules = array( $post_type.'/(.+?)/'.SGM_SLUG.'/?$' => 'index.php?post_type='.$post_type.'&pagename='.$wp_rewrite->preg_index(1) .'&'.SGM_SLUG.'=1' );
				$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
			}
		}
	}
	
	/**
	 * Set the endpoint video
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	function addQueryVar( $wpvar ) {
		$wpvar[] = SGM_SLUG;
		return $wpvar;
	}
		
	/**
	 * Load correct templates
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	function include_template() {
		// If we have he slug redirect
		if( intval( get_query_var( SGM_SLUG ) ) == 1 ) {
			global $wp_query;
			
			//Get the current post_type if present
			$post_type = empty( $wp_query->query_vars['post_type'] ) ? $post_type = 'post' : $post_type = $wp_query->query_vars['post_type'];
			
			//Set the template var
			$templates[] = SGM_SLUG.'.php';
			$templates[] = SGM_SLUG.'-'.$post_type.'.php';
			
			//Add the templates to the list
			locate_template( $templates, true );
			
			exit();
		}
	}	
}
?>