<?php // Display javascript for controlling the tables & buttons

$conditionsJS = $zoneOpsJS = $taxClassJS = $clausesJS = $shipClassesJS = $productsJS = $categoriesJS = $shipMethodsJS = $paymentJS = $rolesJS = "";

//get select box variables
$conditionClass = new Bolder_Fees_Conditions();
$conditions = $conditionClass->bolder_fees_conds;
if( count( $conditions ) )
    foreach ($conditions as $key => $cond) 
        $conditionsJS .= '<option value="'.$key.'">'.$cond['name'].'</option>';

$conditionClass = new Bolder_Fees_Conditions();
$clauses = $conditionClass->clauses;
if( count( $clauses ) )
    foreach( $clauses as $key => $clause )
        $clausesJS .= '<option value="'.$key.'">'.$clause.'</option>';

$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option('woocommerce_tax_classes' ) ) ) );
$taxClassJS = '<option value="standard">Standard</option>';
if( count( $tax_classes ) )
    foreach( $tax_classes as $class )
        $taxClassJS .= "<option>".$class."</option>";

$zones = be_get_zones();
if( count( $zones ) )
    foreach($zones as $val) 
        $zoneOpsJS .= "<option value=\"".$val['zone_id']."\">".$val['zone_title']."</option>";

$products = get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'post_status' => 'publish', 'no_found_rows' => true, 'orderby' => 'title', 'order' => 'ASC' ) );
if( $products )
    foreach ($products as $prod)
        $productsJS .= '<option value="'.$prod->ID.'">'.$prod->post_title.'</option>';

$categories = get_terms('product_cat', array('hide_empty' => 0, 'orderby' => 'ASC'));
if( $categories )
    foreach ($categories as $cat)
        $categoriesJS .= '<option value="'.$cat->term_id.'">'.$cat->name.'</option>';

$shippingClasses = $woocommerce->shipping->get_shipping_classes();
if( $shippingClasses )
    foreach( $shippingClasses as $key => $class )
        $shipClassesJS .= '<option value="'.$class->term_id.'">'.$class->name.'</option>';

$shippingMethods = $woocommerce->shipping->get_shipping_methods();
if( $shippingMethods )
    foreach( $shippingMethods as $key => $method ) {
        $option_title = ( $method->title != '' ) ? $method->title : $method->method_title;
        $shipMethodsJS .= '<option value="'.$method->id.'">'.$option_title.'</option>';
    }

$paymentGateways = $woocommerce->payment_gateways();
if( $paymentGateways )
    foreach ($paymentGateways->payment_gateways as $method)
        $paymentJS .= '<option value="'.$method->id.'">'.$method->title.'</option>';

$roles = get_editable_roles();
foreach ($roles as $rid => $role)
    $rolesJS .= '<option value="'.$rid.'">'.$role['name'].'</option>';
?>
<script>
    // add new row
    jQuery('.tablenav a.add.fee').live('click', function(){
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
        if(selVal == 'includes-class' || selVal == 'excludes-class')
            valueBox.html('<select name="cond_value[]"><?php echo addslashes($shipClassesJS); ?></select>');
        else if(selVal == 'includes-product' || selVal == 'excludes-product')
            valueBox.html('<select name="cond_value[]"><?php echo addslashes($productsJS); ?></select>');
        else if(selVal == 'includes-variation' || selVal == 'excludes-variation')
            valueBox.html('<input type="text" name="cond_value[]" /><br /><small><?php _e( "Comma separated list of variation ID numbers", "bolder-fees-woo" ); ?></small>');
        else if(selVal == 'includes-category' || selVal == 'excludes-category')
            valueBox.html('<select name="cond_value[]"><?php echo addslashes($categoriesJS); ?></select>');
        else if(selVal == 'shipping-is' || selVal == 'shipping-not')
            valueBox.html('<select name="cond_value[]"><?php echo addslashes($shipMethodsJS); ?></select>');
        else if(selVal == 'includes-virtual' || selVal == 'includes-download' || selVal == 'excludes-virtual' || selVal == 'excludes-download')
            valueBox.html('<i><?php _e('Not Applicable', 'bolder-fees-woo'); ?></i>');
        else if(selVal == 'payment-is' || selVal == 'payment-not')
            valueBox.html('<select name="cond_value[]"><?php echo addslashes($paymentJS); ?></select>');
        else if(selVal == 'date-before' || selVal == 'date-after')
            valueBox.html('<input type="text" name="cond_value[]" class="bolder_fees_datepicker" />').ready(function(){ jQuery( ".bolder_fees_datepicker" ).datepicker({ showAnim: "slideDown" }); });
        else if(selVal == 'role-is' || selVal == 'role-not')
            valueBox.html('<select name="cond_value[]"><?php echo addslashes($rolesJS); ?></select>');
        else
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
</script>