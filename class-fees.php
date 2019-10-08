<?php

if( !class_exists( 'BE_Bolder_Fee' ) ) {

    class BE_Bolder_Fee {

        public $bolder_fees_conds = array();
        public $shipping_enabled = true;

        function __construct(){
            global $status, $page, $woocommerce;

            add_action('wp_ajax_bolder_update_shipping_fees', array( $this, 'add_optional_fee_updates'));
            add_action('wp_ajax_nopriv_bolder_update_shipping_fees', array( $this, 'add_optional_fee_updates'));
            add_action('wp_ajax_bolder_update_checkout_fees', array( $this, 'add_optional_fee_updates_checkout'));
            add_action('wp_ajax_nopriv_bolder_update_checkout_fees', array( $this, 'add_optional_fee_updates_checkout'));

            // update appropriate filter based on WC version
            if( version_compare( WC_VERSION, '3.2.0', ">=" ) ) {
                add_filter('woocommerce_calculated_total', array($this, 'update_fee_total_3_2'), 15, 2 );
            } else {
                add_action('woocommerce_calculate_totals', array( $this, 'update_fee_total'), 15, 1);
            }

            add_filter('woocommerce_cart_totals_fee_html', array($this, 'add_required_fee_desc'), 15, 2 );
            add_action('woocommerce_review_order_after_shipping', array( $this, 'add_new_fee_row' ) );
            add_action('woocommerce_cart_calculate_fees', array( $this, 'setup_fees' ), 10 );

            // determine when to process based on WC version
            if( version_compare( WC_VERSION, '3.2', ">=" ) )
                add_action('woocommerce_checkout_create_order', array( $this, 'process_optional_fees_3_2' ), 10, 2 );
            elseif( version_compare( WC_VERSION, '3.0', ">=" ) )
                add_action('woocommerce_checkout_update_user_meta', array( $this, 'process_optional_fees' ) );
            else
                add_action('woocommerce_new_order', array( $this, 'process_optional_fees' ) );

            $this->shipping_enabled = ( get_option('woocommerce_calc_shipping') == 'no' ) ? false : true;

            $this->bolder_fees_conds = get_option( 'woocommerce_be_bolder_fees_conditions' );
        }


        /**
         * setup_fees function.
         *
         * @access public
         * @return void
         */
        function setup_fees() {
            global $woocommerce, $cart_fee_addon;

            if ( is_admin() && ! defined( 'DOING_AJAX' ) )
                return;

            $fees = get_option( 'woocommerce_be_bolder_fees' );
            $addSett = get_option( 'woocommerce_be_bolder_fees_additional' );
            $exclude_virtual = ( isset( $addSett['exclude-virtual'] ) ) ? $addSett['exclude-virtual'] : false;
            $shipping_country   = WC()->customer->get_shipping_country();
            $shipping_state     = WC()->customer->get_shipping_state();
            $shipping_zipcode   = WC()->customer->get_shipping_postcode();
            $new_session = array();

            //get cart total from session
            $cart_items = $woocommerce->cart->get_cart();
            $cart_total = 0;
            if( count( $cart_items ) > 0 ) {
                foreach( $cart_items as $k => $cart_product ) {
                    if( is_array( $cart_product ) && isset( $cart_product['line_subtotal'] ) ) {
                        if( $exclude_virtual && $cart_product['data']->is_virtual() )
                            continue;
                        $cart_total = $cart_total + $cart_product['line_subtotal'];
                    } elseif( is_array( $cart_product ) ) {
                        foreach( $cart_product as $prod ) {
                            if( $exclude_virtual && $prod['data']->is_virtual() )
                                continue;
                            if( is_array( $prod ) && isset( $prod['line_subtotal'] ) )
                                $cart_total = $cart_total + $prod['line_subtotal'];
                        }
                    }
                }
            }

            if( is_array( $fees ) && count( $fees ) > 0 ) {
                $optional_included = false;
                $cart_fee_addon = 0;
                $mandatory_fees = array();

                // Get additional settings
                $round_fee_up = ( isset( $addSett['round-fee-up'] ) ) ? $addSett['round-fee-up'] : '';
                $combine_rates = ( isset( $addSett['combine-rates'] ) ) ? $addSett['combine-rates'] : '';
                $combine_rates_title = ( isset( $addSett['combine-rates-title'] ) ) ? $addSett['combine-rates-title'] : '';

                foreach ($fees as $key => $fee) {
                    // do not show fee if disabled
                    if( $fee['status'] == 0 )
                        continue;

                    // WPML translate settings (if applicable)
                    if( function_exists( 'icl_translate' ) ) {
                        $fee['name'] = icl_translate ( 'bolder-fees-woo', 'fee-title-' . sanitize_title( $fee['name'] ), $fee['name'] );
                    }

                    if( isset( $fee['per_item'] ) && $fee['per_item'] === 'on' ) :
                        if( $cart_items ) :
                            $n = 0;
                            foreach ($cart_items as $p_key => $p_val) {
                                if( be_in_zone( $fee['location'], $shipping_country, $shipping_state, $shipping_zipcode ) ) {
                                    // if conditions are set, check that they all apply before moving on to adding the fee
                                    $CONTINUE = $this->verify_conditions( $fee['conditions'], $fee['condition_type'], $p_val );

                                    // if fee meets all conditions (or none are set) continue on to calculate/add the fee to the cart
                                    if( $CONTINUE ) {
                                        $fee_name = 'bolder_fees_' . sanitize_title($fee['name']) . '_' . $n;
                                        $variation = get_the_title( $p_val['product_id'] );
                                        // Variation data
                                        if ( $p_val['data']->get_type() == 'variation' && is_array( $p_val['variation'] ) ) {
                                            $variation = $p_val['data']->get_name();
                                            //$variation = substr( $variation, 0, strrpos( $variation, ' &ndash; ' ) );
                                            //$variation = str_replace( strstr( $variation, ' &ndash; ', true ) . ' &ndash; ', '', $variation );
                                        }
                                        $prod_name = " (" . $variation . ")";
                                        $temp_price = ( function_exists( 'wc_get_price_excluding_tax' ) ) ? wc_get_price_excluding_tax( $p_val['data'] ) : $p_val['data']->get_price_excluding_tax();
                                        $fee_amount = $this->calculate_fee_amount( $temp_price, $fee['cost_percent'], $fee['cost'], $fee['shipping'], $fee['coupons'], $round_fee_up, $p_val['quantity'] );
                                        $fee_amount = wc_format_decimal( $fee_amount, '' );

                                        if( $fee['optional'] == 'on' ) {
                                            $optional_fee_tmp[ $fee_name ] = array( 'name' => $fee['name'] . $prod_name, 'desc' => $fee['desc'], 'fee' => $fee_amount, 'taxable' => $fee['taxable'], 'tax_class' => $fee['tax_class'], 'selected' => false );
                                            $optional_included = true;

                                            // calculate active optional fees from session data
                                            $session_fees = (isset($woocommerce->session->be_bolder_fees)) ? $woocommerce->session->be_bolder_fees : array();
                                            $fee_selected = false;
                                            if( isset( $session_fees[ $fee_name ] ) && $woocommerce->session->be_bolder_fees[ $fee_name ]['selected'] == "true") $fee_selected = true;

                                            $optional_tax = $this->calculate_optional_tax( $fee['taxable'], $fee_amount, $fee['tax_class'], $fee_selected );

                                            if( $fee_selected ) {
                                                $cart_fee_addon += $fee_amount;
                                                $optional_fee_tmp[ $fee_name ]['selected'] = true;
                                            }
                                            $new_session[ $fee_name ] = array('fee' => $fee_amount, 'name' => $fee['name'] . $prod_name, 'desc' => $fee['desc'], 'taxable' => $fee['taxable'], 'tax_class' => $fee['tax_class'], 'selected' => $fee_selected, 'tax_price' => $optional_tax);
                                        } else {
                                            if( !array_key_exists( $fee_name, $mandatory_fees ) )
                                                $mandatory_fees[ $fee_name ] = array( $fee['name'] . $prod_name, 'desc' => $fee['desc'], $fee_amount, $fee['taxable'], $fee['tax_class'] );
                                        }
                                    }
                                }
                                $n++;
                            }
                        endif;
                    else:
                        if( be_in_zone( $fee['location'], $shipping_country, $shipping_state, $shipping_zipcode ) ) {
                            // if conditions are set, check that they all apply before moving on to adding the fee
                            $CONTINUE = $this->verify_conditions( $fee['conditions'], $fee['condition_type'] );

                            // if fee meets all conditions (or none are set) continue on to calculate/add the fee to the cart
                            if( $CONTINUE ) {
                                $fee_name = 'bolder_fees_' . sanitize_title($fee['name']);
                                $fee_amount = $this->calculate_fee_amount( $cart_total, $fee['cost_percent'], $fee['cost'], $fee['shipping'], $fee['coupons'], $round_fee_up );
                                $fee_amount = wc_format_decimal( $fee_amount, '' );

                                if( $fee['optional'] == 'on' ) {
                                    $optional_fee_tmp[ $fee_name ] = array( 'name' => $fee['name'], 'desc' => $fee['desc'], 'fee' => $fee_amount, 'taxable' => $fee['taxable'], 'tax_class' => $fee['tax_class'], 'selected' => false );
                                    $optional_included = true;

                                    // calculate active optional fees from session data
                                    $session_fees = (isset($woocommerce->session->be_bolder_fees)) ? $woocommerce->session->be_bolder_fees : array();
                                    $fee_selected = false;
                                    $fee_name = $fee['name'];
                                    if(isset($session_fees[ $fee_name ] ) && $woocommerce->session->be_bolder_fees[ $fee_name ]['selected'] == "true") $fee_selected = true;

                                    $optional_tax = $this->calculate_optional_tax( $fee['taxable'], $fee_amount, $fee['tax_class'], $fee_selected );

                                    if($fee_selected) {
                                        $cart_fee_addon += $fee_amount;
                                        $optional_fee_tmp[ $fee_name ]['selected'] = true;
                                    }
                                    $new_session[ $fee_name ] = array('fee' => $fee_amount, 'name' => $fee_name, 'desc' => $fee['desc'], 'taxable' => $fee['taxable'], 'tax_class' => $fee['tax_class'], 'selected' => $fee_selected, 'tax_price' => $optional_tax);
                                } else {
                                    if( !array_key_exists( $fee_name, $mandatory_fees ) )
                                        $mandatory_fees[ $fee_name ] = array( $fee['name'], 'desc' => $fee['desc'], $fee_amount, $fee['taxable'], $fee['tax_class'] );
                                }
                            }
                        }
                    endif;
                }

                if( $combine_rates ) {
                    $fee_amount = 0;
                    foreach ($mandatory_fees as $value) {
                        $fee_amount += $value[1];
                        if ( $value[2] ) {
                            if( version_compare( WC_VERSION, '2.3.0', ">=" ) ) {
                                $tax_rates = WC_Tax::get_rates( $value[3] );
                                $fee_taxes = WC_Tax::calc_tax( $value[1], $tax_rates, false );
                            } else {
                                $tax_rates = $woocommerce->cart->tax->get_rates( $value[3] );
                                $fee_taxes = $woocommerce->cart->tax->calc_tax( $value[1], $tax_rates, false );
                            }
                            //$woocommerce->cart->fees[ $value[0] ]->tax = array_sum( $fee_taxes );
                            //$fee_amount += array_sum( $fee_taxes );
                        }
                    }
                    $combined_fee_title = ( $combine_rates_title == '' ) ? 'Fee' : $combine_rates_title;
                    if( $fee_amount != 0 ) $woocommerce->cart->add_fee( $combined_fee_title, $fee_amount );
                } else {
                    foreach ($mandatory_fees as $value) {
                        $taxable = ( $value[2] == 'on' ) ? true : false;
                        $woocommerce->cart->add_fee( $value[0], $value[1], $taxable, $value[3] );
                    }
                }
                $woocommerce->session->be_bolder_fees = $new_session;
                if($optional_included) {
                    if( $this->shipping_enabled && WC()->cart->needs_shipping() ) {
                        add_action( 'woocommerce_cart_totals_after_shipping', array( $this, 'add_new_fee_row' ), 10);
                        add_action( 'woocommerce_review_order_totals_after_shipping', array( $this, 'add_new_fee_row' ), 10);
                    } else {
                        add_action( 'woocommerce_cart_totals_before_order_total', array( $this, 'add_new_fee_row' ), 10);
                        add_action( 'woocommerce_review_order_before_order_total', array( $this, 'add_new_fee_row' ), 10);
                    }
                }
            }
        }


        /**
         * add_new_fee_row function.
         *
         * @access public
         * @return void
         */
        function add_new_fee_row() {
            global $woocommerce, $optional_fee_tmp;

            if( isset($woocommerce->session->be_bolder_fees) )
            $optional_fee_tmp = array_merge( $optional_fee_tmp, $woocommerce->session->be_bolder_fees);

            if( count($optional_fee_tmp) > 0 ) {
                foreach ($optional_fee_tmp as $key => $fee) {

                    $fee_amount = ( $woocommerce->cart->tax_display_cart == 'excl' ) ? $fee['fee'] : $fee['fee'] + $fee['tax_price'];

                    $session_fees = (isset($woocommerce->session->be_bolder_fees)) ? $woocommerce->session->be_bolder_fees : array();
                    echo '<tr class="fee fee-optional fee-' . sanitize_title($fee['name']) . '">
                        <th class="name">' . sanitize_text_field( $fee['name'] ) . '</th>
                        <td data-title="' . sanitize_text_field( $fee['name'] ) . '"><input type="checkbox" name="' . $key . '" value="' . $fee['fee'] . '" tax_amt="0" title="' . sanitize_text_field( $fee['name'] ) . '" class="fee_optional_box"';
                    if( $fee['selected'] ) echo ' checked="checked"';
                    echo ' /> ';

                        echo ( function_exists('wc_price') ) ? wc_price( $fee_amount ) : woocommerce_price( $fee_amount );
                        if( isset( $fee['desc'] ) & ! empty( $fee['desc'] ) ) echo '<p class="bolder_fees_desc">' . $fee['desc'] . '</p>';
                        echo '</td>
                    </tr>';
                }
            }
        }


        /**
         * add_required_fee_desc function.
         *
         * @access public
         * @return void
         */
        function add_required_fee_desc( $fee_html, $fee ) {
            // unable to do as of WooCommerce 3.6
            return $fee_html;
        }


        /**
         * add_optional_fee_updates function.
         *
         * @access public
         * @return void
         */
        function add_optional_fee_updates() {
            global $woocommerce, $optional_fee_tmp;

            check_ajax_referer( 'update-shipping-method', 'security' );
            if ( ! defined('WOOCOMMERCE_CART') ) define( 'WOOCOMMERCE_CART', true );

            $new_session = $woocommerce->session->be_bolder_fees;
            if(isset($_POST['fees']) && count($_POST['fees']) > 0) {
                foreach ($_POST['fees'] as $key => $value) {
                    $new_session[ $key ]['selected'] = $value['selected'];
                }
                $woocommerce->session->be_bolder_fees = $new_session;
            }

            $woocommerce->cart->calculate_totals();
            woocommerce_cart_totals();

            die();
        }


        /**
         * update_fee_total function.
         *
         * @access public
         * @return void
         */
        function update_fee_total( $cart ) { 
            global $woocommerce;

            $fees = $woocommerce->session->be_bolder_fees;
            if(is_array($fees)) {
                foreach ($fees as $fee) {
                    if($fee['selected'] == "true") {
                        $cart->fee_total += $fee['fee'];
                    }
                }
            }
        }


        /**
         * update_fee_total function.
         *
         * @access public
         * @return void
         */
        function update_fee_total_3_2( $total, $cart ) { 
            global $woocommerce;

            if ( defined('WOOCOMMERCE_CHECKOUT') && isset( $_POST['woocommerce_checkout_place_order'] ) ) return $total;

            $fees = $woocommerce->session->be_bolder_fees;
            if(is_array($fees)) {
                foreach ($fees as $fee) {
                    if($fee['selected'] == "true") {
                            $total += $fee['fee'] + $fee['tax_price'];
                    }
                }
            }

            return $total;
        }


        /**
         * add_optional_fee_updates function.
         *
         * @access public
         * @return void
         */
        function add_optional_fee_updates_checkout() {
            global $woocommerce;

            $new_session = $woocommerce->session->be_bolder_fees;

            if(isset($_POST['fees']) && count($_POST['fees']) > 0) {
                foreach ($_POST['fees'] as $key => $value) {
                    $new_session[ $key ]['selected'] = $value['selected'];
                }
                $woocommerce->session->be_bolder_fees = $new_session;
            }

            die();
        }


        /**
         * process_optional_fees function.
         *
         * @access public
         * @return void
         */
        function process_optional_fees_3_2( $order, $data ) {
            global $woocommerce;

/*
            if( !is_ajax() )
                remove_action( 'woocommerce_calculate_totals', array( $this, 'update_fee_total' ), 15 );
*/
            if(isset($_POST['fees'])) $GLOBALS['fees'] = $session_fees = $_POST['fees'];
                else $session_fees = (isset($woocommerce->session->be_bolder_fees)) ? $woocommerce->session->be_bolder_fees : array();

            if( count( $session_fees ) ) {
                $sessions_applied = array();
                foreach ($session_fees as $s_fee_key => $s_fee) {
                    if( $s_fee['selected'] == "true" ) {
                        // setup $fee
                        $s_taxable = ( $s_fee['taxable'] == 'on') ? true : false;
                        $fee = WC()->cart->fees_api()->add_fee( array(
                            'name'      => $s_fee['name'],
                            'amount'    => $s_fee['fee'],
                            'taxable'   => $s_taxable,
                            'tax_class' => $s_fee['tax_class'],
                            ));
                        $sessions_applied[] = sanitize_title( $s_fee['name'] );
                    }
                }

                new WC_Cart_Totals(WC()->cart);

                foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {

                    if( ! in_array( $fee_key, $sessions_applied ) ) continue;

                    // add fee to order
                    $item                 = new WC_Order_Item_Fee();
                    $item->legacy_fee     = $fee; // @deprecated For legacy actions.
                    $item->legacy_fee_key = sanitize_title( strtolower( $s_fee_key ) ); // @deprecated For legacy actions.
                    $item->set_props( array(
                        'name'      => $fee->name,
                        'tax_class' => $fee->taxable ? $fee->tax_class: 0,
                        'amount'    => (float) $fee->amount,
                        'total'     => $fee->total,
                        'total_tax' => $fee->tax,
                        'taxes'     => array(
                            'total' => isset( $fee->tax_data ) ? $fee->tax_data : '',
                        ),
                    ) );

                    // Add item to order and save.
                    $order->add_item( $item );
                }

            }

        }


        /**
         * process_optional_fees function.
         *
         * @access public
         * @return void
         */
        function process_optional_fees() {
            global $woocommerce;
/*
            if( !is_ajax() )
                remove_action( 'woocommerce_calculate_totals', array( $this, 'update_fee_total' ), 15 );
*/
            if(isset($_POST['fees'])) $GLOBALS['fees'] = $session_fees = $_POST['fees'];
                else $session_fees = (isset($woocommerce->session->be_bolder_fees)) ? $woocommerce->session->be_bolder_fees : array();

            if( count( $session_fees ) ) {
                foreach ($session_fees as $key => $fee) {
                    if( $fee['selected'] == "true" ) {
                        $taxable = ( $fee['taxable'] == 'on') ? true : false;
                        $fee_price = ( $woocommerce->cart->tax_display_cart == 'excl' ) ? $fee['fee'] : $fee['fee'] + $fee['tax_price'];
                        $woocommerce->cart->add_fee( $fee['name'], $fee_price, $taxable, $fee['tax_class'] );
                    }
                }
            }

        }


        /**
         * reset_cache function.
         *
         * @access public
         * @return void
         */
        function reset_cache() { 
            global $woocommerce;

            $woocommerce->session->be_bolder_fees  = array();

        }


        /**
         * verify_conditions function.
         *
         * @access public
         * @return bool
         */
        function verify_conditions( $conditions, $condition_type, $cart_item = array() ) {
            $CONTINUE = false;

            if( count( $conditions ) ) {
                $results = array();
                $conditionsClass = new Bolder_Fees_Conditions();
                foreach ( $conditions as $cond) {
                    $results[] = $conditionsClass->determine_condition_result( $cond, $cart_item );
                }
                switch ( $condition_type ) {
                    case 'all':
                        if( in_array( true, $results ) && !in_array( false, $results ) ) $CONTINUE = true;
                        break;
                    case 'any':
                        if( in_array( true, $results ) ) $CONTINUE = true;
                        break;
                    case 'none':
                        if( in_array( false, $results ) && !in_array( true, $results ) ) $CONTINUE = true;
                        break;

                    default:
                        break;
                }
            } else $CONTINUE = true;

            return $CONTINUE;
        }


        /**
         * calculate_fee_amount function.
         *
         * @access public
         * @return float
         */
        function calculate_fee_amount( $cart_total, $cost_percent, $cost, $tax_shipping, $coupons, $round_fee_up = 0, $qty = 1 ) {
            global $woocommerce;

            $fee_amount = 0;
            $shipping_total = $woocommerce->shipping->shipping_total;
            if( $coupons === 'on' ) {
                $cart_total -= WC()->cart->discount_cart;
            }

            if( isset( $cost_percent ) && $cost_percent != 0 ) {
                if( isset( $tax_shipping ) && $tax_shipping == 'on' )
                    $fee_amount += ($cart_total + $shipping_total ) * ( $cost_percent / 100 );
                else
                    $fee_amount += $cart_total * ( $cost_percent / 100 );
            }

            if( isset( $cost ) && $cost != 0 )
                $fee_amount += $cost;

            $fee_amount *= intval( $qty );

            if( $round_fee_up )
                $fee_amount = ceil( $fee_amount );

            return $fee_amount;
        }


        /**
         * calculate_optional_tax function.
         *
         * @access public
         * @return void
         */
        function calculate_optional_tax( $fee_taxable, $fee_amount, $tax_class, $fee_selected, $add_to_tax = true ) {
            global $woocommerce;

            if( version_compare( WC_VERSION, '3.2.0', ">=" ) ) {
                $wcTaxClass = new WC_Tax();
            } else {
                $wcTaxClass = $woocommerce->cart->tax;
            }
            $tax_fee = 0;

            // add tax if applicable
            if ( $fee_taxable ) {
                $tax_rates = $wcTaxClass->get_rates( $tax_class );
                $fee_taxes = $wcTaxClass->calc_tax( $fee_amount, $tax_rates, false );

                if ( ! empty( $fee_taxes ) ) : 

                    $taxes = ( method_exists( $woocommerce->cart, 'get_cart_contents_taxes' ) ) ? $woocommerce->cart->get_cart_contents_taxes() : $woocommerce->cart->taxes;

                    foreach ( array_keys( $taxes + $fee_taxes ) as $key ) {

                        $tax_fee = ( isset( $fee_taxes[ $key ] ) ) ? $fee_taxes[ $key ] : 0;

                        if( $fee_selected == "true" && $add_to_tax ) {
                            // determine if taxes are applied for this bracket
                            $tax_price_fee = ( isset( $fee_taxes[ $key ] ) ? $fee_taxes[ $key ] : 0 );
                            $tax_price_cart = ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );

                            // add the fee using setter function if exists
                            if( method_exists( $woocommerce->cart, 'set_cart_contents_taxes' ) ) {
                                $tax_ar[ $key ] = $tax_price_fee + $tax_price_cart;
                                $woocommerce->cart->set_cart_contents_taxes( $tax_ar );
                            } else {
                                $woocommerce->cart->taxes[ $key ] = $tax_price_fee + $tax_price_cart;
                            }
                        }

                    }

                endif;

            }

            return $tax_fee;
        }


        /**
         * get_bolder_fees function.
         *
         * @access public
         * @return void
         */
        function get_bolder_fees() {
            $this->bolder_fees = array_filter( (array) get_option( $this->bolder_fees_options ) );
        }
    
    }

    $fees = new BE_Bolder_Fee();
}
