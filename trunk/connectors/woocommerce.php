<?php
require_once(dirname(dirname(__FILE__)).'/api.php');

class WooCommerceConnector {
  
  public function __construct() {
    // init hooks etc
    $this->wc = WooCommerce::instance();

    add_action('woocommerce_new_order', array($this, 'sync_order'));
    add_action('woocommerce_order_status_changed', array($this, 'sync_order'));
  }
  
  public function connect($shop_id) {
    return Belco_API::post('/shops/connect', array(
      'id' => $shop_id,
      'type' => 'woocommerce',
      'url' => get_site_url()
    ));
  }
  
  public function sync_order($id) {
    $order = $this->get_order($id);
    if ($order) {
      Belco_API::post('/sync/order', $order);
    }
  }
  
  public function get_customers() {
    $users = get_users(array(
      'fields'  => 'ID',
      'role'    => 'customer',
      'orderby' => 'registered'
    ));
    
    $customers = array();
    foreach ($users as $user_id) {
      $customers[] = current($this->get_customer($user_id));
    }

    return $customers;
  }
  
  public function get_customer($id) {
    $user = get_userdata($id);
    
    if (!$user) {
      return null;
    }
    
    $customer = array(
      'id' => $user->ID,
      'email' => $user->user_email,
      'name' => get_user_meta($user->ID, 'billing_first_name', true) . ' ' . get_user_meta($user->ID, 'billing_last_name', true),
      'signedUp' => strtotime($user->user_registered),
      'phoneNumber' => get_user_meta($user->ID, 'billing_phone', true),
      'country' => get_user_meta($user->ID, 'billing_country', true),
      'city' => get_user_meta($user->ID, 'billing_city', true)
    );
    return $customer;
  }
  
  public function get_cart() {
    $items = array();

    foreach ( $this->wc->cart->get_cart() as $cart_item_key => $cart_item ) {
      $product = $cart_item['data'];

      if ( $product && $product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
        $items[] = array(
          'id' => $cart_item['product_id'],
          'name' => $product->get_title(),
          'price' => $cart_item['line_total'],
          'url' => $product->get_permalink(),
          'quantity' => $cart_item['quantity']
        );
      }
    }
    
    $cart = array(
      'currency' => get_woocommerce_currency(),
      'total' => $this->wc->cart->total,
      'subtotal' => $this->wc->cart->subtotal,
      'items' => $items
    );
    
    return $cart;
  }
  
  public function get_orders() {
    
  }

  public function get_order($id) {
    $order = new WC_Order($id);
    if (!$order) {
      return null;
    }
    return array(
      'id' => $order->id,
      'note' => $order->customer_note,
      'number' => $order->get_order_number(),
      'date' => strtotime($order->order_date),
      'createdAt' => strtotime($order->order_date),
      'updatedAt' => strtotime($order->modified_date),
      'status' => $order->get_status(),
      'currency' => $order->get_order_currency(),
      'products' => $order->get_order_products($order),
      'total' => wc_format_decimal( $order->get_total(), 2 ),
      'customer' => array(
        'id' => $order->customer_user,
        'email' => $order->billing_email,
        'phoneNumber' => $order->billing_phone,
        'firstName' => $order->billing_first_name,
        'lastName' => $order->billing_last_name,
      )
    );
  }
  
  public function get_order_products($order) {
    return array_map(function($item) {
      return array(
        'name' => $item->name,
        'quantity' => $item->qty,
        'price' => $item->line_total
      );
    }, $order->get_items());
  }
  
}