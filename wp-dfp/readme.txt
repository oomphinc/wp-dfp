=== WP DFP ===
Contributors: webgeekconsulting, thinkoomph
Tags: google dfp, google ads
Requires at least: 4.1
Tested up to: 4.3
Stable tag: 1.1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple & intuitive interface for displaying Google ads on your WP site.

== Description ==

The WP DFP plugin for WordPress greatly simplifies the process of adding
Google DFP ad placements in your WordPress site. Simply provide your DFP
network code in the WP DFP settings screen and then define your ad slots and
sizing rules. Displaying ads where you want is easy either by using a simple
shortcode or by simple PHP function within your template files.

WP DFP gives you maximum control over which ad sizes should be displayed by
allowing you to specify infinite break points for each ad slot. Want to show
one size if the container width is x, but another size if the width is between
y and z? No problem, WP DFP has you covered.

== Screenshots ==

1. Easily manage slots right from with your WordPress admin. Shortcodes and
PHP template tags can also be easily found from the "Ad Slots" menu.
2. Specify your responsive sizing rules right within the WordPress admin. WP DFP
uses an intuitive approach to determining appropriate ad sizes by measuring the
ad container's width **NOT** the browser viewport.

== Changelog ==

= 1.1.6 =
* Update README
* Fix deployment script so screenshots are added to svn

= 1.1.5 =
* Fix slots are hidden until window is resized
* Fix js error if DFP is blocked by an ad blocker

= 1.1.4 =
* Don't show network code nag on settings page
* Fix bug where user is redirected to wrong URL after saving settings
* Fix bug where slot name is always auto-draft

= 1.1.3 =
* Fix incorrect URL that wp_dfp_settings_url() generates

= 1.1.2 =
* Fix bug when importing ad slots from an XML export file the slot name is blank

= 1.1.1 =
* Move settings menu item from under settings to under Ad Slots
* Better handling of slots that don't exist -- silently fail instead of throwing an exception
* Make HTML attributes for ad units/slots more helpful
* Ensure that HTML attributes are unique if a slot is being used more than once

= 1.1 =
* Add "slot path" to ad slot admin columns
* Add ability to clone ad slots
* Fix bug where out-of-page slots might generate a Javascript error

= 1.0.0 =
* Initial release
