<?php
/*
Plugin Name: Bolder Fees for WooCommerce
Plugin URI: http://bolderelements.net/plugins/bolder-fees-woocommerce/
Description: Create additional and optional fees at checkout separate from shipping costs and taxes
Author: Bolder Elements
Author URI: http://www.bolderelements.net/
Version: 1.5
WC requires at least: 3.0.0
WC tested up to: 3.7.0

	Copyright: © 2013-2019 Bolder Elements (email : info@bolderelements.net)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Current version
if ( ! defined( 'BE_WooBolderFees_VERSION' ) ) define( 'BE_WooBolderFees_VERSION', '1.5' );
	
add_action('plugins_loaded', 'woocommerce_bolder_fees_init', 100);

function woocommerce_bolder_fees_init() {

	/**
	 * Check if WooCommerce is active
	 */
	if ( class_exists( 'WooCommerce' ) ) {
		
		if ( !class_exists( 'BE_Bolder_Fees' ) ) {

			if( !class_exists( 'Zone_List_Table' ) ) require('inc/woocommerce-shipping-zones.php');
			require('class-fees.php');
			require('class-fees-list.php');
			
			function be_create_fees_tab() {
		    	$current_tab = ( empty( $_GET['tab'] ) ) ? 'general' : sanitize_text_field( urldecode( $_GET['tab'] ) );

		    	if( WOOCOMMERCE_VERSION >= 2.1 ) 
					echo '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=bolder_fees' ) . '" class="nav-tab ';
				else
					echo '<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=bolder_fees' ) . '" class="nav-tab ';
				if( $current_tab == 'bolder_fees' ) echo 'nav-tab-active';
				echo '">Fees</a>';
			}
			add_action('woocommerce_settings_tabs','be_create_fees_tab');

			function be_bolder_fee_tab() {
				Bolder_Fees_Table::tt_render_list_page();
			}
			add_action('woocommerce_settings_tabs_bolder_fees','be_bolder_fee_tab');

			$GLOBALS['optional_fee_tmp'] = array();
			$GLOBALS['cart_fee_addon'] = 0;

			class BE_Bolder_Fees {

				/**
				 * __construct function.
				 *
				 * @access public
				 * @return void
				 */
				function __construct() {
		        	$this->id = 'be_bolder_fees';
		        	$this->version = '1.5';
					$this->admin_page_heading = __('Bolder Fees', 'bolder-fees-woo' );
					$this->admin_page_description = __( 'Create fees or extra selections to appear separate from shipping/taxes on the cart and checkout pages.', 'bolder-fees-woo' );
					$this->bolder_fees_options = 'woocommerce_bolder_fees';
					$this->optional_fee_tmp = array();

		        	add_action('woocommerce_update_options_bolder_fees', array( 'Bolder_Fees_Table', 'save_fees_table' ) );
		        	add_action('wp_ajax_wbf-fee-enabled', array( 'Bolder_Fees_Table', 'be_enable_fee_link' ) );
		        	
					add_action('admin_head', array( $this, 'add_css_style' ), 500);
					add_action('admin_enqueue_scripts', array( $this, 'register_plugin_admin'), 9999);
					//add_action('wp_footer', array( $this, 'add_checkout_script' ));

					add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
					add_action( 'wp_footer', array( $this, 'add_script_frontend' ) );


					//$this->get_bolder_fees();
				}


				/**
				 * get_bolder_fees function.
				 *
				 * @access public
				 * @return void
				 */
				function add_css_style() {
					if( isset( $_GET['page'] ) && isset( $_GET['tab'] ) && sanitize_text_field( $_GET['page'] ) == 'wc-settings' && sanitize_text_field( $_GET['tab'] ) == 'bolder_fees' ) :
?>
<style type="text/css">
.wp-list-table { border-collapse:collapse; }
	.wp-list-table.fees td, .wp-list-table.fees th, .wp-list-table.conditions td, .wp-list-table.conditions th { width: auto !important; }
.delete-cond { cursor: pointer; display: inline-block; width: 10px; height: 10px; background: url('../../wp-includes/images/xit.gif') 0 50% no-repeat; clear: both;  }
.delete-cond:hover { background-position:100% 50%; }
.ui-datepicker { width: 220px; height: auto; margin: 5px auto 0; font: 13px; font-weight: bold; background: #fff; border: 1px solid #888; font-family: Arial, sans-serif; }
	.ui-datepicker a { text-decoration: none; } 
	.ui-datepicker table { width: 100%; border-collapse: collapse; }
	.ui_tpicker_time_label,
	.ui-datepicker-header { background: #888 !important; color: #fff; font-weight: bold; line-height: 30px; text-align: center; font-size: 14px; }
	.ui-datepicker-prev, .ui-datepicker-next { display: inline-block; width: 30px; height: 30px; text-align: center; cursor: pointer; background-image: url('../img/arrow.png'); background-repeat: no-repeat; line-height: 600%; overflow: hidden; }
	.ui-datepicker-prev { float: left; background-position: 0 0px; }  
	.ui-datepicker-next { float: right; background-position: -30px 0; }
	.ui-datepicker thead { background-color: #e5e5e5; border-bottom: 1px solid #888; color: #888; font-size: 12px; text-align: center; font-weight: normal; }
	.ui-datepicker thead th { text-align: center; }
	.ui-datepicker thead td { padding: 0; }
	.ui-datepicker tbody td { padding: 0; border-right: 1px solid #d9d9d9; border-bottom: 1px solid #d9d9d9; text-align: center; color: #b4b4b4; font-size: 12px; font-weight: bold; margin:0; }
	.ui-datepicker tbody td a { color: #000; width: 100%; line-height: 24px; display: block; }
	.ui-datepicker tbody td:last-child { border-right: 0px; }
	.ui-datepicker tbody tr:last-child { border-bottom: 0px; }  
	.ui-datepicker-calendar .ui-state-active { background: #87a24f; color: #fff; }
	.ui-datepicker td:hover { background: #9dbc5a; } 
	.ui-datepicker td:hover a { color: #fff; } 
	.ui-datepicker tfoot { display: none; }
</style>
<?php
					endif;
				}


				/**
				 * Add javascript functions to frontend
				 */
				public function register_plugin_styles() {
					$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

					wp_enqueue_script( 'be_fees_frontend_js', plugins_url( 'inc/frontend' . $suffix . '.js', __FILE__ ), array( 'jquery' ), false, true );
				}


				/**
				 * Add Script Directly to Dashboard Foot
				 */
				public function add_script_frontend() {

					// Setup translated strings
					$fees_data = array(
						'text_additional_fee'	=> __( 'Additional Fee', 'bolder-fees-woo' ),
						);
?>
<script type='text/javascript'>
/* <![CDATA[ */
var be_fees_data = <?php echo json_encode( $fees_data ) . "\n"; ?>
/* ]]> */
</script>
<?php
				}


				/**
				 * get_bolder_fees function.
				 *
				 * @access public
				 * @return void
				 */
				static function add_checkout_script() {
					global $woocommerce;

					if( is_checkout() ) :
?>

<script>
jQuery(document).ready(function (e) {
e("form.checkout").on("change", "input[name=payment_method]", function () {
        n = !1;
        e("body").trigger("update_checkout")
    });
jQuery("form.checkout div#order_review").on("change", "input.fee_optional_box", function () {

		// Determine which div to 'block' based on WC version
		/*
		var shop_table = jQuery( '#order_review .shop_table' );
		if(shop_table.length)
			jQuery('#order_review .shop_table').block({message: null, overlayCSS: {background: '#fff url(' + wc_checkout_params.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6}});
		else
			jQuery('#order_methods, #order_review').block({message: null, overlayCSS: {background: '#fff url(' + wc_checkout_params.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6}});
		*/

        // Check for options that are checked
        fList = {};
        var t = jQuery(this).val();
        var s = jQuery(this).is(':checked');
        jQuery('form.checkout .fee-optional input').each(function () {
        	var sel = false;
        	var feeName = jQuery(this).attr('title');
        	if(jQuery(this).is(':checked')) { sel = true; }
        	if(typeof feeName === 'undefined') { feeName = "<?php _e('Additional Fee', 'bolder-fees-woo'); ?>"; }
        	fList[jQuery(this).attr('name')] = { 'fee': jQuery(this).val(), 'name': feeName, 'selected': sel }
        });

        var n = {
            action: "bolder_update_checkout_fees",
            fees: fList,
        };
        e.post(wc_checkout_params.ajax_url, n, function (t) {
        	e("body").trigger("update_checkout");
        });
    });
});
</script>
<?php
					endif;
				}


				/**
				 * Modify Scripts in Dashboard
				 */
				static public function register_plugin_admin($hook_suffix) {
					wp_enqueue_script( 'jquery-ui-datepicker' );
					if( $hook_suffix == 'woocommerce_page_wc-settings' && isset( $_GET['tab'] ) && sanitize_text_field( $_GET['tab'] ) == 'bolder_fees' )
						wp_dequeue_style( 'jquery-ui-style' );
				}
			} return $BE_Bolder_Fees = new BE_Bolder_Fees();
		}
	}
}

/**
 * Modify links on plugin listing page (Left)
 *
 * @access public
 * @return void
 */
function be_bolder_fees_plugin_action_links( $links ) {
	return array_merge(
		array(
			'settings' => '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=bolder_fees">' . __( 'Settings', 'bolder_fees' ) . '</a>',
		),
		$links
	);
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'be_bolder_fees_plugin_action_links' );


/**
 * Modify links on plugin listing page (Right)
 *
 * @access public
 * @return array
 */
function be_bolder_fees_wc_plugin_meta( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) {

		// Check if plugin already has a 'View details' link
		$index = 'details';
		foreach( $links as $key => $value )
			if( strstr( $value, 'View details' ) )
				$index = $key;

		$row_meta = array(
			$index	  => '<a href="' . network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce-bolder-fees&TB_iframe=true&width=600&height=550' ) . '" class="thickbox">' . __( 'View details', 'bolder_fees' ) . '</a>',
			'docs'    => '<a href="http://bolderelements.net/docs/woocommerce-bolder-fees/">' . __( 'Docs', 'bolder_fees' ) . '</a>',
			'support' => '<a href="http://bolderelements.net/support/" target="_blank">' . __( 'Support', 'bolder_fees' ) . '</a>'
		);
		return array_merge( $links, $row_meta );
	}
	return (array) $links;
}
add_filter( 'plugin_row_meta', 'be_bolder_fees_wc_plugin_meta', 10, 2 );
