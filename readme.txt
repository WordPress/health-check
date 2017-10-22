=== Health Check ===
Tags: health check
Contributors: westi, pento, Clorith
Requires at least: 3.8
Tested up to: 4.8
Stable tag: 0.4.1

== Description ==

This plugin will perform a number of checks on your WordPress install to detect common configuration errors and known issues.

It currently checks your PHP and MySQL versions, some extensions which are needed or may improve WordPress, and that the WordPress.org services are accessible to you.

There is also a debug section, which allows you to gather information about your WordPress and server configuration that you may easily share with support personell for themes, plugins or on the official WordPress.org support forums.

In the future we may introduce more checks, and welcome feedback both through the [WordPress.org forums](https://wordpress.org/support/plugin/health-check), and the [GitHub project page](https://github.com/WordPress/health-check).

== Installation ==

1. Upload to your plugins folder, usually `wp-content/plugins/`
2. Activate the plugin on the plugin screen.
3. Once activated the plugin will appear under your `Dashboard` menu.

== Screenshots ==

1. This shows the plugin in action.  When you activate it you get a message at the top of the plugins page.

== Changelog ==

= v 0.4.1 =
* Better SQL version detection to account for MariaDB versions
* Added translation support
* Removed unintentional indentations in the debug copying field

= v 0.4 =
* Added debug section
* Added PHP info section
* Cleaned up the health check
* Added WordPress.org connectivity check
* Added HTTPS check
