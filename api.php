<?php
class Belco_API {

	public static function post($path, $data, $options = array()) {
		if (!empty($options['secret'])) {
			$secret = $options['secret'];
		} else {
			$secret = get_option('belco_secret');
		}

		$protocol = BELCO_USE_SSL ? 'https://' : 'http://';

		$response = wp_remote_post($protocol . BELCO_API_HOST . $path, array(
			'method' => 'POST',
			'body' => json_encode($data),
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Api-Key' => $secret
			)
		));

		if ( is_wp_error( $response ) ) {
		   return $response->get_error_message();
		}

    $body = json_decode($response['body']);

    if ($body->success === false) {
      return $body->message;
    }

		return true;
	}

}
?>
