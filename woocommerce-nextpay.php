<?php
/**
 * Plugin Name: WooCommerce NextPay
* Description: The WooCommerce NextPay Extension bridges your online store with NextPay's robust payment gateway. With this extension, store owners can effortlessly offer their customers a wider range of payment options, ensuring a smoother checkout experience. Key functionalities include real-time payment processing, seamless handling of disbursements, and easy management of invoices and deposits. Elevate your WooCommerce store's capabilities and provide your customers with a trusted and reliable payment solution.
 *
 * Version: 1.0.0
 * Author: Ruel Nopal
 * Author URI: https://rnopal.com
 * Plugin URI: https://rnopal.com
 * Text Domain: woo_nextpay
 * Requires at least: 5.0
 * Tested up to: 5.8
 * WC requires at least: 3.0.0
 * WC tested up to: 5.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

final class WC_NextPay {

    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init_plugin']);
    }

    public function init_plugin() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        $this->enqueue_admin_styles();
    }

    private function define_constants() {
        define('WC_NEXTPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
    }

    private function includes() {
        require WC_NEXTPAY_PLUGIN_DIR . 'admin/settings.php';
        require WC_NEXTPAY_PLUGIN_DIR . 'includes/class-nextpay-gateway.php';
    }

    private function init_hooks() {
        add_filter('woocommerce_payment_gateways', [$this, 'add_nextpay_gateway_class']);
    }

    public function add_nextpay_gateway_class($methods) {
        $methods[] = WC_NextPay_Gateway::instance();
        return $methods;
    }

    private function enqueue_admin_styles() {
        add_action('admin_enqueue_scripts', [$this, 'nextpay_admin_styles']);
    }

    public function nextpay_admin_styles($hook) {
        if ('woocommerce_page_wc-settings' !== $hook) {
            return;
        }
        wp_enqueue_style('nextpay_admin_styles', plugin_dir_url(__FILE__) . 'assets/admin.css');
    }

}

function wc_nextpay() {
    return WC_NextPay::instance();
}

// Initialization
wc_nextpay();

