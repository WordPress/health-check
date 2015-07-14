=== Health Check ===
Tags: health check
Contributors: westi, pento
Requires at least: 2.9.2
Tested up to: 4.3
Stable tag: 0.2.1

== Description ==

This plugin will perform a number of checks on your WordPress install to detect
common configuration errors and known issues.

For now it just checks the PHP and MySQL versions of your server are not too low
to meet the requirements that we have announced for WordPress 3.2

Once it has checked the versions it will feed back the results under the header of the Plugins page.

In future this plugin will also provide a whole suite of checks for other things which
may be affecting your install.

== Installation ==

1. Upload to your plugins folder, usually `wp-content/plugins/`
2. Activate the plugin on the plugin screen.
3. See if your server is prepared for WordPress

== Screenshots ==

1. This shows the plugin in action.  When you activate it you get a message at the top of the plugins page.

== Changelog ==

= v 0.3 =
* Added recommended PHP and MySQL versions
* Check for utf8mb4 support
* Fixed a bunch of PHP warnings

= v 0.2.1 =
* Fixed version comparision bug - When the server had the exact required versions we reported it as out of date.

= v 0.2 =
* Updated with actual PHP and MySQL version requirements for WordPress 3.2

= v 0.1 =
* Initial release with checks for the PHP and MySQL versions we will likely target for WordPress 3.2