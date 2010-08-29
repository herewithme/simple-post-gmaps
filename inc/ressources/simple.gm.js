var geoInit = function() {
	jQuery( function($) {
		new geoForm( $('.geo-form') );
	} );
};
google.load( "maps", "3", { other_params: "sensor=false" + simplegmL10n.region + simplegmL10n.language } );
google.setOnLoadCallback( geoInit );