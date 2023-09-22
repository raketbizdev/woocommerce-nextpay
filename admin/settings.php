<?php
// admin/settings.php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

final class NextPay_Settings {

  private static $_instance = null;

  public static function instance() {
      if (is_null(self::$_instance)) {
          self::$_instance = new self();
      }
      return self::$_instance;
  }

  private function __construct() {
      add_filter('woocommerce_settings_tabs_array', [$this, 'add_settings_tab'], 50);
      add_action('woocommerce_settings_tabs_nextpay_settings', [$this, 'settings_tab']);
      add_action('woocommerce_update_options_nextpay_settings', [$this, 'update_settings']);
  }

  public function add_settings_tab($settings_tabs) {
      $settings_tabs['nextpay_settings'] = __('NextPay Settings', 'woo_nextpay');
      return $settings_tabs;
  }

  public function settings_tab() {
      woocommerce_admin_fields($this->get_settings());
  }

  public function update_settings() {
      woocommerce_update_options($this->get_settings());
  }

  public function get_settings() {
    return [
        'section_title' => [
            'name'     => __('NextPay Settings', 'woo_nextpay'),
            'type'     => 'title',
            'desc'     => '',
            'id'       => 'wc_nextpay_section_title'
        ],
        'api_mode' => [
            'name' => __('API Mode', 'woo_nextpay'),
            'type' => 'select',
            'desc' => __('Choose which API environment to use.', 'woo_nextpay'),
            'id'   => 'wc_nextpay_api_mode',
            'options' => [
                'sandbox' => __('Sandbox', 'woo_nextpay'),
                'production' => __('Production', 'woo_nextpay')
            ],
            'default' => 'sandbox'
        ],
        'api_mode_settings_end' => [
          'type' => 'sectionend',
          'id'   => 'wc_nextpay_wc_nextpay_api_mode_end'
      ],
      'sandbox_settings_title' => [
        'name' => __('Sandbox Settings', 'woo_nextpay'),
        'type' => 'title',
        'desc' => sprintf(
            __('Get your Sandbox API keys from <a href="%s" target="_blank">Nextpage sandbox page</a>.', 'woo_nextpay'),
            'https://app-sandbox.nextpay.world/#/login'
        ),
        'id'   => 'wc_nextpay_sandbox_settings_title'
        ],
        'sandbox_client_key' => [
            'name' => __('Sandbox Client Key', 'woo_nextpay'),
            'type' => 'text',
            'desc' => __('Enter your NextPay Sandbox Client Key', 'woo_nextpay'),
            'id'   => 'wc_nextpay_sandbox_client_key'
        ],
        'sandbox_secret_key' => [
            'name' => __('Sandbox Secret Key', 'woo_nextpay'),
            'type' => 'text',
            'desc' => __('Enter your NextPay Sandbox Secret Key', 'woo_nextpay'),
            'id'   => 'wc_nextpay_sandbox_secret_key'
        ],
        'sandbox_settings_end' => [
            'type' => 'sectionend',
            'id'   => 'wc_nextpay_sandbox_settings_end'
        ],
        'production_settings_title' => [
            'name' => __('Production Settings', 'woo_nextpay'),
            'type' => 'title',
            'desc' => sprintf(
                __('Get your Production API keys from <a href="%s" target="_blank">Nextpay page</a>.', 'woo_nextpay'),
                'https://app.nextpay.world/'
            ),
            'id'   => 'wc_nextpay_production_settings_title'
        ],
        'production_client_key' => [
            'name' => __('Production Client Key', 'woo_nextpay'),
            'type' => 'text',
            'desc' => __('Enter your NextPay Production Client Key', 'woo_nextpay'),
            'id'   => 'wc_nextpay_production_client_key'
        ],
        'production_secret_key' => [
            'name' => __('Production Secret Key', 'woo_nextpay'),
            'type' => 'text',
            'desc' => __('Enter your NextPay Production Secret Key', 'woo_nextpay'),
            'id'   => 'wc_nextpay_production_secret_key'
        ],
        'section_end' => [
            'type' => 'sectionend',
            'id'   => 'wc_nextpay_section_end'
        ]
    ];
  }


}

// Initialization
NextPay_Settings::instance();
