<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Api_Token {

	const API_TOKEN_IDENTIFIER = 'iwc_api_key';

	const API_TOKEN_LENGTH = 32;

	/**
	 * Return existing token
	 *
	 * @return string
	 */
	public static function get() {
		return get_site_option( self::API_TOKEN_IDENTIFIER );
	}


	/**
	 * Initiate a token if it doesn't exist
	 *
	 * @throws \Exception
	 */
	public static function initiate() {
		if ( self::get() == '' ) {
			// Use WordPress secure password generation for better entropy
			$secure_token = wp_generate_password( self::API_TOKEN_LENGTH, true, true );
			update_site_option( self::API_TOKEN_IDENTIFIER, $secure_token );
		}
	}


	/**
	 * @param string $token
	 * @return bool
	 */
	public static function is_valid( $token ) {
		// Use hash_equals to prevent timing attacks
		$stored_token = get_site_option( self::API_TOKEN_IDENTIFIER );
		return hash_equals( $stored_token, $token );
	}

	/**
	 * Regenerate API token
	 *
	 * @return string New token
	 * @throws \Exception
	 */
	public static function regenerate() {
		$new_token = wp_generate_password( self::API_TOKEN_LENGTH, true, true );
		update_site_option( self::API_TOKEN_IDENTIFIER, $new_token );
		return $new_token;
	}

}
