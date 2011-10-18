jQuery( function(){
	jQuery( '.gmaps' ).each( function( i,el ) {
		var spgm = {
			map : '' ,
			form : '' ,
			pType :'',
			taxoR : '',
			taxoF : '',
			term : '',
			zoomS : 10,
			geoXml : '',
			options : '',
			filterForm: '',
			search: false,
			searchForm: '',
			searchField: '',
			searchButton: '',
			gLocalSearch :'',
			geoCoder : '',
			gCurrentResults: [],
			init : function( el ){
				var _self = this;
				this.form = jQuery( el ).closest( 'div.spgmWrapper' ).find( 'form.spgmSettings' );
				this.pType = this.form.find( 'input[name=spgm_post_type]' ).val();
				this.taxoR = this.form.find( 'input[name=spgm_requestTaxo]' ).val();
				this.term = this.form.find( 'input[name=spgm_requestTerm]' ).val();
				this.taxoF = this.form.find( 'input[name=spgm_firstTaxo]' ).val();
				this.zoomS = this.form.find( 'input[name=spgm_zoom]' ).val();
				
				this.searchForm = jQuery( el ).closest( 'div.spgmWrapper' ).find( 'form.spgmSearch' );
				this.searchField = this.searchForm.find( 'input[name="spgm_search"]' );
				this.searchButton = this.searchForm.find( 'input[name="spgm_ok"]' );

				if( jQuery( el ).hasClass( 'search' ) ) {
					this.search = true;
				}
				this.searchForm.submit( function(e) { e.preventDefault();_self.centerMap() } );
				
				this.options = {
					zoom: parseInt( this.zoomS ),
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					mapTypeControl: true,
					navigationControl: true,
					center: new google.maps.LatLng(48.892783,2.342877)
				}

				this.geoCoder = new google.maps.Geocoder();
				
				if( this.term == '' ) {
					this.refreshMap( '&post_type='+this.pType+this.taxoR );
				}

				this.filterForm = jQuery( el ).closest( 'div.spgmWrapper' ).find( '.termsFiltering' );
				this.filterForm.find( 'input' ).click( function() { _self.filter() } );

				if( this.term != '' ) {
					_self.filterOneTerm();
				}
			},
			refreshMap: function( params ) {
				var _self = this;
				this.map = new google.maps.Map( el, this.options );
				this.geoXml = new geoXML3.parser( { map:this.map } );
				this.geoXml.parse( geoxml3L10n.kml_url+params );
			},
			centerMap: function( el ){
				var _self = this ;
				var center = '(' + _self.map.getCenter().lat() +', '+ _self.map.getCenter().lng() +')';

				_self.geoCoder.geocode( {
						'address': _self.searchField.val() ,
						'partialmatch': true
					},
					function ( results, status ) {
						if (status == 'OK' && results.length > 0) {
							_self.map.setCenter( new google.maps.LatLng( results[0].geometry.location.Pa, results[0].geometry.location.Qa ) );
							_self.map.setZoom( 13 );
						}
					}
				);
			},
			filter: function() {
				//Get all inputs checked
				var termsFilter = "";
				var taxonomiesFilter = "";
				var inputs = this.filterForm.find( 'input:checked' );

				if( inputs == "" )
					return false;

				//Foreach inputs get set the parameters
				inputs.each( function( i, el ) {
					// Set parameters to filter the taxonomies
					taxonomiesFilter += "&taxonomiesFilter[]="+jQuery(el).closest( "div" ).attr( "class" );

					// Set parameters for the terms
					termsFilter += "&termsFilter[]="+el.value;
				} );

				//Create the new map
				this.refreshMap('&post_type='+this.pType+termsFilter+taxonomiesFilter+'&taxonomiesFilter[]='+this.taxoF);
			},
			filterOneTerm: function() {
				
				var termsFilter = "&termsFilter[]="+this.term;
				
				//Create the new map
				this.refreshMap('&post_type='+this.pType+this.taxoF+termsFilter);
			}
		}
		spgm.init( el );
	} );
});