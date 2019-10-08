<?php

/*************************** LOAD THE BASE CLASS ********************************/
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/************************** CREATE A PACKAGE CLASS ******************************/
class Bolder_Fees_Conditions_List extends WP_List_Table {

    public $conditions;
    public $clauses = array();
    public $bolder_fees_conds = array();
    public $cond_values = array();
    public $js_values = array();
    
    function __construct(){
        global $status, $page, $woocommerce;

        $this->conditions = new Bolder_Fees_Conditions();
        $this->clauses = $this->conditions->clauses;
        $this->bolder_fees_conds = $this->conditions->bolder_fees_conds;

        add_action( 'admin_footer', array( $this, 'add_settings_js' ) );

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'condition', 
            'plural'    => 'conditions',
            'ajax'      => false
        ) );

    }

    function extra_tablenav( $which ) {
        echo'<a href="#" class="add button cond" style="display:inline-block;">'.__( '+ Add Condition', 'bolder-fees-woo' ).'</a>';

        if ( $which == "top" ){
            echo ' All changes must be saved before they can appear in the conditions column ';
        }
        if ( $which == "bottom" ){
            echo' Drag the table rows to sort your conditions. ';
        }

        echo '<a href="#" class="remove button cond" style="display:inline-block;float:right;">'.__( 'Delete Selected Conditions', 'bolder-fees-woo' ).'</a>';
    }
    function get_table_classes() {
        return array( 'widefat', $this->_args['plural'] );
    }
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['cond_id']                //The value of the checkbox should be the record's id
        );
    }
    
    function column_name($item){
        //Return the title contents
        return sprintf('<input type="text" name="cond_name[]" value="%1$s" />',
            /*$1%s*/ $item['name']
        );
    }
    
    function column_clause($item){
        $return = '<select name="cond_clause[]" class="clause-select">';
        foreach($this->clauses as $key => $val) {
            $return .= '<option value="'.$key.'"';
            if($item['clause'] == $key) $return .= ' selected="selected"';
            $return .= '>'.$val.'</option>';
        }
        $return .= '</select>';

        return $return;
    }
    
    function column_value($item){
        global $woocommerce;
        
        //setup return value
        $return = "";

        if($item['clause'] == 'includes-class' || $item['clause'] == 'excludes-class') {

            $return = '<select name="cond_value[]">';
            foreach( $this->get_classes() as $key => $class ){
                $return .= '<option value="'.$class->term_id.'"';
                if($item['value'] == $class->term_id) $return .= ' selected="selected"';
                $return .= '>'.$class->name.'</option>';
            }
            $return .= '</select>';
        }
        elseif($item['clause'] == 'includes-product' || $item['clause'] == 'excludes-product') {

            $return = '<select name="cond_value[]">';
            foreach( $this->get_products() as $key => $prod ){
                $return .= '<option value="'.$prod->ID.'"';
                if($item['value'] == $prod->ID) $return .= ' selected="selected"';
                $return .= '>'.$prod->post_title.'</option>';
            }
            $return .= '</select>';
        }
        elseif($item['clause'] == 'includes-category' || $item['clause'] == 'excludes-category') {

            $return = '<select name="cond_value[]">';
            foreach( $this->get_categories() as $cat ){
                $return .= '<option value="'.$cat->term_id.'"';
                if($item['value'] == $cat->term_id) $return .= ' selected="selected"';
                $return .= '>'.$cat->name.'</option>';
            }
            $return .= '</select>';
        }
        elseif($item['clause'] == 'shipping-is' || $item['clause'] == 'shipping-not') {

            $return = '<select name="cond_value[]">';
            foreach( $this->get_shipping() as $key => $method ){
                $option_title = ( $method->title != '' ) ? $method->title : $method->method_title;

                $return .= '<option value="'.$method->id.'"';
                if($item['value'] == $method->id) $return .= ' selected="selected"';
                $return .= '>'.$option_title.'</option>';
            }
            $return .= '</select>';
        }
        elseif($item['clause'] == 'payment-is' || $item['clause'] == 'payment-not') {

            $return = '<select name="cond_value[]">';
            foreach( $this->get_payments() as $method ){
                $return .= '<option value="'.$method->id.'"';
                if($item['value'] == $method->id) $return .= ' selected="selected"';
                $return .= '>'.$method->title.'</option>';
            }
            $return .= '</select>';
        }
        elseif($item['clause'] == 'date-before' || $item['clause'] == 'date-after') {
            $return .= '<input type="text" name="cond_value[]" class="bolder_fees_datepicker" value="'.$item['value'].'" />';
        }
        elseif($item['clause'] == 'includes-virtual' || $item['clause'] == 'excludes-virtual' || $item['clause'] == 'includes-download' || $item['clause'] == 'excludes-download' || $item['clause'] == 'includes-backorder' || $item['clause'] == 'excludes-backorder') {
            $return .= "<i>".__('Not Applicable','bolder-fees-woo')."</i>";
        }
        elseif($item['clause'] == 'role-is' || $item['clause'] == 'role-not') {
            
            $return = '<select name="cond_value[]">';
            foreach( $this->get_roles() as $rid => $role ){
                $return .= '<option value="'.$rid.'"';
                if($item['value'] == $rid) $return .= ' selected="selected"';
                $return .= '>'.$role['name'].'</option>';
            }
            $return .= '</select>';
        } else {
            $return .= '<input type="text" name="cond_value[]" value="'.$item['value'].'" />';
            if($item['clause'] == 'includes-variation' || $item['clause'] == 'excludes-variation') $return .= '<br /><small>' . __( "Comma separated list of variation ID numbers", "bolder-fees-woo") . '</small>';
        }

        return $return;
    }
    
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'name'     => __('Short Name','bolder-fees-woo').' <a class="tips" data-tip="'.__('Brief way to identify your condition later','bolder-fees-woo').'">[?]</a>',
            'clause'    => __('Clause','bolder-fees-woo').' <a class="tips" data-tip="'.__('The conditional statement','bolder-fees-woo').'">[?]</a>',
            'value'    => __('Value','bolder-fees-woo'),
        );
        return $columns;
    }
    
    function prepare_items() {
    global $wpdb, $_wp_column_headers;
    $screen = get_current_screen();

    /* -- Preparing your query -- */
        $data = (isset($this->bolder_fees_conds) && is_array($this->bolder_fees_conds)) ? array_filter( (array) $this->bolder_fees_conds ) : array();
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
        echo '<input type="hidden" name="fee_id[]" value="'.$item['cond_id'].'" />';
        echo $this->single_row_columns( $item );
        echo '</tr>';
    }

    function add_settings_js() {
        global $woocommerce;

        $zones = be_get_zones();
        $zoneOpsJS = $clausesJS = $conditionsJS = '';
        if( is_array( $zones ) && count( $zones ) )
            foreach($zones as $val) 
                $zoneOpsJS .= "<option value=\"".$val['zone_id']."\">".$val['zone_title']."</option>";

        $clauses = $this->conditions->clauses;
        if( count( $clauses ) )
            foreach( $clauses as $key => $clause )
                $clausesJS .= '<option value="'.$key.'">'.$clause.'</option>';

        $conditions = $this->conditions->bolder_fees_conds;
        if( count( $conditions ) )
            foreach ($conditions as $key => $cond) 
                $conditionsJS .= '<option value="'.$key.'">'.$cond['name'].'</option>';

        $tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option('woocommerce_tax_classes' ) ) ) );
        $taxClassJS = '<option value="standard">Standard</option>';
        if( count( $tax_classes ) )
            foreach( $tax_classes as $class )
                $taxClassJS .= "<option>".$class."</option>";
?>
<script type='text/javascript'>
jQuery( document ).ready(function() {
    // add new row
    jQuery(document).on('click', '.tablenav a.add.fee', function(e) {
        e.preventDefault();
        console.log('yes');
        var size = jQuery('.wp-list-table.fees tbody#the-list').size();
        var tr = jQuery('tr.no-items');
        var row_class = '';
        if(size%2 != 0) row_class = ' class="alternate"';
        if(tr.length) tr.remove();
        var rowID = jQuery('.wp-list-table.fees tbody#the-list tr:last').index() + 1;
        jQuery('<tr'+row_class+'>\
            <th scope="row" class="check-column"><input type="checkbox" name="fee['+rowID+']" /></th>\
            <td class="name column-name"><input type="text" name="fee_name['+rowID+']" /></td>\
            <td class="location column-location"><select name="fee_location['+rowID+']"><?php echo addslashes($zoneOpsJS); ?></select></td>\
            <td class="cost column-cost"><input type="text" name="cost['+rowID+']" size="10" /></td>\
            <td class="cost_percent column-cost_percent"><input type="text" name="cost_percent['+rowID+']" size="10" /></td>\
            <td class="conditions column-conditions"><select name="condition_type['+rowID+']"><option value="all">All of the following</option><option value="any">Any of the following</option><option value="none">None of the following</option></select><div class="conditional-statements"></div><a href="#" class="more-conditional-statements">+ Additional Statements</a></td>\
            <td class="optional column-optional"><p style="text-align:center"><input type="checkbox" name="optional['+rowID+']" /></p></td>\
            <td class="coupons column-coupons"><p style="text-align:center"><input type="checkbox" name="coupons['+rowID+']" /></p></td>\
            <td class="shipping column-shipping"><p style="text-align:center"><input type="checkbox" name="shipping['+rowID+']" /></p></td>\
            <td class="per_item column-per_item"><p style="text-align:center"><input type="checkbox" name="per_item['+rowID+']" /></p></td>\
            <td class="taxable column-taxable"><p style="text-align:center"><input type="checkbox" name="taxable['+rowID+']" /></p></td>\
            <td class="tax_class column-tax_class"><select name="tax_class['+rowID+']"><?php echo addslashes($taxClassJS); ?></select>\
            <td class="status column-status"><p style="text-align:center"><img src="<?php echo plugins_url( "/inc/success.png", __FILE__ ); ?>" alt="yes" /></p></td>\
            </tr>').appendTo('.wp-list-table.fees tbody#the-list');

        return false;
    });

    // Remove row
    jQuery('.tablenav a.remove.fee').live('click', function(){
        var answer = confirm("<?php _e('Delete the selected fees?', 'woocommerce'); ?>")
        if (answer) {
            jQuery('.wp-list-table.fees tbody#the-list tr th.check-column input:checked').each(function(i, el){
                jQuery(el).closest('tr').remove();
            });
        }
        return false;
    });


    // add new row
    jQuery('.tablenav a.add.cond').live('click', function(){
        var size = jQuery('.wp-list-table.conditions tbody#the-list').size();
        var tr = jQuery('tr.no-items');
        var row_class = '';
        if(size%2 != 0) row_class = ' class="alternate"';
        if(tr.length) tr.remove();

        jQuery('<tr'+row_class+'>\
            <th scope="row" class="check-column"><input type="checkbox" name="cond[]" /></th>\
            <td class="name column-name"><input type="text" name="cond_name[]" /></td>\
            <td class="clause column-clause"><select name="cond_clause[]" class="clause-select"><?php echo addslashes($clausesJS); ?></select></td>\
            <td class="value column-value"><input type="text" name="cond_value[]" /></td>\
            </tr>').appendTo('.wp-list-table.conditions tbody#the-list');

        return false;
    });

    // Remove row
    jQuery('.tablenav a.remove.cond').live('click', function(){
        var answer = confirm("<?php _e('Delete the selected fees?', 'woocommerce'); ?>")
        if (answer) {
            jQuery('.wp-list-table.conditions tbody#the-list tr th.check-column input:checked').each(function(i, el){
                jQuery(el).closest('tr').remove();
            });
        }
        return false;
    });

    jQuery( ".clause-select" ).live('change', function() {
        var valueBox = jQuery(this).closest('td').next();
        var selVal = jQuery(this).val();
        if(selVal == 'includes-class' || selVal == 'excludes-class') {
<?php
            // retrieve HTML for select box options
            if( ! isset( $this->js_values[ 'classes' ] ) ) {
                $this->js_values[ 'classes' ] = '';
                foreach( $this->get_classes() as $key => $cond )
                    $this->js_values[ 'classes' ] .= '<option value="' . $cond->term_id . '">' . $cond->name . '</option>';
            }
?>
            valueBox.html('<select name="cond_value[]"><?php echo addslashes( $this->js_values[ 'classes' ] ); ?></select>');
        
        } else if(selVal == 'includes-product' || selVal == 'excludes-product') {
<?php
            // retrieve HTML for select box options
            if( ! isset( $this->js_values[ 'products' ] ) ) {
                $this->js_values[ 'products' ] = '';
                foreach( $this->get_products() as $key => $prod )
                    $this->js_values[ 'products' ] .= '<option value="' . $prod->ID . '">' . $prod->post_title . '</option>';
            }
?>
            valueBox.html('<select name="cond_value[]"><?php echo addslashes( $this->js_values[ 'products' ] ); ?></select>');
        
        } else if(selVal == 'includes-variation' || selVal == 'excludes-variation') {
            // retrieve HTML for select box options
            valueBox.html('<input type="text" name="cond_value[]" /><br /><small><?php _e( "Comma separated list of variation ID numbers", "bolder-fees-woo" ); ?></small>');
        
        } else if(selVal == 'includes-category' || selVal == 'excludes-category') {
<?php
            // retrieve HTML for select box options
            if( ! isset( $this->js_values[ 'categories' ] ) ) {
                $this->js_values[ 'categories' ] = '';
                foreach( $this->get_categories() as $key => $cond )
                    $this->js_values[ 'categories' ] .= '<option value="' . $cond->term_id . '">' . $cond->name . '</option>';
            }
?>
            valueBox.html('<select name="cond_value[]"><?php echo addslashes( $this->js_values[ 'categories' ] ); ?></select>');
        
        } else if(selVal == 'shipping-is' || selVal == 'shipping-not') {
<?php
            // retrieve HTML for select box options
            if( ! isset( $this->js_values[ 'shipping' ] ) ) {
                $this->js_values[ 'shipping' ] = '';
                foreach( $this->get_shipping() as $key => $method ) {
                    $option_title = ( $method->title != '' ) ? $method->title : $method->method_title;
                    $this->js_values[ 'shipping' ] .= '<option value="' . $method->id . '">' . $option_title . '</option>';
                }
            }
?>
            valueBox.html('<select name="cond_value[]"><?php echo addslashes( $this->js_values[ 'shipping' ] ); ?></select>');
        
        } else if(selVal == 'includes-virtual' || selVal == 'includes-download' || selVal == 'includes-backorder' || selVal == 'excludes-virtual' || selVal == 'excludes-download' || selVal == 'excludes-backorder') {
            // retrieve HTML for select box options
            valueBox.html('<i><?php _e('Not Applicable', 'bolder-fees-woo'); ?></i>');
        
        } else if(selVal == 'payment-is' || selVal == 'payment-not') {
<?php
            // retrieve HTML for select box options
            if( ! isset( $this->js_values[ 'payments' ] ) ) {
                $this->js_values[ 'payments' ] = '';
                foreach( $this->get_payments() as $key => $method )
                    $this->js_values[ 'payments' ] .= '<option value="' . $method->id . '">' . $method->title . '</option>';
            }
?>
            valueBox.html('<select name="cond_value[]"><?php echo addslashes( $this->js_values[ 'payments' ] ); ?></select>');
        
        } else if(selVal == 'date-before' || selVal == 'date-after') {
            // retrieve HTML for select box options
            valueBox.html('<input type="text" name="cond_value[]" class="bolder_fees_datepicker" />').ready(function(){ jQuery( ".bolder_fees_datepicker" ).datepicker({ showAnim: "slideDown" }); });
        
        } else if(selVal == 'role-is' || selVal == 'role-not') {
<?php
            // retrieve HTML for select box options
            if( ! isset( $this->js_values[ 'roles' ] ) ) {
                $this->js_values[ 'roles' ] = '';
                foreach( $this->get_roles() as $rid => $role )
                    $this->js_values[ 'roles' ] .= '<option value="'.$rid.'">'.$role['name'].'</option>';
            }
?>
            valueBox.html('<select name="cond_value[]"><?php echo addslashes( $this->js_values[ 'roles' ] ); ?></select>');
        
        } else
            valueBox.html('<input type="text" name="cond_value[]" />');
    });


    jQuery( ".more-conditional-statements" ).live('click', function() {
        var condBox = jQuery(this).closest('tr').find('.conditional-statements');
        var curBoxes = condBox.html();
        var rowID = jQuery(this).closest('tr').index();

        condBox.append('<div><select name="cond_statement['+rowID+'][]"><?php echo addslashes($conditionsJS); ?></select> <div class="delete-cond"></div></div>');

        return false;
    });

    jQuery(function() {
        jQuery( ".bolder_fees_datepicker" ).datepicker({ showAnim: "slideDown" });
    });

    jQuery( ".delete-cond" ).live('click', function() {
        jQuery(this).parent().remove();
    });

    jQuery(function() {
        var fixHelperModified = function(e, tr) {
            var originals = tr.children();
            var helper = tr.clone();
            helper.children().each(function(index)
            {
              jQuery(this).width(originals.eq(index).width())
            });
            return helper;
        };
        jQuery("#the-list").sortable({
            helper: fixHelperModified
        })/*.disableSelection()*/;
    });
});
</script>
<?php
    }

    // retrieve array of classes
    function get_classes() {
        global $woocommerce;
        
        if( ! isset( $this->cond_values[ 'classes' ] ) )
            $this->cond_values[ 'classes' ] = $woocommerce->shipping->get_shipping_classes();

        return $this->cond_values[ 'classes' ];
    }

    // retrieve array of products
    function get_products() {
        
        if( ! isset( $this->cond_values[ 'products' ] ) )
            $this->cond_values[ 'products' ] = get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'post_status' => 'publish', 'no_found_rows' => true, 'orderby' => 'title', 'order' => 'ASC' ) );

        return $this->cond_values[ 'products' ];
    }

    // retrieve array of categories
    function get_categories() {
        
        if( ! isset( $this->cond_values[ 'categories' ] ) )
            $this->cond_values[ 'categories' ] = get_terms('product_cat', array('hide_empty' => 0, 'orderby' => 'ASC'));

        return $this->cond_values[ 'categories' ];
    }

    // retrieve array of shipping
    function get_shipping() {
        global $woocommerce;
        
        if( ! isset( $this->cond_values[ 'shipping' ] ) )
            $this->cond_values[ 'shipping' ] = $woocommerce->shipping->get_shipping_methods();

        return $this->cond_values[ 'shipping' ];
    }

    // retrieve array of payments
    function get_payments() {
        global $woocommerce;
        
        if( ! isset( $this->cond_values[ 'payments' ] ) )
            $this->cond_values['payments'] = $woocommerce->payment_gateways()->payment_gateways;

        return $this->cond_values[ 'payments' ];
    }

    // retrieve array of roles
    function get_roles() {
        
        if( ! isset( $this->cond_values[ 'roles' ] ) )
            $this->cond_values[ 'roles' ] = get_editable_roles();

        return $this->cond_values[ 'roles' ];
    }

}
