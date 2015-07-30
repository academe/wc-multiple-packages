<?php

class Academe_Multiple_Packages
{
    // Singleton instance.
    private static $instance;

    // The shipping method ID.
    protected $id = 'academe_multiple_packages';

    // The current packages list.

    protected $packages = array();

    // Shipping plugin enabled?
    protected $enabled;

    protected $multi_packages_free_shipping;
    protected $multi_packages_type;
    protected $multi_packages_meta_field;
    protected $shipping_restrictions_classes = array();

    // Meta field names used to link shipping IDs and item IDs.
    const SHIPPING_LINE_ID_FIELD = '_shipping_line_id';
    const ORDER_LINE_IDS_FIELD = '_order_line_ids';

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Get the settings from the plugin.
        // CHEKME: there may be an API for this.
        $settings = get_option(
            'woocommerce_' . $this->id . '_settings',
            array()
        );

        // Extract some of the settings we will need.
        $this->enabled = !empty($settings['multi_packages_enabled']);

        $this->multi_packages_free_shipping =
            isset($settings['multi_packages_free_shipping'])
            ? $settings['multi_packages_free_shipping']
            : '';

        $this->multi_packages_type =
            isset($settings['multi_packages_type'])
            ? $settings['multi_packages_type']
            : '';

        $this->multi_packages_meta_field =
            isset($settings['multi_packages_meta_field'])
            ? $settings['multi_packages_meta_field']
            : '';

        $this->shipping_restrictions_classes =
            isset($settings['shipping_restrictions_classes'])
            ? $settings['shipping_restrictions_classes']
            : array();
    }

    // Create a new singleton instance.
    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
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
    public static function link_shipping_line_item($order_id, $shipping_line_id, $package_key)
    {
        $packages = WC()->shipping->get_packages();

        if (!isset($packages[$package_key])) {
            return;
        }

        $package = $packages[$package_key];

        $product_ids = array();
        foreach($package['contents'] as $product) {
            if (!in_array($product['product_id'], $product_ids)) {
                $product_ids[] = $product['product_id'];
            }
        }

        // Add the product_ids to the shipping order line, for reference.
        wc_add_order_item_meta($shipping_line_id, 'product_ids', $product_ids, true);

        // Go through the order lines and find those that contain these product.
        $order_line_ids = array();
        $order = new WC_Order($order_id);
        foreach ($order->get_items() as $order_line_id => $product) {
            if (isset($product['product_id']) && in_array($product['product_id'], $product_ids)) {
                $order_line_ids[] = $order_line_id;

                // Link this product order line to its shipping order line.
                wc_add_order_item_meta($order_line_id, static::SHIPPING_LINE_ID_FIELD, $shipping_line_id, true);
            }
        }

        // Throw the order line IDs onto the shipping line too, for a two-way
        // link.
        // Convert it to a string before saving it, as arrays do not get exposed to the REST API.
        wc_add_order_item_meta($shipping_line_id, static::ORDER_LINE_IDS_FIELD, implode('|', $order_line_ids), true);
    }

    /**
     * Get Settings for Restrictions Table
     *
     * @access public
     * @return void
     */
    public function generate_packages($packages)
    {
        if ($this->enabled) {
            // Reset the packages.
            // CHECKME: do we really want to reset the packages, or can
            // we just go over the packages as they are, and maybe split
            // them out further if necessary?

            $package_restrictions = $this->shipping_restrictions_classes;

            $free_classes = $this->multi_packages_free_shipping;

            //
            $product_meta_prefix = 'product-meta';

            // Determine Type of Grouping
            $multi_packages_type = $this->multi_packages_type;
            if ($multi_packages_type == 'per-product') {
                // separate each item into a package
                $n = 0;
                foreach ( WC()->cart->get_cart() as $item ) {
                    if ( $item['data']->needs_shipping() ) {
                        // Put inside packages
                        $this->packages[$n] = array(
                            'contents' => array($item),
                            'contents_cost' => array_sum(wp_list_pluck(array($item), 'line_total')),
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
                            $this->packages[$n]['ship_via'] = array('free_shipping');
                        } elseif (count($package_restrictions) && isset($package_restrictions[$key])) {
                            $this->packages[$n]['ship_via'] = $package_restrictions[$key];
                        }
                        $n++;
                    }
                }
            } elseif ($multi_packages_type == 'shipping-class') {
                // FIXME: move these $$ variables into arrays to help debugging.
                // This section seems to be more complicated than it needs to be,
                // just because it tries to keep the index of the packages numeric
                // right the way through, rather than just make them numeric at the
                // end. It could be simplified a lot.
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

                        $this->packages[$n] = array(
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
                            $this->packages[$n]['ship_via'] = array('free_shipping');
                        } elseif (count($package_restrictions) && isset($package_restrictions[$key])) {
                            $this->packages[$n]['ship_via'] = $package_restrictions[$key];
                        }
                        $n++;
                    }
                }
            } elseif (substr($multi_packages_type, 0, strlen($product_meta_prefix)) == $product_meta_prefix) {
                // Get the metafield name.
                // It can come from the setting in th eplugin admin page, or
                // from the package name field, as a suffix.
                //
                // We hope it is lower-case and with underscores, as that is
                // what most packages seem to use, but the WP documentation is
                // totally silent on the key format, and in reality anyth string
                // is accepted.

                if ($multi_packages_type == $product_meta_prefix) {
                    // A custom meta field key.
                    $meta_field_name = $this->multi_packages_meta_field;
                } else {
                    // A pre-defined meta field key.
                    $meta_field_name = substr($multi_packages_type, strlen($product_meta_prefix));
                }

                if (!is_string($meta_field_name) || empty($meta_field_name)) {
                    // If we don't have a string for the field name, then we
                    // can't move forward.
                    return $this->packages;
                }

                // Go over the items in the cart to get the package names.
                foreach ( WC()->cart->get_cart() as $item ) {
                    if ( $item['data']->needs_shipping() ) {
                        $product_id = $item['product_id'];

                        $meta_value = get_post_meta($product_id, $meta_field_name, true);

                        $this->package_add_item($meta_value, $item);
                    }
                }
            } elseif ($multi_packages_type == 'per-owner') {
                // FIXME: this is 90% duplicated from the previous grouping
                // option. Do some refactoring.
                // Go over the items in the cart to get the package names.
                foreach ( WC()->cart->get_cart() as $item ) {
                    if ( $item['data']->needs_shipping() ) {
                        $product_id = $item['product_id'];

                        if ( isset( $item['data']->post->post_author ) ) {
                            $post_author = $item['data']->post->post_author;
                        } else {
                            $post_author = '-1';
                        }

                        $this->package_add_item($post_author, $item);
                    }
                }
            }

            // The packages will be indexed by package name.
            // We actually want it indexed numerically.
            return array_values($this->packages);
        }
    }

    /**
     * Create a new package if it does not already exist.
     * The package_id is a string or number - whatever they are grouped by.
     */
    function check_create_package($package_id)
    {
        // Has this package name been encountered already?

        if (!isset($this->packages[$package_id])) {
            // No - so create it.
            $this->packages[$package_id] = array(
                // contents is the array of products in the group.
                'contents' => array(),
                'contents_cost' => 0,
                'applied_coupons' => WC()->cart->applied_coupons,
                'package_name' => $package_id,
                'destination' => array(
                    'country' => WC()->customer->get_shipping_country(),
                    'state' => WC()->customer->get_shipping_state(),
                    'postcode' => WC()->customer->get_shipping_postcode(),
                    'city' => WC()->customer->get_shipping_city(),
                    'address' => WC()->customer->get_shipping_address(),
                    'address_2' => WC()->customer->get_shipping_address_2()
                )
            );
        }
    }

    /**
     * Add an item to a package.
     */
    function package_add_item($package_id, $item)
    {
        // Make sure the package exists.
        $this->check_create_package($package_id);

        // Add the item to the package.
        $this->packages[$package_id]['contents'][] = $item;

        // Add on the line total to the package.
        $this->packages[$package_id]['contents_cost'] += $item['line_total'];
    }
}
