<?php

/**
 *
 */

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
    const ORDER_PRODUCT_IDS_FIELD = '_product_ids';

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Get the settings from the plugin.
        // CHECKME: there may be an API for this.
        $settings = get_option(
            'woocommerce_' . $this->id . '_settings',
            array()
        );

        // Extract some of the settings we will need.
        // Issue #9 The enabled flag is "yes" or "no".
        $this->enabled = !empty($settings['enabled']) && strtolower($settings['enabled']) == 'yes';

        $this->multi_packages_free_shipping =
            isset($settings['free_shipping'])
            ? $settings['free_shipping']
            : '';

        $this->multi_packages_type =
            isset($settings['type'])
            ? $settings['type']
            : '';

        $this->multi_packages_meta_field =
            isset($settings['meta_field'])
            ? $settings['meta_field']
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
        // Implode to a string, otherwise the API has difficulty supplying it.
        wc_add_order_item_meta($shipping_line_id, self::ORDER_PRODUCT_IDS_FIELD, implode('|', $product_ids), true);

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
     * Get Settings for Restrictions Table.
     * Called directly from the woocommerce_cart_shipping_packages filter.
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

            //
            // Per product grouping.
            //

            $package_meta = array('package_grouping_rule' => $multi_packages_type);

            if ($multi_packages_type == 'per-product') {
                // separate each item into a package
                $n = 0;
                foreach ( WC()->cart->get_cart() as $item ) {
                    if ( $item['data']->needs_shipping() ) {
                        // Determine if 'ship_via' applies
                        $key = $item['data']->get_shipping_class_id();


                        if ($free_classes && in_array($key, $free_classes)) {
                            $package_meta['ship_via'] = array('free_shipping');
                        } elseif (count($package_restrictions) && isset($package_restrictions[$key])) {
                            $package_meta['ship_via'] = $package_restrictions[$key];
                        }

                        $package_meta['package_grouping_value'] = $item->id;

                        // Put inside package.
                        $this->package_add_item($n, $item, $package_meta);

                        $n++;
                    }
                }
            }

            //
            // Shipping class grouping.
            //

            elseif ($multi_packages_type == 'shipping-class') {
                // Get array of shipping classes.
                $shipping_classes = WC()->shipping->get_shipping_classes();

                // Get a list of shipping class slugs.
                // The value of each element is the shipping class term ID.
                $shipping_class_slugs = array('other' => -1);
                foreach($shipping_classes as $key => $value) {
                    $shipping_class_slugs[$value->slug] = $value->term_id;
                }

                // Go through each item in the cart.
                foreach (WC()->cart->get_cart() as $item) {
                    // Skip the item if it does not need shipping.
                    if ( ! $item['data']->needs_shipping()) continue;

                    $item_shipping_class = $item['data']->get_shipping_class();

                    // If the class is not recognised or not present, then put
                    // this item into the "other" group.
                    if ( ! isset($shipping_class_slugs[$item_shipping_class])) {
                        $item_shipping_class = 'other';
                    }

                    // Determine if 'ship_via' applies
                    // $free_classes is an array of term IDs for the classes that have been
                    // set as "free" in the settings page.
                    // The "ship via" setting on the package seems to provide an additional
                    // filter that restricts what shipping methods can be used in that package.

                    $shipping_class_term_id = (int)$shipping_class_slugs[$item_shipping_class];

                    if ($free_classes && in_array($shipping_class_term_id, $free_classes)) {
                        // The shipping class is one in the list of "free shipping" classes in
                        // the plugin settings.
                        // The "free shipping" method must be enabled for this to work.

                        $package_meta['ship_via'] = array('free_shipping');
                    } elseif (count($package_restrictions) && isset($package_restrictions[$shipping_class_term_id])) {
                        // If there are restrictions set in the class/shipping method settings table
                        // for this shipping class, then set the selected methods in that table as the
                        // "ship via" filter. This will be an array of shipping method slugs.

                        $package_meta['ship_via'] = $package_restrictions[$shipping_class_term_id];
                    }

                    $package_meta['package_grouping_value'] = $item_shipping_class;

                    $this->package_add_item($item_shipping_class, $item, $package_meta);
                }
            }

            //
            // Product meta field grouping.
            //

            elseif (substr($multi_packages_type, 0, strlen($product_meta_prefix)) == $product_meta_prefix) {
                // Get the metafield name.
                // It can come from the setting in the plugin admin page, or
                // from the package name field, as a suffix.
                //
                // We hope it is lower-case and with underscores (snake-case), as that is
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
                        $package_meta['package_grouping_value'] = $meta_value;

                        $this->package_add_item($meta_value, $item, $package_meta);
                    }
                }
            }

            //
            // Owner grouping.
            //

            elseif ($multi_packages_type == 'per-owner') {
                // Go over the items in the cart to get the package names.
                foreach ( WC()->cart->get_cart() as $item ) {
                    if ( $item['data']->needs_shipping() ) {
                        $product_id = $item['product_id'];

                        if ( isset( $item['data']->post->post_author ) ) {
                            $post_author = $item['data']->post->post_author;
                        } else {
                            $post_author = '-1';
                        }

                        $package_meta['package_grouping_value'] = $post_author;

                        $this->package_add_item($post_author, $item, $package_meta);
                    }
                }
            }

            //
            // Unknown grouping.
            //

            else {
                // Use what was supplied.
                $this->packages = $packages;
            }

            // The packages will be indexed by package name.
            // It needs to be indexed numerically and contiguously.

            return array_values($this->packages);
        }

        // Fallback - use whatever we already have, possibly generated
        // by another plugin.

        return $packages;
    }

    /**
     * Create a new package if it does not already exist.
     * The package_id is a string or number - whatever they are grouped by.
     * It may be useful to add other metadata to the package for use in
     * the templates that display the order details. By default those templates
     * list the packages as "Shipping #N", which means nothing to the customer.
     * If it were able to describe a package as "Heavy Items" or "Signed-for Required"
     * then that would be more meaningful.
     */
    function check_create_package($package_id, $package_meta = array())
    {
        // Has this package ID been encountered already?

        if (!isset($this->packages[$package_id])) {
            // Does not exist - create it.
            $this->packages[$package_id] = array(
                // 'contents' is the array of products in the package.
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

        // Merge in any additional meta data.
        if ( ! empty($package_meta) && is_array($package_meta)) {
            $this->packages[$package_id] = array_merge_recursive($this->packages[$package_id], $package_meta);
        }
    }

    /**
     * Add an item to a package.
     * $package_meta is an array of additional items to merge into the package data.
     */
    protected function package_add_item($package_id, $item, $package_meta = array())
    {
        // Make sure the package exists.
        $this->check_create_package($package_id, $package_meta);

        // Add the item to the package.
        $this->packages[$package_id]['contents'][] = $item;

        // Add on the line total to the package.
        $this->packages[$package_id]['contents_cost'] += $item['line_total'];
    }

    /**
     * Add shipping line meta fields to the shipping line in the order API.
     */
    public static function api_show_shipping_line_meta($order_data, $order, $fields, $server)
    {
        if (!empty($order_data['shipping_lines'])) {
            $shipping_methods = $order->get_shipping_methods();
            //mail('jason@consil.co.uk', 'shipping methods', print_r($shipping_methods, true));

            foreach($order_data['shipping_lines'] as $key => $shipping_line) {
                $shipping_line_id = $shipping_line['id'];

                $item_meta = array();

                if (isset($shipping_methods[$shipping_line_id])) {
                    $meta = new WC_Order_Item_Meta($shipping_methods[$shipping_line_id]);

                    $hideprefix = null;
                    foreach ($meta->get_formatted($hideprefix) as $meta_key => $formatted_meta) {
                        $item_meta[] = array(
                            'key' => $meta_key,
                            'label' => $formatted_meta['label'],
                            'value' => $formatted_meta['value'],
                        );
                    }
                }

                $order_data['shipping_lines'][$key]['meta'] = $item_meta;
            }
        }

        return $order_data;
    }

}
