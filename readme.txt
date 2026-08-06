=== Health Check & Troubleshooting ===
Tags: health check
Contributors: wordpressdotorg, westi, pento, Clorith
Requires at least: 4.6
Requires PHP: 5.6
Tested up to: 6.6
Stable tag: 1.7.1
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Health Check identifies common problems, and helps you troubleshoot plugin and theme conflicts.

== Description ==

**This plugin has been deprecated, and its core features included in WordPress since version 5.2**

Troubleshooting Mode and Site Health Tools have been moved to their own canonical plugins:

- [Troubleshooting](https://wordpress.org/plugins/troubleshooting/)
- [Site Health Tools](https://wordpress.org/plugins/site-health-tools/)


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

= 1.7.1 (2024-07-25) =
* Security: Prevent a potential information disclosure from the screenshot beta feature, reported independently by Jarko Piironen.
* Security hardening: Make each screenshot delete nonce unique to that image.
* Security hardening: Add a capability check alongside the nonce validation when toggling beta features on or off.
* General: Updated the `Tested up to` tag.
* General: Added notice about future changes to the Troubleshooting and Tools sections.
* Added Twenty Twenty Four as a known and valid default theme.
* Tools: Fixed a PHP warning when checking for PHP version compatibility and no data was found.
* Tools: Fixed a PHP warning when `WP_DEBUG` is disabled, or nothing has been written to the logfile yet.
* Tools: Improved the description for the `robots.txt` file viewer.

= 1.7.0 (2023-08-06) =
* General: Improved styling inconsistency between the plugin and WordPress core.
* General: Fixed an issue with plugin translations where language strings would get mixed when using third party language plugins, or a separate profile language.
* Troubleshooting Mode: Fixed the URL used when disabling elements and having a subdirectory installation.
* Troubleshooting Mode: Fixed a deprecation warning when disabling troubleshooting mode on PHP version 8.3 or higher.
* Troubleshooting Mode: Added reference on how to troubleshoot as different users when testing scenarios.
* Tools: Fixed integration with WPTide for the PHP Compatibility checker.
* Tools: Added a viewer that will display debug log output when enabled.
* Tools: Added a warning to the File Integrity tester if unexpected files are mixed in with WordPress core files.
* Tools: Added a warning if sending emails is taking longer than expected.
* Tools: Added beta feature toggle for those who wish to test new functionality that may not be fully ready yet.
* CLI: Fixed the CLI commands, you can now `wp health-check status` to your hearts content!
* Beta feature: Added a new beta feature, making it easier for non-technical users to grab screenshots of issues on their site, and share them.
