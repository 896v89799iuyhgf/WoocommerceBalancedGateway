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

function woocommerce_balanced_init()
{

    if (!class_exists('WC_Payment_Gateway')) return;

    /**
     * Localisation
     */
    load_plugin_textdomain('wc-balanced', FALSE, dirname(plugin_basename(__FILE__)) . '/languages');

    /**
     * Gateway class
     */
    class WC_Balanced extends WC_Payment_Gateway
    {
        /**
         * Create gateway
         */
        function __construct()
        {
            // Register plugin information
            $this->id                 = 'balanced';
            $this->has_fields         = TRUE;
            $this->method_title       = __('Balanced Payment', 'woocommerce');
            $this->method_description = __('Balanced Payment Gateway', 'woocommerce');

            $this->init_form_fields();
            $this->init_settings();

            foreach ($this->settings as $key => $val) $this->$key = $val;

            $this->title           = $this->get_option('title');
            $this->description     = $this->get_option('description');
            $this->api_key         = $this->get_option('api_key');
            $this->marketplace_uri = $this->get_option('marketplace_uri');
            $this->testmode        = $this->get_option('testmode');
            /*$this->creditcard 		= $this->get_option( 'creditcard' );
            $this->bank_account 		= $this->get_option( 'bank_account' );*/

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this,
                                                                                         'process_admin_options'));
        }

        /**
         * Create payment form field
         * @return string|void
         */
        function init_form_fields()
        {
            $this->form_fields = array('enabled'         => array('title'       => __('Enable/Disable', 'woothemes'),
                                                                  'label'       => __('Enable Balanced Payment', 'woothemes'),
                                                                  'type'        => 'checkbox',
                                                                  'description' => '',
                                                                  'default'     => 'no'),
                                       'title'           => array('title'       => __('Title', 'woothemes'),
                                                                  'type'        => 'text',
                                                                  'description' => __('This controls the title which the user sees during checkout.', 'woothemes'),
                                                                  'default'     => __('Credit Card (Balanced Payment)', 'woothemes')),
                                       'description'     => array('title'       => __('Description', 'woothemes'),
                                                                  'type'        => 'textarea',
                                                                  'description' => __('This controls the description which the user sees during checkout.', 'woothemes'),
                                                                  'default'     => 'Pay with your credit card via Balanced Payment.'),
                                       'basic_info'      => array('title'       => __('Basic Information', 'woocommerce'),
                                                                  'type'        => 'title',
                                                                  'description' => ''),
                                       'api_key'         => array('title'       => __('API Key', 'woothemes'),
                                                                  'type'        => 'text',
                                                                  'description' => __('This is the API username generated within the Balanced Payment gateway.', 'woothemes'),
                                                                  'default'     => ''),
                                       'marketplace_uri' => array('title'       => __('Marketplace URI', 'woothemes'),
                                                                  'type'        => 'text',
                                                                  'description' => __('This is the Marketplace URI generated within the  Balanced Payment gateway.', 'woothemes'),
                                                                  'default'     => ''),
                                       'testmode'        => array('title'       => __('Test mode', 'woothemes'),
                                                                  'label'       => __('Enable Test Enviroment in Balanced Payment', 'woothemes'),
                                                                  'type'        => 'checkbox',
                                                                  'description' => '',
                                                                  'default'     => 'no'),/*'payment_type' => array(
                    'title' => __( 'Payment Type', 'woocommerce' ),
                    'type' => 'title',
                    'description' => ''),
                'creditcard'     => array(
                    'title'       => __( 'Credit Card', 'woothemes' ),
                    'label'       => __( 'Enable pay by Credit Card in Balanced Payment', 'woothemes' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes'
                )
                'bank_account'     => array(
                    'title'       => __( 'Bacnk Account', 'woothemes' ),
                    'label'       => __( 'Enable pay by Bacnk Account in Balanced Payment', 'woothemes' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes'
                ),*/);
        }

        /**
         * Add fields to gateway page in wp-admin
         */
        public function admin_options()
        {
            ?>
            <h3><?php _e('Balanced Payment', 'woocommerce'); ?></h3>
            <p><?php _e('www.balancedpayments.com.', 'woocommerce'); ?></p>
            <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
            </table><!--/.form-table-->
        <?php
        }

        /**
         * Create Payment fields on front-end
         */
        function payment_fields()
        {
            global $woocommerce;
            wp_enqueue_script('balanced_js', '//js.balancedpayments.com/v1/balanced.js');
//            wp_enqueue_script('balanced_init_js', plugins_url('js/balanced_init.js', __FILE__ ), null, false, true);
            if ($this->description) {
                ?>
                <p><?php echo $this->description; ?></p>
            <?php } ?>
            <label>Card Number</label>
            <p>
                <input type="text" maxlength="16" placeholder="" autocomplete="off" class="form-control" id="balanced-cc-number" name="balanced-cc-number">
            </p>
            <label>Expiration</label>
            <p>
                <input type="text" maxlength="2" placeholder="month" autocomplete="off" class="form-control" id="balanced-cc-ex-month" name="balanced-cc-ex-month">
                <input type="text" maxlength="4" placeholder="year" autocomplete="off" class="form-control" id="balanced-cc-ex-year" name="balanced-cc-ex-year">
            </p>
            <label>Security Code (CSC)</label>
            <p>
                <input type="text" maxlength="4" placeholder="" autocomplete="off" class="form-control" id="balanced-ex-csc" name="balanced-ex-csc">
            </p>
        <?php
        }

        /**
         * Validate fields on front-ends
         * @return bool
         */
        function validate_fields()
        {
            global $woocommerce;
            // Check for saving payment info without having or creating an account
            if (!is_user_logged_in()) {
                $woocommerce->add_error(__('Sorry, you need to create an account in order for us to save your payment information.', 'woocommerce'));

                return FALSE;
            }

            $name                = $this->get_post('billing_first_name');
            $email               = $this->get_post('billing_email');
            $cardNumber          = $this->get_post('balanced-cc-number');
            $cardCSC             = $this->get_post('balanced-ex-csc');
            $cardExpirationMonth = $this->get_post('balanced-cc-ex-month');
            $cardExpirationYear  = $this->get_post('balanced-cc-ex-year');

            // Check card number
            if (empty($cardNumber) || !ctype_digit($cardNumber)) {
                $woocommerce->add_error(__('Card number is invalid.', 'woocommerce'));

                return FALSE;
            }

            // Check security code
            if (!ctype_digit($cardCSC)) {
                $woocommerce->add_error(__('Card security code is invalid (only digits are allowed).', 'woocommerce'));

                return FALSE;
            }

            // Check expiration data
            $currentYear = date('Y');

            if (!ctype_digit($cardExpirationMonth) || !ctype_digit($cardExpirationYear) || $cardExpirationMonth > 12 || $cardExpirationMonth < 1 || $cardExpirationYear < $currentYear || $cardExpirationYear > $currentYear + 20
            ) {
                $woocommerce->add_error(__('Card expiration date is invalid', 'woocommerce'));

                return FALSE;
            }

            // Strip spaces and dashes
            $cardNumber = str_replace(array(' ', '-'), '', $cardNumber);
            return true;
        }

        /**
         * Process Payment on front-end
         * @param int $order_id
         */
        function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order( $order_id );
            $amount = $order->get_total();

            require_once('include/httpful/bootstrap.php');
            require_once('include/restful/bootstrap.php');
            require_once('include/balanced/bootstrap.php');

            Balanced\Settings::$api_key = $this->api_key;
            $marketplace = Balanced\Marketplace::mine();

            // Create a Card
            $card = $marketplace->cards->create(array(
                                                "card_number" => $_POST['balanced-cc-number'],
                                                "expiration_month" => $_POST['balanced-cc-ex-month'],
                                                "expiration_year" => $_POST['balanced-cc-ex-year']
                                                ));

            // Create a Customer
            $customer = new \Balanced\Customer(array(
                                               "name" => $order->billing_last_name . $order->billing_first_name,
                                               "email" => $order->billing_email
                                               ));
            $customer->save();
            $customer->addCard($card->uri);
            try {
                $debit = $customer->debit($amount * 1000);
                $order->payment_complete();
                return array(
                    'result' 	=> 'success',
                    'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('thanks'))))
                );
            }
            catch (Balanced\Errors\Declined $e) {
                $woocommerce->add_error(__('Oh no, the processor declined the debit!', 'woothemes') . $error_message);
                return;
            }
            catch (Balanced\Errors\NoFundingSource $e) {
                $woocommerce->add_error(__('Oh no, the buyer has not active funding sources!', 'woothemes') . $error_message);
                return;
            }
            catch (Balanced\Errors\CannotDebit $e) {
                $woocommerce->add_error(__('Oh no, the buyer has no debitable funding sources!', 'woothemes') . $error_message);
                return;
            }
        }

        /**
         * Get post data if set
         */
        private function get_post( $name ) {
            if ( isset( $_POST[ $name ] ) ) {
                return $_POST[ $name ];
            }
            return null;
        }
    }

    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_balanced_gateway($methods)
    {
        $methods[] = 'WC_Balanced';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_balanced_gateway');
}
