<?php
/*
Plugin Name: WooCommerce Balanced Gateway
Plugin URI: https://github.com/maolivn/WoocommerceBalancedGateway
Description: Extends WooCommerce with an Balanced gateway.
Version: 1.0
Author: Tan Phan
Author URI: https://github.com/maolivn
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

add_action('plugins_loaded', 'woocommerce_balanced_init', 0);

function woocommerce_balanced_init() {

    if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

    /**
     * Localisation
     */
    load_plugin_textdomain('wc-balanced', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

    /**
     * Gateway class
     */
    class WC_Balanced extends WC_Payment_Gateway {
        function __construct() {
            // Register plugin information
            $this->id			    = 'balanced';
            $this->has_fields = true;
            $this->method_title = __( 'Balanced Payment', 'woocommerce' );
            $this->method_description = __( 'Balanced Payment Gateway', 'woocommerce' );

            $this->init_form_fields();
            $this->init_settings();

            foreach ( $this->settings as $key => $val ) $this->$key = $val;
            $this->title 			= $this->get_option( 'title' );
            $this->description 		= $this->get_option( 'description' );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Create payment form field
         * @return string|void
         */
        function init_form_fields() {
            $this->form_fields = array(
                'enabled'     => array(
                    'title'       => __( 'Enable/Disable', 'woothemes' ),
                    'label'       => __( 'Enable Balanced Payment', 'woothemes' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title'       => array(
                    'title'       => __( 'Title', 'woothemes' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ),
                    'default'     => __( 'Credit Card (Balanced Payment)', 'woothemes' )
                ),
                'description' => array(
                    'title'       => __( 'Description', 'woothemes' ),
                    'type'        => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ),
                    'default'     => 'Pay with your credit card via Balanced Payment.'
                ),
                'basic_info' => array(
                    'title' => __( 'Basic Information', 'woocommerce' ),
                    'type' => 'title',
                    'description' => ''),
                'api_key'    => array(
                    'title'       => __( 'API Key', 'woothemes' ),
                    'type'        => 'text',
                    'description' => __( 'This is the API username generated within the Balanced Payment gateway.', 'woothemes' ),
                    'default'     => ''
                ),
                'marketplace_uri'    => array(
                    'title'       => __( 'Marketplace URI', 'woothemes' ),
                    'type'        => 'text',
                    'description' => __( 'This is the Marketplace URI generated within the  Balanced Payment gateway.', 'woothemes' ),
                    'default'     => ''
                ),
                'testmode'     => array(
                    'title'       => __( 'Test mode', 'woothemes' ),
                    'label'       => __( 'Enable Test Enviroment in Balanced Payment', 'woothemes' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'payment_type' => array(
                    'title' => __( 'Payment Type', 'woocommerce' ),
                    'type' => 'title',
                    'description' => ''),
                'creditcard'     => array(
                    'title'       => __( 'Credit Card', 'woothemes' ),
                    'label'       => __( 'Enable pay by Credit Card in Balanced Payment', 'woothemes' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes'
                ),
                'bank_account'     => array(
                    'title'       => __( 'Bacnk Account', 'woothemes' ),
                    'label'       => __( 'Enable pay by Bacnk Account in Balanced Payment', 'woothemes' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes'
                ),
            );
        }

        public function admin_options() {
            ?>
            <h3><?php _e( 'Balanced Payment', 'woocommerce' ); ?></h3>
            <p><?php _e( 'www.balancedpayments.com.', 'woocommerce' ); ?></p>
                <table class="form-table">
                    <?php
                    // Generate the HTML For the settings form.
                    $this->generate_settings_html();
                    ?>
                </table><!--/.form-table-->
<?php
        }


        function process_payment( $order_id ) {

        }
    }

    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_balanced_gateway($methods) {
        $methods[] = 'WC_Balanced';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_balanced_gateway' );
}
