<?php 
class WP_Ajax {
	function __construct( $ajax_prefix = 'a', $nopriv_prefix = 'n' ) {
		$regex = "/^($ajax_prefix)?($nopriv_prefix)?_|^($nopriv_prefix)?($ajax_prefix)?_/";
		$methods = get_class_methods( $this );
		foreach ( $methods as $method ) {
			if ( preg_match( $regex, $method, $matches ) ) {
				if ( count( $matches ) > 1 ) {
					$action = preg_replace( $regex, '', $method );
					if ( count( $matches ) == 3 ) {
						add_action( "wp_ajax_$action", array( $this, $method ) );
						add_action( "wp_ajax_nopriv_$action", array( $this, $method ) );
					} else {
						if ( $matches[1] == $ajax_prefix ) {
							add_action( "wp_ajax_$action", array( $this, $method ) );
						} else {
							add_action( "wp_ajax_nopriv_$action", array( $this, $method ) );
						}
					}
				}
			}
		}
	}
}
?>