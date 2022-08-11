<?php


/**
 * ApiCheck Address Validator
 *
 * @package       APICHECK
 * @author        ApiCheck.nl
 * @license       gplv2
 * @version       1.0.3
 *
 * @wordpress-plugin
 * Plugin Name:   ApiCheck | Automatische Adres Aanvulling
 * Plugin URI:    https://apicheck.nl/wordpress-woocommerce
 * Description:   Deze plugin helpt de gebruikers bij het invullen van adresgegevens. Het doel van de plugin is het voorkomen van fouten tijdens het invullen van adresgegevens. Zodra een Nederlandse, Belgische of Luxemburgse gebruikers zijn/haar postcode en huisnummer invult haalt onze database direct de straat- en plaatsnaam erbij.
 * Version:       1.0.3
 * Author:        ApiCheck
 * Author URI:    https://apicheck.nl/
 * Text Domain:   apicheck-woocommerce-postcode-checker
 * Domain Path:   /languages
 * License:       GPLv3
 * License URI:   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * WC requires at least: 3.7.0
 * WC tested up to: 6.8.0
 * 
 * You should have received a copy of the GNU General Public License
 * along with ApiCheck Address Validator. If not, see <https://www.gnu.org/licenses/gpl-3.0.html/>.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

// Plugin name
define('APICHECK_NAME', 'ApiCheck | Automatische Adres Aanvulling');

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


if (!function_exists('write_log')) {

    function write_log($log)
    {
        error_log('------------------------------------------------');
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}


class APICheck
{
    private static $_supported_countries = array('NL', 'LU', 'BE');
    private static $_action = 'ac_search_address';

    private static $_billing = array(
        'prefix' => 'billing',
        'company' => '#billing_company',
        'country' => '#billing_country',
        'city' => '#billing_city',
        'state' => '#billing_state',
        'postcode' => '#billing_postcode',
        'address_1' => '#billing_address_1',
        'address_2' => '#billing_address_2',
        // Custom fields
        'street' => '#billing_street',
        'housenumber' => '#billing_housenumber',
        'housenumber_addition' => '#billing_housenumber_addition',
        'municipality_autocomplete' => '#billing_municipality_autocomplete',
        'street_autocomplete' => '#billing_street_autocomplete',
    );

    private static $_shipping = array(
        'prefix' => 'shipping',
        'company' => '#shipping_company',
        'country' => '#shipping_country',
        'city' => '#shipping_city',
        'state' => '#shipping_state',
        'postcode' => '#shipping_postcode',
        'address_1' => '#shipping_address_1',
        'address_2' => '#shipping_address_2',
        // Custom fields
        'street' => '#shipping_street',
        'housenumber' => '#shipping_housenumber',
        'housenumber_addition' => '#shipping_housenumber_addition',
        'municipality_autocomplete' => '#shipping_municipality_autocomplete',
        'street_autocomplete' => '#shipping_street_autocomplete',
    );

    function __construct()
    {
        add_action('init', array($this, 'ac_start_from_here'));
        if (get_option('ac_enable_disabled') == 1) {
            add_action('wp_enqueue_scripts', array($this, 'ac_enqueue_script_front'));
            add_action('admin_init', array($this, 'bpem_if_woocommerce_not_active'));
            add_filter('woocommerce_checkout_fields', array($this, 'custom_override_checkout_fields'), 1);
            add_action('woocommerce_checkout_posted_data', array($this, 'checkout_posted_data'));
            add_action('woocommerce_checkout_update_order_review', array($this, 'checkout_update_order_review'));

            add_action('woocommerce_after_checkout_validation', [$this, 'custom_checkout_validation']);
        }
    }

    // Check If WooCommerce exists
    function bpem_if_woocommerce_not_active($message)
    {
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            echo $message .= "<div class='notice notice-error is-dismissible'><h4>ApiCheck: WooCommerce is niet actief. Installeer WooCommerce om deze plugin te kunnen gebruiken.</h4></div>";
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
        if (function_exists('is_checkout') && is_checkout()) {
            //Styles
            wp_enqueue_style('ac-style', plugins_url('assets/css/apicheck.css', __FILE__), '1.0.0', 'all');
            wp_enqueue_style('ac-autocomplete-style', plugins_url('assets/css/autocomplete.css', __FILE__), '1.0.0', 'all');

            // Scripts
            wp_register_script('ac-script', plugins_url('assets/js/apicheck.js', __FILE__), array('jquery', 'woocommerce'), '1.0.0', true);
            wp_enqueue_script('ac-script');

            wp_localize_script('ac-script', 'apicheck_billing_fields', self::$_billing);
            wp_localize_script('ac-script', 'apicheck_shipping_fields', self::$_shipping);

            wp_localize_script('ac-script', 'apicheck_params', array(
                'url' => admin_url('admin-ajax.php'),
                'action' => self::$_action,
                'supported_countries' => self::$_supported_countries,
            ));

            // Autocomplete script
            wp_enqueue_script('ac-autocomplete-script', plugins_url('assets/js/autocomplete.min.js', __FILE__), '1.0.0', true);

            wp_localize_script(
                'ac-script',
                'ac_ajax_object',
                array('ajax_url' => admin_url('admin-ajax.php'))
            );
        }
    }

    // Re-arrange checkout form fields
    function custom_override_checkout_fields($fields)
    {
        // Billing fields
        $fields['billing']['billing_municipality_autocomplete'] = array(
            'id' => 'billing_municipality_autocomplete',
            'type' => 'text',
            'class' => array('form-row form-row-wide validate-required'),
            'label' => 'Gemeente of postcode',
            'placeholder' => 'Start met typen...',
            'required' => false,
        );
        $fields['billing']['billing_municipality_city'] = array(
            'id' => 'billing_municipality_city',
            'type' => 'text',
            'class' => array('form-row-wide hidden-checkout-field'),
            'label' => '',
            'placeholder' => '',
            'required' => false,
        );
        $fields['billing']['billing_municipality_postalcode'] = array(
            'id' => 'billing_municipality_postalcode',
            'type' => 'text',
            'class' => array('form-row-wide hidden-checkout-field'),
            'label' => '',
            'placeholder' => '',
            'required' => false,
        );

        $fields['billing']['billing_street_autocomplete'] = array(
            'id' => 'billing_street_autocomplete',
            'type' => 'text',
            'class' => array('form-row form-row-wide validate-required'),
            'label' => 'Straat',
            'placeholder' => 'Start met typen...',
            'required' => false,
        );

        $fields['billing']['billing_housenumber'] = array(
            'id' => 'billing_housenumber',
            'type' => 'text',
            'class' => array('form-row form-row-first validate-require'),
            'label' => 'Huisnummer',
            'placeholder' => '',
            'required' => true,
        );

        $fields['billing']['billing_housenumber_addition'] = array(
            'id' => 'billing_housenumber_addition',
            'type' => 'text',
            'class' => array('form-row form-row-last'),
            'label' => 'Toevoeging',
            'placeholder' => '',
            'required' => false,
        );

        $fields['billing']['billing_street'] = array(
            'type' => 'text',
            'class' => array('form-row form-row-wide validate-required'),
            'label' => 'Straat',
            'placeholder' => '',
            'required' => true,
        );

        // Shipping fields
        $fields['shipping']['shipping_municipality_autocomplete'] = array(
            'id' => 'shipping_municipality_autocomplete',
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => 'Gemeente of postcode',
            'placeholder' => 'Start met typen...',
            'required' => false,
        );
        $fields['shipping']['shipping_municipality_city'] = array(
            'id' => 'shipping_municipality_city',
            'type' => 'text',
            'class' => array('form-row-wide hidden-checkout-field'),
            'label' => '',
            'placeholder' => '',
            'required' => false,
        );
        $fields['shipping']['shipping_municipality_postalcode'] = array(
            'id' => 'shipping_municipality_postalcode',
            'type' => 'text',
            'class' => array('form-row-wide hidden-checkout-field'),
            'label' => '',
            'placeholder' => '',
            'required' => false,
        );

        $fields['shipping']['shipping_street_autocomplete'] = array(
            'id' => 'shipping_street_autocomplete',
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => 'Straat',
            'placeholder' => 'Start met typen...',
            'required' => false,
        );

        $fields['shipping']['shipping_housenumber'] = array(
            'type' => 'text',
            'class' => array('form-row form-row-first validate-require'),
            'label' => 'Huisnummer',
            'placeholder' => '',
            'required' => true,
        );

        $fields['shipping']['shipping_housenumber_addition'] = array(
            'type' => 'text',
            'class' => array('form-row form-row-last'),
            'label' => 'Toevoeging',
            'placeholder' => '',
            'required' => false,
        );

        $fields['shipping']['shipping_street'] = array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => 'Straat',
            'placeholder' => '',
            'required' => true,
        );

        $billing_order = array(
            "billing_first_name",
            "billing_last_name",
            "billing_company",
            "billing_country",
            "billing_municipality_autocomplete",
            "billing_municipality_city",
            "billing_municipality_postalcode",
            "billing_street_autocomplete",
            "billing_postcode",
            "billing_housenumber",
            "billing_housenumber_addition",
            "billing_street",
            "billing_city",
            "billing_phone",
            "billing_email"
        );

        $shipping_order = array(
            "shipping_first_name",
            "shipping_last_name",
            "shipping_company",
            "shipping_country",
            "shipping_municipality_autocomplete",
            "shipping_municipality_city",
            "shipping_municipality_postalcode",
            "shipping_street_autocomplete",
            "shipping_postcode",
            "shipping_housenumber",
            "shipping_housenumber_addition",
            "shipping_street",
            "shipping_city",
        );

        foreach ($billing_order as $field) {
            $ordered_billing_fields[$field] = $fields["billing"][$field];
        }

        foreach ($shipping_order as $field) {
            $ordered_shipping_fields[$field] = $fields["shipping"][$field];
        }

        // Set new fields
        $fields["billing"] = $ordered_billing_fields;
        $fields["shipping"] = $ordered_shipping_fields;

        return $fields;
    }

    function custom_override_checkout_fields_checkout($fields)
    {
        return $fields;
    }

    // This function makes sure the new fields are filled
    public function checkout_update_order_review($posted)
    {
        $data = array();
        $vars = explode('&', $posted);
        foreach ($vars as $k => $value) {
            $v = explode('=', urldecode($value));
            $data[$v[0]] = $v[1];
        }

        foreach (['billing', 'shipping'] as $type) {
            foreach ([$type . '_municipality_autocomplete', $type . '_municipality_city', $type . '_municipality_postalcode', $type . '_street_autocomplete', $type . '_housenumber', $type . '_housenumber_addition', $type . '_street'] as $key) {
                if (isset($data[$key]) && $data[$key]) {
                    WC()->session->set("customer_" . $key, $data[$key]);
                }
            }
        }
    }

    // This function manipulates the incomming (new) fields
    public function checkout_posted_data($posted)
    {
        if (get_option('ac_enable_disabled') == false) {
            return $posted;
        }

        foreach (['billing', 'shipping'] as $group) {
            $country = $posted[$group . '_country'];

            $streetName = $group . '_street';
            $streetNumber = $group . '_housenumber';
            $streetNumberSuffix = $group . '_housenumber_addition';

            if ($country === 'BE') {
                // Belgium magic
                $posted[$group . '_city'] = $posted[$group . '_municipality_city'];
                $posted[$group . '_postcode'] = $posted[$group . '_municipality_postalcode'];
                $posted[$group . '_street'] = $posted[$group . '_street_autocomplete'];
            }

            // Concatenate street all into address_1
            $posted[$group . '_address_1'] = $posted[$streetName] . ' ' . trim($posted[$streetNumber] . ' ' . ($posted[$streetNumberSuffix] ?? ''));
        }
        return $posted;
    }

    public function custom_checkout_validation($args)
    {
        write_log('args for validation below @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@');
        write_log($args);
    }
}

// CHECK WETHER CLASS EXISTS OR NOT.
if (class_exists('APICheck')) {
    $obj = new APICheck();
}
