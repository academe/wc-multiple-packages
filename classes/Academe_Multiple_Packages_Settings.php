<?php

class Academe_Multiple_Packages_Settings extends WC_Shipping_Method
{
    public function __construct()
    {
        $this->id = 'academe_multiple_packages';

        // Title shown in admin
        $this->method_title = __('Package Grouping');

        // Description shown in admin
        $this->method_description = __('Group products in an order into shipping packages');

        // Can be forced if needed, but we'll just keep it selectable in the settings page.
        //$this->enabled  = "yes";

        $this->init();
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init()
    {
        // Load the settings API

        // This is part of the settings API.
        // Override the method to add your own settings
        $this->init_form_fields();

        // This is part of the settings API.
        // Loads settings you previously init.
        $this->init_settings();

        $this->package_restrictions_classes =
            isset($this->settings['shipping_restrictions_classes'])
            ? $this->settings['shipping_restrictions_classes']
            : array();

        // Save any settings that may have been submitted.
        add_action(
            'woocommerce_update_options_shipping_' . $this->id,
            array($this, 'process_admin_options')
        );
    }

    /**
     * Return the HTML for the "shipping_restrictions" for shipping
     * classes custom admin field.
     */
    function generate_shipping_restrictions_classes_html($key)
    {
        ob_start();
        $this->output_shipping_class_method_restrictions();
        return ob_get_clean();
    }

    /**
     * Validate the shipping restrictions for classes submission.
     */
    public function validate_shipping_restrictions_classes_field($key) {
        if (isset($_POST['restrictions_classes'])) {
            $restrictions_safe = array();

            foreach ($_POST['restrictions_classes'] as $key => $value) {
                $key_safe = intval($key);
                foreach ($value as $key_method => $value_method) {
                    $restrictions_safe[$key_safe][] = sanitize_title($key_method);
                }
            }

            return $restrictions_safe;
        }
    }

    /**
     * Set the form fields that will be available on this admin form.
     */
    public function init_form_fields()
    {
        $this->form_fields = $this->get_settings();
    }

    /**
     * Get Page Settings
     *
     * @return array
     */
    function get_settings($current_section = '')
    {
        $shipping_classes = $this->get_shipping_classes();

        $settings = apply_filters('woocommerce_multi_packages_settings', array(
            'multi_packages_options' => array(
                'id'    => 'multi_packages_options',
                'type'  => 'title',
                'title' => __('Multiple Packages for Shipping', 'woocommerce'),
                'desc'  => __('Separate your customer\'s shopping cart into groups or per product to display multiple shipping select boxes', 'woocommerce'),
            ),

            'multi_packages_enabled' => array(
                'id'        => 'multi_packages_enabled',
                'type'      => 'checkbox',
                'title'     => __('Enable/Disable', 'bolder-multi-package-woo'),
                'default'   => 'yes',
                'desc'      => __('Enable Multiple Shipping Packages', 'bolder-multi-package-woo'),
            ),

            'multi_packages_type' => array(
                'id'        => 'multi_packages_type',
                'type'      => 'select',
                'title'     => __('Group By', 'bolder-multi-package-woo'),
                'desc'      => __('How packages are defined, in groups or per product', 'bolder-multi-package-woo'),
                'default'   => 'shipping-class',
                'class'     => 'chosen_select',
                'desc_tip'  => true,
                'options'   => array(
                    'shipping-class' => __('Shipping Class', 'bolder-multi-package-woo'),
                    'per-product' => __('Product (individual)', 'bolder-multi-package-woo'),
                    'per-owner' => __('Product Owner (vendor)', 'bolder-multi-package-woo'),
                    'product-meta' => __('Custom Product Field', 'bolder-multi-package-woo'),
                    'product-meta_printtrail_package' => __('Printtrail Package Names', 'bolder-multi-package-woo'),
                ),
            ),

            // TODO: validate and transform the value entered here.
            // It should be a valid metafield key. The documentation is
            // silent on what is "valid", but all examples seem to be
            // lower-case ASCII with underscores for spaces.
            'multi_packages_meta_field' => array(
                'id'        => 'multi_packages_meta_field',
                'type'      => 'text',
                'class'     => '',
                'css'       => 'min-width:300px;',
                'title'     => __('Group By Meta Field', 'bolder-multi-package-woo'),
                'desc'  => '<em>' . __('Custom product meta field key', 'bolder-multi-package-woo') . '</em>',
                'default'   => __('', 'bolder-multi-package-woo'),
                'desc_tip'  => __('The custom product meta field name (key) used to group the products into shipping packages.', 'bolder-multi-package-woo'),
            ),

            'multi_packages_free_shipping' => array(
                'id'        => 'multi_packages_free_shipping',
                'type'      => 'multiselect',
                'class'     => 'chosen_select',
                'title'     => __('Free Shipping Classes', 'bolder-multi-package-woo'),
                'desc'      => '<em>' . __('\'Free_Shipping\' method must be enabled', 'bolder-multi-package-woo') . '</em>',
                'default'   => __('Let me know when this item is back in stock!', 'bolder-multi-package-woo'),
                'desc_tip'  => __('Exclude the selected shipping classes from being charged shipping', 'bolder-multi-package-woo'),
                'options'   => $shipping_classes,
            ),

            'multi_packages_method_settings' => array(
                'id'        => 'multi_packages_method_settings',
                'type'      => 'title',
                'title'     => __('Shipping Method Restrictions by Class', 'woocommerce'),
                'desc'      => __('Select which shipping methods will be used for each shipping class package', 'woocommerce'),
            ),

            'shipping_restrictions_classes' => array(
                'id'        => 'shipping_restrictions_classes',
                'type'      => 'shipping_restrictions_classes',
            ),
        ));

        return apply_filters('woocommerce_get_settings_' . $this->id, $settings);
    }

    /** 
     * Get list of current shipping classes.
     */
    public function get_shipping_classes()
    {
        $shipping_classes = array();
        $get_classes = WC()->shipping->get_shipping_classes();

        foreach ($get_classes as $key => $class) {
            $shipping_classes[$class->term_id] = $class->name;
        }

        return $shipping_classes;
    }

    /**
     * Generate the shipping class method restrictions HTML table.
     *
     * @return array
     */
    public function output_shipping_class_method_restrictions($current_section = '')
    {
        $shipping_classes = $this->get_shipping_classes();

        $shipping_methods = WC()->shipping->load_shipping_methods();
        $total_shipping_methods = count($shipping_methods) + 1;

        include(dirname(__FILE__) . '/../views/shipping_class_method_restrictions.php');
    }

    public function save_shipping_class_method_restrictions($current_section = '')
    {
        if (isset($_POST['restrictions'])) {
            // Save settings
            $restrictions_safe = array();
            foreach ($_POST['restrictions'] as $key => $value) {
                $key_safe = intval( $key );
                foreach ($value as $key_method => $value_method) {
                    $restrictions_safe[ $key_safe ][] = sanitize_title($key_method);
                }
            }
        }
    }
}
