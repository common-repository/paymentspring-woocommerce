<?php

  class WC_Gateway_ACH_PaymentSpring extends WC_Payment_Gateway_ECheck{

    public $form_fields, $keysAreSet;

    public $publicKey = null;
    protected $privateKey = null;

    public function __construct(){
      $this->method_title = __("PaymentSpring ACH", "wc_paymentspring_ach");
      $this->id = "paymentspring_ach";
      $this->has_fields = true;
      $this->method_description = "PaymentSpring by Nelnet";

      $this->view_transaction_url = 'https://manage.paymentspring.com/payments/%s';

      $this->init_form_fields();
      $this->init_settings();

      $this->title = $this->get_option("title");
      $this->apiMode = $this->get_option("api_mode");

      $this->keysAreSet = false;
      $this->setPaymentSpringKeys(); 

      // Because PaymentSpring only supports one card per customer, we don't enable subscription payment method changes.
      $this->supports = array(
        'subscriptions',
        'subscription_cancellation', 
        'subscription_suspension', 
        'subscription_reactivation',
        'subscription_amount_changes',
        'subscription_date_changes',
        'products',
        'refunds'
      );
      
      add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields(){
      $this->form_fields = include('settings-paymentspring-ach.php');
    }

    public function payment_scripts(){
      if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order']) && !is_add_payment_method_page()) {
        return;
      }
      wp_enqueue_script('paymentspring_ach_js_api', plugins_url('assets/js/paymentspring-ach.js', PAYMENT_SPRING_WC_BASE_FILE), array(), PAYMENT_SPRING_PLUGIN_VERSION);
      wp_enqueue_script('paymentspring_ach_woocommerce', plugins_url('assets/js/paymentspring-form-ach.js', PAYMENT_SPRING_WC_BASE_FILE), array(), PAYMENT_SPRING_PLUGIN_VERSION);
      // Create a public_key variable for use in our JS files 
      if($this->publicKey){
        $apiKey = $this->publicKey;
      }else{
        $apiKey = "false";
      }
      wp_localize_script('paymentspring_ach_woocommerce', 'ach_settings_public_key', apply_filters('ach_settings_public_key', $apiKey));
    }

    public function process_payment( $order_id, $retry = true, $force_customer = false ) {
      global $woocommerce;
      $order = new WC_Order( $order_id );
      $token = $_POST['ps_token'];

      if(!$this->keysAreSet){
        throw new Exception(
          __("Please set your API Keys in WooCommerce > Settings > Payments > PaymentSpring ACH", 'paymentspring-woocommerce-gateway')
        );
      }

      if($order->get_total() > 0){
        if(get_current_user_id()){
          /*
           * If a user is logged in, we'll get or create a customer add a token to their account, then charge their account.
           *
           * This is helpful for subscriptions. We don't actually save cards to the user's account. WooCommerce expects 
           * gateway plugins which enable card saving to handle multiple cards per account, unfortunately, PaymentSpring 
           * currently only allows one card to be stored per customer.
           */
          $customerId = $this->get_customer_id($order, $token);
          $description = __("Payment made via WooCommerce - ACH");
          $response = $this->chargeCustomer($customerId, $order, $description);
        }else{
          // Other users will simply have a one-time use token created and will be charged once.
          $description = __("Payment made via WooCommerce - ACH");
          $response = $this->chargeToken($token, $order, $description);
        }

        // Handle Wordpress or PaymentSpring errors
        if(is_wp_error($response)){
          throw new Exception($response->get_error_message());
        }else if(isset($response->errors)){
          throw new Exception($this->format_json_errors($response->errors));
        }

        $response = $this->process_payment_success($response, $order);
      }else{
        // If the total amount is $0, we don't need to go through our payment gateway.
        $order->payment_complete();
      }

      $order->reduce_order_stock();
      $woocommerce->cart->empty_cart();

      do_action( 'wc_gateway_paymentspring_ach_process_payment', $response, $order );

      // Return a success message if no exceptions were thrown.
      return array(
        'result' => 'success',
        'redirect' => $this->get_return_url( $order )
      );
    }

    // Get a customer ID from either the database or an order
    public function get_customer_id($order = null, $token = null){
      $customerId = get_user_meta(get_current_user_id(), "_paymentspring_ach_customer_id_".$this->apiMode, true);
      if($token && !$customerId){
        $customer_params = $this->construct_customer_params($order, $token);
        $customerResponse = \PaymentSpring\Customer::createCustomer($customer_params);

        if(is_wp_error($customerResponse)){
          throw new Exception($customerResponse->get_error_message());
        }else if(isset($customerResponse->errors)){
          throw new Exception($this->format_json_errors($customerResponse->errors));
        }

        // If it's coming from an order, we'll want to persist that to the database
        update_user_meta(get_current_user_id(), '_paymentspring_ach_customer_id', $customerResponse->id );
        $customerId = $customerResponse->id;
      }else if(!$customerId && !$token){
        throw new Exception(__("PaymentSpring requires token or customer to process", "paymentspring-woocommerce-gateway"));
      }
      return $customerId;
    }

    public function construct_customer_params($order, $token){
      if(version_compare( WC_VERSION, '3.0.0', '<' )){
        $customer_params = array(
          'first_name' => $order->billing_first_name,
          'last_name' => $order->billing_last_name,
          'address_1' => $order->billing_address_1,
          'address_2' => $order->billing_address_2,
          'state' => $order->state,
          'city' => $order->city,
          'zip' => $order->zip,
          'token' => $token,
        );
      }else{
        $customer_params = array(
          'first_name' => $order->get_billing_first_name(),
          'last_name' => $order->get_billing_last_name(),
          'address_1' => $order->get_billing_address_1(),
          'address_2' => $order->get_billing_address_2(),
          'state' => $order->get_billing_state(),
          'city' => $order->get_billing_city(),
          'zip' => $order->get_billing_postcode(),
          'token' => $token,
        );
      }
      return $customer_params;
    }

    public function process_refund($orderId, $amount = null, $reason = '') {
      $chargeId = get_post_meta( $orderId, '_paymentspring_ach_charge_id', true );
      $path = "charge/$chargeId/cancel";
      $amountInCents = $amount * 100;

      return \PaymentSpring\PaymentSpring::makeRequest($path, array("amount" => $amountInCents), true);
    }

    public function chargeCustomer($customerId, $order, $description){
      $amount = $order->get_total();
      $email = $order->get_billing_email();
      $amount_in_cents = $amount * 100;
      return \PaymentSpring\Charge::chargeCustomer($customerId, $amount_in_cents, array("description" => $description, "email_address" => $email));
    }

    public function chargeToken($token, $order, $description){
      $amount = $order->get_total();
      $email = $order->get_billing_email();
      $amount_in_cents = $amount * 100;
      return \PaymentSpring\Charge::chargeToken($token, $amount_in_cents, array("description" => $description, "email_address" => $email));
    }

    protected function get_token($order){
      if(isset( $_POST['wc-paymentspring-payment-token'] ) && 'new' !== $_POST['wc-paymentspring-payment-token']){
        $token_id = wc_clean($_POST['wc-paymentspring-payment-token']);
        $token = WC_Payment_Tokens::get($token_id);

        if (!$token || $token->get_user_id() !== get_current_user_id()){
          WC()->session->set( 'refresh_totals', true );
          throw new Exception( __( 'Invalid payment method. Please input a new card number.', 'paymentspring-woocommerce-gateway' ) );
        }
        $raw_token = $token->get_token();
        return $raw_token;
      }
      return null;
    }

    private function setPaymentSpringKeys(){
      if($this->apiMode == 'test'){
        $this->publicKey = $this->get_option('test_public_key');
        $this->privateKey = $this->get_option('test_private_key');
      }else{
        $this->publicKey = $this->get_option('live_public_key');
        $this->privateKey = $this->get_option('live_private_key');
      }
      if($this->publicKey && $this->privateKey){
        $this->keysAreSet = true;
        \PaymentSpring\PaymentSpring::setApiKeys($this->publicKey, $this->privateKey);
      }
    }

    private function format_json_errors ( $errors ) {
      $str = "";
      foreach ( $errors as $error ) {
        $str .= "Code " . $error->code . " : " . $error->message . " ";
      }
      return $str;
    }

    // Sets payment success messages based on the response
    // This also handles a failed payment and will place an order as "on hold"
    protected function process_payment_success($response, $order){
      update_post_meta( $order->id, '_paymentspring_ach_charge_id', $response->id );
      update_post_meta( $order->id, '_paymentspring_ach_charge_captured', $response->status == "SETTLED" ? 'yes' : 'no' );
      
      if($response->status == "SETTLED"){
        $order->payment_complete( $response->id ); 
        $message = sprintf( __( 'PaymentSpring charge complete (Charge ID: %s)', 'paymentspring_ach-woocommerce-gateway' ), $response->id );
        $order->add_order_note( $message );
      }else{
        $order->update_status('on-hold');
        $message = sprintf( __( 'PaymentSpring charge complete (Charge ID: %s) with status %s', 'paymentspring_ach-woocommerce-gateway' ), $response->id, $response->status );
        $order->add_order_note($message);
      }
      return $response;
    }
      
  }
