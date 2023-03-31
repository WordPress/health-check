=== Health Check & Troubleshooting ===
Tags: health check
Contributors: wordpressdotorg, westi, pento, Clorith
Requires at least: 4.4
Requires PHP: 5.6
Tested up to: 6.2
Stable tag: 1.6.0
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

= Where can I report security bugs? =

The Site Health team and WordPress community take security bugs seriously. We appreciate your efforts to responsibly disclose your findings, and will make every effort to acknowledge your contributions.

To report a security issue, please visit the [WordPress HackerOne](https://hackerone.com/wordpress) program.


== Screenshots ==

1. The health check screen after the automated tests have gone over the system.
2. The debug information, with the copy and paste field expanded.
3. A selection of tools that can be ran on your site.
4. Troubleshooting mode enabled, showing your website Dashboard

== Changelog ==

= v1.6.0 (2023-03-31) =
* Improved the visual aspects of the Troubleshooting Mode Widget.
* Improved security by hardening Troubleshooting Mode actions with security tokens (nonces).
* Added a new tool to check `.htaccess` rules (where applicable).
* Added TwentyTwenty Three to the list of default themes.
* Added option to install the latest classic (non Site Editor-focused) default theme if no default theme exists.
* Added a new security confirmation prompt in Troubleshooting Mode, if a security token (nonce) value is either expired, or missing.
* Added better documentation around reporting security concerns.
* Fixed a bug where notices from previous Troubleshooting sessions would show up in a new session, which is just confusing.

= v1.5.1 (2022-11-02) =
* Fixed a bug where if Health Check was disabled during troubleshooting, you would need to force-enable/disable other plugins or themes.

= v1.5.0 (2022-09-10) =
* Added a custom filter for the Health Check plugin PHP Compatibility check.
* Added functions which will try to disable cache solutions during troubleshooting.
* Added ability to force changes if loopbacks fail during troubleshooting.
* Changed how JavaScript is built and bundled in the plugin.
* Changed the location of the `phpinfo()` check to the Tools section.
* Changed how troubleshooting mode implements its conditional actions and filters when enabled.
* Fixed styling issues for troubleshooting mode in WordPress 5.9.
* Removed Site Health Status from the plugin, as they were implemented in WordPress 5.2.
