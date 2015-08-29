<?php
/*
Plugin Name: Packages Configuration for WooCommerce
Plugin URI: https://github.com/academe/wc-multiple-packages
Description: Configure how products are grouped into shipping packages for WooCommerce.
Author: Jason Judge jason@academe.co.uk
Original Plugin Author: Erica Dion erica@bolderelements.net (http://www.bolderelements.net/)
Author URI: https://github.com/judgej
Version: 1.2.3

Copyright: © 2014 Bolder Elements, © 2015 Academe Computing
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Check if WooCommerce is active.
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    if (!class_exists('Academe_Multiple_Packages')) {
        // Include the main class.
        // We will keep classes each defined in their own files.
        include_once(dirname(__FILE__) . '/classes/Academe_Multiple_Packages.php');
    }

    // Add the filter to generate the packages.
    // At the moment, this plugin will discard any packages already created and then
    // generate its own from scratch. A future enhancement would see this plugin
    // taking the existing packages, and perhaps splitting them down further if
    // necessary, then adding the linking meta fields to the result.
    add_filter(
        'woocommerce_cart_shipping_packages',
        array(Academe_Multiple_Packages::get_instance(), 'generate_packages')
    );

    // This action allows plugins to add order item meta to shipping.
    add_action(
        'woocommerce_add_shipping_order_item',
        array(Academe_Multiple_Packages::get_instance(), 'link_shipping_line_item'),
        10, 3
    );

    // Add shipping line meta fields to the shipping line in the order API.
    add_filter(
        'woocommerce_api_order_response',
        array(Academe_Multiple_Packages::get_instance(), 'api_show_shipping_line_meta'),
        10, 4
    );

    /**
     * The settings are needed only when in the admin area.
     */
    if (is_admin()) {
        /**
         * Define the shipping method.
         */
        function academe_wc_multiple_packages_init()
        {
            if (!class_exists('Academe_Multiple_Packages_Settings')) {
                // Include the settings and create a new instance.
                require_once(dirname(__FILE__) . '/classes/Academe_Multiple_Packages_Settings.php');
            }
        }
        add_action('woocommerce_shipping_init', 'academe_wc_multiple_packages_init');

        /**
         * Add the shipping method to the WC list of methods.
         * It is not strictly a shipping method itself, but a tool for grouping other
         * shipping methods.
         */
        function academe_add_wc_multiple_packages($methods)
        {
            $methods[] = 'Academe_Multiple_Packages_Settings';
            return $methods;
        }
        add_filter('woocommerce_shipping_methods', 'academe_add_wc_multiple_packages');
    }
}
