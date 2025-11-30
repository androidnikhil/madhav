<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Logger {
	const MAXFILESIZEMB = 5;
	const CIPHERMETHOD  = 'AES-256-CBC'; // More secure than ECB mode
	const ENCRYPTION_KEY_LENGTH = 32;
	const BYTES_IN_MB = 1000000;
	const API_KEY_PREVIEW_LENGTH = 5;

	/**
	 * Get secure log file location outside web root
	 *
	 * @return string
	 */
	private static function get_file_location() {
		// Store logs outside web-accessible directory for security
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/iwc-logs';
		
		// Create directory if it doesn't exist
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
			// Add .htaccess to deny web access
			file_put_contents( $log_dir . '/.htaccess', "Deny from all\n" );
			// Add index.php to prevent directory listing
			file_put_contents( $log_dir . '/index.php', "<?php\n// Silence is golden.\n" );
		}
		
		return $log_dir . '/iwclog.dat';
	}

	/**
	 * Get encryption key for log data
	 *
	 * @return string
	 */
	private static function get_encryption_key() {
		$key = get_site_option( 'iwc_log_encryption_key' );
		if ( empty( $key ) ) {
			// Generate a new encryption key
			$key = wp_generate_password( self::ENCRYPTION_KEY_LENGTH, true, true );
			update_site_option( 'iwc_log_encryption_key', $key );
		}
		return $key;
	}

	/**
	 * Generate IV for encryption
	 *
	 * @return string
	 */
	private static function generate_iv() {
		return openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::CIPHERMETHOD ) );
	}

	private static function check() {
		if ( ! self::file_exists() ) {
			self::create_file();
		} else {
			$fsize = filesize( self::get_file_location() );
			if ( $fsize > ( self::MAXFILESIZEMB * self::BYTES_IN_MB ) ) {
				self::create_file();
			}
		}
	}

	private static function create_file() {
		// Create an empty log file without server info or CSV header
		file_put_contents( self::get_file_location(), self::encrypt( '' ) );
		if ( ! self::file_exists() ) {
			wp_die( wp_json_encode( array( 'code' => 'log_write_fail', 'message' => 'Log file can not be created. Check permissions.' ) ) );
		}
	}

	/**
	 * Generate server info and CSV header for download
	 *
	 * @return string
	 */
	private static function get_server_info_and_header() {
		$init                            = "====== SERVER INFO START ======\n\n";
		$server_data                     = array();
		
		// Safely extract server data with existence checks and unslashing
		$server_data['REQUEST_URI']      = isset( $_SERVER['REQUEST_URI'] ) ? self::strip_request_query( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : 'Not Available';
		$server_data['HTTP_IWC_API_KEY'] = isset( $_SERVER['HTTP_IWC_API_KEY'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IWC_API_KEY'] ) ), 0, self::API_KEY_PREVIEW_LENGTH ) . '...' : 'Not Provided';

		// List of server variables to extract using the helper method
		$server_vars = array(
			'SERVER_SOFTWARE', 'REDIRECT_UNIQUE_ID', 'REDIRECT_STATUS', 'UNIQUE_ID',
			'HTTP_X_DATADOG_SAMPLING_PRIORITY', 'HTTP_X_DATADOG_SAMPLED', 'HTTP_X_DATADOG_PARENT_ID',
			'HTTP_X_DATADOG_TRACE_ID', 'CONTENT_TYPE', 'HTTP_USER_AGENT', 'HTTP_X_FORWARDED_PORT',
			'HTTP_X_FORWARDED_SSL', 'HTTP_X_FORWARDED_PROTO', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP',
			'HTTP_CONNECTION', 'HTTP_HOST', 'HTTP_X_FORWARDED_HOST', 'PATH', 'DYLD_LIBRARY_PATH',
			'SERVER_SIGNATURE', 'SERVER_NAME', 'SERVER_ADDR', 'SERVER_PORT', 'REMOTE_ADDR',
			'DOCUMENT_ROOT', 'REQUEST_SCHEME', 'CONTEXT_PREFIX', 'CONTEXT_DOCUMENT_ROOT',
			'SCRIPT_FILENAME', 'REMOTE_PORT', 'REDIRECT_URL', 'GATEWAY_INTERFACE',
			'SERVER_PROTOCOL', 'REQUEST_METHOD', 'SCRIPT_NAME', 'PHP_SELF', 'REQUEST_TIME_FLOAT', 'REQUEST_TIME'
		);
		
		// Handle all server variables using helper method
		foreach ( $server_vars as $var ) {
			$server_data[ $var ] = self::get_sanitized_server_var( $var );
		}
		
		// Special handling for SERVER_ADMIN using email sanitization
		$server_data['SERVER_ADMIN'] = isset( $_SERVER['SERVER_ADMIN'] ) ? sanitize_email( wp_unslash( $_SERVER['SERVER_ADMIN'] ) ) : 'Not Available';

		foreach ( $server_data as $key => $value ) {
			$init .= $key . ': ' . $value . "\n";
		}
		$init .= "\n====== SERVER INFO END ======\n\n";
		$init .= "date,method,uri,ip,codes,logged_in\n";
		
		return $init;
	}

	public static function file_exists() {
		return file_exists( self::get_file_location() );
	}

	/**
	 * Encrypt data using secure AES-256-CBC
	 *
	 * @param string $data
	 * @return string
	 */
	private static function encrypt( $data ) {
		$key = self::get_encryption_key();
		$iv  = self::generate_iv();
		
		$encrypted = openssl_encrypt( $data, self::CIPHERMETHOD, $key, 0, $iv );
		
		// Prepend IV to encrypted data
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt data using secure AES-256-CBC
	 *
	 * @param string $data
	 * @return string
	 */
	private static function decrypt( $data ) {
		$key  = self::get_encryption_key();
		$data = base64_decode( $data );
		
		$iv_length = openssl_cipher_iv_length( self::CIPHERMETHOD );
		$iv        = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );
		
		return openssl_decrypt( $encrypted, self::CIPHERMETHOD, $key, 0, $iv );
	}

	/**
	 * Helper method to safely get and sanitize server variables
	 * 
	 * @param string $var_name The server variable name
	 * @return string Sanitized value or 'Not Available'
	 */
	private static function get_sanitized_server_var( $var_name ) {
		return isset( $_SERVER[ $var_name ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $var_name ] ) ) : 'Not Available';
	}

	private static function strip_request_query( $request ) {
		$f = explode( '?', $request );
		return $f[0];
	}

	private static function get_record( $codes ) {
		$request_method = self::get_sanitized_server_var( 'REQUEST_METHOD' );
		if ( $request_method === 'Not Available' ) {
			$request_method = 'Unknown';
		}
		
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? self::strip_request_query( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : 'Unknown';
		$remote_addr = self::get_sanitized_server_var( 'REMOTE_ADDR' );
		if ( $remote_addr === 'Not Available' ) {
			$remote_addr = 'Unknown';
		}
		
		$r = array(
			'date' => gmdate( 'Y-m-d\TH:i:s.v\Z' ),
			'method' => $request_method,
			'uri' => $request_uri,
			'ip'      => $remote_addr,
			'codes'   => $codes,
			'logged_in' => (string) is_user_logged_in(),
		);
		return trim( implode(',', $r) ) . "\n";
	}

	public static function write( $codes ) {
		self::check();
		$log_data     = self::get_plain_file_content();
		$new_log_data = self::encrypt( $log_data . self::get_record( $codes ) );
		file_put_contents( self::get_file_location(), $new_log_data );
	}

	public static function get_plain_file_content() {
		if ( ! file_exists( self::get_file_location() ) ) {
			wp_die( wp_json_encode( array( 'code' => 'log_read_fail', 'message' => 'Log file does not exist.' ) ) );
		}
		$enc_data = file_get_contents( self::get_file_location() );
		return self::decrypt( $enc_data );
	}

	public static function download() {
		$log_data = self::get_plain_file_content();
		
		// Prepend server info and CSV header to the log data for download
		$download_data = self::get_server_info_and_header() . $log_data;
		
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-disposition: attachment; filename="log.txt"' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We want to output raw log data
		echo $download_data;
		exit;
	}

	/**
	 * Purge all log data
	 *
	 * @return bool Success status
	 */
	public static function purge() {
		$log_file = self::get_file_location();
		
		if ( file_exists( $log_file ) ) {
			// Remove the log file using WordPress function
			$result = wp_delete_file( $log_file );
			
			if ( $result ) {
				// Also remove the encryption key to ensure complete cleanup
				delete_site_option( 'iwc_log_encryption_key' );
				return true;
			}
			return false;
		}
		
		// If file doesn't exist, consider it a success
		return true;
	}
}
