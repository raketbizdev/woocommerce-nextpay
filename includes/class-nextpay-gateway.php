<?php
// includes/class-nextpay-gateway.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class WC_NextPay_Gateway extends WC_Payment_Gateway {

    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->id                 = 'nextpay';
        $this->icon               = plugin_dir_url(dirname(__FILE__)) . 'assets/nextpay-logo-dark.svg';
        $this->has_fields         = false;
        $this->method_title       = __('NextPay', 'woo_nextpay');
        $this->method_description = __('Accept payments via NextPay.', 'woo_nextpay');
        
        $this->supports = array(
            'products'
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
    }

    public function init_form_fields() {
      $this->form_fields = [
          'enabled' => [
            'title'   => __('Enable/Disable', 'woo_nextpay'),
            'type'    => 'checkbox',
            'label'   => __('Enable NextPay Payment', 'woo_nextpay'),
            'default' => 'no',
        ],
          'title' => [
              'title'       => __('Title', 'woo_nextpay'),
              'type'        => 'text',
              'description' => __('This controls the title which the user sees during checkout.', 'woo_nextpay'),
              'default'     => __('NextPay', 'woo_nextpay'),
              'desc_tip'    => true,
          ],
          'description' => [
              'title'       => __('Description', 'woo_nextpay'),
              'type'        => 'textarea',
              'description' => __('This controls the description which the user sees during checkout.', 'woo_nextpay'),
              'default'     => __('Pay via NextPay; you can pay with your credit card if you don’t have a NextPay account.', 'woo_nextpay'),
              'desc_tip'    => true,
          ]
      ];
    }
    public function enqueue_styles() {
      // Enqueue for the front end
      wp_enqueue_style('nextpay-frontend', plugin_dir_url(dirname(__FILE__)) . 'assets/frontend.css');
  
      // If you also have CSS for the admin dashboard, enqueue it like so:
      // wp_enqueue_style('nextpay-admin', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css');
    }
    public function enqueue_scripts() {
      wp_enqueue_script('nextpay-custom-js', plugin_dir_url(dirname(__FILE__)) . 'assets/js/custom.js', array('jquery'), '', true);
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Here you would make the necessary API calls to process the payment
        // and then handle the response accordingly.

        // For this example, we'll assume the payment was successful:
        $order->payment_complete();
        $order->add_order_note(__('Payment successfully processed using NextPay.', 'woo_nextpay'));
        
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order)
        ];
    }



}

// Initialization
WC_NextPay_Gateway::instance();
