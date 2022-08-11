<?php

add_action('wp_ajax_ac_search_address', 'ac_search_address');
add_action('wp_ajax_nopriv_ac_search_address', 'ac_search_address');


function ac_search_address()
{
    try {
        $ac_api_url = get_option('ac_api_url');
        $ac_api_key = get_option('ac_api_key');

        $params = [];

        // Create request params
        foreach ($_POST as $key => $value) {
            if (!in_array($key, ['action', 'country'])) {
                $params[$key] = $value;
            }
        }

        $query = http_build_query($params);

        $url = $ac_api_url . strtolower($_POST['country']) . '?' . $query;
        $args = array(
            'headers' => array(
                'x-api-key' => $ac_api_key
            ),
            'timeout' => 600
        );

        $response = wp_remote_request($url, $args);

        if (is_object($response) && get_class($response) === 'WP_Error') {
            write_log(var_export($response, true));
            wp_die();
        }

        $result = $response['body'];
        $code = $response['response']['code'];

        write_log(['code' => $code, 'result' => $result]);

        if ($code == 200) {
            $res = array("result" => json_decode($result), "status" => 1);
            wp_send_json($res);
        } else {
            $ac_error_message = esc_attr(get_option('ac_error_message'));
            $res = array("result" => $ac_error_message, "status" => 0);
            wp_send_json($res);
        }

        wp_die();
    } catch (Exception $exception) {
        write_log($exception . '');
    }
}
