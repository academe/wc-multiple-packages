<?php
/*
Plugin Name: Multiple Packages for WooCommerce
Plugin URI: http://www.bolderelements.net/multiple-packages-woocommerce/
Description: A simple UI to take advatage of multiple shipping packages without PHP knowledge
Author: Erica Dion
Author URI: http://www.bolderelements.net/
Version: 1.0

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
				 * Get Settings for Restrictions Table
				 *
				 * @access public
				 * @return void
				 */
				function generate_packages( $packages ) {
					if( get_option( 'multi_packages_enabled' ) ) {
						// Reset the packages
	    				$packages = array();
	    				$settings_class = new BE_Multiple_Packages_Settings();
	    				$package_restrictions = $settings_class->package_restrictions;
	    				$free_classes = get_option( 'multi_packages_free_shipping' );

	    				// Determine Type of Grouping
	    				if( get_option( 'multi_packages_type' ) == 'per-product' ) :
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
						    
	    				else :
	    					// Create arrays for each shipping class
							$shipping_classes = $other = array();
							$get_classes = WC()->shipping->get_shipping_classes();
							foreach ( $get_classes as $key => $class ) {
								$shipping_classes[ $class->term_id ] = $class->slug;
								$array_name = $class->slug;
								$$array_name = array();
							}
							$shipping_classes['misc'] = 'other';

							// Sort bulky from regular
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
							        $packages[ $n ] = array(
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
							    	if( $free_classes && in_array( $key, $free_classes ) ) {
							    		$packages[ $n ]['ship_via'] = array('free_shipping');
							    	} elseif( count( $package_restrictions ) && isset( $package_restrictions[ $key ] ) ) {
							        	$packages[ $n ]['ship_via'] = $package_restrictions[ $key ];
							    	}
							    	$n++;
							    }
							}
    
	    				endif;

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
