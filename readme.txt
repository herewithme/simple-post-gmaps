=== Simple Post GMaps ===
Contributors: momo360modena, rahe
Donate link: http://www.beapi.fr/donate/
Tags : google, maps, googlemaps, simple series, map, geo, geolocalize, localisation
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 3.2

== Description ==

This plugin allow to geolocalise post with Google Maps (API in v3). No google maps key are required.

You can choose with the map the position of the post on admin.
You can use shortcode for display the map, or the widget. You can also display a maps with each posts localized on the same maps !

For full info go the [Simple Post Gmaps](http://redmine.beapi.fr/projects/show/simple-googlemaps) page.
Read the sample file for an example of query.

== Installation ==

1. Download, unzip and upload to your WordPress plugins directory
2. Activate the plugin within you WordPress Administration Backend
3. Go to Settings > Maps and follow the steps on the [Simple Post Gmaps](http://redmine.beapi.fr/projects/show/simple-googlemaps) page.

== Changelog ==

* 3.2
	* Performance improvements
	* Shortcode enerator with terms
	* Remove some javascript debug
* 3.1.2
	* Add simple post gmaps icon and script on ever admin post insertion/edition page.
* 3.1.1
	* Check WP_Ajax present or not before include file
	* Add global maps shortcode configurator in the admin editor
	* Externalize the javascipt for performances
	* Add a search feld for centering the map in one location
* 3.1
	* Use maps V3 massively
	* Clean code and use jquery for multiple global maps
	* Add button en editor for the shortcode on global gmaps
	* Add search field under gmaps
* 3.0.10
	* Massive clean of the code source. (try to economize memory!)
	* Fix small bug with saving meta geo
	* Use WordPress API...
* 3.0.9
	* Add possibility to not add the hidden coordinates a the end of posts
* 3.0.8
	* Fix a bug with edition of custom type and registering Javascript/CSS
* 3.0.7
	* Only insert in table at post publish
	* Some minor bug fix
	* Remove php Notices
* 3.0.6
	* Add a table at plugin activation for queries
	* Add possibility to get posts by latitude and longitude coordinates ordered by distance( distance in km )
	* Meta are merged with the table data's
* 3.0.5
	* Fix a bug with javascript recursive loop
* 3.0.4
	* Rename all code for "Simple Post Gmaps"
	* Add admin page for optionnaly active meta box on each custom types
	* Add documentation on whole code.
	* Clean function with unsed part
* 3.0.3
	* Allow custom icons depending category
* 3.0.2
	* Add shortcode for display all posts on same maps, with dynamic KML
* 3.0.1
	* Add first shortcode for display post geolocalisation on Gmaps.
* 3.0.0
	* Clone Post Geo Meta from WP.com