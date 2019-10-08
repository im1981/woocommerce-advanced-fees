<?php

/*************************** LOAD THE BASE CLASS ********************************/
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
require_once('class-conditions-list.php');
require_once('class-conditions.php');

/************************** CREATE A PACKAGE CLASS ******************************/
class Bolder_Fees_Table extends WP_List_Table {

    public $bolder_fees = array();
    public $fieldsInc = 0;
    
    function __construct(){
        global $status, $page, $woocommerce;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'fee', 
            'plural'    => 'fees',
            'ajax'      => false
        ) );

        $this->bolder_fees = get_option( 'woocommerce_be_bolder_fees' );
    }
    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav( $which ) {
        echo '<a href="#" class="add button fee" style="display:inline-block;">'.__( '+ Add Fee', 'bolder-fees-woo' ).'</a>';

        if ( $which == "top" ){
            echo ' ';
        }
        if ( $which == "bottom" ){
            echo ' Drag the table rows to sort your fees. ';
        }

        echo '<a href="#" class="remove button fee" style="display:inline-block;float:right;">'.__( 'Delete Selected Fees', 'bolder-fees-woo' ).'</a>';
    }
    function get_table_classes() {
        return array( 'widefat', $this->_args['plural'] );
    }
    function column_default($item, $column_name){
        global $woocommerce;

        switch($column_name){
            case 'status':
                $url = wp_nonce_url( admin_url( 'admin-ajax.php?action=wbf-fee-enabled&fee_id=' . $item['order'] ), 'woocommerce-settings' );
                $return_image = '<p style="text-align:center"><a href="' . $url . '" title="'. __( 'Toggle featured', 'bolder-fees-woo' ) . '">';
                if ($item['status'] == '1')
                    $return_image .=  '<img src="' . plugins_url( '/inc/success.png', __FILE__ ) . '" alt="yes" />';
                else
                    $return_image .=  '<img src="' . plugins_url( '/inc/success-off.png', __FILE__ ) . '" alt="no" />';
                return $return_image . '</a> <input type="hidden" name="enabled['.$this->fieldsInc.']" value="' . $item['status'] . '" /></p>';
            case 'shipping':
                return '<p style="text-align:center"><input type="checkbox" name="shipping['.$this->fieldsInc.']" '.checked( 'on', $item['shipping'], 0).' /></p>';
            case 'coupons':
                return '<p style="text-align:center"><input type="checkbox" name="coupons['.$this->fieldsInc.']" '.checked( 'on', $item['coupons'], 0).' /></p>';
            case 'per_item':
                return '<p style="text-align:center"><input type="checkbox" name="per_item['.$this->fieldsInc.']" '.checked( 'on', $item['per_item'], 0).' /></p>';
            case 'taxable':
                return '<p style="text-align:center"><input type="checkbox" name="taxable['.$this->fieldsInc.']" '.checked( 'on', $item['taxable'], 0).' /></p>';
            case 'optional':
                return '<p style="text-align:center"><input type="checkbox" name="optional['.$this->fieldsInc.']" '.checked( 'on', $item['optional'], 0).' /></p>';
            case 'cost':
                return '<input type="text" name="cost['.$this->fieldsInc.']" value="'.$item['cost'].'" size="10" />';
            case 'cost_percent':
                return '<input type="text" name="cost_percent['.$this->fieldsInc.']" value="'.$item['cost_percent'].'" size="10" />';
            default:
                return "Data Could Not Be Found";
        }
    }
    
    function column_name($item){
        //Return the title contents
        return sprintf('<input type="text" name="fee_name['.$this->fieldsInc.']" value="%1$s" /><br /><textarea name="fee_desc['.$this->fieldsInc.']" placeholder="(optional fees only)">%2$s</textarea>',
            /*$1%s*/ $item['name'],
            /*$1%s*/ ( isset( $item['desc'] ) ) ? $item['desc'] : ''
        );
    }
    
    function column_location($item){
        $return = '<select name="fee_location['.$this->fieldsInc.']">';
        $zones = be_get_zones();
        foreach($zones as $val) {
            $return .= '<option value="'.$val['zone_id'].'"';
            if($item['location'] == $val['zone_id']) $return .= ' selected="selected"';
            $return .= '>'.$val['zone_title'].'</option>';
        }
        $return .= '</select>';

        return $return;
    }

    function column_conditions($item){
        // create conditional type select box
        $options = array(
            'all' => __('All of the following','bolder-fees-woo'),
            'any' => __('Any of the following','bolder-fees-woo'),
            'none' => __('None of the following','bolder-fees-woo'),
            );
        $return = '<select name="condition_type['.$this->fieldsInc.']">';
        foreach ($options as $key => $value)
            $return .= '<option value="'.$key.'"'.selected($item['condition_type'], $key, false).'>'.$value.'</option>';
        $return .= '</select>';

        // create div and existing list for conditional statements
        $return .= '<div class="conditional-statements">';
        if( count( $item['conditions'] ) ) {
            $conditionClass = new Bolder_Fees_Conditions();
            $conditions = $conditionClass->bolder_fees_conds;
            foreach ($item['conditions'] as $value) {
                $return .= '<div><select name="cond_statement['.$this->fieldsInc.'][]">';
                foreach ($conditions as $key => $cond) 
                    $return .= '<option value="'.$key.'"'.selected($value, $key, false).'>'.$cond['name'].'</option>';
                $return .= '</select> <div class="delete-cond"></div></div>';
            }
        }
        $return .= '</div>';

        // create link for additional condition name boxes
        $return .= '<a href="#" class="more-conditional-statements">+ Additional Statements</a>';

        return $return;
    }
    
    function column_tax_class($item){
    	$tax_classes = WC_Tax::get_tax_classes();
        $return = '<select name="tax_class['.$this->fieldsInc.']"><option value="">Standard</option>';
        foreach( $tax_classes as $class ) {
            $return .= "<option";
            if($item['tax_class'] == $class) $return .= ' selected="selected"';
            $return .= '>'.$class.'</option>';
        }
        $return .= '</select>';

        return $return;
    }


    /** ************************************************************************
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s['.$this->fieldsInc.']" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['fee_id']                //The value of the checkbox should be the record's id
        );
    }
    
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'name'     => __('Name','bolder-fees-woo').' <a class="tips" data-tip="'.__('This controls the fee\'s title which the user sees during checkout','bolder-fees-woo').'">[?]</a>',
            'location'    => __('Location','bolder-fees-woo').' <a class="tips" data-tip="'.__('Fees will only apply to customers in the specified region','bolder-fees-woo').'">[?]</a>',
            'cost'    => __('Cost','bolder-fees-woo').' <a class="tips" data-tip="'.__('Flat fee to charge the customer','bolder-fees-woo').'">[?]</a>',
            'cost_percent'    => __('Cost %','bolder-fees-woo').' <a class="tips" data-tip="'.__('Fee based on a percentage of the customer\'s subtotal','bolder-fees-woo').'">[?]</a>',
            'conditions'    => __('Conditions','bolder-fees-woo').' <a class="tips" data-tip="'.__('Use the table below to setup conditions. Select which ones apply here','bolder-fees-woo').'">[?]</a>',
            'optional'    => "<p style=\"text-align:center;margin:0;\">".__('Optional','bolder-fees-woo').' <a class="tips" data-tip="'.__('When checked, customers can choose to pay or skip this fee','bolder-fees-woo').'">[?]</a></p>',
            'coupons'    => "<p style=\"text-align:center;margin:0;\">".__('Coupons','bolder-fees-woo').' <a class="tips" data-tip="'.__('When checked, percentage fees are calculated after coupons','bolder-fees-woo').'">[?]</a></p>',
            'shipping'    => "<p style=\"text-align:center;margin:0;\">".__('Shipping','bolder-fees-woo').' <a class="tips" data-tip="'.__('When checked, shipping will be included in the cart\'s subtotal','bolder-fees-woo').'">[?]</a></p>',
            'per_item'    => "<p style=\"text-align:center;margin:0;\">".__('Per Item','bolder-fees-woo').' <a class="tips" data-tip="'.__('When checked, fee will be added for every qualifying item in the cart','bolder-fees-woo').'">[?]</a></p>',
            'taxable'    => "<p style=\"text-align:center;margin:0;\">".__('Taxable','bolder-fees-woo').' <a class="tips" data-tip="'.__('When checked, tax will be applied to this fee','bolder-fees-woo').'">[?]</a></p>',
            'tax_class'    => __('Tax Class','bolder-fees-woo').' <a class="tips" data-tip="'.__('If the fee is marked taxable, this tax class will be applied','bolder-fees-woo').'">[?]</a>',
            'status'  => "<p style=\"text-align:center;margin:0;\">".__('Status','bolder-fees-woo').' <a class="tips" data-tip="'.__('Click the image to enable/disable the associated fee','bolder-fees-woo').'">[?]</a></p>',
        );
        return $columns;
    }
    
    function prepare_items() {
    global $wpdb, $_wp_column_headers;
    $screen = get_current_screen();

    /* -- Preparing your query -- */
        $data = (isset($this->bolder_fees) && is_array($this->bolder_fees)) ? array_filter( (array) $this->bolder_fees ) : array();
        $per_page = 9999;

    /* -- Register the Columns -- */
        $columns = $this->get_columns();
        $_wp_column_headers[$screen->id]=$columns;

    /* -- Fetch the items -- */

        $columns = $this->get_columns();
        $hidden = array();
        
        $this->_column_headers = array($columns, $hidden, false);
        
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        
    /* -- Register the pagination -- */

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );

    }

    function single_row( $item ) {
        static $row_class = '';
        $row_class = ( $row_class == '' ? ' class="alternate"' : '' );

        echo '<tr' . $row_class . '>';
        echo '<input type="hidden" name="fee_id['.$this->fieldsInc.']" value="'.$item['fee_id'].'" />';
        echo $this->single_row_columns( $item );
        echo '</tr>';
        $this->fieldsInc++;
    }


    static function tt_render_list_page(){
        global $woocommerce;
        
        //Create an instance of our package class...
        $feeListTable = new Bolder_Fees_Table();
        $condListTable = new Bolder_Fees_Conditions_List();
        //Fetch, prepare, sort, and filter our data...
        $feeListTable->prepare_items();
        $condListTable->prepare_items();

        // display message if shipping zones field is empty
        if( ! get_option( 'be_woocommerce_shipping_zones' ) )
            echo '<div class="error"><p>No regions have been setup under the Fee Regions tab above. The <strong>Location</strong> field is required, so without those regions your fees will not be able to save. Please setup one or more regions as needed before continuing.<br />
                <a href="' . admin_url( 'admin.php?page=wc-settings&tab=fee-regions' ) . '" class="button">Setup Regions</a></p></div>';
        ?>
        <div class="wrap woocommerce">
            
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php _e( 'Bolder Fees', 'bolder-fees-woo'); ?></h2>

            <h3><?php _e( 'Fees Table', 'bolder-fees-woo'); ?></h3>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'true') : ?>
            <div class="error"><p><strong>However, not all of your fees were saved!</strong><br />You must supply a name, location, and cost in order for the fee to be saved. Please review your list below and re-enter the missing fees.</p></div>
            <?php endif; ?>

            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="fees-filter" method="post" action="">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <!-- Now we can render the completed list table -->
                <?php $feeListTable->display() ?>
                <?php wp_nonce_field( 'woocommerce-settings', '_wpnonce', true, true ); ?>
                <p class="submit"><input name="save" class="button-primary" type="submit" value="Save changes" /></p>

                <h3><?php _e( 'Conditions for Fees', 'bolder-fees-woo'); ?></h3>
                <?php $condListTable->display() ?>
                <?php wp_nonce_field( 'woocommerce-settings', '_wpnonce', true, true ); ?>
                <p class="submit"><input name="save" class="button-primary" type="submit" value="Save changes" /></p>

<?php 
    $addSett = get_option( 'woocommerce_be_bolder_fees_additional' );
    $exclude_virtual = ( isset( $addSett['exclude-virtual'] ) ) ? $addSett['exclude-virtual'] : '';
    $round_fee_up = ( isset( $addSett['round-fee-up'] ) ) ? $addSett['round-fee-up'] : '';
    $combine_rates = ( isset( $addSett['combine-rates'] ) ) ? $addSett['combine-rates'] : '';
    $combine_rates_title = ( isset( $addSett['combine-rates-title'] ) ) ? $addSett['combine-rates-title'] : '';
?>

                <h3><?php _e( 'Additional Settings', 'bolder-fees-woo'); ?></h3>
                <table class="form-table">
                <tr valign="top">
                <th scope="row" class="titledesc"><?php _e('Exclude Virtual Products from Subtotal', 'bolder-fees-woo'); ?></th>
                <td class="forminp">
                <fieldset><legend class="screen-reader-text"><span><?php _e('Exclude Virtual Products', 'bolder-fees-woo'); ?></span></legend>
                <label for="exclude-virtual-prod"><input name="exclude-virtual-prod" id="exclude-virtual-prod" type="checkbox" value="1"  <?php if( $exclude_virtual ) : ?>checked='checked'<?php endif; ?> />
                    <?php _e('Calculate the subtotal based on shipped items only', 'bolder-fees-woo'); ?></label><br />
                </fieldset></td>
                </tr>
                <tr valign="top">
                <th scope="row" class="titledesc"><?php _e('Round Fee Up', 'bolder-fees-woo'); ?></th>
                <td class="forminp">
                <fieldset><legend class="screen-reader-text"><span><?php _e('Round Fee Up', 'bolder-fees-woo'); ?></span></legend>
                <label for="round-fee-up"><input name="round-fee-up" id="round-fee-up" type="checkbox" value="1"  <?php if( $round_fee_up ) : ?>checked='checked'<?php endif; ?> />
                    <?php _e('Round fee price up to the next whole number', 'bolder-fees-woo') . " (i.e. $5.29 = $6.00)"; ?></label><br />
                </fieldset></td>
                </tr>
                <tr valign="top">
                <th scope="row" class="titledesc"><?php _e('Combine Eligible Fees into One Rate', 'bolder-fees-woo'); ?></th>
                <td class="forminp">
                <fieldset><legend class="screen-reader-text"><span><?php _e('Combine Eligible Fees into One Rate', 'bolder-fees-woo'); ?></span></legend>
                <label for="combine-rates"><input name="combine-rates" id="combine-rates" type="checkbox" value="1"  <?php if( $combine_rates ) : ?>checked='checked'<?php endif; ?> />
                    <?php _e('Optional fees not applicable. These will appear separately at all times.', 'bolder-fees-woo'); ?></label><br />
                </fieldset></td>
                </tr>
                <tr valign="top">
                <th scope="row" class="titledesc"><?php _e('Title for Combined Fees', 'bolder-fees-woo'); ?></th>
                <td class="forminp">
                <fieldset><legend class="screen-reader-text"><span><?php _e('Title for Combined Fees', 'bolder-fees-woo'); ?></span></legend>
                <label for="combine-rates-title"><input name="combine-rates-title" id="combine-rates-title" type="input" value="<?php echo $combine_rates_title; ?>" />
                    <?php _e('If combined fees are enabled, this is the title that will appear next to it. (Default: Fee)', 'bolder-fees-woo'); ?></label><br />
                </fieldset></td>
                </tr>
                </table>
                <?php wp_nonce_field( 'woocommerce-settings', '_wpnonce', true, true ); ?>
        </div>
        <?php
        //require_once('inc/js.php');
    }

    static function save_fees_table() {
        global $woocommerce;

        $fee_table = $cond_table = $fee_table_fee = $fee_table_name = $fee_table_desc = $fee_table_location = $fee_table_cost = $fee_table_cost_percent = $fee_table_condition_type = $fee_table_conditions = $fee_table_optional = $fee_table_shipping = $fee_table_taxable = $fee_table_tax_class = $fee_table_order = $cond_table_name = $cond_table_clause = $cond_table_value = array();
        $varFees = array('fee_name' => 'fee_table_name', 'fee_desc' => 'fee_table_desc', 'fee_location' => 'fee_table_location', 'cost' => 'fee_table_cost', 'cost_percent' => 'fee_table_cost_percent', 'condition_type' => 'fee_table_condition_type', 'optional' => 'fee_table_optional', 'shipping' => 'fee_table_shipping', 'coupons' => 'fee_table_coupons', 'per_item' => 'fee_table_item', 'taxable' => 'fee_table_taxable', 'tax_class' => 'fee_table_tax_class', 'enabled' => 'fee_table_enabled', 'cond_name' => 'cond_table_name', 'cond_clause' => 'cond_table_clause', 'cond_value' => 'cond_table_value');
        $clean_function = ( function_exists( 'wc_clean' ) ) ? 'wc_clean' : 'woocommerce_clean';
        foreach( $varFees as $key => $fee ) {
            if ( isset( $_POST[ $key ] ) )  $$fee = array_map( $clean_function, $_POST[ $key ] );
        }
        if( isset( $_POST['cond_statement'] ) && count( $_POST['cond_statement'] ) ) {
            foreach ($_POST['cond_statement'] as $key => $stats) {
                foreach ($stats as $skey => $stat) {
                    $fee_table_conditions[ $key ][ $skey ] = sanitize_title( $stat );
                }
            }
        }
        $n = 1;
        foreach ($fee_table_name as $key => $value) {
            $fee_table_order[ $key ] = $n;
            $n++;
        }

        // Get max key
        $values = $fee_table_name;
        ksort( $values );
        $value = end( $values );
        $key = key( $values );

        $SUCCESS = true;
        for ( $i = 0; $i <= $key; $i++ ) {
            if ( isset( $fee_table_name[$i] ) && isset( $fee_table_location[$i] ) && ( isset( $fee_table_cost[$i] ) || isset( $fee_table_cost_percent[$i] ) ) ) {

                if ( !isset($fee_table_cost[$i]) || $fee_table_cost[$i] == '' || !is_numeric( $fee_table_cost[$i] ) ) $fee_table_cost[$i] = '0';
                if ( !isset($fee_table_cost_percent[$i]) || $fee_table_cost_percent[$i] == '' || !is_numeric( $fee_table_cost_percent[$i] ) ) $fee_table_cost_percent[$i] = '0';

                if( WOOCOMMERCE_VERSION >= 2.1 )
                    $fee_table_cost[ $i ] = wc_format_decimal( $fee_table_cost[ $i ], get_option( 'woocommerce_price_num_decimals' ), false );
                else
                    $fee_table_cost[ $i ] = woocommerce_format_total( $fee_table_cost[ $i ] );

                $fee_table_enabled[ $i ] = ( isset( $fee_table_enabled[ $i ] ) ) ? (int) $fee_table_enabled[ $i ] : 1;

                // regroup all fees into a new array
                $m = $fee_table_order[ $i ];
                
                // Register title with WPML
                if( function_exists( 'icl_register_string' ) )
                    icl_register_string( 'bolder-fees-woo', 'fee-title-' . sanitize_title( $fee_table_name[ $i ] ), $fee_table_name[ $i ] );
        
                $fee_table[ $fee_table_order[ $i ] ] = array(
                    'key' => sanitize_title( $fee_table_name[ $i ]."-".$i ),
                    'fee_id' => sanitize_title( $fee_table_name[ $i ] ),
                    'name' => $fee_table_name[ $i ],
                    'desc' => $fee_table_desc[ $i ],
                    'location' => $fee_table_location[ $i ],
                    'cost' => $fee_table_cost[ $i ],
                    'cost_percent' => $fee_table_cost_percent[ $i ],
                    'condition_type' => $fee_table_condition_type[ $i ],
                    'conditions' => ( isset( $fee_table_conditions[ $i ] ) && is_array( $fee_table_conditions[ $i ] ) ) ? $fee_table_conditions[ $i ] : array(),
                    'optional' => ( isset( $fee_table_optional[ $i ] ) ) ? $fee_table_optional[ $i ] : false,
                    'shipping' => ( isset( $fee_table_shipping[ $i ] ) ) ? $fee_table_shipping[ $i ] : false,
                    'coupons' => ( isset( $fee_table_coupons[ $i ] ) ) ? $fee_table_coupons[ $i ] : false,
                    'per_item' => ( isset( $fee_table_item[ $i ] ) ) ? $fee_table_item[ $i ] : false,
                    'taxable' => ( isset( $fee_table_taxable[ $i ] ) ) ? $fee_table_taxable[ $i ] : false,
                    'tax_class' => $fee_table_tax_class[ $i ],
                    'status'  => $fee_table_enabled[ $i ],
                    'order' => $fee_table_order[ $i ],
                );
            } else $SUCCESS = false;
        }
        ksort($fee_table);

        // Get max key
        $values = $cond_table_name;
        ksort( $values );
        $value = end( $values );
        $key = key( $values );

        if( isset( $cond_table_name ) && count( $cond_table_name ) ) {
            for ( $i = 0; $i <= $key; $i++ ) {
                if ( isset( $cond_table_name[ $i ] ) && isset( $cond_table_clause[ $i ] ) ) {

                    // regroup all fees into a new array
                    $cond_table[sanitize_title( $cond_table_name[ $i ]."-".$i )] = array(
                        'cond_id' => sanitize_title( $cond_table_name[ $i ]."-".$i ),
                        'name' => $cond_table_name[ $i ],
                        'clause' => $cond_table_clause[ $i ],
                        'value' => ( isset( $cond_table_value[ $i ] ) ) ? sanitize_text_field( $cond_table_value[ $i ] ) : '',
                    );
                } else $SUCCESS = false;
            }
        }

        // regroup all fees into a new array
        $additional_settings = array(
        	'exclude-virtual'		=> ( isset( $_POST['exclude-virtual-prod'] ) ) ? $_POST['exclude-virtual-prod'] : false,
            'round-fee-up'          => ( isset( $_POST['round-fee-up'] ) ) ? $_POST['round-fee-up'] : false,
            'combine-rates'         => ( isset( $_POST['combine-rates'] ) ) ? $_POST['combine-rates'] : false,
            'combine-rates-title'   => sanitize_text_field( $_POST['combine-rates-title'] ),
        );

        //save rates in database
        update_option('woocommerce_be_bolder_fees', $fee_table);
        update_option('woocommerce_be_bolder_fees_conditions', $cond_table);
        update_option('woocommerce_be_bolder_fees_additional', $additional_settings);

    }

    static function be_enable_fee_link() {
        global $wpdb;

        $GLOBALS['hook_suffix'] = 'wp_ajax_woocommerce_';

        if ( ! is_admin() ) wp_die( __('You do not have sufficient permissions to access this page.') );
        if ( ! current_user_can('edit_posts') ) wp_die( __('You do not have sufficient permissions to access this page.') );
        if ( ! check_admin_referer('woocommerce-settings')) wp_die( __('You have taken too long. Please go back and retry.') );

        $fee_id = (isset( $_GET['fee_id'] ) ) ? sanitize_title($_GET['fee_id']) : 0;
        if (!$fee_id)  wp_die( __('A proper fee ID number was not supplied.') );

        $newInstance = new Bolder_Fees_Table();
        $fee_table_copy = $newInstance->bolder_fees;
        if(isset($fee_table_copy[$fee_id]) && $fee_verify = $fee_table_copy[$fee_id] != 0) {
            $fee_enabled = $fee_table_copy[$fee_id]['status'];

            if ( $fee_enabled == '1' ) {
                $fee_table_copy[$fee_id]['status'] = 0;
            } else
                $fee_table_copy[$fee_id]['status'] = 1;
        }
        update_option( 'woocommerce_be_bolder_fees', $fee_table_copy );
        wp_safe_redirect( remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), wp_get_referer() ) );
    }
}
