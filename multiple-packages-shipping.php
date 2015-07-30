<?php
/*
Plugin Name: WooCommerce Multiple Packages Configuration
Plugin URI: https://github.com/academe/wc-multiple-packages
Description: Configure product grouping for shipping packages.
Author: Jason Judge jason@academe.co.uk
Author: Erica Dion erica@bolderelements.net
Author URI: https://github.com/judgej
Author URI: http://www.bolderelements.net/
Version: 1.2.0

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

    add_filter(
        'woocommerce_cart_shipping_packages',
        array(Academe_Multiple_Packages::get_instance(), 'generate_packages')
    );

    // Allows plugins to add order item meta to shipping
    add_action(
        'woocommerce_add_shipping_order_item',
        array(Academe_Multiple_Packages::get_instance(), 'link_shipping_line_item'),
        10, 3
    );

    /**
     * Check if WooCommerce is active.
     * The settings are needed only when in the admin area.
     */
    if (is_admin()) {
        /**
         * Define the shipping method.
         */
        function woocommerce_multiple_packaging_init()
        {
            if (!class_exists('Academe_Multiple_Packages_Settings')) {
                // Include the settings and create a new instance.
                require_once(dirname(__FILE__) . '/classes/Academe_Multiple_Packages_Settings.php');
            }

            //Academe_Multiple_Packages_Settings::get_instance();
        }
        add_action('woocommerce_shipping_init', 'woocommerce_multiple_packaging_init');

        /**
         * Add the shipping method to the WC list of methods.
         */
        function add_woocommerce_multiple_packaging($methods)
        {
            $methods[] = 'Academe_Multiple_Packages_Settings';
            return $methods;
        }
        add_filter('woocommerce_shipping_methods', 'add_woocommerce_multiple_packaging');
    }
}
