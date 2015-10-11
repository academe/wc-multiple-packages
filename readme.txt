=== Plugin Name ===
Contributors: judgej
Donate link: 
Tags: woocommerce, shipping, packages
Requires at least: 3.8
Tested up to: 4.3.0
Stable tag: 1.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Split up the items in your customer's cart to offer multiple shipping method selections for a single order

== Description ==

Take advantage of a new feature presented in WooCommerce 2.1 and split your cart into packages to offer your users multiple shipping selections. Packages can group products for shipping based on:

* shipping classes
* any product meta field
* product owner (vendor using many WC vendor plugins)
* on a per product basis.

Each package will have its own shipping selection under the shipping section of your cart and checkout forms,
to the customer can choose a different shipping method for each.

This plugin can limit which shipping methods are used for each package.
Using the provided table, match each shipping class to its applicable method, or leave it blank to include them all.

This plugin is designed as a simplistic UI for users who want to ship their cart items in separate packages.
The actual functionality of multiple shipping options is provided through WooCommerce 2.1+ but it has no 
GUI out-of-the-box.

WooCommerce does nto handle the progress of shipping packages beyond the selection of shipping methods in the
checkout. This plugin does link the order shipping lines and product lines together usign metadata however,
so custom plugins can make use of those links.

The project is maintained on github, and issues are tracked there:

<https://github.com/academe/wc-multiple-packages>

The plugin can be downloaded from wordpress.org here:

<https://wordpress.org/plugins/packages-configuration-for-woocommerce/>

Banner Photo: "Container" by Izabela Reimers via Flickr Creative Commons

== Installation ==

= Minimum Requirements =

* WooCommerce 2.1 or greater
* WordPress 3.8 or greater
* PHP version 5.3 or greater

= Installation through FTP =

1. Upload the entire `wc-multiple-packages` folder to the `wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. That's it! You will find a new tab under your WooCommerce > Settings page to set up the plugin

== Frequently Asked Questions ==

= What happened to the item details for each shipping method after an order is placed? =

By default, WooCommerce does not keep track of which shipped item was sent through which shipping method.
This plugin does keep track. It will create a custom meta field "_shipping_line_id" on each item line,
containing the numeric ID of the shipping line that it corresponds to.

It will also build a custom meta field "_order_line_ids" on each shipping line, pointing to the IDs of the
item lines that it shipped. At the moment this a bar-separated list (e.g. 1|2|3) but may change to an array.

The WooCommerce V2 and V3 REST API will expose the meta fields for the item lines, but does not expose any
meta fields for the shipping lines.

== Screenshots ==

TBC

== Changelog ==

= 1.2.3 =
* Issue #9 Checkout packages not disabled when the shipping method was disabled.
* Issue #11 Refactoring of the shipping class and individual product rules; functionally remains the same.
* Issue #12 Additional package metadata to help with theming; docs and examples to come later.

* hystericallyme removed from contributers as it appears to be an official collaboration, which wasn't the intention.
= 1.2.2 =

* hystericallyme removed from contributers as it appears to be an official collaboration, which wasn't the intention.
* Issue #10 fix - thanks to https://github.com/GoTeamScotch

= 1.2.1 =
* Change of name for consistency with wordpress.org slug.
* Added link to github project page.
* Added experimental composer.json file.
* Fixed "enabled" flag on shipping settings overview page.

= 1.2.0 =
* Issue #1: change order line shipping link fields to hidden fields.

= 1.1.1 =
* Refactor to use (eager-loading) singletons.
* Only load settings class when in admin area.

= 1.1.0 =
* PHP fixes
* Extend types of package grouping available.

= 1.0 =
* Initial Release

== Upgrade Notice ==

When moving from 1.2.0 to 1.2.1 you may need to resave the settings for this plugin.
Some of the stored settings have changed their name, and so those settings will need
to be reentered. Appologies for the patch-only version upgrade on this.

