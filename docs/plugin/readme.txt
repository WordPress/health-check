=== Health Check & Troubleshooting ===
Tags: health check
Contributors: wordpressdotorg, westi, pento, Clorith
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 1.2.1
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

= v 1.2.1 =
* Make sure only those with access to the plugin see the backup encouragement notice.
* Make sure the `is_troubleshooting()` checks are available to the Site Status tester when the MU plugin may not have updated yet.
* Avoid a warning of an undefined variable if you have the latest WordPress version installed.

= v 1.2.0 =
* Changed plugin name, it now better describes the plugins two primary purposes.
* Changed the `Health Check` tab, it's now named `Site Status`, as we used the old name too many places and it was confusing.
* Site status tests now run asynchronously, making the page load much faster.
* The HTTPS tests now also check your Site URL settings to make sure they are following recommended best practices.
* Fixed a warning preventing plugin names from displaying on the front-end in some cases.
* Fixed an issue where you might get a 500 error if you tried using Troubleshooting Mode while using a child theme.
* Automatically disable/enable a plugin or theme in Troubleshooting Mode if they are detected to cause errors.
* Introduce a new dashboard widget during Troubleshooting Mode (and a simplified version on the plugins screen) to better explain what is going on, and make available actions more discoverable than the admin menu is.
* Some text improvements throughout the plugin.
* When loopback tests fail, we previously tested all plugins at once, for sites that have many plugins this may fail as the request times out. We now test one plugin at a time to avoid this, while also showing more information at the tests are running to the end user.
