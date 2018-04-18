<?php
/*
Plugin Name:       WooCommerce Payment Gateway WhatsApp
Plugin URI:        https://wordpress.org/plugins/woocommerce-simple-payment-gateway-WhatsApp/
Description:       A WooCommerce Extension that adds payment gateway "Payment Gateway WhatsApp"
Version:           0.0.1
Author:            MegaCreativo
Author URI:        http://megacreativo.com
License:           GPL-2.0+
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/
function wc_spg_init() {
    global $woocommerce;
    if( !isset( $woocommerce ) ) { return; }
if ( ! defined( 'ABSPATH' ) ) exit;
    if( !class_exists( 'WC_Gateway_Payment_Gateway_WhatsApp' ) ): class WC_Gateway_Payment_Gateway_WhatsApp extends WC_Payment_Gateway {
     public function __construct() {
            $this->id                = 'spg';
            $this->icon              = apply_filters('woocommerce_spg_icon', '');
            $this->has_fields        = false;
            $this->method_title      = __( 'Payment Gateway WhatsApp', 'wc_spg' );
            $this->order_button_text = apply_filters( 'woocommerce_spg_order_button_text', __( 'Place order', 'wc_spg' ) );
            $this->init_form_fields();
            $this->init_settings();
            $this->title              = $this->get_option( 'title' );
            $this->description        = $this->get_option( 'description' );
            $this->instructions       = $this->get_option( 'instructions' );
            $this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_spg', array( $this, 'thankyou' ) );
        }
        function admin_options() {
        ?>
            <h3><?php _e('Payment Gateway WhatsApp','wc_spg'); ?></h3>
            <p><?php _e('Extra payment gateway with selection for shipping methods', 'wc_spg' ); ?></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table>
        <?php
        }
        public function init_form_fields() {
            $shipping_methods = array();
            if ( is_admin() ) {
                foreach ( WC()->shipping->load_shipping_methods() as $method ) {
                    $shipping_methods[ $method->id ] = $method->get_title();
                }
            }$order_id;
            $this->form_fields = array(
                
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc_spg'),
                    'type' => 'checkbox',
                    'label' => __('Enable Payment Gateway WhatsApp', 'wc_spg'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'wc_spg'),
                    'type' => 'text',
                    'description' => __('Payment method title which the customer will see during checkout', 'wc_spg'),
                    'default' => __('Payment Gateway WhatsApp', 'wc_spg'),
                    'desc_tip'      => true,
                ),
                'description' => array(
                    'title' => __('Description', 'wc_spg'),
                    'type' => 'textarea',
                    'description' => __('Payment method description which the customer will see during checkout', 'wc_spg'),
                    'default' => __('<style>
										.page-header__logo {
											width: 35px;
											height: 35px;
											background-size: auto 35px;
											overflow: hidden;
											padding-right: 0;
											float: left;
											display: block;
											background-repeat: no-repeat;
											background-image: url(https://www-cdn.whatsapp.net/img/v4/whatsapp-logo.svg?v=bfe2fe6);
                                        }
                                        
										</style>
										<h2>Información de pago vía WhatsApp.<a class="page-header__logo" href="#"></a></h2><br/><p>Presione el botón <strong>REALIZAR PEDIDO</strong> para continuar</p>', 'wc_spg'),
                    'desc_tip'      => true,
                ),
                'instructions' => array(
                    'title' => __('Instructions', 'wc_spg'),
                    'type' => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page.', 'wc_spg'),
                    'default' => __('
                    <h2><a class="page-header__logo" href="#">Informacion de pago via WhatsApp.</a></h2>', 'wc_spg'),
                    'desc_tip'      => true,
                ),
                'enable_for_methods' => array(
                    'title'         => __('Enable for shipping methods', 'wc_spg'),
                    'type'          => 'multiselect',
                    'class'         => 'chosen_select',
                    'css'           => 'width: 450px;',
                    'default'       => '',
                    'description'   => __('Set up shipping methods that are available for  Payment Gateway WhatsApp. Leave blank to enable for all shipping methods.', 'wc_spg'),
                    'options'       => $shipping_methods,
                    'desc_tip'      => true,
                )
            );
        }
		
		
		
        public function is_available() {
            if ( ! empty( $this->enable_for_methods ) ) {
                $chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );
                if ( isset( $chosen_shipping_methods_session ) ) {
                    $chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
                } else {
                    $chosen_shipping_methods = array();
                }
                $check_method = false;
                if ( is_page( wc_get_page_id( 'checkout' ) ) && ! empty( $wp->query_vars['order-pay'] ) ) {
                    $order_id = absint( $wp->query_vars['order-pay'] );
                    $order    = new WC_Order( $order_id );
                    if ( $order->shipping_method )
                        $check_method = $order->shipping_method;
                } elseif ( empty( $chosen_shipping_methods ) || sizeof( $chosen_shipping_methods ) > 1 ) {
                    $check_method = false;
                } elseif ( sizeof( $chosen_shipping_methods ) == 1 ) {
                    $check_method = $chosen_shipping_methods[0];
                }
                if ( ! $check_method )
                    return false;
                $found = false;
                foreach ( $this->enable_for_methods as $method_id ) {
                    if ( strpos( $check_method, $method_id ) === 0 ) {
                        $found = true;
                        break;
                    }
                }
                if ( ! $found )
                    return false;
            }
            return parent::is_available();
        }
        public function process_payment( $order_id ) {

            $order = new WC_Order( $order_id );            
            $nombre = "";
         
            $description = [];
            $products = $order->get_items();

            foreach($products as $product) {
                if( isset ($product['name']) ) $description[] = $product['name'];
            }

            $description = $nombre.'Productos: '.implode(', ',$description);
            
            $order->update_status( apply_filters( 'wc_spg_default_order_status', 'on-hold' ), __( 'Awaiting payment', 'wc_spg' ) );
            $order->reduce_order_stock();
            WC()->cart->empty_cart();

            $h = array(
                'result' => 'success',
                'redirect'  => 'http://api.whatsapp.com/send?phone=573147910366&text='.$description
            );

            return $h;
        }
        public function thankyou() {
            echo $this->instructions != '' ? wpautop( wptexturize( wp_kses_post( $this->instructions ) ) ) : '';
        }
    }
endif;
}
add_action( 'plugins_loaded', 'wc_spg_init' );
function add_spg( $methods ) {
    $methods[] = 'WC_Gateway_Payment_Gateway_WhatsApp';
    return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_spg' );
