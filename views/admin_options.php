<h3><?php echo $this->method_title; ?></h3>

<p><?php _e('Local delivery is a simple shipping method for delivering orders locally.', 'woocommerce'); ?></p>

<table class="form-table">
    <?php $this->generate_settings_html(); ?>
</table>
