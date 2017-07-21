=== Ajax Plugin Helper ===
Contributors: sivel
Donate Link: http://sivel.net/donate
Tags: ajax, plugin, plugins, activate, deactivate, upgrade, update, delete
Requires at least: 2.8
Tested up to: 2.9
Stable tag: 1.0.5

An Ajax Plugin Helper for the WordPress admin plugin page.  Adds Ajax activate, deactivate, delete and upgrade functionality.

== Description ==

= This plugin is no longer supported.  If you would like to take over development of this plugin, please contact the developer. =

An Ajax Plugin Helper for the WordPress admin plugin page.  Adds Ajax activate, deactivate, delete and upgrade functionality.

Allows a user to activate, deactive, delete and upgrade plugins from the admin plugins page without leaving the plugins page. Also provides an Ajax Upgrade All button for upgrading all plugins requiring upgrades using Ajax.

This plugin was designed to meet the needs of bulk plugin upgrades and a more efficient upgrade process to decrease the amount of time required to update the plugins for your blog.

= Translations: =

* Finnish by Christian Hellbeg

== Installation ==

1. Use the WordPress Add Plugins page or...
1. Upload the `ajax-plugin-helper` folder to the `/wp-content/plugins/` directory or install directly through the plugin installer.
1. Activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer

== Frequently Asked Questions ==

= Why isn't any of this plugins functionality working? =

You likely have Javascript disabled or you are using a browser that doesn't support Javascript.  This plugin requires Javascript to function.

= Why do I not have Ajax Delete or Ajax Upgrade links? =

In order for these links to show up WordPress has to determine if PHP can perform the necessary file system level tasks without prompting for FTP or SSH/SFTP login information. See [Editing wp-config.php](http://codex.wordpress.org/Editing_wp-config.php#FTP.2FSSH_Constants) and [Using SSH to Install/Upgrade](http://www.firesidemedia.net/dev/wordpress-install-upgrade-ssh/) for information on configuring WordPress to not prompt you for this information.

= Why can't I use the Ajax functions on the Ajax Plugin Helper itself? =

Using the Ajax Plugin Helper to perform the functions provided by the plugin would cause several things to break since it would become deactivated before it could perform all of its required functions.  Because of this you will not have Ajax links added to the Ajax Plugin Helper plugin.

= Does this plugin work with WordPress Mu? =

Yes.  Just place it in `wp-content/plugins`.  Please note that it does not yet add an Ajax Activate Site Wide link yet.  The Activate Site Wide links will just operate just as it did before.

== Screenshots ==

1. Plugin Page

== Upgrade ==

1. Use the plugin updater in WordPress or...
1. Deactivate the `Ajax Plugin Helper` plugin
1. Delete the previous `ajax-plugin-helper` folder from the `/wp-content/plugins/` directory
1. Upload the new `ajax-plugin-helper` folder to the `/wp-content/plugins/` directory
1. Activate the `Ajax Plugin Helper` plugin

== Usage ==

1. Click the Ajax Activate, Ajax Deactivate, Ajax Upgrade, Ajax Delete or Ajax Upgrade All to perform these tasks without leaving the plugins page.

== To Do ==

1. Add Ajax Activate Site Wide links for WordPress Mu

== Changelog ==

= 1.0.5 (2009-09-13): =
* Fix bug where edit link for active plugins would always reference the first active plugin in the plugin list

= 1.0.4 (2009-09-12): =
* Add Finnish translation by Christian Hellbeg

= 1.0.3 (2009-09-12): =
* Make sure in WP 2.8 that we do not show an Ajax Upgrade link for the Ajax Plugin Helper itself in update messages

= 1.0.2 (2009-09-12): =
* WordPress 2.9 updates to remove duplicate update message

= 1.0.1 (2009-09-08): =
* Localization fixes

= 1.0 (2009-07-23): =
* Initial Public Release
