<style>
#shipping_package_restrictions_classes .restriction_rows th, #shipping_package_restrictions_classes .restriction_rows td {text-align: center;}
#shipping_package_restrictions_classes .class_name {font-weight: bold; text-align: left;}
</style>

<table>
    <tr valign="top" id="shipping_package_restrictions_classes">
        <th scope="row" class="titledesc">
            <?php _e('Shipping Methods', 'academe-package-config-woo'); ?>
            <a class="tips" data-tip="<?php _e('If separating by shipping class, select which shipping methods to use for each class','academe-package-config-woo'); ?>">[?]</a>
        </th>
        <td class="forminp" id="<?php echo $this->id; ?>_restrictions_classes">
            <table class="restriction_rows widefat" style="width: 60%; min-width:550px;" cellspacing="0">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <?php foreach ($shipping_methods as $key => $method) : ?>
                        <th><?php _e( $method->get_title(), 'academe-package-config-woo' ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tfoot>
                    <tr>
                        <td colspan="<?php echo $total_shipping_methods; ?>"><em><?php _e('If left blank, all active shipping methods will be used for each shipping class', 'academe-package-config-woo');?> </em></td>
                    </tr>
                </tfoot>

                <tbody class="shipping_restrictions_classes">
<?php
                $i = -1;
                if (count($shipping_classes) > 0) :
                    foreach ($shipping_classes as $id => $name) :
?>
                    <tr>
                        <td class="class_name"><?php echo $name; ?></td>
                        <?php foreach ( $shipping_methods as $key => $method ) : ?>
                        <?php $checked = (isset($this->package_restrictions_classes[$id]) && in_array(sanitize_title($key), $this->package_restrictions_classes[$id]) ) ? 'checked="checked"' : ''; ?>
                        <td><input type="checkbox" name="restrictions_classes[<?php echo $id; ?>][<?php echo sanitize_title($key); ?>]" <?php echo $checked; ?> /></td>
                        <?php endforeach; ?>
                    </tr>
<?php
                    endforeach;
                else :
                    echo '<tr colspan="'.$total_shipping_methods.'">' . _e( 'No shipping classes have been created yet...', 'academe-package-config-woo' ) . '</tr>';
                endif;
?>
                </tbody>
            </table>
        </td>
    </tr>
</table>
