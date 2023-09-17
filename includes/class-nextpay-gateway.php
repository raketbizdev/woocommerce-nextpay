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
        $this->icon               = plugin_dir_url(__FILE__) . 'assets/nextpay-logo.svg';
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
        add_filter('woocommerce_settings_api_validate', [$this, 'validate_api_key'], 10, 3);
        
    }

    public function validate_api_key($validated_input, $input, $instance) {
      if ($instance !== $this) {
          return $validated_input;
      }
  
      if (isset($input['enabled']) && $input['enabled'] === 'yes') {
          $api_key_sandbox = $input['api_key_sandbox'] ?? ''; // Adjust this to your field name
          $api_key_production = $input['api_key_production'] ?? ''; // Adjust this to your field name
  
          if (empty($api_key_sandbox) && empty($api_key_production)) {
              WC_Admin_Settings::add_error(__('NextPay gateway cannot be enabled without a valid API key.', 'woo_nextpay'));
              $validated_input['enabled'] = 'no';
          }
      }
  
      return $validated_input;
  }

    /**
     * Validate the "enabled" field.
     *
     * @param string $key Field key.
     * @param string $value Posted Value.
     * @return string
     */
    public function validate_enabled_field($key, $value) {
      if ($value === 'yes') {
          $api_key_sandbox = $_POST['woocommerce_nextpay_api_key_sandbox'] ?? '';
          $api_key_production = $_POST['woocommerce_nextpay_api_key_production'] ?? '';
  
          if (empty($api_key_sandbox) && empty($api_key_production)) {
              WC_Admin_Settings::add_error(__('NextPay gateway cannot be enabled without a valid API key.', 'woo_nextpay'));
              return 'no';  // Return 'no' to ensure the gateway isn't enabled.
          }
      }
      return $value;
  }
  

    public function is_available() {
        $is_available = parent::is_available();
        
        // Check if the API key is available
        $api_key = get_option('wc_nextpay_api_key'); // Fetch this from where you store the API key.
        if (empty($api_key)) {
            $is_available = false;
        }

        return $is_available;
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
              'title'   => __('Enable/Disable', 'woo_nextpay'),
              'type'    => 'checkbox',
              'label'   => __('Enable NextPay Payment', 'woo_nextpay'),
              'default' => 'no',
              'validate' => 'validate_enabled_field', // Add this line for custom validation
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
