=== Health Check & Troubleshooting ===
Tags: health check
Contributors: wordpressdotorg, westi, pento, Clorith
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 1.1.2
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This plugin will perform a number of checks on your WordPress install to detect common configuration errors and known issues.

It currently checks your PHP and MySQL versions, some extensions which are needed or may improve WordPress, and that the WordPress.org services are accessible to you.

The debug section, which allows you to gather information about your WordPress and server configuration that you may easily share with support representatives for themes, plugins or on the official WordPress.org support forums.

Troubleshooting allows you to have a vanilla WordPress session, where all plugins are disabled, and a default theme is used, but only for your user.

For a more extensive example of how to efficiently use the Health Check plugin, check out the [WordPress.org support team handbook page about this plugin](https://make.wordpress.org/support/handbook/appendix/troubleshooting-using-the-health-check/).

In the future we may introduce more checks, and welcome feedback both through the [WordPress.org forums](https://wordpress.org/support/plugin/health-check), and the [GitHub project page](https://github.com/WordPress/health-check).

== Frequently Asked Questions ==

= I am unable to access my site after enabling troubleshooting =

If you should find your self stuck in Troubleshooting Mode for any reason, you can easily disable it by clearing your cookies.

Are you unfamiliar with how to clear your cookies? No worries, you may also close all your browser windows, or perform a computer restart and it will clear this specific cookie automatically.

== Screenshots ==

1. The health check screen after the automated tests have gone over the system.
2. The debug information, with the copy and paste field expanded.
3. The generic PHP information tab, when more detailed information is required.

== Changelog ==

= v 1.1.2 =
* Fixed child themes not displaying properly in Troubleshooting Mode.
* Improved styling for the backup warning, shown when activating the plugin, so it doesn't block mobile users.
* Added explanations to the plugins screen if you enter Troubleshooting Mode there, so users know what is going on.
* Fixed admin menu overflowing if too many plugins exist.

= v 1.1.1 =
* Fixed a fatal error that would occur if a user had an older version of Troubleshooting Mode on their system.

= v 1.1.0 =
* Check for theme, plugin and WordPress updates when visiting the debug tab.
* Improved wording on some failure situations.
* Made the Debug Information tab a bit easier to read with fixed table styles.
* Redesigned tools page, with added accordion to avoid information overload, and different features mixing together.
* Mail test tool now allows you to include an optional customized message.
* Users can now change between any installed theme while in troubleshooting mode.
* Renamed the Must-Use plugin, making it align with what features present in the file.
* Improved the plugin cleanup process, when the plugin is deleted.
* Show full plugin names, and not slugs, in the troubleshooting admin bar menu.
* Check if the .htaccess file contains any rules not added by WordPress core in the debug section.
* Allow the disabling of Troubleshooting Mode from the same page as you previously enabled it from.
* Removed cURL checks from the automated test page, this was more confusion than help.
* Add installation size to the debug information.
