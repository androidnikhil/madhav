<?php
namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Guard {
	/**
	 * Is currently requested endpoint protected?
	 *
	 * @return bool
	 */
	public static function is_protected() {
		// Only guard if IWC-API-KEY header is present
		if ( ! isset( $_SERVER['HTTP_IWC_API_KEY'] ) || empty( $_SERVER['HTTP_IWC_API_KEY'] ) ) {
			return false; // No protection if no IWC-API-KEY header
		}
		
		// Validate required server variables
		if ( ! isset( $_SERVER['REQUEST_URI'] ) || ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			return true; // Err on the side of caution
		}
		
		$entities      = array( 'posts', 'users', 'comments', 'tags', 'categories', 'media' );
		$json_base     = str_replace( get_site_url(), '', get_rest_url( null, 'wp/v2/' ) );
		$request_uri   = sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$endpoint      = str_replace( $json_base, '', $request_uri );
		$request_method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) );
		
		// Parse endpoint safely
		$endpoint_parts = explode( '/', trim( $endpoint, '/' ) );
		$first_part     = isset( $endpoint_parts[0] ) ? sanitize_text_field( $endpoint_parts[0] ) : '';
		
		$protected_methods = array( 'POST', 'PUT', 'DELETE', 'PATCH' );
		
		return in_array( $first_part, $entities, true ) && in_array( $request_method, $protected_methods, true );
	}
}
