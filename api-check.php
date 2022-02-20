<?php

/**
 * ApiCheck WooCommerce Postcode Checker
 *
 * @package       APICHECK
 * @author        ApiCheck.nl
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   ApiCheck WooCommerce Postcode Checker
 * Plugin URI:    https://apicheck.nl/wordpress-woocommerce
 * Description:   Deze plugin helpt de gebruikers bij het invullen van adresgegevens. Het doel van de plugin is het voorkomen van fouten tijdens het invullen van adresgegevens. Zodra een Nederlandse of Belgische gebruikers zijn/haar postcode en huisnummer invult haalt onze database direct de straat- en plaatsnaam erbij.
 * Version:       1.0.0
 * Author:        ApiCheck
 * Author URI:    https://apicheck.nl/
 * Text Domain:   apicheck-woocommerce-postcode-checker
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with ApiCheck Postcode Checker. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

// Plugin name
define('APICHECK_NAME', 'ApiCheck Postcode Checker');

// Plugin version
define('APICHECK_VERSION', '1.0.0');

// Plugin Root File
define('APICHECK_PLUGIN_FILE', __FILE__);

// Plugin base
define('APICHECK_PLUGIN_BASE', plugin_basename(APICHECK_PLUGIN_FILE));

// Plugin Folder Path
define('APICHECK_PLUGIN_DIR', plugin_dir_path(APICHECK_PLUGIN_FILE));

// Plugin Folder URL
define('APICHECK_PLUGIN_URL', plugin_dir_url(APICHECK_PLUGIN_FILE));

class APICheck
{
   function __construct()
   {
      add_action('init', array($this, 'ac_start_from_here'));
      if (get_option('ac_enable_disabled') == 1) {

         add_action('wp_enqueue_scripts', array($this, 'ac_enqueue_script_front'));
         add_action('admin_init', array($this, 'bpem_if_woocommerce_not_active'));
         add_filter("woocommerce_checkout_fields",  array($this, "custom_override_checkout_fields"), 1);
         add_filter('woocommerce_default_address_fields',  array($this, 'custom_override_default_locale_fields'));

         add_action('woocommerce_checkout_process',  array($this, 'ac_validate_new_checkout_field'));
         add_action('woocommerce_checkout_update_order_meta', array($this, 'ac_save_new_checkout_field'));
         add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'ac_show_new_checkout_field_order'), 10, 1);
         add_action('woocommerce_email_after_order_table',  array($this, 'ac_show_new_checkout_field_emails'), 20, 4);

         add_action('woocommerce_before_order_notes', array($this, 'ac_add_custom_checkout_field'));
      }
   }

   // Check If WooCommerce exists
   function bpem_if_woocommerce_not_active($message)
   {
      if (!is_plugin_active('woocommerce/woocommerce.php')) {
         echo $message .= "<div class='notice notice-error is-dismissible'><h4> WooCommerce is niet actief. Installeer WooCommerce om deze plugin te kunnen gebruiken.</h4></div>";
         deactivate_plugins('/api-check/api-check.php');
      }
   }

   // Add plugin files
   function ac_start_from_here()
   {
      require_once plugin_dir_path(__FILE__) . 'front/ac_search_address.php';
      require_once plugin_dir_path(__FILE__) . 'back/ac_options_page.php';
   }

   // Enqueue Style and Scripts
   function ac_enqueue_script_front()
   {
      //Style & Script
      wp_enqueue_style('ac-style', plugins_url('assets/css/ac.css', __FILE__), '1.0.0', 'all');
      wp_enqueue_script('ac-script', plugins_url('assets/js/ac.js', __FILE__), array('jquery'), '1.0.0', true);
      wp_localize_script(
         'ac-script',
         'ac_ajax_object',
         array('ajax_url' => admin_url('admin-ajax.php'))
      );
   }

   // Add House number Field
   function ac_add_custom_checkout_field($checkout)
   {
      $current_user = wp_get_current_user();
      $saved_house_no = $current_user->house_no;
      woocommerce_form_field('house_no', array(
         'type' => 'text',
         'class' => array('form-row-wide houseNo househidden'),
         'label' => 'House No',
         'placeholder' => '1',
         'required' => true,
         'default' => $saved_house_no,
      ), $checkout->get_value('house_no'));
   }

   // Validate House number Field
   function ac_validate_new_checkout_field()
   {
      if (!$_POST['house_no']) {
         wc_add_notice('Please enter your house no', 'error');
      }
   }

   // Save Field
   function ac_save_new_checkout_field($order_id)
   {
      if ($_POST['house_no']) update_post_meta($order_id, '_house_no', esc_attr($_POST['house_no']));
   }

   // Display In order details
   function ac_show_new_checkout_field_order($order)
   {
      $order_id = $order->get_id();
      if (get_post_meta($order_id, '_house_no', true)) echo '<p><strong>House No:</strong> ' . get_post_meta($order_id, '_house_no', true) . '</p>';
   }

   // Display In emails  
   function ac_show_new_checkout_field_emails($order, $sent_to_admin, $plain_text, $email)
   {
      if (get_post_meta($order->get_id(), '_house_no', true)) echo '<p><strong>License Number:</strong> ' . get_post_meta($order->get_id(), '_house_no', true) . '</p>';
   }

   // Re-arrange checkout form fields
   function custom_override_checkout_fields($fields)
   {
      $fields['billing']['billing_first_name']['priority'] = 1;
      $fields['billing']['billing_last_name']['priority'] = 2;
      $fields['billing']['billing_company']['priority'] = 3;
      $fields['billing']['billing_country']['priority'] = 4;
      $fields['billing']['billing_state']['priority'] = 5;
      $fields['billing']['billing_address_1']['priority'] = 6;
      $fields['billing']['billing_address_2']['priority'] = 7;
      $fields['billing']['billing_city']['priority'] = 8;
      $fields['billing']['billing_postcode']['priority'] = 9;
      $fields['billing']['billing_email']['priority'] = 10;
      $fields['billing']['billing_phone']['priority'] = 11;
      return $fields;
   }

   // Re-arrange checkout form fields by priority
   function custom_override_default_locale_fields($fields)
   {
      $fields['postcode']['priority'] = 4;
      $fields['address_2']['priority'] = 5;
      $fields['address_1']['priority'] = 6;
      $fields['city']['priority'] = 7;

      return $fields;
   }
} // class ends

// CHECK WETHER CLASS EXISTS OR NOT.
if (class_exists('APICheck')) {
   $obj = new APICheck();
}
