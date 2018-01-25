=== Health Check ===
Tags: health check
Contributors: westi, pento, Clorith
Requires at least: 3.8
Tested up to: 4.9
Stable tag: 0.9.0

== Description ==

This plugin will perform a number of checks on your WordPress install to detect common configuration errors and known issues.

It currently checks your PHP and MySQL versions, some extensions which are needed or may improve WordPress, and that the WordPress.org services are accessible to you.

The debug section, which allows you to gather information about your WordPress and server configuration that you may easily share with support representatives for themes, plugins or on the official WordPress.org support forums.

Troubleshooting allows you to have a vanilla WordPress session, where all plugins are disabled, and a default theme is used, but only for your user.

For a more extensive example of how to efficiently use the Health Check plugin, check out the [WordPress.org support team handbook page about this plugin](https://make.wordpress.org/support/handbook/appendix/troubleshooting-using-the-health-check/).

In the future we may introduce more checks, and welcome feedback both through the [WordPress.org forums](https://wordpress.org/support/plugin/health-check), and the [GitHub project page](https://github.com/WordPress/health-check).

== Installation ==

1. Upload to your plugins folder, usually `wp-content/plugins/`
2. Activate the plugin on the plugin screen.
3. Once activated the plugin will appear under your `Dashboard` menu.

== Screenshots ==

1. The health check screen after the automated tests have gone over the system.
2. The debug information, with the copy and paste field expanded.
3. The generic PHP information tab, when more detailed information is required.

== Changelog ==

= v 0.9.0 =
* Various string changes, typo fixes and translation enhancements.
* Added conditional hiding of the plugins list from the admin bar, if there's too many plugins it becomes a bad experience. (Hidden if there are more than 20 active plugins)
* Added ability to enable/disable plugins in Troubleshooting Mode from the plugins list.
* Added filter to remove actions from the plugin list in Troubleshooting Mode.
* Fixed notices on the plugin screen when plugin data may be inconsistent.
* Fixed jumping directly to troubleshooting mode for single file plugins placed directly in the plugin directory root.
* Fixed issue where troubleshooting a plugin directly made it impossible to disable it while in Troubleshooting Mode.
* Fixed so that the original language is returned when translating the debug data for copying.
* Fixed issue where the Debug screen would turn to half-English when using a non-English language.
* Fixed an issue where plugins could become truly disabled on a site when in Troubleshooting Mode.
* Fixed so that enabled/disabled plugins don't carry over between troubleshooting sessions.

= v 0.8.0 =
* Updated recommended PHP version to mirror WordPress.org.
* Updated texts for troubleshooting mode.
* Re-labeled database terms to be more user friendly.
* Added media information to the debug tab.
* Added individual `Troubleshoot` links for the list of active plugins.
* Added automatic copy to clipboard with supported browsers in the debug tab.

= v 0.7.0 =
* Troubleshooting mode now also switches to a default theme.
* Introduced a method for toggling default or active theme use when in troubleshooting mode.
* Introduced a method for enabling/disabling plugins while in troubleshooting mode.
* Introduced a method for disabling troubleshooting mode without needing to log out and back in again.

