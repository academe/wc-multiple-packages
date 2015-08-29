<?php

class Academe_Multiple_Packages_Settings extends WC_Shipping_Method
{
    protected $current_dir;

    public function __construct()
    {
        $this->id = 'academe_multiple_packages';

        // Title shown in admin
        $this->method_title = __('Packages Grouping', 'academe-package-config-woo');

        // Issue #10 "title" is used in the Shipping Methods table.
        $this->title = $this->method_title;

        // Description shown in admin
        $this->method_description = __('Group products in an order into shipping packages', 'academe-package-config-woo');

        // Set the current directory.
        $this->current_dir = dirname(__FILE__);

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
            'enabled' => array(
                'type'      => 'checkbox',
                'title'     => __('Enable Package Grouping', 'academe-package-config-woo'),
                'default'   => 'yes',
                'desc'      => __('Enable Multiple Shipping Packages', 'academe-package-config-woo'),
            ),

            'type' => array(
                'type'      => 'select',
                'title'     => __('Group By', 'academe-package-config-woo'),
                'desc'      => __('How packages are defined, in groups or per product', 'academe-package-config-woo'),
                'default'   => 'shipping-class',
                'class'     => 'chosen_select',
                'desc_tip'  => true,
                'options'   => array(
                    'shipping-class' => __('Shipping Class', 'academe-package-config-woo'),
                    'per-product' => __('Product (individual)', 'academe-package-config-woo'),
                    'per-owner' => __('Product Owner (vendor)', 'academe-package-config-woo'),
                    'product-meta' => __('Custom Product Field', 'academe-package-config-woo'),
                    // Removed for now, while some details of Print Trail get ironed out.
                    //'product-meta_printtrail_package' => __('Printtrail Package Names', 'academe-package-config-woo'),
                ),
            ),

            // TODO: validate and transform the value entered here.
            // It should be a valid metafield key. The documentation is
            // silent on what is "valid", but all examples seem to be
            // lower-case ASCII with underscores for spaces.
            'meta_field' => array(
                'type'      => 'text',
                'class'     => '',
                'css'       => 'min-width:300px;',
                'title'     => __('Custom Field to Group By', 'academe-package-config-woo'),
                'desc'  => '<em>' . __('Custom product field key', 'academe-package-config-woo') . '</em>',
                'default'   => __('', 'academe-package-config-woo'),
                'desc_tip'  => __('The custom product meta field name (key) used to group the products into shipping packages.', 'academe-package-config-woo'),
            ),

            'free_shipping' => array(
                'type'      => 'multiselect',
                'class'     => 'chosen_select',
                'title'     => __('Free Shipping Classes', 'academe-package-config-woo'),
                'desc'      => '<em>' . __('\'Free_Shipping\' method must be enabled', 'academe-package-config-woo') . '</em>',
                'default'   => __('Let me know when this item is back in stock!', 'academe-package-config-woo'),
                'desc_tip'  => __('Exclude the selected shipping classes from being charged shipping', 'academe-package-config-woo'),
                'options'   => $shipping_classes,
            ),

            'method_settings' => array(
                'id'        => 'multi_packages_method_settings',
                'type'      => 'title',
                'title'     => __('Shipping Method Restrictions When Grouping by Class', 'woocommerce'),
                'desc'      => __('Select which shipping methods will be used for each shipping class package', 'woocommerce'),
            ),

            'shipping_restrictions_classes' => array(
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

        include($this->current_dir . '/../views/shipping_class_method_restrictions.php');
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
