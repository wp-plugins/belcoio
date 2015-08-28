<?php
/**
 * @package Belco
 * @version 0.3.4
 *
 */
/*
Plugin Name: Belco.io
Plugin URI: http://www.belco.io
Description: Telephony for webshops
Version: 0.3.5
Author: Forwarder B.V.
Author URI: http://www.forwarder.nl
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define('BELCO_HOST', 'app.belco.io');
define('BELCO_API_HOST', 'api.belco.io');
define('BELCO_USE_SSL', true);

if(!class_exists('WP_Belco')) {

  class WP_Belco {

    /**
     * Construct Belco
     */
    public function __construct() {
      $this->plugin_path = plugin_dir_path(__FILE__);

      // register filters
      add_action( 'init', array(&$this, 'init') );
      add_action( 'admin_init', array(&$this, 'admin_init') );
      add_action( 'admin_menu', array(&$this, 'add_menu') );
      add_action( 'plugins_loaded', array(&$this, 'enqueue_scripts') );
    }

    /**
     * Activate Belco
     */
    public static function activate() {

    }

    /**
     * Deactive Belco
     */
    public static function deactivate() {
      flush_rewrite_rules();
      delete_option('belco_shop_id');
      delete_option('belco_secret');
    }

    public static function user_role($role, $user_id = null) {
      if ( is_numeric( $user_id ) )
        $user = get_userdata( $user_id );
      else
        $user = wp_get_current_user();

      if ( empty( $user ) )
        return false;

      return in_array( $role, (array) $user->roles );
    }

    public function init() {
      require('connectors/woocommerce.php');
      $this->connector = new WooCommerceConnector();
    }

    /**
     * hook into WP's admin_init action hook
     */
    public function admin_init() {
      $this->init_settings();
      add_action( 'admin_notices', array(&$this, 'installation_notice') );
      add_action( 'admin_notices', array(&$this, 'woocommerce_notice') );
    }

    /**
     * Initialize some custom settings
     */

    public function init_settings() {
      register_setting('wp_belco', 'belco_shop_id');
      register_setting('wp_belco', 'belco_secret');

      add_action('pre_update_option_belco_shop_id', array(&$this, 'connect'));
    }

    public function enqueue_scripts() {
      if (!is_user_logged_in() || WP_Belco::user_role('customer')) {
        add_action('wp_footer', array(&$this, 'init_widget'));
      } else if(is_admin() && current_user_can('manage_options')){
        wp_enqueue_style( 'belco-admin', plugins_url('css/admin.css', __FILE__));
        wp_enqueue_script('belco-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), null, false);
      }
    }

    /**
     * Check if plugin installation is completed
     */

    public function installation_complete() {
      $shop_id = get_option('belco_shop_id');
      $secret = get_option('belco_secret');

      return !empty($shop_id) && !empty($secret);
    }

    /**
     * Create a menu
     */

    public function add_menu()
    {
      add_menu_page('Belco settings', 'Belco', 'manage_options', 'belco', array(&$this, 'settings_page'), null, 60);
      // add_submenu_page( 'belco', 'Belco settings', 'Settings', 'manage_options', 'belco-settings', array(&$this, 'settings_page'));
    }

    /**
     * Initialize the Belco client widget
     */

    public function init_widget() {
      $secret = get_option('belco_secret');
      $config = array(
        'shopId' => get_option('belco_shop_id')
      );

      if (is_user_logged_in() && WP_Belco::user_role('customer')) {
        $user = wp_get_current_user();

        if ($secret) {
          $config['hash'] = hash_hmac("sha256", $user->user_email, $secret);
        }
        $config = array_merge($config, $this->connector->get_customer($user->ID));
        $config['cart'] = $this->connector->get_cart();
      }

      include(sprintf("%s/templates/widget.php", dirname(__FILE__)));
    }

    /**
     * Dashboard page
     */

    public function dashboard_page()
    {
      if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
      }

      $installed = $this->installation_complete();

      $shop_id = get_option('belco_shop_id');

      $page = '/';
      if (!$installed) {
        $page = '/connect?type=woocommerce';
      }

      include(sprintf("%s/templates/dashboard.php", dirname(__FILE__)));
    }

    /**
     * Settings page
     */

    public function settings_page()
    {
      if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
      }

      $installed = $this->installation_complete();

      $shop_id = get_option('belco_shop_id');
      $secret = get_option('belco_secret');

      include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
    }

    /**
     * Show installation notice when Belco hasnt been configured yet
     */
    public function installation_notice() {
      if (!$this->installation_complete()) {
        include(sprintf("%s/templates/notice.php", dirname(__FILE__)));
      }
    }

    /**
     * Show notice when WooCommerce hasnt been activated yet
     */
    public function woocommerce_notice() {
      if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        include(sprintf("%s/templates/activate.php", dirname(__FILE__)));
      }
    }

    public function connect($shopId) {
      $result = $this->connector->connect($shopId);

      if ($result !== true)
        add_settings_error('belco_shop_id', 'shop-id', $result);

      return $shopId;
    }
  }

}

if(class_exists('WP_Belco'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_Belco', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_Belco', 'deactivate'));

    // instantiate the plugin class
    $wp_belco = new WP_Belco();
}
?>
