<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class User {

	/**
	 * Get an administrator account
	 *
	 * @return int
	 */
	public static function get_administrator_user() {
		$users = get_users( array( 'role__in' => array( 'administrator' ), 'number' => 5 ) );
		
		if ( empty( $users ) ) {
			return 0;
		}

		// Prioritize user ID 1 (default admin).
		foreach ( $users as $user ) {
			if ( $user->data->ID == 1 && in_array( 'administrator', $user->roles, true ) ) {
				return $user->data->ID;
			}
		}

		// Search for another admin, if user ID 1 doesn't exist or hasn't administrator role.
		foreach ( $users as $user ) {
			if ( in_array( 'administrator', $user->roles, true ) ) {
				return $user->data->ID;
			}
		}

		return 0;
	}


	/**
	 * Set current user context for API requests with specific permissions
	 *
	 * @param int $user_id
	 * @param string $endpoint The REST endpoint being accessed
	 * @param string $method HTTP method
	 * @return bool Success status
	 */
	public static function set_api_user_context( $user_id, $endpoint = '', $method = 'GET' ) {
		// Validate user exists and has administrator role
		$user = get_user_by( 'id', $user_id );
		if ( ! $user || ! in_array( 'administrator', $user->roles, true ) ) {
			return false;
		}
		
		// Set user context without full authentication
		wp_set_current_user( $user_id );
		
		// If endpoint and method provided, check API-specific permissions
		// Note: Api_Permissions::check_permission() will internally verify if this is a Make request
		if ( ! empty( $endpoint ) && ! empty( $method ) ) {
			if ( ! \Integromat\Api_Permissions::check_permission( $endpoint, $method ) ) {
				// Log permission failure
				if ( get_option( 'iwc-logging-enabled' ) == 'true' ) {
					\Integromat\Logger::write( 11 );
				}
				return false;
			}
		}
		
		return true;
	}

}
