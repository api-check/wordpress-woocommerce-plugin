<?php

add_action('wp_ajax_ac_search_address', 'ac_search_address');
add_action('wp_ajax_nopriv_ac_search_address', 'ac_search_address');

function ac_search_address()
{
	$ac_api_url = get_option('ac_api_url');
	$ac_api_key = get_option('ac_api_key');

	$country = $_POST['country'];
	$postcode = $_POST['postcode'];
	$houseNo = $_POST['address'];
	$url = $ac_api_url . "$country?postalcode=$postcode&number=$houseNo";
	$args = array(
		'headers' => array(
			'x-api-key' => $ac_api_key
		)
	);

	$response = wp_remote_request($url, $args);
	$result = $response['body'];

	$code = $response['response']['code'];

	if ($code == 200) {
		$res = array("result" => json_decode($result), "status" => 1);
		wp_send_json($res);
	} else {
		$ac_error_message = esc_attr(get_option('ac_error_message'));
		$res = array("result" => $ac_error_message, "status" => 0);
		wp_send_json($res);
	}

	wp_die();
}
