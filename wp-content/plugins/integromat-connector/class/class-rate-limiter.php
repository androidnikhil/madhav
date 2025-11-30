<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Rate_Limiter {

	/**
	 * Rate limit settings
	 */
	const DEFAULT_LIMIT = 100; // requests per window
	const DEFAULT_WINDOW = 60; // 1 minute in seconds

	/**
	 * Get configured payload size limit
	 *
	 * @return int Maximum payload size in bytes
	 */
	public static function get_max_payload_size() {
		$size_mb = get_option( 'iwc_max_payload_size', 10 );
		return intval( $size_mb ) * 1048576; // Convert MB to bytes
	}

	/**
	 * Check if request should be rate limited
	 *
	 * @param string $identifier Unique identifier (IP or API key)
	 * @param int $limit Maximum requests per window
	 * @param int $window Time window in seconds
	 * @return bool True if rate limit exceeded
	 */
	public static function is_rate_limited( $identifier, $limit = null, $window = null ) {
		// Check if rate limiting is enabled
		if ( get_option( 'iwc_rate_limit_enabled', '1' ) !== '1' ) {
			return false;
		}

		if ( ! $identifier ) {
			return true;
		}

		$limit = $limit ?? intval( get_option( 'iwc_rate_limit_requests', self::DEFAULT_LIMIT ) );
		$window = $window ?? self::DEFAULT_WINDOW;

		$cache_key = 'iwc_rate_limit_' . md5( $identifier );
		$current_time = time();
		$window_start = $current_time - $window;

		// Get current request data
		$request_data = get_transient( $cache_key );
		
		if ( false === $request_data ) {
			$request_data = array(
				'count' => 0,
				'window_start' => $current_time,
				'requests' => array(),
			);
		}

		// Clean old requests outside the window
		$request_data['requests'] = array_filter(
			$request_data['requests'],
			function( $timestamp ) use ( $window_start ) {
				return $timestamp > $window_start;
			}
		);

		// Check if limit exceeded
		if ( count( $request_data['requests'] ) >= $limit ) {
			return true;
		}

		// Add current request
		$request_data['requests'][] = $current_time;
		$request_data['count'] = count( $request_data['requests'] );

		// Update cache
		set_transient( $cache_key, $request_data, $window );

		return false;
	}

	/**
	 * Get rate limit identifier from request
	 *
	 * @return string
	 */
	public static function get_identifier() {
		// Use API key if available, otherwise use IP
		if ( isset( $_SERVER['HTTP_IWC_API_KEY'] ) ) {
			return 'api_' . substr( md5( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IWC_API_KEY'] ) ) ), 0, 8 );
		}

		// Fallback to IP address
		$ip = self::get_client_ip();
		return 'ip_' . md5( $ip );
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$ip_headers = array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) && ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// Handle comma-separated IPs (X-Forwarded-For)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}

	/**
	 * Check request payload size
	 *
	 * @return bool True if payload is too large
	 */
	public static function is_payload_too_large() {
		$payload_limit_enabled = get_option( 'iwc_payload_limit_enabled', '0' );
		if ( $payload_limit_enabled !== '1' ) {
			return false;
		}

		$max_size = self::get_max_payload_size();
		
		// Check Content-Length header
		if ( isset( $_SERVER['CONTENT_LENGTH'] ) ) {
			$content_length = intval( $_SERVER['CONTENT_LENGTH'] );
			if ( $content_length > $max_size ) {
				return true;
			}
		}

		// Check POST data size
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$post_data = file_get_contents( 'php://input', false, null, 0, $max_size + 1 );
			if ( strlen( $post_data ) > $max_size ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get current rate limit status
	 *
	 * @param string $identifier
	 * @return array
	 */
	public static function get_rate_limit_status( $identifier ) {
		$cache_key = 'iwc_rate_limit_' . md5( $identifier );
		$request_data = get_transient( $cache_key );
		
		if ( false === $request_data ) {
			return array(
				'requests' => 0,
				'limit' => intval( get_option( 'iwc_rate_limit_requests', self::DEFAULT_LIMIT ) ),
				'window' => self::DEFAULT_WINDOW,
				'reset_time' => time() + self::DEFAULT_WINDOW,
			);
		}

		return array(
			'requests' => count( $request_data['requests'] ),
			'limit' => intval( get_option( 'iwc_rate_limit_requests', self::DEFAULT_LIMIT ) ),
			'window' => self::DEFAULT_WINDOW,
			'reset_time' => $request_data['window_start'] + self::DEFAULT_WINDOW,
		);
	}
}
