<?php
/**
 * Plugin Name: PaymentSpring for WooCommerce 
 * Plugin URI: https://www.paymentspring.com/docs/integrations/wordpress
 * Description: Integrates WooCommerce and PaymentSpring.
 * Version: 2.0.6
 * Author: PaymentSpring
 * Author URI: https://www.paymentspring.com/
 * License: GPL2
 *
 * ----------------------------------------------------------------------------
 * Copyright 2017 PaymentSpring
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


// Don't allow this class to be accessed directly
defined( 'ABSPATH' ) or die();

// Autoload composer files
require_once(__DIR__ . "/vendor/autoload.php");

// Create constants so we can reference this file elsewhere in the plugin 
$payment_spring_woo_file = __FILE__;

define('PAYMENT_SPRING_WC_BASE_FILE', $payment_spring_woo_file);
define('PAYMENT_SPRING_WC_BASE_PATH', WP_PLUGIN_DIR . '/' . basename(dirname($payment_spring_woo_file)));

define('PAYMENT_SPRING_WC_FILE', $payment_spring_woo_file);
define('PAYMENT_SPRING_WC_PATH', WP_PLUGIN_DIR . '/' . basename(dirname($payment_spring_woo_file)) . '/credit-card');

define('PAYMENT_SPRING_ACH_WC_FILE', $payment_spring_woo_file);
define('PAYMENT_SPRING_ACH_WC_PATH', WP_PLUGIN_DIR . '/' . basename(dirname($payment_spring_woo_file)). '/ach');

define('PAYMENT_SPRING_PLUGIN_VERSION', '2.0.6');

// Load the plugins
require("credit-card/includes/class-payment-spring-woocommerce.php");
require("ach/includes/class-payment-spring-woocommerce-ach.php");

new WC_PaymentSpring();
new WC_ACH_PaymentSpring();
