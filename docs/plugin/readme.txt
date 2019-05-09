=== Health Check & Troubleshooting ===
Tags: health check
Contributors: wordpressdotorg, westi, pento, Clorith
Requires at least: 4.0
Requires PHP: 5.2
Tested up to: 5.2
Stable tag: 1.3.2
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Health Check identifies common problems, and helps you troubleshoot plugin and theme conflicts.

== Description ==

*The plugins menu position has changed, and can now be found under **Tools** > **Site Health**, where it replaces the Site Health feature included with WordPress 5.2*

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
3. A selection of tools that can be ran on your site.
4. Troubleshooting mode enabled, showing your website Dashboard

== Changelog ==

= v1.3.2 =
* Add polyfill for directory size calculations for sites running WordPress versions older than 5.2.0
* Fix link for the extended PHP information

= v1.3.1 =
* Include missing dependency for JavaScript files, first introduced in WordPress 5.2

= v1.3.0 =
* Plugin moved to the Tools section in the admin menu
* New UI/UX for the plugin pages
* New troubleshooting mode UI/UX
* Removed the backup reminder nag
* Improved security hardening
* Changed cookie names for improved hosting compatibility
* Improved accessibility
* Automatically check for critical issues once a week (adds a counter next to the menu item)
* Dates in the email tester now follow your site settings
