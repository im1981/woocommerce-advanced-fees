<?php

/***********************************************************
    Conditional statements to check the validity of each
    fee's selected conditional statements
 ***********************************************************/
class Bolder_Fees_Conditions {

    public $bolder_fees_conds;
    public $exclude_virtual;

    function __construct(){
        global $status, $page, $woocommerce;

        $this->clauses = apply_filters( 'wc_advanced_fees_clauses', array(
            'price-greater'     =>  __('Subtotal is greater than','bolder-fees-woo'),
            'price-less'        =>  __('Subtotal is less than','bolder-fees-woo'),
            'price-equal'       =>  __('Subtotal is equal too','bolder-fees-woo'),
            'price-c-greater'   =>  __('Subtotal with coupons is greater than','bolder-fees-woo'),
            'price-c-less'      =>  __('Subtotal with coupons is less than','bolder-fees-woo'),
            'price-c-equal'     =>  __('Subtotal with coupons is equal too','bolder-fees-woo'),
            'total-greater'     =>  __('Total (after tax) is greater than','bolder-fees-woo'),
            'total-less'        =>  __('Total (after tax) is less than','bolder-fees-woo'),
            'total-equal'       =>  __('Total (after tax) is equal too','bolder-fees-woo'),
            'item-greater'      =>  __('Item count is greater than','bolder-fees-woo'),
            'item-less'         =>  __('Item count is less than','bolder-fees-woo'),
            'item-equal'        =>  __('Item count is equal to','bolder-fees-woo'),
            'weight-greater'    =>  __('Weight is greater than','bolder-fees-woo'),
            'weight-less'       =>  __('Weight is less than','bolder-fees-woo'),
            'weight-equal'      =>  __('Weight is equal to','bolder-fees-woo'),
            'dimension-greater' =>  __('Dimensions are greater than','bolder-fees-woo'),
            'dimension-less'    =>  __('Dimensions are less than','bolder-fees-woo'),
            'dimension-equal'   =>  __('Dimensions are equal to','bolder-fees-woo'),
            'includes-class'    =>  __('Includes shipping class','bolder-fees-woo'),
            'includes-product'  =>  __('Includes product','bolder-fees-woo'),
            'includes-variation'=>  __('Includes product variation','bolder-fees-woo'),
            'includes-category' =>  __('Includes product category','bolder-fees-woo'),
            'includes-virtual'  =>  __('Includes a virtual product','bolder-fees-woo'),
            'includes-download' =>  __('Includes a downloadable product','bolder-fees-woo'),
            'includes-backorder'=>  __('Includes a product on backorder','bolder-fees-woo'),
            'excludes-class'    =>  __('Excludes shipping class','bolder-fees-woo'),
            'excludes-product'  =>  __('Excludes product','bolder-fees-woo'),
            'excludes-variation'=>  __('Excludes product variation','bolder-fees-woo'),
            'excludes-category' =>  __('Excludes product category','bolder-fees-woo'),
            'excludes-virtual'  =>  __('Excludes a virtual product','bolder-fees-woo'),
            'excludes-download' =>  __('Excludes a downloadable product','bolder-fees-woo'),
            'excludes-backorder'=>  __('Excludes a product on backorder','bolder-fees-woo'),
            'shipping-is'       =>  __('Shipping method is','bolder-fees-woo'),
            'shipping-not'      =>  __('Shipping method is not','bolder-fees-woo'),
            'shipping-label-is' =>  __('Shipping label is','bolder-fees-woo'),
            'shipping-label-not'=>  __('Shipping label is not','bolder-fees-woo'),
            'payment-is'        =>  __('Payment method is','bolder-fees-woo'),
            'payment-not'       =>  __('Payment method is not','bolder-fees-woo'),
            'date-before'       =>  __('Date is before','bolder-fees-woo'),
            'date-after'        =>  __('Date is after','bolder-fees-woo'),
            'role-is'           =>  __('User role is','bolder-fees-woo'),
            'role-not'          =>  __('User role is not','bolder-fees-woo'),
            ) );


        $this->bolder_fees_conds = get_option( 'woocommerce_be_bolder_fees_conditions' );

        $addSett = get_option( 'woocommerce_be_bolder_fees_additional' );
        $this->exclude_virtual = ( isset( $addSett['exclude-virtual'] ) && $addSett['exclude-virtual'] ) ? true : false;
    }

    public function determine_condition_result( $cond, $cart_item = array() ) {
        $return = false;
        $conds = get_option( 'woocommerce_be_bolder_fees_conditions' );
        $clause = $conds[ $cond ];
        $cond_value = $conds[ $cond ]['value'];
        $current_clause = $clause['clause'];

        switch ( $current_clause ) {
            case 'price-greater':
                $return = $this->if_subtotal_greater( $cond_value, $cart_item );
                break;
            case 'price-less':
                $return = $this->if_subtotal_less( $cond_value, $cart_item );
                break;
            case 'price-equal':
                $return = $this->if_subtotal_equal( $cond_value, $cart_item );
                break;
            case 'price-c-greater':
                $return = $this->if_subtotal_greater( $cond_value, $cart_item, true );
                break;
            case 'price-c-less':
                $return = $this->if_subtotal_less( $cond_value, $cart_item, true );
                break;
            case 'price-c-equal':
                $return = $this->if_subtotal_equal( $cond_value, $cart_item, true );
                break;
            case 'total-greater':
                $return = $this->if_total_greater( $cond_value, $cart_item );
                break;
            case 'total-less':
                $return = $this->if_total_less( $cond_value, $cart_item );
                break;
            case 'total-equal':
                $return = $this->if_total_equal( $cond_value, $cart_item );
                break;
            case 'item-greater':
                $return = $this->if_itemcount_greater( $cond_value, $cart_item );
                break;
            case 'item-less':
                $return = $this->if_itemcount_less( $cond_value, $cart_item );
                break;
            case 'item-equal':
                $return = $this->if_itemcount_equal( $cond_value, $cart_item );
                break;
            case 'weight-greater':
                $return = $this->if_weight_greater( $cond_value, $cart_item );
                break;
            case 'weight-less':
                $return = $this->if_weight_less( $cond_value, $cart_item );
                break;
            case 'weight-equal':
                $return = $this->if_weight_equal( $cond_value, $cart_item );
                break;
            case 'dimension-greater':
                $return = $this->if_dimensions_greater( $cond_value, $cart_item );
                break;
            case 'dimension-less':
                $return = $this->if_dimensions_less( $cond_value, $cart_item );
                break;
            case 'dimension-equal':
                $return = $this->if_dimensions_equal( $cond_value, $cart_item );
                break;
            case 'includes-class':
                $return = $this->if_includes_class( $cond_value, $cart_item );
                break;
            case 'includes-product':
                $return = $this->if_includes_product( $cond_value, $cart_item );
                break;
            case 'includes-variation':
                $return = $this->if_includes_variation( $cond_value, $cart_item );
                break;
            case 'includes-category':
                $return = $this->if_includes_category( $cond_value, $cart_item );
                break;
            case 'includes-virtual':
                $return = $this->if_includes_virtual( $cond_value, $cart_item );
                break;
            case 'includes-download':
                $return = $this->if_includes_downloadable( $cond_value, $cart_item );
                break;
            case 'includes-backorder':
                $return = $this->if_includes_backorder( $cond_value, $cart_item );
                break;
            case 'excludes-class':
                $return = $this->if_excludes_class( $cond_value, $cart_item );
                break;
            case 'excludes-product':
                $return = $this->if_excludes_product( $cond_value, $cart_item );
                break;
            case 'excludes-variation':
                $return = $this->if_excludes_variation( $cond_value, $cart_item );
                break;
            case 'excludes-category':
                $return = $this->if_excludes_category( $cond_value, $cart_item );
                break;
            case 'excludes-virtual':
                $return = $this->if_excludes_virtual( $cond_value, $cart_item );
                break;
            case 'excludes-download':
                $return = $this->if_excludes_downloadable( $cond_value, $cart_item );
                break;
            case 'excludes-backorder':
                $return = $this->if_excludes_backorder( $cond_value, $cart_item );
                break;
            case 'shipping-is':
                $return = $this->if_shipping_method_is( $cond_value );
                break;
            case 'shipping-not':
                $return = $this->if_shipping_method_not( $cond_value );
                break;
            case 'shipping-label-is':
                $return = $this->if_shipping_label_is( $cond_value );
                break;
            case 'shipping-label-not':
                $return = $this->if_shipping_label_not( $cond_value );
                break;
            case 'payment-is':
                $return = $this->if_payment_is( $cond_value );
                break;
            case 'payment-not':
                $return = $this->if_payment_not( $cond_value );
                break;
            case 'date-before':
                $return = $this->if_date_before( $cond_value );
                break;
            case 'date-after':
                $return = $this->if_date_after( $cond_value );
                break;
            case 'role-is':
                $return = $this->if_role_is( $cond_value );
                break;
            case 'role-not':
                $return = $this->if_role_is_not( $cond_value );
                break;

            default:
           apply_filters( 'wc_advanced_fees_'.$current_clause, $cond_value, $cart_item );

                break;
        }

        return $return;
    }

    // create conditional 'IF' functions
    function if_subtotal_greater( $value, $cart_item, $with_coupons = false ) {
        global $woocommerce;

        if( ! empty( $cart_item ) ) {
            if( $this->exclude_virtual && $cart_item['data']->is_virtual() ) {
                $cart_subtotal = 0;
            } else {
                $cart_subtotal = $cart_item['data']->price;
            }
        } else {
            $cart_subtotal = $woocommerce->cart->subtotal_ex_tax;
            if( is_bool( $with_coupons ) && $with_coupons ) {
                $cart_subtotal -= WC()->cart->discount_cart;
            }
            if( $this->exclude_virtual ) {
                $virtual_subtotal = $this->calculate_virtual_subtotal();
                $cart_subtotal -= $virtual_subtotal;
            }
        }

        if( $cart_subtotal > $value) return true;

        return false;
    }
    function if_subtotal_less( $value, $cart_item, $with_coupons = false ) {
        global $woocommerce;

        if( ! empty( $cart_item ) ) {
            if( $this->exclude_virtual && $cart_item['data']->is_virtual() ) {
                $cart_subtotal = 0;
            } else {
                $cart_subtotal = $cart_item['data']->price;
            }
        } else {
            $cart_subtotal = $woocommerce->cart->subtotal_ex_tax;
            if( is_bool( $with_coupons ) && $with_coupons ) {
                $cart_subtotal -= WC()->cart->discount_cart;
            }
            if( $this->exclude_virtual ) {
                $virtual_subtotal = $this->calculate_virtual_subtotal();
                $cart_subtotal -= $virtual_subtotal;
            }
        }

        if( $cart_subtotal < $value) return true;

        return false;
    }
    function if_subtotal_equal( $value, $cart_item, $with_coupons = false ) {
        global $woocommerce;

        if( ! empty( $cart_item ) ) {
            if( $this->exclude_virtual && $cart_item['data']->is_virtual() ) {
                $cart_subtotal = 0;
            } else {
                $cart_subtotal = $cart_item['data']->price;
            }
        } else {
            $cart_subtotal = $woocommerce->cart->subtotal_ex_tax;
            if( is_bool( $with_coupons ) && $with_coupons ) {
                $cart_subtotal -= WC()->cart->discount_cart;
            }
            if( $this->exclude_virtual ) {
                $virtual_subtotal = $this->calculate_virtual_subtotal();
                $cart_subtotal -= $virtual_subtotal;
            }
        }

        if( $cart_subtotal == $value) return true;

        return false;
    }
    function if_total_greater( $value, $cart_item ) {
        global $woocommerce;

        $cart_subtotal = ( !empty( $cart_item ) ) ? $cart_item['data']->price * $cart_item['quantity'] : $woocommerce->cart->subtotal;
        if( $cart_subtotal > $value) return true;

        return false;
    }
    function if_total_less( $value, $cart_item ) {
        global $woocommerce;

        $cart_subtotal = ( !empty( $cart_item ) ) ? $cart_item['data']->price * $cart_item['quantity'] : $woocommerce->cart->subtotal;
        if( $cart_subtotal < $value) return true;
        
        return false;
    }
    function if_total_equal( $value, $cart_item ) {
        global $woocommerce;

        $cart_subtotal = ( !empty( $cart_item ) ) ? $cart_item['data']->price * $cart_item['quantity'] : $woocommerce->cart->subtotal;
        if( $cart_subtotal == $value) return true;
        
        return false;
    }
    function if_itemcount_greater( $value, $cart_item ) {
        global $woocommerce;

        $cart_count = ( !empty( $cart_item ) ) ? $cart_item['quantity'] : $woocommerce->cart->get_cart_contents_count();
        if( $cart_count > $value) return true;
        
        return false;
    }
    function if_itemcount_less( $value, $cart_item ) {
        global $woocommerce;

        $cart_count = ( !empty( $cart_item ) ) ? $cart_item['quantity'] : $woocommerce->cart->get_cart_contents_count();
        if( $cart_count < $value) return true;
        
        return false;
    }
    function if_itemcount_equal( $value, $cart_item ) {
        global $woocommerce;

        $cart_count = ( !empty( $cart_item ) ) ? $cart_item['quantity'] : $woocommerce->cart->get_cart_contents_count();
        if( $cart_count == $value) return true;
        
        return false;
    }
    function if_weight_greater( $value, $cart_item ) {

        $cart_weight = ( !empty( $cart_item ) ) ? $cart_item['data']->get_weight() : $this->calculate_cart_weight();
        if( $cart_weight > $value) return true;
        
        return false;
    }
    function if_weight_less( $value, $cart_item ) {
        
        $cart_weight = ( !empty( $cart_item ) ) ? $cart_item['data']->get_weight() : $this->calculate_cart_weight();
        if( $cart_weight < $value) return true;
        
        return false;
    }
    function if_weight_equal( $value, $cart_item ) {
        
        $cart_weight = ( !empty( $cart_item ) ) ? $cart_item['data']->get_weight() : $this->calculate_cart_weight();
        if( $cart_weight == $value) return true;
        
        return false;
    }
    function if_dimensions_greater( $value, $cart_item ) {
        
        $cart_dimensions = ( !empty( $cart_item ) ) ? $this->get_single_product_dimensions( $cart_item ) : $this->calculate_cart_dimensions();
        if( $cart_dimensions > $value ) return true;

        return false;
    }
    function if_dimensions_less( $value, $cart_item ) {
        
        $cart_dimensions = ( !empty( $cart_item ) ) ? $this->get_single_product_dimensions( $cart_item ) : $this->calculate_cart_dimensions();
        if( $cart_dimensions < $value ) return true;

        return false;
    }
    function if_dimensions_equal( $value, $cart_item ) {
        
        $cart_dimensions = ( !empty( $cart_item ) ) ? $this->get_single_product_dimensions( $cart_item ) : $this->calculate_cart_dimensions();
        if( $cart_dimensions == $value ) return true;

        return false;
    }
    function if_includes_class( $value, $cart_item ) {

        $cart_classes = ( !empty( $cart_item ) ) ? array( $cart_item['data']->get_shipping_class_id() ) : $this->get_classes_in_cart();
        if( in_array( $value , $cart_classes ) ) return true;

        return false;
    }
    function if_includes_product( $value, $cart_item ) {

        $cart_products = ( !empty( $cart_item ) ) ? array( $cart_item['product_id'] ) : $this->get_products_in_cart();
        if( in_array( $value , $cart_products ) ) return true;

        return false;
    }
    function if_includes_variation( $value, $cart_item ) {

        $cart_variations = ( !empty( $cart_item ) ) ? array( $cart_item['variation_id'] ) : $this->get_variations_in_cart();
        $variations = explode( ',', str_replace( ' ', '', $value ) );
        foreach ( $variations as $var ) {
            if( in_array( $var , $cart_variations ) ) return true;
        }

        return false;
    }
    function if_includes_category( $value, $cart_item ) {

        $categories_in_cart = ( !empty( $cart_item ) ) ? $this->get_single_product_categories( $cart_item['product_id'] ) : $this->get_categories_in_cart();
        if( in_array( $value , $categories_in_cart ) ) return true;

        return false;
    }
    function if_includes_virtual( $value, $cart_item ) {
        global $woocommerce;

        if( !empty( $cart_item ) ) {
            if( $cart_item['data']->is_virtual() ) return true;
        } else {
            $cart = $woocommerce->cart->get_cart();
            if( count( $cart ) ) {
                foreach ($cart as $key => $value) {
                    if( $value['data']->is_virtual() ) return true;
                }
            }
        }

        return false;
    }
    function if_includes_downloadable( $value, $cart_item ) {
        global $woocommerce;

        if( !empty( $cart_item ) ) {
            if( $cart_item['data']->is_downloadable() ) return true;
        } else {
            $cart = $woocommerce->cart->get_cart();
            if( count( $cart ) ) {
                foreach ($cart as $key => $value) {
                    if( $value['data']->is_downloadable() ) return true;
                }
            }
        }

        return false;
    }
    function if_includes_backorder( $value, $cart_item ) {
        global $woocommerce;

        if( !empty( $cart_item ) ) {
            if( $cart_item['data']->is_on_backorder() ) return true;
        } else {
            $cart = $woocommerce->cart->get_cart();
            if( count( $cart ) ) {
                foreach ($cart as $key => $value) {
                    if( $value['data']->is_on_backorder() ) return true;
                }
            }
        }

        return false;
    }
    function if_excludes_class( $value, $cart_item ) {

        $cart_classes = ( !empty( $cart_item ) ) ? array( $cart_item['data']->get_shipping_class_id() ) : $this->get_classes_in_cart();
        if( in_array( $value , $cart_classes ) ) return false;

        return true;
    }
    function if_excludes_product( $value, $cart_item ) {

        $cart_products = ( !empty( $cart_item ) ) ? array( $cart_item['product_id'] ) : $this->get_products_in_cart();
        if( in_array( $value , $cart_products ) ) return false;

        return true;
    }
    function if_excludes_variation( $value, $cart_item ) {

        $cart_variations = ( !empty( $cart_item ) ) ? array( $cart_item['variation_id'] ) : $this->get_variations_in_cart();
        $variations = explode( ',', str_replace( ' ', '', $value ) );
        foreach ( $variations as $var ) {
            if( in_array( $var , $cart_variations ) ) return false;
        }

        return true;
    }
    function if_excludes_category( $value, $cart_item ) {

        $categories_in_cart = ( !empty( $cart_item ) ) ? $this->get_single_product_categories( $cart_item['product_id'] ) : $this->get_categories_in_cart();
        if( in_array( $value , $categories_in_cart ) ) return false;

        return true;
    }
    function if_excludes_virtual( $value, $cart_item ) {
        global $woocommerce;

        if( !empty( $cart_item ) ) {
            if( $cart_item['data']->is_virtual() ) return false;
        } else {
            $cart = $woocommerce->cart->get_cart();
            if( count( $cart ) ) {
                foreach ($cart as $key => $value) {
                    if( $value['data']->is_virtual() ) return false;
                }
            }
        }

        return true;
    }
    function if_excludes_downloadable( $value, $cart_item ) {
        global $woocommerce;

        if( !empty( $cart_item ) ) {
            if( $cart_item['data']->is_downloadable() ) return false;
        } else {
            $cart = $woocommerce->cart->get_cart();
            if( count( $cart ) ) {
                foreach ($cart as $key => $value) {
                    if( $value['data']->is_downloadable() ) return false;
                }
            }
        }

        return true;
    }
    function if_excludes_backorder( $value, $cart_item ) {
        global $woocommerce;

        if( !empty( $cart_item ) ) {
            if( $cart_item['data']->is_on_backorder() ) return false;
        } else {
            $cart = $woocommerce->cart->get_cart();
            if( count( $cart ) ) {
                foreach ($cart as $key => $value) {
                    if( $value['data']->is_on_backorder() ) return false;
                }
            }
        }

        return true;
    }
    function if_shipping_method_is( $value ) {
        global $woocommerce;

        $chosen_methods = array();
        $chosen_rates = WC()->session->get( 'chosen_shipping_methods' );
        $available_methods = $woocommerce->shipping->get_packages();
        foreach ($available_methods as $method)
                foreach ($chosen_rates as $chosen)
                    if( isset( $method['rates'][$chosen] ) ) $chosen_methods[ $method['rates'][ $chosen ]->method_id ] = true;

        if( array_key_exists( $value, $chosen_methods ) ) return true;

        return false;
    }
    function if_shipping_method_not( $value ) {
        global $woocommerce;

        $chosen_methods = array();
        $chosen_rates = WC()->session->get( 'chosen_shipping_methods' );
        $available_methods = $woocommerce->shipping->get_packages();
        foreach ($available_methods as $method)
                foreach ($chosen_rates as $chosen)
                    if( isset( $method['rates'][$chosen] ) ) $chosen_methods[ $method['rates'][ $chosen ]->method_id ] = true;

        if( !array_key_exists( $value, $chosen_methods ) ) return true;

        return false;
    }
    function if_shipping_label_is( $value ) {
        global $woocommerce;

        $current_titles = array();
        $packages = $woocommerce->shipping->get_packages();
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

        foreach( $packages as $key => $pkg )
            foreach( $pkg[ 'rates' ] as $r_id => $rate )
                if( $chosen_methods[ $key ] == $r_id )
                    $current_titles[] = $rate->label;

        if( in_array( $value, $current_titles ) ) return true;

        return false;
    }
    function if_shipping_label_not( $value ) {
        global $woocommerce;

        $current_titles = array();
        $packages = $woocommerce->shipping->get_packages();
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

        foreach( $packages as $key => $pkg )
            foreach( $pkg[ 'rates' ] as $r_id => $rate )
                if( $chosen_methods[ $key ] == $r_id )
                    $current_titles[] = $rate->label;

        if( ! in_array( $value, $current_titles ) ) return true;

        return false;
    }
    function if_payment_is( $value ) {
        global $woocommerce;

        $current_method = $woocommerce->session->chosen_payment_method;
        if( $current_method != '' && $current_method == $value ) return true;

        return false;
    }
    function if_payment_not( $value ) {
        global $woocommerce;

        $current_method = $woocommerce->session->chosen_payment_method;
        if( $current_method != '' && $current_method != $value ) return true;

        return false;
    }

    function if_date_before( $value ) {
        global $woocommerce;

        $current_date = time();
        if( $current_date < strtotime( $value ) ) return true;

        return false;
    }
    function if_date_after( $value ) {
        global $woocommerce;

        $current_date = time();
        if( $current_date > strtotime( $value ) ) return true;

        return false;
    }

    function if_role_is( $value ) {
        global $current_user;
        $current_user = wp_get_current_user();

        $roles = $current_user->roles;
        if( in_array( $value, $roles ) )
            return true;

        return false;
    }
    function if_role_is_not( $value ) {
        global $current_user;
        $current_user = wp_get_current_user();

        $roles = $current_user->roles;
        if( !in_array( $value, $roles ) )
            return true;

        return false;
    }

    // Calculate cart data
    function calculate_virtual_subtotal() {
        global $woocommerce;

        $virtual_subtotal = 0;
        $cart = $woocommerce->cart->get_cart();
        if( count( $cart ) ) {
            foreach( $cart as $item ) {
                if( $item['data']->is_virtual() ) {
                    $virtual_subtotal += $item['line_subtotal'];
                }
            }
        }
        
        return $virtual_subtotal;
    }

    // Calculate cart data
    function calculate_cart_weight() {
        global $woocommerce;

        $cart_weight = 0;
        $cart = $woocommerce->cart->get_cart();
        if( count( $cart ) ) {
            foreach ($cart as $key => $value) {
                $product = get_product( $value['product_id'] );
                if( $value['variation_id'] && $value['data']->variation_has_weight ) {
                    $weight = $value['data']->get_weight();
                } else $weight = $product->get_weight();

                $cart_weight += $weight * $value['quantity'];
            }
        }
        return $cart_weight;
    }

    function calculate_cart_dimensions() {
        global $woocommerce;

        $cart_dimensions = 0;
        $cart = $woocommerce->cart->get_cart();
        if( count( $cart ) ) {
            foreach ($cart as $key => $value) {
                $length = ( $value['variation_id'] && $value['data']->variation_has_length ) ? $value['data']->product_custom_fields['_length'][0] : $value['data']->length;
                $width = ( $value['variation_id'] && $value['data']->variation_has_width ) ? $value['data']->product_custom_fields['_width'][0] : $value['data']->width;
                $height = ( $value['variation_id'] && $value['data']->variation_has_height ) ? $value['data']->product_custom_fields['_height'][0] : $value['data']->height;

                $length = ( $length ) ? $length : 1 ;
                $height = ( $height ) ? $height : 1 ;
                $width = ( $width ) ? $width : 1 ;
                $dimensions = $length * $width * $height;

                $cart_dimensions += $dimensions * $value['quantity'];
            }
        }
        return $cart_dimensions;
    }

    function get_classes_in_cart() {
        global $woocommerce;

        $shipping_classes = array();
        $cart = $woocommerce->cart->get_cart();
        if( count( $cart ) ) {
            foreach ($cart as $key => $value) {
                $shipping_classes[] = $value['data']->get_shipping_class_id();
            }
        }

        return $shipping_classes;
    }
    
    function get_products_in_cart( $get_parent = false ) {
        global $woocommerce;

        $products = array();
        $cart = $woocommerce->cart->get_cart();
        if( count( $cart ) ) {
            foreach ($cart as $key => $value) {
                if( $value['data']->get_type() == 'variation' )
                    $parent_id = ( version_compare( WC_VERSION, '3.0', ">=" ) ) ? $value['data']->get_parent_id(): $value['data']->parent->id;
                $products[] = ( $get_parent == true && $value['data']->get_type() == 'variation' ) ? $parent_id : $value['product_id'];
                //$products[] = $value['product_id'];
            }
        }

        return $products;
    }
    
    function get_variations_in_cart() {
        global $woocommerce;

        $variations = array();
        $cart = $woocommerce->cart->get_cart();
        if( count( $cart ) ) {
            foreach ($cart as $key => $value) {
                if( isset( $value['variation_id'] ) && $value['variation_id'] != '' )
                    $variations[] = $value['variation_id'];
            }
        }

        return $variations;
    }
    
    function get_categories_in_cart() {
        global $woocommerce;

        $categories_in_cart = array();
        $cart_products = $this->get_products_in_cart( true );
        foreach ($cart_products as $prod) {
            $categories = get_the_terms( $prod, 'product_cat' );
            if( $categories ) {
                foreach ($categories as $cat)
                    $categories_in_cart[] = $cat->term_id;
            }
        }

        return $categories_in_cart;
    }

    function get_single_product_dimensions( $value ) {
        global $woocommerce;

        $length = ( $value['variation_id'] && $value['data']->variation_has_length ) ? $value['data']->product_custom_fields['_length'][0] : $value['data']->length;
        $width = ( $value['variation_id'] && $value['data']->variation_has_width ) ? $value['data']->product_custom_fields['_width'][0] : $value['data']->width;
        $height = ( $value['variation_id'] && $value['data']->variation_has_height ) ? $value['data']->product_custom_fields['_height'][0] : $value['data']->height;

        $length = ( $length ) ? $length : 1 ;
        $height = ( $height ) ? $height : 1 ;
        $width = ( $width ) ? $width : 1 ;
        $item_weight = $length * $width * $height;

        return $item_weight;
    }

    function get_single_product_categories( $pid ) {
        global $woocommerce;

        $categories = get_the_terms( $pid, 'product_cat' );
        foreach ($categories as $cat)
            $categories_in_cart[] = $cat->term_id;

        return $categories_in_cart;
    }

        
}
