=== Plugin Name ===
Contributors: judgej, hystericallyme
Donate link: 
Tags: woocommerce, shipping, packages
Requires at least: 3.8
Tested up to: 4.2.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Split up the items in your customer's cart to offer multiple shipping method selections for a single order

== Description ==

Take advantage of a new feature presented in WooCommerce 2.1 and split your cart into packages to offer your users multiple shipping selections. Packages can group products for shipping based on:

* shipping classes
* any product meta field
* product owner (vendor using many WC vendor plugins)
* on a per product basis.

Each package group will have its own shipping selection under the shipping section of your cart and checkout forms.

This plugin can limit which shipping methods are used for each package.
Using the provided table, match each shipping class to its applicable method, or leave it blank to include them all.
This feature works only for shipping class methods, but is planned to be extended to the meta field method.

This plugin is designed as a simplistic UI for users who want to ship their cart items in separate packages.
The actual functionality of multiple shipping options is provided through WooCommerce 2.1+ but it has no 
GUI out-of-the-box.

== Installation ==

<h4>Minimum Requirements</h4>

* WooCommerce 2.1 or greater
* WordPress 3.8 or greater
* PHP version 5.3 or greater

<h4>Installation through FTP</h4>

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

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets 
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png` 
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.2.0 =
* Issue #1: change order line shipping link fields to hidden fields.

= 1.1.1 =
* Refactor to use (eager-loading) singletons.
* Only load settings class whwn in admin area.

= 1.1.0 =
* PHP fixes
* Extend types of package grouping available.

= 1.0 =
* Initial Release

== Upgrade Notice ==
