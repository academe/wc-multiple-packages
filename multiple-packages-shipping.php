<?php
/*
Plugin Name: Multiple Packages for WooCommerce
Plugin URI: http://www.bolderelements.net/multiple-packages-woocommerce/
Description: A simple UI to take advatage of multiple shipping packages without PHP knowledge
Author: Erica Dion
Author URI: http://www.bolderelements.net/
Version: 1.1.0

Copyright: © 2014 Bolder Elements (email : erica@bolderelements.net)
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

add_action('plugins_loaded', 'woocommerce_multiple_packaging_init', 106);

function woocommerce_multiple_packaging_init() {

    /**
     * Check if WooCommerce is active
     */
    if ( class_exists( 'woocommerce' ) || class_exists( 'WooCommerce' ) ) {
        
        if ( !class_exists( 'BE_Multiple_Packages' ) ) {

            // Include Necessary files
            require_once('class-settings.php');

            add_filter( 'woocommerce_cart_shipping_packages', array( 'BE_Multiple_Packages', 'generate_packages' ) );

            // Allows plugins to add order item meta to shipping
            add_action( 'woocommerce_add_shipping_order_item', array( 'BE_Multiple_Packages', 'link_shipping_line_item' ), 10, 3 );

            class BE_Multiple_Packages {

                /**
                 * Constructor.
                 */
                public function __construct() {
                    $settings_class = new BE_Multiple_Packages_Settings();
                    $settings_class->get_package_restrictions();
                    $this->package_restrictions = $settings_class->package_restrictions;
                }

                /**
                 * For each package, find the order lines that contain the products that
                 * are included in that package, and link those order lines to this
                 * shipping line.
                 * The assumption is made that however the packages are grouped, each product
                 * can only be put into one package (so no grouping by product variation, for
                 * example).
                 *
                 * @order_id int The Order ID in wp_posts
                 * @shipping_line_id int The ID of the shipping order line that has just been added.
                 * @package_key int The index number of the package in the WC()->shipping->get_packages() list
                 */
                public static function link_shipping_line_item ( $order_id, $shipping_line_id, $package_key ) {
                    $packages = WC()->shipping->get_packages();

                    if ( !isset($packages[$package_key]) ) {
                        return;
                    }

                    $package = $packages[$package_key];

                    $product_ids = array();
                    foreach($package['contents'] as $product) {
                        if ( !in_array($product['product_id'], $product_ids ) ) {
                            $product_ids[] = $product['product_id'];
                        }
                    }

                    // Add the product_ids to the shipping order line, for reference.
                    wc_add_order_item_meta( $shipping_line_id, 'product_ids', $product_ids, true );

                    // Go through the order lines and find those that contain these product.
                    $order_line_ids = array();
                    $order = new WC_Order( $order_id );
                    foreach ( $order->get_items() as $order_line_id => $product ) {
                        if ( isset($product['product_id']) && in_array($product['product_id'], $product_ids) ) {
                            $order_line_ids[] = $order_line_id;

                            // Link this product order line to its shipping order line.
                            wc_add_order_item_meta( $order_line_id, 'shipping_line_id', $shipping_line_id, true );
                        }
                    }

                    // Throw the order line IDs onto the shipping line too, for a two-way
                    // link.
                    wc_add_order_item_meta( $shipping_line_id, 'order_line_ids', $order_line_ids, true );
                }

                /**
                 * Get Settings for Restrictions Table
                 *
                 * @access public
                 * @return void
                 */
                public static function generate_packages( $packages ) {
                    if( get_option( 'multi_packages_enabled' ) ) {
                        // Reset the packages
                        $packages = array();

                        $settings_class = new BE_Multiple_Packages_Settings();
                        $package_restrictions = $settings_class->package_restrictions;
                        $free_classes = get_option( 'multi_packages_free_shipping' );

                        // Determine Type of Grouping
                        if( get_option( 'multi_packages_type' ) == 'per-product' ) {
                            // separate each item into a package
                            $n = 0;
                            foreach ( WC()->cart->get_cart() as $item ) {
                                if ( $item['data']->needs_shipping() ) {
                                    // Put inside packages
                                    $packages[ $n ] = array(
                                        'contents' => array($item),
                                        'contents_cost' => array_sum( wp_list_pluck( array($item), 'line_total' ) ),
                                        'applied_coupons' => WC()->cart->applied_coupons,
                                        'destination' => array(
                                            'country' => WC()->customer->get_shipping_country(),
                                            'state' => WC()->customer->get_shipping_state(),
                                            'postcode' => WC()->customer->get_shipping_postcode(),
                                            'city' => WC()->customer->get_shipping_city(),
                                            'address' => WC()->customer->get_shipping_address(),
                                            'address_2' => WC()->customer->get_shipping_address_2()
                                        )
                                    );

                                    // Determine if 'ship_via' applies
                                    $key = $item['data']->get_shipping_class_id();
                                    if( $free_classes && in_array( $key, $free_classes ) ) {
                                        $packages[ $n ]['ship_via'] = array('free_shipping');
                                    } elseif( count( $package_restrictions ) && isset( $package_restrictions[ $key ] ) ) {
                                        $packages[ $n ]['ship_via'] = $package_restrictions[ $key ];
                                    }
                                    $n++;
                                }
                            }

                        } else {
                            // FIXME: move these $$ variables into arrays to help debugging.
                            // Create arrays for each shipping class
                            $shipping_classes = array();
                            $other = array();

                            $get_classes = WC()->shipping->get_shipping_classes();
                            foreach ( $get_classes as $key => $class ) {
                                $shipping_classes[ $class->term_id ] = $class->slug;
                                $array_name = $class->slug;
                                $$array_name = array();
                            }
                            $shipping_classes['misc'] = 'other';

                            // Group packages by shipping class (e.g. sort bulky from regular)
                            foreach ( WC()->cart->get_cart() as $item ) {
                                if ( $item['data']->needs_shipping() ) {
                                    $item_class = $item['data']->get_shipping_class();
                                    if( isset( $item_class ) && $item_class != '' ) {
                                        foreach ($shipping_classes as $class_id => $class_slug) {
                                            if ( $item_class == $class_slug ) {
                                                array_push( $$class_slug, $item );
                                            }
                                        }
                                    } else {
                                        $other[] = $item;
                                    }
                                }
                            }

                            // Put inside packages
                            $n = 0;
                            foreach ($shipping_classes as $key => $value) {
                                if ( count( $$value ) ) {
                                    // Custom elements can be added to this array and be displayed
                                    // in template cart/cart-shipping.php for additional information
                                    // or context.

                                    $packages[ $n ] = array(
                                        // contents is the array of products in the group.
                                        'contents' => $$value,
                                        'contents_cost' => array_sum( wp_list_pluck( $$value, 'line_total' ) ),
                                        'applied_coupons' => WC()->cart->applied_coupons,
                                        'destination' => array(
                                            'country' => WC()->customer->get_shipping_country(),
                                            'state' => WC()->customer->get_shipping_state(),
                                            'postcode' => WC()->customer->get_shipping_postcode(),
                                            'city' => WC()->customer->get_shipping_city(),
                                            'address' => WC()->customer->get_shipping_address(),
                                            'address_2' => WC()->customer->get_shipping_address_2()
                                        )
                                    );

                                    // Determine if 'ship_via' applies
                                    if ( $free_classes && in_array( $key, $free_classes ) ) {
                                        $packages[ $n ]['ship_via'] = array('free_shipping');
                                    } elseif ( count( $package_restrictions ) && isset( $package_restrictions[ $key ] ) ) {
                                        $packages[ $n ]['ship_via'] = $package_restrictions[ $key ];
                                    }
                                    $n++;
                                }
                            }
                        }

                        return $packages;
                    }
                }

            } // end class BE_Multiple_Packages

        } // end IF class 'BE_Multiple_Packages' exists

    } // end IF woocommerce exists

    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'be_multiple_packages_plugin_action_links' );

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
