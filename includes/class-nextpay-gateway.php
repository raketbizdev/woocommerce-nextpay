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
  private function get_api_mode() {
    return NextPay_Settings::get_api_mode();
  }

  private function get_key_based_on_mode($base_key) {
    $mode = $this->get_api_mode();
    return get_option("wc_nextpay_{$mode}_{$base_key}", '');
  }

  private function get_client_key() {
    return $this->get_key_based_on_mode('client_key');
  }

  private function get_secret_key() {
    return $this->get_key_based_on_mode('secret_key');
  }
  public function process_payment($order_id) {
    $order = wc_get_order($order_id);
  
    $request_body = $this->generate_request_body($order);
    // error_log('Request body: ' . $request_body);
    $headers = $this->generate_headers($request_body);
  
    $response = $this->send_request($request_body, $headers);
  
    return $this->handle_response($response, $order);
  }
  
  private function generate_request_body($order) {
    // Get order items and construct a description array
    $items = $order->get_items();
    $description_array = [];

    foreach ($items as $item) {
        $product = $item->get_product();
        $description_array[] = [
            'product_name' => $product->get_name(),
            'quantity' => $item->get_quantity(),
            'price' => $product->get_price()
        ];
    }

    // Convert the description array to a JSON string
    $description_json = json_encode($description_array, JSON_UNESCAPED_SLASHES);

    return json_encode([
        'title' => $order->get_billing_company(),
        'amount' => $order->get_total(),
        'currency' => $order->get_currency(),
        'description' => $description_json,
        'private_notes' => 'Order ' . $order->get_order_number(),
        'limit' => 0,
        'redirect_url' => $this->get_return_url($order),
        'nonce' => round(microtime(true) * 1000)
    ], JSON_UNESCAPED_SLASHES);
  }

  
  private function generate_headers($request_body) {
    $signature = hash_hmac('sha256', $request_body, $this->get_secret_key());
    return [
      "Accept: application/json",
      "Content-Type: application/json",
      "client-id: " . $this->get_client_key(),
      "idempotency-key: " . uniqid(),
      "signature: " . $signature
    ];
  }
  
  private function send_request($request_body, $headers) {
    $api_endpoint = WC_NextPay::instance()->get_api_endpoint();
  
    $curl = curl_init();
  
    curl_setopt_array($curl, [
      CURLOPT_URL => $api_endpoint . "/paymentlinks",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $request_body,
      CURLOPT_HTTPHEADER => $headers
    ]);
  
    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    error_log('NextPay Response Body: ' . $response);
    $err = curl_error($curl);
  
    curl_close($curl);
  
    if ($err) {
      throw new Exception(__('Payment error:', 'woo_nextpay') . $err);
    }
  
    return json_decode($response, true);
  }
  
  private function handle_response($response_data, $order) {
    // Check if response_data is an array and contains the necessary keys
    if (!is_array($response_data) || !isset($response_data['status']) || !isset($response_data['url'])) {
        wc_add_notice(__('Payment error: Invalid response from NextPay.', 'woo_nextpay'), 'error');
        error_log('NextPay Invalid Response: ' . print_r($response_data, true));
        return;
    }

    if ($response_data['status'] == 'enabled') {
        $order->payment_complete();
        $order->add_order_note(__('Payment successfully processed using NextPay.', 'woo_nextpay'));
        return [
            'result'   => 'success',
            'redirect' => $response_data['url']
        ];
    } else {
        $errorMessage = isset($response_data['description']) ? $response_data['description'] : 'Unknown error';
        wc_add_notice(__('Payment error:', 'woo_nextpay') . $errorMessage, 'error');
        return;
    }
  }

  private function generate_invoice_data($order) {
    return [
        'title' => $order->get_billing_company(),
        'description' => 'Order details for order #' . $order->get_order_number(),
        'amount' => $order->get_total(),
        'currency' => $order->get_currency(),
        'invoice_number' => $order->get_order_number(),
        'due_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'invoice_date' => date('Y-m-d H:i:s'),
        'footer_notes' => 'Please pay the fees before the due date.',
        'private_notes' => 'Thank you for your purchase!',
        'status' => 'published',
        'url' => $order->get_view_order_url()
    ];
  }

  private function send_invoice_email($order_id) {
    $order = wc_get_order($order_id);
    $invoice_data = $this->generate_invoice_data($order);
    $invoice_content = $this->generate_invoice_content($invoice_data);

    // Email details
    $to = $order->get_billing_email();
    $subject = 'Invoice #' . $invoice_data['invoice_number'] . ' from ' . $invoice_data['title'];
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    // Use WordPress's wp_mail function to send the email
    wp_mail($to, $subject, $invoice_content, $headers);
  }
  private function generate_invoice_content($invoice_data) {
      $content = '<h2>Invoice #' . $invoice_data['invoice_number'] . '</h2>';
      $content .= '<p><strong>Title:</strong> ' . $invoice_data['title'] . '</p>';
      $content .= '<p><strong>Description:</strong> ' . $invoice_data['description'] . '</p>';
      $content .= '<p><strong>Amount:</strong> ' . $invoice_data['amount'] . ' ' . $invoice_data['currency'] . '</p>';
      $content .= '<p><strong>Due Date:</strong> ' . $invoice_data['due_date'] . '</p>';
      $content .= '<p><strong>Invoice Date:</strong> ' . $invoice_data['invoice_date'] . '</p>';
      $content .= '<p><strong>Footer Notes:</strong> ' . $invoice_data['footer_notes'] . '</p>';
      $content .= '<p><strong>Private Notes:</strong> ' . $invoice_data['private_notes'] . '</p>';
      $content .= '<p><strong>Status:</strong> ' . $invoice_data['status'] . '</p>';
      $content .= '<p><a href="' . $invoice_data['url'] . '">View Invoice Online</a></p>';

      return $content;
  }

}

WC_NextPay_Gateway::instance();
