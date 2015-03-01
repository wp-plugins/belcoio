<?php
class Belco_API {
	
	public static function post($path, $data) {
		$secret = get_option('belco_secret');
		$protocol = BELCO_USE_SSL ? 'https://' : 'http://';
		
		$response = wp_remote_post($protocol . BELCO_HOST . $path, array(
			'method' => 'POST',
			'sslverify' => false,
			'body' => json_encode($data),
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Api-Key' => $secret
			)
		));

		if ( is_wp_error( $response ) ) {
		   return $response->get_error_message();
		}
		return true;
	}
	
}
?>