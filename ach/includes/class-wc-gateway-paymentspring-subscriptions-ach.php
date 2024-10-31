<?php

class WC_Gateway_ACH_PaymentSpring_Subscriptions extends WC_Gateway_ACH_PaymentSpring {
  public function __construct(){
    parent::__construct();

    // Only enable actions if the WooCommerce Subscriptions plugin is installed
    if(class_exists('WC_Subscriptions_Order')){
      add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
			add_action('wcs_resubscribe_order_created', array($this, 'delete_resubscribe_meta'), 10);
    }
  }

  // Process and handle errors messages for schedules subscription payments
  public function scheduled_subscription_payment($amount_to_charge, $renewal_order){
		$response = $this->process_subscription_payment($amount_to_charge, $renewal_order);

		if(is_wp_error( $response )){
      $message = sprintf( 
        __('PaymentSpring Transaction Failed (%s)', 'paymentspring-woocommerce-gateway'), $response->get_error_message()
      );
			$renewal_order->update_status('failed', $message);
		}
  }

  public function render_subscription_payment_method( $payment_method_to_display, $subscription ) {
    if ($subscription->payment_method !== $this->id) {
      return $payment_method_to_display;
    }
  }

  // Once the user has been charged, tell woocommerce that this subscription payment has been processed 
	public function delete_resubscribe_meta($resubscribe_order) {
		delete_post_meta($resubscribe_order->id, '_paymentspring_customer_id');
		$this->delete_renewal_meta($resubscribe_order);
	}

  // Charge a PaymentSpring customer for their subscription.
  // This is executed on renewals, WC_Gateway_PaymentSpring handles the initial payment.
	protected function process_subscription_payment($amount = 0, $order = '') {
    $customerId = get_user_meta($order->customer_user, '_paymentspring_customer_id', true);

		if (!$customerId) {
			return new WP_Error('paymentspring_error', __('Customer not found', 'paymentspring-woocommerce-gateway'));
		}

		$order_id = version_compare(WC_VERSION, '3.0.0', '<') ? $order->id : $order->get_id();
    $description = __("Recurring Payment made via WooCommerce");
		$response = $this->chargeCustomer($customerId, $order, $description);

		if(!is_wp_error($response)){
			$this->process_payment_success($response, $order);
		}
		return $response;
	}

  // Determine if an order is a subscription
  protected function is_subscription($order_id){
		return function_exists('wcs_order_contains_subscription') && 
      (wcs_order_contains_subscription($order_id) || 
        wcs_is_subscription($order_id) || 
          wcs_order_contains_renewal($order_id));
  }
}
