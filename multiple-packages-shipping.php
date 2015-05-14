<?php
/*
Plugin Name: Multiple Packages for WooCommerce
Plugin URI: http://www.bolderelements.net/multiple-packages-woocommerce/
Description: A simple UI to take advatage of multiple shipping packages without PHP knowledge
Author: Erica Dion
Author URI: http://www.bolderelements.net/
Version: 1.1.1

Copyright: © 2014 Bolder Elements (email : erica@bolderelements.net)
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

add_action('plugins_loaded', 'woocommerce_multiple_packaging_init', 106);

function woocommerce_multiple_packaging_init()
{
    /**
     * Check if WooCommerce is active
     */
    if (class_exists( 'woocommerce' ) || class_exists( 'WooCommerce' )) {
        if (!class_exists('BE_Multiple_Packages')) {
            // Include the class and create a singleton.
            // FIXME: we only really want to create the singleton when we need it,
            // so the filter and action that hook into the multiple packages
            // class should use an intermediate class that creates the singleton
            // on first use.

            include_once(dirname(__FILE__) . '/BE_Multiple_Packages.php');
            //$be_multiple_packages = BE_Multiple_Packages::get_instance();

            add_filter(
                'woocommerce_cart_shipping_packages',
                array(BE_Multiple_Packages::get_instance(), 'generate_packages')
            );

            // Allows plugins to add order item meta to shipping
            add_action(
                'woocommerce_add_shipping_order_item',
                array(BE_Multiple_Packages::get_instance(), 'link_shipping_line_item'),
                10, 3
            );
        } // end IF class 'BE_Multiple_Packages' exists

        // The settings are needed only when in the admin area.
        if (is_admin()) {
            // Include the settings and create a new instance.
            require_once(dirname(__FILE__) . '/class-settings.php');
            BE_Multiple_Packages_Settings::get_instance();
        }
    } // woocommerce exists

    add_filter(
        'plugin_action_links_' . plugin_basename( __FILE__ ),
        'be_multiple_packages_plugin_action_links'
    );

    function be_multiple_packages_plugin_action_links( $links ) {
        return array_merge(
            array(
                'settings' => '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=wc-settings&tab=multiple_packages">Settings</a>',
                'support' => '<a href="http://bolderelements.net/" target="_blank">Bolder Elements</a>'
            ),
            $links
        );
    }

} // end function: woocommerce_multiple_packaging_init
