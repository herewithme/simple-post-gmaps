/*
 geoXML3.js
 Renders KML on the Google Maps JavaScript API Version 3
 http://code.google.com/p/geoxml3/
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
// Extend the global String with a method to remove leading and trailing whitespace
if (!String.prototype.trim) {
	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g, '');
	};
}

// Declare namespace
geoXML3 = window.geoXML3 || {};

// Constructor for the root KML parser object
geoXML3.parser = function(options) {
	// Private variables
	var parserOptions = geoXML3.combineOptions(options, {
		singleInfoWindow: false,
		processStyles: true,
		zoom: true
	});
	var docs = []; // Individual KML documents
	var lastMarker;

	// Private methods
	var parse = function(urls) {
		// Process one or more KML documents
		if (typeof urls === 'string') {
			// Single KML document
			urls = [urls];
		}

		// Internal values for the set of documents as a whole
		var internals = {
			docSet: [],
			remaining: urls.length,
			parserOnly: !parserOptions.afterParse
		};

		var thisDoc;
		for (var i = 0; i < urls.length; i++) {
			thisDoc = {
				url: urls[i],
				internals: internals
			};
			internals.docSet.push(thisDoc);
			geoXML3.fetchXML(thisDoc.url, function(responseXML) {
				render(responseXML, thisDoc);
			});
		}
	};

	var hideDocument = function(doc) {
		// Hide the map objects associated with a document 
		var i;
		for (i = 0; i < doc.markers.length; i++) {
			this.markers[i].set_visible(false);
		}
		for (i = 0; i < doc.overlays.length; i++) {
			doc.overlays[i].setOpacity(0);
		}
	};

	var showDocument = function(doc) {
		// Show the map objects associated with a document 
		var i;
		for (i = 0; i < doc.markers.length; i++) {
			doc.markers[i].set_visible(true);
		}
		for (i = 0; i < doc.overlays.length; i++) {
			doc.overlays[i].setOpacity(doc.overlays[i].percentOpacity_);
		}
	};

	var render = function(responseXML, doc) {
		// Callback for retrieving a KML document: parse the KML and display it on the map
		if (!responseXML) {
			// Error retrieving the data
			geoXML3.log('Unable to retrieve ' + doc.url);
			if (parserOptions.failedParse) {
				parserOptions.failedParse(doc);
			}
		} else if (!doc) {
			throw 'geoXML3 internal error: render called with null document';
		} else {
			doc.styles = {};
			doc.placemarks = [];
			doc.groundOverlays = [];
			if (parserOptions.zoom && !!parserOptions.map) doc.bounds = new google.maps.LatLngBounds();

			// Parse styles
			var styleID, iconNodes, i;
			var styleNodes = responseXML.getElementsByTagName('Style');
			for (i = 0; i < styleNodes.length; i++) {
				styleID = styleNodes[i].getAttribute('id');
				iconNodes = styleNodes[i].getElementsByTagName('Icon');
				if ( !! iconNodes.length) {
					doc.styles['#' + styleID] = {
						href: geoXML3.nodeValue(iconNodes[0].getElementsByTagName('href')[0])
					};
				}
			}
			if ( !! parserOptions.processStyles || !parserOptions.createMarker) {
				// Convert parsed styles into GMaps equivalents
				processStyles(doc);
			}

			// Parse placemarks
			var placemark, node, coords, path;
			var placemarkNodes = responseXML.getElementsByTagName('Placemark');
			for (i = 0; i < placemarkNodes.length; i++) {
				// Init the placemark object
				node = placemarkNodes[i];
				placemark = {
					name: geoXML3.nodeValue(node.getElementsByTagName('name')[0]),
					permalink: geoXML3.nodeValue(node.getElementsByTagName('permalink')[0]),
					description: geoXML3.nodeValue(node.getElementsByTagName('description')[0]),
					styleUrl: geoXML3.nodeValue(node.getElementsByTagName('styleUrl')[0])
				};
				placemark.style = doc.styles[placemark.styleUrl] || {};
				if (/^https?:\/\//.test(placemark.description)) {
					placemark.description = '<a href="' + placemark.description + '">' + placemark.description + '</a>';
				}

				// Extract the coordinates
				coords = geoXML3.nodeValue(node.getElementsByTagName('coordinates')[0]).trim();
				coords = coords.replace(/\s+/g, ' ').replace(/, /g, ',');
				path = coords.split(' ');

				// What sort of placemark?
				if (path.length === 1) {
					// Polygons/lines not supported in v3, so only plot markers
					coords = path[0].split(',');
					placemark.point = {
						lat: parseFloat(coords[1]),
						lng: parseFloat(coords[0]),
						alt: parseFloat(coords[2])
					};
					if ( !! doc.bounds) {
						doc.bounds.extend(new google.maps.LatLng(placemark.point.lat, placemark.point.lng));
					}

					// Call the appropriate function to create the marker
					if ( !! parserOptions.createMarker) {
						parserOptions.createMarker(placemark, doc);
					} else {
						createMarker(placemark, doc);
					}
				}
			}

			// Parse ground overlays
			var groundOverlay, color, transparency;
			var groundNodes = responseXML.getElementsByTagName('GroundOverlay');
			for (i = 0; i < groundNodes.length; i++) {
				node = groundNodes[i];

				// Init the ground overlay object
				groundOverlay = {
					name: geoXML3.nodeValue(node.getElementsByTagName('name')[0]),
					permalink: geoXML3.nodeValue(node.getElementsByTagName('permalink')[0]),
					description: geoXML3.nodeValue(node.getElementsByTagName('description')[0]),
					icon: {
						href: geoXML3.nodeValue(node.getElementsByTagName('href')[0])
					},
					latLonBox: {
						north: parseFloat(geoXML3.nodeValue(node.getElementsByTagName('north')[0])),
						east: parseFloat(geoXML3.nodeValue(node.getElementsByTagName('east')[0])),
						south: parseFloat(geoXML3.nodeValue(node.getElementsByTagName('south')[0])),
						west: parseFloat(geoXML3.nodeValue(node.getElementsByTagName('west')[0]))
					}
				};
				if ( !! doc.bounds) {
					doc.bounds.union(new google.maps.LatLngBounds(new google.maps.LatLng(groundOverlay.latLonBox.south, groundOverlay.latLonBox.west), new google.maps.LatLng(groundOverlay.latLonBox.north, groundOverlay.latLonBox.east)));
				}

				// Opacity is encoded in the color node
				color = geoXML3.nodeValue(node.getElementsByTagName('color')[0]);
				if ((color !== '') && (color.length == 8)) {
					transparency = parseInt(color.substring(0, 2), 16);
					groundOverlay.opacity = Math.round((255 - transparency) / 2.55);
				} else {
					groundOverlay.opacity = 100;
				}

				// Call the appropriate function to create the overlay
				if ( !! parserOptions.createOverlay) {
					parserOptions.createOverlay(groundOverlay, doc);
				} else {
					createOverlay(groundOverlay, doc);
				}
			}

			if ( !! doc.bounds) {
				doc.internals.bounds = doc.internals.bounds || new google.maps.LatLngBounds();
				doc.internals.bounds.union(doc.bounds);
			}
			if ( !! doc.styles || !!doc.markers || !!doc.overlays) {
				doc.internals.parserOnly = false;
			}

			doc.internals.remaining -= 1;
			if (doc.internals.remaining === 0) {
				// We're done processing this set of KML documents
				// Options that get invoked after parsing completes
				if ( !! doc.internals.bounds) {
					parserOptions.map.fitBounds(doc.internals.bounds);
				}
				if (parserOptions.afterParse) {
					parserOptions.afterParse(doc.internals.docSet);
				}

				if (!doc.internals.parserOnly) {
					// geoXML3 is not being used only as a real-time parser, so keep the parsed documents around
					docs.concat(doc.internals.docSet);
				}
			}
		}
	};

	var processStyles = function(doc) {
		for (var styleID in doc.styles) {
			if ( !! doc.styles[styleID].href) {
				// Init the style object with a standard KML icon
				doc.styles[styleID].icon = new google.maps.MarkerImage(doc.styles[styleID].href, new google.maps.Size(30, 48));
			}
		}
	};

	var createMarker = function(placemark, doc) {
		// create a Marker to the map from a placemark KML object
		// Load basic marker properties
		var markerOptions = geoXML3.combineOptions(parserOptions.markerOptions, {
			map: parserOptions.map,
			position: new google.maps.LatLng(placemark.point.lat, placemark.point.lng),
			title: placemark.name,
			zIndex: Math.round(-placemark.point.lat * 100000),
			icon: placemark.style.icon,
			shadow: placemark.style.shadow
		});

		// Create the marker on the map
		var marker = new google.maps.Marker(markerOptions);
		
		// Replace HTML from DB settings and replace marker by dynamic value
		var cur_tooltip = html_entity_decode(geoxml3L10n.tooltip);
		cur_tooltip = cur_tooltip.replace(/%permalink%/g, placemark.permalink);
		cur_tooltip = cur_tooltip.replace(/%description%/g, placemark.description);
		cur_tooltip = cur_tooltip.replace(/%title%/g, placemark.name);
		cur_tooltip = cur_tooltip.replace(/%readmore%/g, geoxml3L10n.readmore);

		// Set up and create the infowindow
		var infoWindowOptions = geoXML3.combineOptions(parserOptions.infoWindowOptions, {
			width: 300,
			content: cur_tooltip
			//pixelOffset: new google.maps.Size(0, 2)
		});
		marker.infoWindow = new google.maps.InfoWindow(infoWindowOptions);

		// Infowindow-opening event handler
		google.maps.event.addListener(marker, 'click', function() {
			if ( !! parserOptions.singleInfoWindow) {
				if ( !! lastMarker && !!lastMarker.infoWindow) {
					lastMarker.infoWindow.close();
				}
				lastMarker = this;
			}
			this.infoWindow.open(this.map, this);
		});

		if ( !! doc) {
			doc.markers = doc.markers || [];
			doc.markers.push(marker);
		}

		return marker;
	};

	var createOverlay = function(groundOverlay, doc) {
		// Add a ProjectedOverlay to the map from a groundOverlay KML object
		if (!window.ProjectedOverlay) {
			throw 'geoXML3 error: ProjectedOverlay not found while rendering GroundOverlay from KML';
		}

		var bounds = new google.maps.LatLngBounds(new google.maps.LatLng(groundOverlay.latLonBox.south, groundOverlay.latLonBox.west), new google.maps.LatLng(groundOverlay.latLonBox.north, groundOverlay.latLonBox.east));
		var overlayOptions = geoXML3.combineOptions(parserOptions.overlayOptions, {
			percentOpacity: groundOverlay.opacity
		});
		var overlay = new ProjectedOverlay(parserOptions.map, groundOverlay.icon.href, bounds, overlayOptions);

		if ( !! doc) {
			doc.overlays = doc.overlays || [];
			doc.overlays.push(overlay);
		}

		return
	};

	return {
		// Expose some properties and methods
		options: parserOptions,
		docs: docs,
		parse: parse,
		hideDocument: hideDocument,
		showDocument: showDocument,
		processStyles: processStyles,
		createMarker: createMarker,
		createOverlay: createOverlay
	};
};
// End of KML Parser
// Helper objects and functions
// Log a message to the debugging console, if one exists
geoXML3.log = function(msg) {
	if ( !! window.console) {
		console.log(msg);
	}
};

// Combine two options objects, a set of default values and a set of override values 
geoXML3.combineOptions = function(overrides, defaults) {
	var result = {};
	if ( !! overrides) {
		for (var prop in overrides) {
			if (overrides.hasOwnProperty(prop)) {
				result[prop] = overrides[prop];
			}
		}
	}
	if ( !! defaults) {
		for (prop in defaults) {
			if (defaults.hasOwnProperty(prop) && (result[prop] === undefined)) {
				result[prop] = defaults[prop];
			}
		}
	}
	return result;
};

// Retrieve a text document from url and pass it to callback as a string
geoXML3.fetchers = [];
geoXML3.fetchXML = function(url, callback) {
	function timeoutHandler() {
		callback();
	};

	var xhrFetcher;
	if ( !! geoXML3.fetchers.length) {
		xhrFetcher = geoXML3.fetchers.pop();
	} else {
		if ( !! window.XMLHttpRequest) {
			xhrFetcher = new window.XMLHttpRequest(); // Most browsers
		} else if ( !! window.ActiveXObject) {
			xhrFetcher = new window.ActiveXObject('Microsoft.XMLHTTP'); // Some IE
		}
	}

	if (!xhrFetcher) {
		geoXML3.log('Unable to create XHR object');
		callback(null);
	} else {
		xhrFetcher.open('GET', url, true);
		xhrFetcher.onreadystatechange = function() {
			if (xhrFetcher.readyState === 4) {
				// Retrieval complete
				if ( !! xhrFetcher.timeout) clearTimeout(xhrFetcher.timeout);
				if (xhrFetcher.status >= 400) {
					geoXML3.log('HTTP error ' + xhrFetcher.status + ' retrieving ' + url);
					callback();
				} else {
					// Returned successfully
					callback(xhrFetcher.responseXML);
				}
				// We're done with this fetcher object
				geoXML3.fetchers.push(xhrFetcher);
			}
		};
		xhrFetcher.timeout = setTimeout(timeoutHandler, 60000);
		xhrFetcher.send(null);
	}
};

//nodeValue: Extract the text value of a DOM node, with leading and trailing whitespace trimmed
geoXML3.nodeValue = function(node) {
	if ( !(node.innerText || node.text || node.textContent) ) {
		return '';
	} else {
		return (node.innerText || node.text || node.textContent).trim();
	}
};

function get_html_translation_table (table, quote_style) {
    // http://kevin.vanzonneveld.net
    // +   original by: Philip Peterson
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: noname
    // +   bugfixed by: Alex
    // +   bugfixed by: Marco
    // +   bugfixed by: madipta
    // +   improved by: KELAN
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Frank Forte
    // +   bugfixed by: T.Wild
    // +      input by: Ratheous
    // %          note: It has been decided that we're not going to add global
    // %          note: dependencies to php.js, meaning the constants are not
    // %          note: real constants, but strings instead. Integers are also supported if someone
    // %          note: chooses to create the constants themselves.
    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
    // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}
    
    var entities = {}, hash_map = {}, decimal = 0, symbol = '';
    var constMappingTable = {}, constMappingQuoteStyle = {};
    var useTable = {}, useQuoteStyle = {};
    
    // Translate arguments
    constMappingTable[0]      = 'HTML_SPECIALCHARS';
    constMappingTable[1]      = 'HTML_ENTITIES';
    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
    constMappingQuoteStyle[2] = 'ENT_COMPAT';
    constMappingQuoteStyle[3] = 'ENT_QUOTES';

    useTable       = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
    useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
        throw new Error("Table: "+useTable+' not supported');
        // return false;
    }

    entities['38'] = '&amp;';
    if (useTable === 'HTML_ENTITIES') {
        entities['160'] = '&nbsp;';
        entities['161'] = '&iexcl;';
        entities['162'] = '&cent;';
        entities['163'] = '&pound;';
        entities['164'] = '&curren;';
        entities['165'] = '&yen;';
        entities['166'] = '&brvbar;';
        entities['167'] = '&sect;';
        entities['168'] = '&uml;';
        entities['169'] = '&copy;';
        entities['170'] = '&ordf;';
        entities['171'] = '&laquo;';
        entities['172'] = '&not;';
        entities['173'] = '&shy;';
        entities['174'] = '&reg;';
        entities['175'] = '&macr;';
        entities['176'] = '&deg;';
        entities['177'] = '&plusmn;';
        entities['178'] = '&sup2;';
        entities['179'] = '&sup3;';
        entities['180'] = '&acute;';
        entities['181'] = '&micro;';
        entities['182'] = '&para;';
        entities['183'] = '&middot;';
        entities['184'] = '&cedil;';
        entities['185'] = '&sup1;';
        entities['186'] = '&ordm;';
        entities['187'] = '&raquo;';
        entities['188'] = '&frac14;';
        entities['189'] = '&frac12;';
        entities['190'] = '&frac34;';
        entities['191'] = '&iquest;';
        entities['192'] = '&Agrave;';
        entities['193'] = '&Aacute;';
        entities['194'] = '&Acirc;';
        entities['195'] = '&Atilde;';
        entities['196'] = '&Auml;';
        entities['197'] = '&Aring;';
        entities['198'] = '&AElig;';
        entities['199'] = '&Ccedil;';
        entities['200'] = '&Egrave;';
        entities['201'] = '&Eacute;';
        entities['202'] = '&Ecirc;';
        entities['203'] = '&Euml;';
        entities['204'] = '&Igrave;';
        entities['205'] = '&Iacute;';
        entities['206'] = '&Icirc;';
        entities['207'] = '&Iuml;';
        entities['208'] = '&ETH;';
        entities['209'] = '&Ntilde;';
        entities['210'] = '&Ograve;';
        entities['211'] = '&Oacute;';
        entities['212'] = '&Ocirc;';
        entities['213'] = '&Otilde;';
        entities['214'] = '&Ouml;';
        entities['215'] = '&times;';
        entities['216'] = '&Oslash;';
        entities['217'] = '&Ugrave;';
        entities['218'] = '&Uacute;';
        entities['219'] = '&Ucirc;';
        entities['220'] = '&Uuml;';
        entities['221'] = '&Yacute;';
        entities['222'] = '&THORN;';
        entities['223'] = '&szlig;';
        entities['224'] = '&agrave;';
        entities['225'] = '&aacute;';
        entities['226'] = '&acirc;';
        entities['227'] = '&atilde;';
        entities['228'] = '&auml;';
        entities['229'] = '&aring;';
        entities['230'] = '&aelig;';
        entities['231'] = '&ccedil;';
        entities['232'] = '&egrave;';
        entities['233'] = '&eacute;';
        entities['234'] = '&ecirc;';
        entities['235'] = '&euml;';
        entities['236'] = '&igrave;';
        entities['237'] = '&iacute;';
        entities['238'] = '&icirc;';
        entities['239'] = '&iuml;';
        entities['240'] = '&eth;';
        entities['241'] = '&ntilde;';
        entities['242'] = '&ograve;';
        entities['243'] = '&oacute;';
        entities['244'] = '&ocirc;';
        entities['245'] = '&otilde;';
        entities['246'] = '&ouml;';
        entities['247'] = '&divide;';
        entities['248'] = '&oslash;';
        entities['249'] = '&ugrave;';
        entities['250'] = '&uacute;';
        entities['251'] = '&ucirc;';
        entities['252'] = '&uuml;';
        entities['253'] = '&yacute;';
        entities['254'] = '&thorn;';
        entities['255'] = '&yuml;';
    }

    if (useQuoteStyle !== 'ENT_NOQUOTES') {
        entities['34'] = '&quot;';
    }
    if (useQuoteStyle === 'ENT_QUOTES') {
        entities['39'] = '&#39;';
    }
    entities['60'] = '&lt;';
    entities['62'] = '&gt;';


    // ascii decimals to real symbols
    for (decimal in entities) {
        symbol = String.fromCharCode(decimal);
        hash_map[symbol] = entities[decimal];
    }
    
    return hash_map;
}

function html_entity_decode (string, quote_style) {
    // http://kevin.vanzonneveld.net
    // +   original by: john (http://www.jd-tech.net)
    // +      input by: ger
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: marc andreu
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Ratheous
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Nick Kolosov (http://sammy.ru)
    // +   bugfixed by: Fox
    // -    depends on: get_html_translation_table
    // *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
    // *     returns 1: 'Kevin & van Zonneveld'
    // *     example 2: html_entity_decode('&amp;lt;');
    // *     returns 2: '&lt;'

    var hash_map = {}, symbol = '', tmp_str = '', entity = '';
    tmp_str = string.toString();
    
    if (false === (hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {
        return false;
    }

    // fix &amp; problem
    // http://phpjs.org/functions/get_html_translation_table:416#comment_97660
    delete(hash_map['&']);
    hash_map['&'] = '&amp;';

    for (symbol in hash_map) {
        entity = hash_map[symbol];
        tmp_str = tmp_str.split(entity).join(symbol);
    }
    tmp_str = tmp_str.split('&#039;').join("'");
    
    return tmp_str;
}
