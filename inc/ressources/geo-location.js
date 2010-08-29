var geoForm = function(form, options) {
	var _this = this;
	this.options = jQuery.extend({},
	this.options, options);
	this.form = form;
	if (form.is('form')) {
		this.topForm = form;
	} else {
		this.topForm = form.parents('form:first');
	}
	this.sendCheck = jQuery('.geo-auto-detect', form).click(function() {
		_this.enable.call(_this);
	});
	jQuery('.geo-address-find', form).click(function() {
		return _this.geocode();
	});
	jQuery('.geo-address', form).keypress(function(event) {
		if (13 != event.keyCode) {
			return true;
		}
		return _this.geocode();
	}).blur(function() {
		_this.geocode();
	});
	var enabled = jQuery('.geo-enable').change(function() {
		if (jQuery(this).is(':checked')) {
			_this.form.show();
			_this.initMap();
		} else {
			_this.form.hide();
		}
	});
	if (!enabled.size() || enabled.is(':checked')) {
		this.initMap();
	}
	this.setPosition = function() {
		return _this._setPosition.apply(_this, arguments);
	}
	this.handlePositionError = function() {
		return _this._handlePositionError.apply(_this, arguments);
	}
};
if ('undefined' != typeof navigator && navigator.geolocation) {
	geoForm.geolocation = navigator.geolocation;
} else if (window.google && google.gears) {
	geoForm.geolocation = google.gears.factory.create('beta.geolocation');
}
geoForm.geocodeSortLook = {
	ROOFTOP: 0,
	RANGE_INTERPOLATED: 1,
	GEOMETRIC_CENTER: 2,
	APPROXIMATE: 3
};
geoForm.geocodeSort = function(a, b) {
	return geoForm.geocodeSortLook[a.geometry.location_type] - geoForm.geocodeSortLook[b.geometry.location_type];
};
geoForm.prototype = {
	form: null,
	topForm: null,
	options: {
		postName: 'geo',
		error: null
	},
	sendCheck: null,
	map: false,
	mapInitialized: false,
	address: false,
	geocoding: false,
	position: false,
	fields: ['latitude', 'longitude', 'accuracy'],
	marker: false,
	accuracyCircle: false,
	accuracyBound: false,
	initMap: function() {
		if (this.mapInitialized) {
			return;
		}
		var _this = this,
			map = jQuery('.geo-map', this.form);
		if (!map.is(':visible')) {
			return;
		}
		if (map.size()) {
			var lat = jQuery('.latitude', this.form),
				lon = jQuery('.longitude', this.form),
				acc = jQuery('.accuracy', this.form),
				accVal, center = new google.maps.LatLng(lat.val() || lat.attr('title') || lat.text(), lon.val() || lon.attr('title') || lon.text());
			this.map = new google.maps.Map(map.get(0), {
				zoom: this.options.zoom || 0,
				center: center,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			});
			google.maps.event.addListener(this.map, 'click', function(event) {
				this.marker = new google.maps.Marker({
					position: event.latLng,
					map: this.map
				});
				_this.setFields(event.latLng);
				_this.reverseGeocode(event.latLng);
			});
			accVal = acc.val() || acc.attr('title') || acc.text();
			this.centerMap(center, accVal);
		} else {
			this.map = false;
		}
		this.mapInitialized = true;
	},
	enable: function() {
		this.preventSubmit();
		if (this.position) {
			this.setPosition(this.position);
			this.allowSubmit();
		} else {
			geoForm.geolocation.getCurrentPosition(this.setPosition, this.handlePositionError, {
				timeout: 6000
			});
		}
	},
	_setPosition: function(position) {
		var _this = this;
		this.position = position;
		setTimeout(function() {
			var center = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
			_this.reverseGeocode(center);
		},
		10);
		this.setFields(position.coords);
		this.allowSubmit();
		if (this.map) {
			this.centerMap();
		}
	},
	setFields: function(fields) {
		var _this = this,
			center, accuracy, marker, se;
		if (fields.location_type && fields.bounds) {
			center = fields.location;
			se = new google.maps.LatLng(fields.bounds.getSouthWest().lat(), fields.bounds.getNorthEast().lng());
			accuracy = (_this.distance(fields.bounds.getSouthWest(), se) + _this.distance(fields.bounds.getNorthEast(), se)) / 4;
			fields = fields.location;
		} else if (fields.location_type) {
			center = fields.location;
			accuracy = false;
			fields = fields.location;
		} else if (fields.constructor == google.maps.LatLng) {
			center = fields;
			accuracy = false;
		}
		if (fields.constructor == google.maps.LatLng) {
			jQuery.each(this.fields, function() {
				if ('latitude' == this) {
					jQuery('.latitude', _this.form).val(fields.lat());
				} else if ('longitude' == this) {
					jQuery('.longitude', _this.form).val(fields.lng());
				} else if ('accuracy' == this) {
					jQuery('.accuracy', _this.form).val(accuracy);
				} else {
					jQuery('.' + this, _this.form).val('');
				}
			});
		} else {
			center = new google.maps.LatLng(fields.latitude, fields.longitude),
			accuracy = fields.accuracy;
			jQuery.each(this.fields, function() {
				var val = '',
					input;
				if (('undefined' != typeof fields[this]) && fields[this]) {
					val = fields[this];
				}
				input = _this.form.find('.' + this);
				if (input.size()) {
					input.val(val);
				} else {
					_this.form.append('<input type="hidden" class="geo-field ' + this + '" name="' + _this.options.postName + '[' + this + ']" value="' + val + '" />');
				}
			});
		}
		if (!this.map) {
			return;
		}
		if (accuracy) {
			if (this.marker) {
				this.marker.setMap();
				this.marker = false;
			}
			this.drawCircle(this.map, center, parseInt(accuracy) / 1000, 100);
		} else {
			if (this.accuracyCircle) {
				this.accuracyCircle.setMap();
				this.accuracyCircle = false;
			}
			marker = {
				position: center,
				map: this.map
			};
			if (this.marker) {
				this.marker.setOptions(marker);
			} else {
				this.marker = new google.maps.Marker(marker);
			}
		}
	},
	centerMap: function(latLng, accuracy) {
		if (!latLng) {
			if (this.accuracyCircle) {
				this.map.fitBounds(this.accuracyBound);
			} else if (this.marker) {
				this.map.setCenter(this.marker.getPosition());
			}
			return;
		}
		if (accuracy) {
			if (0 == accuracy) {
				if (this.marker) {
					this.marker.setPosition(latLng);
					this.marker.setMap(this.map);
				} else {
					this.marker = new google.maps.Marker({
						position: latLng,
						map: this.map
					});
					this.map.setZoom(8);
				}
			} else {
				this.drawCircle(this.map, latLng, parseInt(accuracy) / 1000, 100);
				this.map.fitBounds(this.accuracyBound);
			}
		}
		this.map.setCenter(latLng);
	},
	_handlePositionError: function(error) {
		this.allowSubmit();
		this.sendCheck.attr('checked', false);
		if ('function' == typeof this.options.error) {
			this.options.error(error);
		}
	},
	disable: function() {
		this.form.find('.geo-field').remove();
		this.allowSubmit();
	},
	preventSubmit: function() {
		var _this = this;
		jQuery('.geo-throbber', this.form).css('visibility', 'visible');
		this.topForm.find('[type=submit]').attr('disabled', 'disabled');
		this.topForm.bind('submit.geoForm', function() {
			return false;
		});
		setTimeout(function() {
			_this.allowSubmit.call(_this);
		},
		6000);
	},
	allowSubmit: function() {
		jQuery('.geo-throbber', this.form).css('visibility', 'hidden');
		this.topForm.find('[type=submit]').attr('disabled', false);
		this.topForm.unbind('submit.geoForm');
	},
	geocode: function() {
		if (this.geocoding) {
			return false;
		}
		this.geocoding = true;
		var _this = this,
			geocoder = new google.maps.Geocoder;
		if (_this.address == jQuery('.geo-address', _this.form).val()) {
			_this.geocoding = false;
			return true;
		}
		this.preventSubmit();
		geocoder.geocode({
			address: jQuery('.geo-address', _this.form).val()
		},
		function(responses, status) {
			_this.geocoding = false;
			if ('OK' != status) {
				_this.allowSubmit();
				return;
			}
			responses.sort(geoForm.geocodeSort);
			_this.setFields(responses[0].geometry);
			_this.allowSubmit();
			_this.address = jQuery('.geo-address', _this.form).val();
			if (_this.map) {
				_this.centerMap();
			}
		});
		return false;
	},
	reverseGeocode: function(latLng) {
		var _this = this,
			geocoder = new google.maps.Geocoder;
		geocoder.geocode({
			latLng: latLng
		},
		function(responses, status) {
			if ('OK' != status) return;
			responses.sort(geoForm.geocodeSort);
			_this.form.find('.geo-address').val(responses[0].formatted_address);
			_this.address = jQuery('.geo-address', _this.form).val();
		});
	},
	drawCircle: function(map, center, radius, complexity) {
		if (this.accuracyCircle) {
			this.accuracyCircle.setMap();
		}
		this.accuracyBound = new google.maps.LatLngBounds();
		var circlePoints = [];
		var r = radius / 6378.8;
		var lat1 = Math.PI / 180 * center.lat();
		var lng1 = Math.PI / 180 * center.lng();
		complexity = complexity || 24;
		var aa = 360 / complexity;
		for (var a = 0; a < 361; a = a + aa) {
			var tc = Math.PI / 180 * a;
			var y = Math.asin(Math.sin(lat1) * Math.cos(r) + Math.cos(lat1) * Math.sin(r) * Math.cos(tc));
			var dlng = Math.atan2(Math.sin(tc) * Math.sin(r) * Math.cos(lat1), Math.cos(r) - Math.sin(lat1) * Math.sin(y));
			var x = ((lng1 - dlng + Math.PI) % (2 * Math.PI)) - Math.PI;
			var point = new google.maps.LatLng(parseFloat(y * 180 / Math.PI), parseFloat(x * 180 / Math.PI));
			circlePoints.push(point);
			this.accuracyBound.extend(point);
		}
		if (jQuery.browser.msie) {
			return;
		}
		this.accuracyCircle = new google.maps.Polygon({
			paths: circlePoints,
			strokeColor: '#FF0000',
			strokeOpacity: 0.5,
			strokeWeight: 2,
			fillColor: '#FF0000',
			fillOpacity: 0.2
		});
		this.accuracyCircle.setMap(map);
	},
	distance: function(a, b) {
		var dLat = (b.lat() - a.lat()) * Math.PI / 180,
			dLon = (b.lng() - a.lng()) * Math.PI / 180,
			h = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(a.lat() * Math.PI / 180) * Math.cos(b.lat() * Math.PI / 180) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
		return 6371000 * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
	}
};
jQuery(function($) {
	if (!geoForm.geolocation) {
		if (!google.loader.ClientLocation) {
			return;
		}
		geoForm.geolocation = {
			getCurrentPosition: function(success, error, args) {
				success({
					coords: {
						latitude: google.loader.ClientLocation.latitude,
						longitude: google.loader.ClientLocation.longitude
					}
				});
			}
		};
	}
	$('.hide-if-no-geo').show();
	$('.hide-if-geo').hide();
});
