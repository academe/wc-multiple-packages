=== Plugin Name ===
Contributors: hystericallyme
Donate link: http://bolderelements.net/
Tags: woocommerce, shipping, packages
Requires at least: 3.8
Tested up to: 3.8.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Split up the items in your customer's cart to offer multiple shipping method selections for a single order

== Description ==

Take advantage of a new feature presented in WooCommerce 2.1 and split your cart into packages to offer your users 
multiple shipping selections. Packages can be broken down based on shipping classes or on a per product basis. Each
group will have its own shipping selection under the shipping section of your cart and checkout forms.

In addition, this plugin can limit which shipping methods are used for each package. Using the provided table, match
each shipping class to its applicable method, or leave it blank to include them all!

This plugin is designed as a simplistic UI for users who want to separate their cart in packages without the need
of a developer. The actual functionality of multiple shipping options is provided through WooCommerce 2.1

== Installation ==

<h4>Minimum Requirements</h4>

* WooCommerce 2.1 or greater
* WordPress 3.8 or greater
* PHP version 5.2.4 or greater
* MySQL version 5.0 or greater

<h4>Installation through FTP</h4>

1. Upload the entire `woocommerce-multiple-packaging` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. That's it! You will find a new tab under your WooCommerce > Settings page to setup the plugin

== Frequently Asked Questions ==

= What happened to the item details for each shipping method after an order is placed? =

This is a WooCommerce design choice. In the cart and checkout fields, each shipping option lists the products below that apply to the given rate.
After submitted, the receipt and order page will only list the options selected, and not which items should be shipped that way. If you would like
to request this feature, please contact WooCommerce support.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets 
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png` 
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
* Initial Release

== Upgrade Notice ==
