<?php

add_action('wp_ajax_apichecknl_search_address', 'apichecknl_search_address');
add_action('wp_ajax_nopriv_apichecknl_search_address', 'apichecknl_search_address');

const ALLOWED_APICHECKNL_SEARCH_ADDRESS_AJAX_STRINGS = [
    'postalcode',
    'street',
    'numberaddition',
    'municipality',
];
const ALLOWED_APICHECKNL_SEARCH_ADDRESS_AJAX_INTS = [
    'postalcode_id',
    'street_id',
    'number',
];

function apichecknl_search_address()
{
    try {
        $apichecknl_api_url = get_option('apichecknl_api_url');
        $apichecknl_api_key = get_option('apichecknl_api_key');

        if (!isset($_POST['country'])) {
            wp_die();
        }

        $country = strtoupper(sanitize_text_field($_POST['country']));
        if (!in_array($country, APICHECKNL_SUPPORTED_COUNTRIES)) {
            wp_die();
        }

        $params = [];

        // Create request params
        foreach ($_POST as $key => $value) {
            $key = strtolower($key);
            if (in_array($key, ALLOWED_APICHECKNL_SEARCH_ADDRESS_AJAX_STRINGS)) {
                $params[$key] = sanitize_text_field($value);
            } elseif (in_array($key, ALLOWED_APICHECKNL_SEARCH_ADDRESS_AJAX_INTS)) {
                $params[$key] = intval($value);
            }
        }

        $query = http_build_query($params);

        $url = $apichecknl_api_url . strtolower($country) . '?' . $query;
        $args = [
            'headers' => [
                'x-api-key' => $apichecknl_api_key
            ],
            'timeout' => 600
        ];

        $response = wp_remote_request($url, $args);

        if (is_object($response) && get_class($response) === 'WP_Error') {
            write_log(var_export($response, true));
            wp_die();
        }

        if (!is_array($response) || !isset($response['body']) || !isset($response['response']['code'])) {
            wp_die();
        }

        $result = $response['body'];
        $code = intval($response['response']['code']);

        $res = json_decode($result);
        if ($code !== 200 || json_last_error() !== JSON_ERROR_NONE) {
            $apichecknl_error_message = wp_kses_post(get_option('apichecknl_error_message'));
            $res = ["result" => $apichecknl_error_message, "status" => 0];
            wp_send_json($res);
        }

        $res = ["result" => $res, "status" => 1];
        wp_send_json($res);
    } catch (Exception $exception) {
        write_log($exception . '');
    }
}
