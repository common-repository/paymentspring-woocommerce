<?php

  if (!defined('ABSPATH')){
    exit;
  }

  return apply_filters('wc_paymentspring_ach_settings',
    array(
      'enabled' => array(
        'title' => __( 'Enable/Disable', 'woocommerce' ),
        'type' => 'checkbox',
        'label' => __( 'Enable PaymentSpring ACH', 'woocommerce' ),
        'default' => 'yes'
      ),
      'title' => array(
        'title' => __( 'Title', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
        'default' => __( 'ACH (PaymentSpring)', 'woocommerce' ),
        'desc_tip'      => true,
      ),
      'api_mode' => array(
        'title' => __( 'API Mode', 'wc-paymentspring-ach' ),
        'type' => 'select',
        'label' => __( 'Use live or test keys', 'wc-paymentspring-ach' ),
        'default' => 'test',
        'options' => array(
          'test' => __('Test Mode', 'wc-paymentspring-ach'),
          'live' => __('Live Mode', 'wc-paymentspring-ach')
        )
      ),
      'test_public_key' => array(
        'title' => __( 'Test Public Key', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Your PaymentSpring Public Test Key. (Begins with "test_")', 'woocommerce' ),
        'default' => "",
        'desc_tip' => true,
      ),
      'test_private_key' => array(
        'title' => __( 'Test Private Key', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Your PaymentSpring Private Test Key. (Begins with "test_")', 'woocommerce' ),
        'default' => "",
        'desc_tip' => true,
      ),
      'live_public_key' => array(
        'title' => __( 'Live Public Key', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Your PaymentSpring Public Live Key. ', 'woocommerce' ),
        'default' => "",
        'desc_tip'      => true,
      ),
      'live_private_key' => array(
        'title' => __( 'Live Private Key', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Your PaymentSpring Private Live Key.', 'woocommerce' ),
        'default' => "",
        'desc_tip'      => true,
      ),
    )
  );
