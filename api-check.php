<?php


/**
 * ApiCheck Address Validator
 *
 * @package       APICHECK
 * @author        ApiCheck.nl
 * @license       gplv2
 * @version       1.0.4
 *
 * @wordpress-plugin
 * Plugin Name:   ApiCheck | Automatische Adres Aanvulling
 * Plugin URI:    https://apicheck.nl/wordpress-woocommerce
 * Description:   Deze plugin helpt de gebruikers bij het invullen van adresgegevens. Het doel van de plugin is het voorkomen van fouten tijdens het invullen van adresgegevens. Zodra een Nederlandse, Belgische of Luxemburgse gebruikers zijn/haar postcode en huisnummer invult haalt onze database direct de straat- en plaatsnaam erbij.
 * Version:       1.0.4
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
define('APICHECKNL_NAME', 'ApiCheck | Automatische Adres Aanvulling');

// Plugin version
define('APICHECKNL_VERSION', '1.0.4');

// Plugin Root File
define('APICHECKNL_PLUGIN_FILE', __FILE__);

// Plugin base
define('APICHECKNL_PLUGIN_BASE', plugin_basename(APICHECKNL_PLUGIN_FILE));

// Plugin Folder Path
define('APICHECKNL_PLUGIN_DIR', plugin_dir_path(APICHECKNL_PLUGIN_FILE));

// Plugin Folder URL
define('APICHECKNL_PLUGIN_URL', plugin_dir_url(APICHECKNL_PLUGIN_FILE));


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

const APICHECKNL_SUPPORTED_COUNTRIES = ['NL', 'BE', 'LU'];


class APICheck
{
    private static $_action = 'apichecknl_search_address';

    private static $_billing = [
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
    ];

    private static $_shipping = [
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
    ];

    function __construct()
    {
        add_action('init', [$this, 'apichecknl_start_from_here']);
        if (get_option('apichecknl_enable_disabled') == 1) {
            add_action('wp_enqueue_scripts', [$this, 'apichecknl_enqueue_script_front']);
            add_action('admin_init', [$this, 'bpem_if_woocommerce_not_active']);
            add_filter('woocommerce_checkout_fields', [$this, 'custom_override_checkout_fields'], 1);
            add_action('woocommerce_checkout_posted_data', [$this, 'checkout_posted_data']);
            add_action('woocommerce_checkout_update_order_review', [$this, 'checkout_update_order_review']);

            add_action('woocommerce_after_checkout_validation', [$this, 'custom_checkout_validation']);
        }
    }

    // Check If WooCommerce exists
    function bpem_if_woocommerce_not_active($message)
    {
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            echo "<div class='notice notice-error is-dismissible'><h4>ApiCheck: WooCommerce is niet actief. Installeer WooCommerce om deze plugin te kunnen gebruiken.</h4></div>";
            deactivate_plugins('/api-check/api-check.php');
        }
    }

    // Add plugin files
    function apichecknl_start_from_here()
    {
        require_once plugin_dir_path(__FILE__) . 'front/apichecknl_search_address.php';
        require_once plugin_dir_path(__FILE__) . 'back/apichecknl_options_page.php';
    }

    // Enqueue Style and Scripts
    function apichecknl_enqueue_script_front()
    {
        if (function_exists('is_checkout') && is_checkout()) {
            //Styles
            wp_enqueue_style('apichecknl-style', plugins_url('assets/css/apicheck.css', __FILE__), '1.0.0', 'all');
            wp_enqueue_style('apichecknl-autocomplete-style', plugins_url('assets/css/autocomplete.css', __FILE__), '1.0.0', 'all');

            // Scripts
            wp_register_script('apichecknl-script', plugins_url('assets/js/apicheck.js', __FILE__), ['jquery', 'woocommerce'], '1.0.0', true);
            wp_enqueue_script('apichecknl-script');

            wp_localize_script('apichecknl-script', 'apichecknl_billing_fields', self::$_billing);
            wp_localize_script('apichecknl-script', 'apichecknl_shipping_fields', self::$_shipping);

            wp_localize_script('apichecknl-script', 'apichecknl_params', [
                'url' => admin_url('admin-ajax.php'),
                'action' => self::$_action,
                'supported_countries' => APICHECKNL_SUPPORTED_COUNTRIES,
            ]);

            // Autocomplete script
            wp_enqueue_script('apichecknl-autocomplete-script', plugins_url('assets/js/autocomplete.min.js', __FILE__), '1.0.0', true);

            wp_localize_script(
                'apichecknl-script',
                'apichecknl_ajax_object',
                ['ajax_url' => admin_url('admin-ajax.php')]
            );
        }
    }

    // Re-arrange checkout form fields
    function custom_override_checkout_fields($fields)
    {
        // Billing fields
        $fields['billing']['billing_municipality_autocomplete'] = [
            'id' => 'billing_municipality_autocomplete',
            'type' => 'text',
            'class' => ['form-row form-row-wide validate-required'],
            'label' => 'Gemeente of postcode',
            'placeholder' => 'Start met typen...',
            'required' => false,
        ];
        $fields['billing']['billing_municipality_city'] = [
            'id' => 'billing_municipality_city',
            'type' => 'text',
            'class' => ['form-row-wide hidden-checkout-field'],
            'label' => '',
            'placeholder' => '',
            'required' => false,
        ];
        $fields['billing']['billing_municipality_postalcode'] = [
            'id' => 'billing_municipality_postalcode',
            'type' => 'text',
            'class' => ['form-row-wide hidden-checkout-field'],
            'label' => '',
            'placeholder' => '',
            'required' => false,
        ];

        $fields['billing']['billing_street_autocomplete'] = [
            'id' => 'billing_street_autocomplete',
            'type' => 'text',
            'class' => ['form-row form-row-wide validate-required'],
            'label' => 'Straat',
            'placeholder' => 'Start met typen...',
            'required' => false,
        ];

        $fields['billing']['billing_housenumber'] = [
            'id' => 'billing_housenumber',
            'type' => 'text',
            'class' => ['form-row form-row-first validate-require'],
            'label' => 'Huisnummer',
            'placeholder' => '',
            'required' => true,
        ];

        $fields['billing']['billing_housenumber_addition'] = [
            'id' => 'billing_housenumber_addition',
            'type' => 'text',
            'class' => ['form-row form-row-last'],
            'label' => 'Toevoeging',
            'placeholder' => '',
            'required' => false,
        ];

        $fields['billing']['billing_street'] = [
            'type' => 'text',
            'class' => ['form-row form-row-wide validate-required'],
            'label' => 'Straat',
            'placeholder' => '',
            'required' => true,
        ];

        // Shipping fields
        $fields['shipping']['shipping_municipality_autocomplete'] = [
            'id' => 'shipping_municipality_autocomplete',
            'type' => 'text',
            'class' => ['form-row-wide'],
            'label' => 'Gemeente of postcode',
            'placeholder' => 'Start met typen...',
            'required' => false,
        ];
        $fields['shipping']['shipping_municipality_city'] = [
            'id' => 'shipping_municipality_city',
            'type' => 'text',
            'class' => ['form-row-wide hidden-checkout-field'],
            'label' => '',
            'placeholder' => '',
            'required' => false,
        ];
        $fields['shipping']['shipping_municipality_postalcode'] = [
            'id' => 'shipping_municipality_postalcode',
            'type' => 'text',
            'class' => ['form-row-wide hidden-checkout-field'],
            'label' => '',
            'placeholder' => '',
            'required' => false,
        ];

        $fields['shipping']['shipping_street_autocomplete'] = [
            'id' => 'shipping_street_autocomplete',
            'type' => 'text',
            'class' => ['form-row-wide'],
            'label' => 'Straat',
            'placeholder' => 'Start met typen...',
            'required' => false,
        ];

        $fields['shipping']['shipping_housenumber'] = [
            'type' => 'text',
            'class' => ['form-row form-row-first validate-require'],
            'label' => 'Huisnummer',
            'placeholder' => '',
            'required' => true,
        ];

        $fields['shipping']['shipping_housenumber_addition'] = [
            'type' => 'text',
            'class' => ['form-row form-row-last'],
            'label' => 'Toevoeging',
            'placeholder' => '',
            'required' => false,
        ];

        $fields['shipping']['shipping_street'] = [
            'type' => 'text',
            'class' => ['form-row-wide'],
            'label' => 'Straat',
            'placeholder' => '',
            'required' => true,
        ];

        $billing_order = [
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
        ];

        $shipping_order = [
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
        ];

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
        $data = [];
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
        if (!get_option('apichecknl_enable_disabled')) {
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
