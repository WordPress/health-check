=== Health Check & Troubleshooting ===
Tags: health check
Contributors: wordpressdotorg, westi, pento, Clorith
Requires at least: 4.0
Requires PHP: 5.2
Tested up to: 5.2
Stable tag: 1.4.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Health Check identifies common problems, and helps you troubleshoot plugin and theme conflicts.

== Description ==

This plugin will perform a number of checks on your WordPress installation to detect common configuration errors and known issues, and also allows plugins and themes to add their own checks.

The debug section, which allows you to gather information about your WordPress and server configuration that you may easily share with support representatives for themes, plugins or on the official WordPress.org support forums.

Troubleshooting allows you to have a clean WordPress session, where all plugins are disabled, and a default theme is used, but only for your user until you disable it or log out.

The Tools section allows you to check that WordPress files have not been tampered with, that emails can be sent, and if your plugins are compatible with any PHP version updates in the future.

For a more extensive example of how to efficiently use the Health Check plugin, check out the [WordPress.org support team handbook page about this plugin](https://make.wordpress.org/support/handbook/appendix/troubleshooting-using-the-health-check/).

Feedback is welcome both through the [WordPress.org forums](https://wordpress.org/support/plugin/health-check), the [GitHub project page](https://github.com/WordPress/health-check), or on [Slack](https://make.wordpress.org/chat) in either [#forums](https://wordpress.slack.com/messages/forums/) or [#core-site-health](https://wordpress.slack.com/messages/core-site-health/).

== Frequently Asked Questions ==

= I am unable to access my site after enabling troubleshooting =

If you should find your self stuck in Troubleshooting Mode for any reason, you can easily disable it by clearing your cookies.

Are you unfamiliar with how to clear your cookies? No worries, you may also close all your browser windows, or perform a computer restart and it will clear this specific cookie automatically.

= The PHP compatibility says this plugin only work with PHP version X? =

The plugin is made to be a support tool for as many users as possible, this means it needs code that is written for older sites as well.

Tools that check for PHP compatibility do not know how to separate this code from the real code, so it will give a false positive response.

At this time, the plugin has been tested with every version of PHP from 5.2 through 7.3, and works with all of these.

== Screenshots ==

1. The health check screen after the automated tests have gone over the system.
2. The debug information, with the copy and paste field expanded.
3. A selection of tools that can be ran on your site.
4. Troubleshooting mode enabled, showing your website Dashboard

== Changelog ==

= v1.4.0 =
* Fix a bug when viewing the Site Health page if enabling the Health Check plugin in troubleshooting mode.
* Fix an inconsistency with how database versions are checked.
* Fix the file comparison view on Windows systems if there are modified core files.
* Fix a bug where some premium plugins could not be enabled in troubleshooting mode
* Improved styles for older browsers.
* Improved the PHP module checks to allow for constant checks as well. Should help with some edge case tests.
* Improved the core file integrity checker.
* Improved testing of WP_cron, now works properly for those running a "real cron" outside of WordPress.
* Improved the htaccess rule test to only run if using an Apache server that supports these.
* Modify the Site Health grading indicator.
* Modified strings to make them clearer.
* Added server headers to the Debug information.
* Added polyfills for core features from WordPress 5.2 so they work for older sites.
* Added a link to the Site Health page from the plugin overview.
* Added a custom capability, `view_site_health_checks` for the plugin.
* Added support for parent/child theme output in the Debug screen.
* Added system user information to the Debug information.
* Added a Site Health test for timezone localization.
* Added `mbstring` and `json` (again) as requirements to the list of PHP extensions.
* Added a missing toggle to the list of plugins/themes to the troubleshooting dashboard widget.
* Added bulk actions to enable or disable plugins when troubleshooting, or to initiate troubleshooting mode.
* Added plugin compatibility checker ot the tools section.
* Added a dashboard widget to show your Site Health status at a glance when logging in.
* Added filters for Site Health test results.
* Added WP-CLI support, you can now run `wp health-check status` for a list of test and their status.
* Moved compatibility functions out of primary files and into a `compat.php` so they can be conditionally loaded.
* Disable the Fatal Error (WSOD) protection in WordPress while in troubleshooting mode.