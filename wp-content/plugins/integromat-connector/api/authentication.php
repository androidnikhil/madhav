<?php defined( 'ABSPATH' ) || die( 'No direct access allowed' );

add_filter(
	'rest_authentication_errors',
	function ( $result ) {

		$skip  = false;
		$codes = array();
		$log   = ( get_option( 'iwc-logging-enabled' ) == 'true' ) ? true : false;

		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$skip    = true;
			$codes[] = 1;
		}

		if ( is_user_logged_in() ) {
			$skip    = true;
			$codes[] = 2;
		}

		$user_id = \Integromat\User::get_administrator_user();
		if ( $user_id === 0 ) {
			$skip    = true;
			$codes[] = 3;
		}

		if ( $skip ) {
			$log && \Integromat\Logger::write( implode( ';', $codes ) );
			return $result;
		}

		if ( isset( $_SERVER['HTTP_IWC_API_KEY'] ) && ! empty( $_SERVER['HTTP_IWC_API_KEY'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Token is hashed before comparison in is_valid()
			$token =  $_SERVER['HTTP_IWC_API_KEY'];

			if ( strlen( $token ) !== \Integromat\Api_Token::API_TOKEN_LENGTH || ! \Integromat\Api_Token::is_valid( $token ) ) {
				$log && \Integromat\Logger::write( 6 );
				\Integromat\Rest_Response::render_error( 401, 'Provided API key is invalid', 'invalid_token' );
			} else {
				// Check rate limiting
				$rate_limit_id = \Integromat\Rate_Limiter::get_identifier();
				if ( \Integromat\Rate_Limiter::is_rate_limited( $rate_limit_id ) ) {
					$rate_status = \Integromat\Rate_Limiter::get_rate_limit_status( $rate_limit_id );
					$log && \Integromat\Logger::write( 9 );
					\Integromat\Rest_Response::render_error( 
						429, 
						'Rate limit exceeded. Try again later.', 
						'rate_limit_exceeded',
						array(
							'X-RateLimit-Limit' => $rate_status['limit'],
							'X-RateLimit-Remaining' => max( 0, $rate_status['limit'] - $rate_status['requests'] ),
							'X-RateLimit-Reset' => $rate_status['reset_time'],
						)
					);
				}

				// Check payload size
				if ( \Integromat\Rate_Limiter::is_payload_too_large() ) {
					$log && \Integromat\Logger::write( 10 );
					\Integromat\Rest_Response::render_error( 413, 'Request payload too large', 'payload_too_large' );
				}

				// Extract endpoint and method for permission checking
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET';
				
				$endpoint = '';
				if ( preg_match( '#\/wp-json/(.*?)(\?.*)?$#i', $request_uri, $matches ) ) {
					$endpoint = '/' . $matches[1];
				}

				// Use safer user context setting with permission checking
				if ( ! \Integromat\User::set_api_user_context( $user_id, $endpoint, $method ) ) {
					$log && \Integromat\Logger::write( 8 );
					\Integromat\Rest_Response::render_error( 403, 'Insufficient API permissions', 'insufficient_permissions' );
				}
				$log && \Integromat\Logger::write( 7 );
				\Integromat\Rest_Request::dispatch();
			}
		} else {
			if ( \Integromat\Guard::is_protected() ) {
				$log && \Integromat\Logger::write( 5 );
				\Integromat\Rest_Response::render_error( 401, 'API key is missing', 'missing_token' );

			} else {
				$log && \Integromat\Logger::write( 4 );
				return $result;
			}
		}

		return $result;

	}
);
