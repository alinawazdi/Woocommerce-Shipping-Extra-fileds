<?php
/*

Plugin Name: Woo BB Extra Fields
Description: Woocommerce Extra fields for shipping
Version: 1.0
Text Domain: Woocommerce Extra fields for shipping
Author: Ali Nawaz
Author URI: https://codeconvolution.com/
Plugin URI: http://codeconvolution.com/
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

# Add custom field to the checkout page
function shipping_charges_method_woocommerce_after_order_notes($checkout) {
	$data = ""; $data = array(); $data["choose"] = "Choose the option"; $get_option = get_option("woo-ship-data"); foreach($get_option as $k => $v) { $data[$k] = $v; }
	woocommerce_form_field(
		'shipping_charges_method',
		array(
			'type' => 'select',
			'class' => array( 'shipping charges' ) ,
			'label' => __('For Shipping Charges'),
			'placeholder' => __('For Shipping Charges'),
			'options' => $data,
		),
		$checkout->get_value('shipping_charges_method')
	);
} add_action('woocommerce_after_order_notes', 'shipping_charges_method_woocommerce_after_order_notes');

#2 Calculate New Total
function bbloomer_checkout_radio_choice_fee( $cart ) {
  if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
  $radio = WC()->session->get( 'shipping_charges_method' );
  if ( "choose" == $radio ) { $fee = 0; } else { $fee = $radio; }
  $cart->add_fee( __('Shipping Day', 'woocommerce'), $fee );
} add_action( 'woocommerce_cart_calculate_fees', 'bbloomer_checkout_radio_choice_fee', 20, 1 );

#3 Refresh
function bbloomer_checkout_radio_choice_refresh() { if ( ! is_checkout() ) return; ?>
    <script type="text/javascript">jQuery( function($){ $('form.checkout').on('change', '#shipping_charges_method', function(e){
		e.preventDefault();
		var p = $(this).val();
		$.ajax({
			type: 'POST',
			url: wc_checkout_params.ajax_url,
			data: { 'action': 'woo_get_ajax_data', 'val': p },
			success: function (result) { $('body').trigger('update_checkout'); }
		});
	}); });</script>
<?php } add_action( 'wp_footer', 'bbloomer_checkout_radio_choice_refresh' );

#4 Ajax
function bbloomer_checkout_radio_choice_set_session() { if ( isset($_POST['val']) ){
	$radio = sanitize_key( $_POST['val'] );
	WC()->session->set('shipping_charges_method', $radio );
	echo json_encode( $radio );
} die(); } add_action( 'wp_ajax_woo_get_ajax_data', 'bbloomer_checkout_radio_choice_set_session' ); add_action( 'wp_ajax_nopriv_woo_get_ajax_data', 'bbloomer_checkout_radio_choice_set_session' );

# Checkout Process
function customised_checkout_field_process() {
	if (!$_POST['shipping_charges_method']) wc_add_notice(__('Choose a value') , 'error');
} add_action('woocommerce_checkout_process', 'customised_checkout_field_process');

# Update the value given in custom field
function custom_checkout_field_update_order_meta($order_id) {
	if (!empty($_POST['shipping_charges_method'])) { update_post_meta($order_id, sanitize_text_field($_POST['shipping_charges_method'])); }
} add_action('woocommerce_checkout_update_order_meta', 'custom_checkout_field_update_order_meta');

function woo_iconic_add_engraving_text_to_order_items( $item, $cart_item_key, $values, $order ) {
	if ( empty( $values['shipping_charges_method'] ) ) { return; }
	$item->add_meta_data( __( 'Shipping Charges', 'ws' ), $values['shipping_charges_method'] );
} add_action( 'woocommerce_checkout_create_order_line_item', 'iconic_add_engraving_text_to_order_items', 10, 4 );

#CREATE A PAGE
class woo_shipping_options_page {
	function __construct() { add_action( 'admin_menu', array( $this, 'admin_menu' ) ); }

	function admin_menu() { add_options_page( 'Woo Shipping Days', 'Woo Shipping Days', 'manage_options', 'options_page_slug', array( $this, 'settings_page' ) ); }

	function  settings_page() { ?>
    	<?php if(isset($_POST["woo-ship-submit"])) {
			$data = ""; $data = array();
			$price = $_POST["woo-ship-price"];
			$day = $_POST["woo-ship-day"];
			for($x=0;$x<count($price);$x++) { $k = $price[$x]; $v = $day[$x]; $data[$k] = $v; }
			update_option("woo-ship-data", $data); ?>
            <div class="update-nag">Data Update Successfully!</div>
		<?php } ?>
    	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
        <style type="text/css">.row{margin-bottom:15px}</style>
    	<div class="wrap">
        	<form action="" method="post">
                <div id="add-new-input-area" class="input area">
                	<?php $data = get_option("woo-ship-data"); if(!empty($data) && is_array($data)) { foreach($data as $k => $v) { ?>
						<div class="row">
							<label for="woo-ship-price">Price</label>
							<input type="text" class="regular-text" id="woo-ship-price" name="woo-ship-price[]" value="<?php echo $k; ?>" />
							<label for="woo-ship-day">Day</label>
							<input type="text" class="regular-text" id="woo-ship-day" name="woo-ship-day[]" value="<?php echo $v; ?>" />
							<i class="fas fa-times button button-primary" onClick="jQuery(this).parent().remove();"></i>
						</div>
					<?php } } ?>
                </div>
                <div id="add-new-input" class="button button-primary">+ Add New</div>
                <input type="submit" name="woo-ship-submit" class="button button-primary" id="woo-ship-submit" value="Submit" />
            </form>
        </div>
        <script>jQuery(document).ready(function(e) {
            jQuery("#add-new-input").click(function(){
				var txt = "";
				var txt = txt + '<div class="row">';
					var txt = txt + '<label for="woo-ship-price">Price</label>';
					var txt = txt + '<input type="text" class="regular-text" id="woo-ship-price" name="woo-ship-price[]" value="" />';
					var txt = txt + '<label for="woo-ship-day">Day</label>';
					var txt = txt + '<input type="text" class="regular-text" id="woo-ship-day" name="woo-ship-day[]" value="" />';
					var txt = txt + '<i class="fas fa-times button button-primary" onClick="jQuery(this).parent().remove();"></i>';
				var txt = txt + '</div>';
				jQuery("#add-new-input-area").append(txt);
			});
        });</script>
	<?php }
} new woo_shipping_options_page;