<?php
/**
 * WooCommerce Multiple Packages Settings Page
 *
 * @author 		Erica Dion
 * @category 	Classes
 * @package 	WooCommerce-Multiple-Packaging
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'BE_Multiple_Packages_Settings' ) ) :

include_once( WC()->plugin_path().'/includes/admin/settings/class-wc-settings-page.php' );

class BE_Multiple_Packages_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $woocommerce;

    	$this->id = 'multiple_packages';
    	$this->version = '1.0';
		$this->label = __( 'Multiple Packages', 'bolder-multi-package-woo' );
		$this->multi_package_restrictions = 'bolder_multi_package_woo_restrictions';

		$this->get_package_restrictions();

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 82 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_admin_field_shipping_restrictions', array( $this, 'output_additional_settings' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'additional_output' ) );

	}

	/**
	 * Output the settings
	 */
	public function output() {

		$settings = $this->get_settings( );
 		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings
	 */
	public function save() {

		$settings = $this->get_settings( );
		WC_Admin_Settings::save_fields( $settings );
		$this->save_additional_settings();
	}


	/**
	 * Get Page Settings
	 *
	 * @return array
	 */
	function get_settings( $current_section = '' ) {

		$shipping_classes = array();
		$get_classes = WC()->shipping->get_shipping_classes();
		foreach ($get_classes as $key => $class) {
			$shipping_classes[ $class->term_id ] = $class->name;
		}

		return apply_filters('woocommerce_multi_packages_settings', array(

			array(	
				'id' 		=> 'multi-packages_options',
				'type' 		=> 'title', 
				'title' 	=> __( 'Multiple Packages for Shipping', 'woocommerce' ), 
				'desc' 		=> __( 'Separate your customer\'s shopping cart into groups or per product to display multiple shipping select boxes', 'woocommerce' ), 
				),

			array(
				'id'		=> 'multi_packages_enabled',
				'type' 		=> 'checkbox',
				'title' 	=> __( 'Enable/Disable', 'bolder-multi-package-woo' ),
				'default' 	=> 'yes',
				'desc'		=> __( 'Enable Multiple Shipping Packages', 'bolder-multi-package-woo' ),
				),

			 array(
			 	'id'		=> 'multi_packages_type',
				'type' 		=> 'select',
				'title' 	=> __( 'Group By', 'bolder-multi-package-woo' ),
				'desc' 		=> __( 'How packages are defined, in groups or per product','bolder-multi-package-woo'),
				'default' 	=> 'shipping-class',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options' 	=> array(
					'shipping-class' 	=> __( 'Shipping Class', 'bolder-multi-package-woo'),
					'per-product' 		=> __( 'Product (individual)', 'bolder-multi-package-woo' ),
					)
				),

			array(
			 	'id'		=> 'multi_packages_free_shipping',
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'title' 	=> __( 'Free Shipping Classes', 'bolder-multi-package-woo' ),
				'desc' 		=> '<em>' . __( '\'Free_Shipping\' method must be enabled', 'bolder-multi-package-woo' ) . '</em>',
				'default' 	=> __( 'Let me know when this item is back in stock!', 'bolder-multi-package-woo' ),
				'desc_tip'	=> __( 'Exclude the selected shipping classes from being charged shipping', 'bolder-multi-package-woo' ),
				'options' 	=> $shipping_classes,
				),

			array( 'type' => 'sectionend', 'id' => 'message_text' ),

			array(	
				'id' 		=> 'multi_packages_method_settings',
				'type' 		=> 'title', 
				'title' 	=> __( 'Shipping Method Restrictions', 'woocommerce' ), 
				'desc' 		=> __( 'Select which shipping methods will be used for each shipping class package', 'woocommerce' ), 
				),

			array(
				'type' 		=> 'shipping_restrictions',
				),
			)

		);
	}


	/**
	 * Print Out Additional Settings
	 *
	 * @return array
	 */
	function output_additional_settings( $current_section = '' ) {
		// get list of current shipping classes		
		$shipping_classes = array();
		$get_classes = WC()->shipping->get_shipping_classes();
		foreach ($get_classes as $key => $class) {
			$shipping_classes[ $class->term_id ] = $class->name;
		}

		$shipping_methods = WC()->shipping->load_shipping_methods();
		$total_shipping_methods = count( $shipping_methods ) + 1;
?>
			<style>#shipping_package_restrictions .restriction_rows th, #shipping_package_restrictions .restriction_rows td {text-align: center;} #shipping_package_restrictions .class_name {font-weight: bold;text-align: left;}</style>
			<table>
		    	<tr valign="top" id="shipping_package_restrictions">
		            <th scope="row" class="titledesc"><?php _e( 'Shipping Methods', 'bolder-multi-package-woo' ); ?>
		            	<a class="tips" data-tip="<?php _e('If separating by shipping class, select which shipping methods to use for each class','bolder-multi-package-woo'); ?>">[?]</a></th>
		            <td class="forminp" id="<?php echo $this->id; ?>_restrictions">
		            	<table class="restriction_rows widefat" style="width: 60%;min-width:550px;" cellspacing="0">
		            		<thead>
		            			<tr>
		            				<th>&nbsp;</th>
		            				<?php foreach ( $shipping_methods as $key => $method ) : ?>
		        	            	<th><?php _e( $method->get_title(), 'bolder-multi-package-woo' ); ?></th>
		        	            	<?php endforeach; ?>
		            			</tr>
		            		</thead>
		            		<tfoot>
		            			<tr>
		            				<td colspan="<?php echo $total_shipping_methods; ?>"><em><?php _e( 'If left blank, all active shipping methods will be used for each shipping class', 'bolder-multi-package-woo' ); ?></em></td>
		            			</tr>
		            		</tfoot>
		            		<tbody class="shipping_restrictions">
<?php
		                	$i = -1;
		                	if( count( $shipping_classes ) > 0 ) :

		                		foreach ( $shipping_classes as $id => $name ) :
?>
								<tr>
									<td class="class_name"><?php echo $name; ?></td>
									<?php foreach ( $shipping_methods as $key => $method ) : ?>
									<?php $checked = ( isset( $this->package_restrictions[ $id ] ) && in_array( sanitize_title( $key ), $this->package_restrictions[ $id ] ) ) ? 'checked="checked"' : ''; ?>
		        	            	<td><input type="checkbox" name="restrictions[<?php echo $id; ?>][<?php echo sanitize_title( $key ); ?>]" <?php echo $checked; ?> /></td>
									<?php endforeach; ?>
								</tr>
<?php
		                		endforeach;
		                	else :
		                		echo '<tr colspan="'.$total_shipping_methods.'">' . _e( 'No shipping classes have been created yet...', 'bolder-multi-package-woo' ) . '</tr>';
		                	endif;
?>
		                	</tbody>
		                </table>
		            </td>
		        </tr>
		    </table>
		</div>
<?php
	}


	/**
	 * Output Additional Information
	 *
	 * @return array
	 */
	function additional_output( $current_section = '' ) {
?>
			<style>.woocommerce_wrap { position:relative; padding-right: 300px; } a:hover { text-decoration: none; }</style>
			<div class="woocommerce_wrap">
			<div style="position:absolute;top:25px;right:10px;width:275px;display:block;padding:10px;border:1px solid #9dbc5a;border-radius:5px;">
			    <h3>Bolder Elements also offers premium plugins! Here are a few you might be interested in...</h3>
			    <div style="margin-bottom:25px;">
			    	<strong>Table Rate Shipping for WooCommerce</strong>
			    	<p>Has the ability to return multiple rates based on a variety of conditions such as location, subtotal, shipping class, weight, and more</p>
			    	<p style="text-align:right"><a href="http://codecanyon.net/item/table-rate-shipping-for-woocommerce/3796656?ref=bolderelements" style="color:#9dbc5a;" target="_blank">More Info</a></p>
			    </div>
			    <div style="margin-bottom:25px;">
			    	<strong>Bolder Fees for WooCommerce</strong>
			    	<p>Add extra flat rate, percentage, and even optional (checkbox) fees to the customer's cart. Can be based on subtotal, item details, and more</p>
			    	<p style="text-align:right"><a href="http://codecanyon.net/item/bolder-fees-for-woocommerce/6125068?ref=bolderelements" style="color:#9dbc5a;" target="_blank">More Info</a></p>
			    </div>
			    <div style="">
			    	<strong>Cart Based Shipping for WooCommerce</strong>
			    	<p>Allows you to change the shipping rate based on the customer's cart. Could be based on the subtotal, item count, or weight.</p>
			    	<p style="text-align:right"><a href="http://codecanyon.net/item/woocommerce-cart-based-shipping/3156515?ref=bolderelements" style="color:#9dbc5a;" target="_blank">More Info</a></p>
			    </div>
			</div>
<?php
	}


	/**
	 * Print Out Additional Settings
	 *
	 * @return array
	 */
	function save_additional_settings( $current_section = '' ) {

		if ( isset( $_POST['restrictions'] ) ) {

			// Save settings
			$restrictions_safe = array();
			foreach ($_POST['restrictions'] as $key => $value) {
				$key_safe = intval( $key );
				foreach ($value as $key_method => $value_method) {
					$restrictions_safe[ $key_safe ][] = sanitize_title( $key_method );
				}
			}

			update_option( $this->multi_package_restrictions, $restrictions_safe );
		}
	}


	/**
	 * Get Settings for Restrictions Table
	 *
	 * @access public
	 * @return void
	 */
	function get_package_restrictions() {
		$this->package_restrictions = array_filter( (array) get_option( $this->multi_package_restrictions ) );
	}

}

endif;

	return new BE_Multiple_Packages_Settings();
