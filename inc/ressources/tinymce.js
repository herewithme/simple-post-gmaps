(function() {
	jQuery( '.sendSMPG select[name=post_type]' ).live( 'change', function( e ) {
		e.preventDefault();
		spgm_refresh_taxonomies( this );
	} );
	jQuery( '.sendSMPG select[name=taxonomy]' ).live( 'change', function( e ) {
		e.preventDefault();
		spgm_refresh_terms( this );
	} );
	launched = false;
	tinymce.create('tinymce.plugins.spgm', {
		init : function(ed, url) {
			ed.addButton('spgm', {
				title : 'Simple Post GMaps',
				onclick : function() {
					tb_show('Simple post gmaps Shortcode', ajaxurl+'?action=spgm_shortcodePrinter&width=600&height=900');
					if( !launched ) {
						jQuery( 'form.sendSMPG' ).live( "submit", function( e ) {
							launched = true;
							_self = this;
							e.preventDefault();
							ed.execCommand(
								'mceInsertContent',
								true,
								spgm_create_shortcode( _self )
							);
							tb_remove();
						} );
					}
				}
			});
		},
		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
				longname : "Simple Post Gmaps",
				author : 'Beapi',
				authorurl : 'http://beapi.fr/',
				infourl : 'http://beapi.fr/',
				version : "1.0"
			};
		}
	});
	tinymce.PluginManager.add('spgm', tinymce.plugins.spgm);
})();

function spgm_create_shortcode( el ) {
	var inputs = jQuery( el ) .serializeArray();
	var shortcode = ' [global-googlemaps ';
	for( var a in inputs ) {
		
		if( inputs[a].value == "-1" || inputs[a].value == "any" )
			continue;
			
		if( inputs[a].value == "" )
			inputs[a].value = false;
			
		shortcode += ' '+inputs[a].name+'="'+inputs[a].value+'"';
	}
	
	shortcode += ' ] ';
	
	return shortcode;
}

function spgm_preview( el ) {
	var inputs = jQuery( el ).closest( 'form' ).serialize();
	
}

/**
 * Refresh taxonomies of one post_type
 *
 * @param el : the element clicked
 * @return void
 * @author Nicolas Juen
 */
function spgm_refresh_taxonomies( el ) {
	// get current object
	var _self = jQuery( el );

	// Get the parent widget
	var taxo = jQuery( '.sendSMPG select[name=taxonomy]' );
	var parent = taxo.closest( 'td' );
	var ptype = _self.val();
	
	// Check if we are not currently ajaxng on this frame
	if( !parent.hasClass( 'ajaxing' ) ) {
		jQuery.ajax( {
			url: ajaxurl,
			type: "POST",
			dataType: 'json',
			data: { action : "post_type_taxonomy", post_type : ptype },
			beforeSend: function() {
				// Add ajax class to the parent
				parent.addClass( 'ajaxing' );
				
				// Fade the parent and the list while searching
				parent.fadeTo( 'fast','0.5' );
			},
			success: function( result ) {
				output = jQuery( '<option/>' ).attr( 'value', "-1" ).html( 'None' );

				// remvoe class for ajaxing and set the opacity to 1
				parent.removeClass( 'ajaxing' );
				parent.fadeTo( 'fast','1' );

				for( var i in result ) {
					output.push( jQuery( '<option/>' ).attr( 'value', i ).html( result[i] )[0] );
				}

				taxo.html(output);
			}
		} );
	}
}

/**
 * Refresh terms of one taxonomy
 *
 * @param el : the element clicked
 * @return void
 * @author Nicolas Juen
 */
function spgm_refresh_terms( el ) {
	// get current object
	var _self = jQuery( el );

	// Get the parent widget
	var term = jQuery( '.sendSMPG select[name=term]' );
	var parent = term.closest( 'td' );
	var taxo = _self.val();
	
	// Check if we are not currently ajaxng on this frame
	if( !parent.hasClass( 'ajaxing' ) ) {
		jQuery.ajax( {
			url: ajaxurl,
			type: "POST",
			dataType: 'json',
			data: { action : "taxonomy_terms", taxonomy : taxo },
			beforeSend: function() {
				// Add ajax class to the parent
				parent.addClass( 'ajaxing' );
				
				// Fade the parent and the list while searching
				parent.fadeTo( 'fast','0.5' );
			},
			success: function( result ) {
				output = jQuery( '<option/>' ).attr( 'value', "-1" ).html( 'None' );

				// remvoe class for ajaxing and set the opacity to 1
				parent.removeClass( 'ajaxing' );
				parent.fadeTo( 'fast','1' );

				for( var i in result ) {
					output.push( jQuery( '<option/>' ).attr( 'value', i ).html( result[i] )[0] );
				}

				term.html(output);
			}
		} );
	}
}