<?php
require_once(dirname(dirname(__FILE__)).'/api.php');

class WooCommerceConnector {

  public function __construct() {
    // init hooks etc
    $this->wc = WooCommerce::instance();

    add_action('woocommerce_thankyou', array($this, 'order_completed'));

    add_filter('woocommerce_api_query_args', array($this, 'api_order_search_custom_fields'), 20, 2);
  }

  public function connect($shop_id, $secret) {
    return Belco_API::post('/shops/connect', array(
      'id' => $shop_id,
      'type' => 'woocommerce',
      'url' => get_site_url()
    ), array('secret' => $secret));
  }

  public function order_completed($id) {
    $customer = $this->get_customer_from_order($id);
    if ($customer) {
      Belco_API::post('/sync/customer', $customer);
    }
  }

  public function api_order_search_custom_fields($args, $request_args) {
    global $wpdb;

		if ( empty( $request_args['email'] ) ) {
			return $args;
		}

		// Search orders
		$post_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT DISTINCT p1.post_id
				FROM {$wpdb->postmeta} p1
				INNER JOIN {$wpdb->postmeta} p2 ON p1.post_id = p2.post_id
				WHERE
					( p1.meta_key = '_billing_email' AND p1.meta_value LIKE '%%%s%%' )
				",
				esc_attr( $request_args['email'] )
			)
		);

    if ( !empty ($args['post__in']) ) {
      $args['post__in'] = array_unique( array_merge( $args['post__in'], $post_ids ) );
    } else {
  		$args['post__in'] = $post_ids;
    }

    return $args;
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
    $cart = null;
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

    if (count($items)) {
      $cart = array(
        'currency' => get_woocommerce_currency(),
        'total' => $this->wc->cart->total,
        'subtotal' => $this->wc->cart->subtotal,
        'items' => $items
      );
    }

    return $cart;
  }

  public function get_customer_from_order($id) {
    $order = new WC_Order($id);
    if (!$order) {
      return null;
    }

    if ($order->customer_user) {
      $customer = $this->get_customer($order->customer_user);
    } else {
      $customer = array(
        'email' => $order->billing_email,
        'phoneNumber' => $order->billing_phone,
        'name' => $order->billing_first_name . ' ' . $order->billing_last_name,
        'country' => $order->billing_country,
        'city' => $order->billing_city,
      );
    }

    return array_merge(array(
      'ipAddress' => $order->customer_ip_address,
      'userAgent' => $order->customer_user_agent
    ), $customer);
  }

}
