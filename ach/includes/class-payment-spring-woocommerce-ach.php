<?php


class WC_ACH_PaymentSpring{

  private $subscriptions_enabled = false;

  public function __construct(){
    add_action( 'plugins_loaded', array( $this, 'init_payment_gateway' ) );
  } 

  // Add PaymentSpring as a payment gateway
  public function init_payment_gateway(){
    #if (class_exists('WC_Subscriptions_Order') && function_exists('wcs_create_renewal_order')){
    #  $this->subscriptions_enabled = true;
    #}
    if (!class_exists('WC_Payment_Gateway')) {
      return;
    }
    include_once(PAYMENT_SPRING_ACH_WC_PATH . "/includes/class-wc-gateway-paymentspring-ach.php");
    include_once(PAYMENT_SPRING_ACH_WC_PATH . "/includes/class-wc-gateway-paymentspring-subscriptions-ach.php");

    add_filter( 'woocommerce_payment_gateways', array($this, 'load_wc_gateway') );
    add_filter('plugin_action_links_' . plugin_basename(PAYMENT_SPRING_ACH_WC_FILE), array($this, 'plugin_action_links'));
  } 

  // Determine which gateway to load based on subscriptions existence 
  public function load_wc_gateway($methods){
    $methods[] = 'WC_Gateway_ACH_PaymentSpring'; 
    return $methods; 
  }

  // Add a settings link on the plugins list page 
  public function plugin_action_links($links){
    $setting_link = $this->get_setting_link();
    $setting_link_array = array('<a href="' . $setting_link . '">' . __('ACH Settings', 'paymentspring-ach-woocommerce-gateway') . '</a>');
    return array_merge($setting_link_array, $links);
  }

  // Get the URL for the settings page
  public function get_setting_link() {
    $use_id_for_slug = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;
    $slug = $use_id_for_slug ? 'paymentspring_ach' : strtolower( 'WC_Gateway_PaymentSpring_ACH' );
    return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $slug );
  }

}
