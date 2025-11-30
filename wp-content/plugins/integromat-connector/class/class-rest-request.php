<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Rest_Request {

	public static function dispatch() {		
		// Check payload size early
		if ( \Integromat\Rate_Limiter::is_payload_too_large() ) {
			Rest_Response::render_error( 413, 'Request payload too large', 'payload_too_large' );
			return;
		}

		// Add authentication check for security (use API-specific permissions)
		if ( ! current_user_can( 'iwc_read_posts' ) ) {
			Rest_Response::render_error( 403, 'Insufficient API permissions', 'rest_forbidden' );
			return;
		}
	
		// Validate and sanitize REQUEST_URI
		if ( ! isset( $_SERVER['REQUEST_URI'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			Rest_Response::render_error( 400, 'Invalid request URI', 'rest_invalid_request' );
			return;
		}
		
		$request_uri = sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		
		// Extract the REST route from the request URI with better validation
		if ( ! preg_match( '#\/wp-json/(.*?)(\?.*)?$#i', $request_uri, $route_match ) ) {
			Rest_Response::render_error( 400, 'Invalid REST API request', 'rest_invalid_route' );
			return;
		}
		
		if ( ! isset( $route_match[1] ) || empty( $route_match[1] ) ) {
			Rest_Response::render_error( 400, 'Missing REST route', 'rest_missing_route' );
			return;
		}
		
		$rest_route = '/' . sanitize_text_field( $route_match[1] );
		
		// Validate HTTP method
		$allowed_methods = array( 'GET', 'POST', 'PUT', 'DELETE', 'PATCH' );
		$request_method  = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		
		if ( ! in_array( $request_method, $allowed_methods, true ) ) {
			Rest_Response::render_error( 405, 'Method not allowed', 'rest_method_not_allowed' );
			return;
		}

		// Authentication isn't performed when making internal requests.
		$request = new \WP_REST_Request( $request_method, $rest_route );
		
		// Sanitize and validate query parameters
		$query_params = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- REST API endpoint, authentication handled separately
		if ( ! empty( $_GET ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- REST API endpoint, authentication handled separately
			foreach ( $_GET as $key => $value ) {
				$clean_key = sanitize_text_field( $key );
				if ( is_array( $value ) ) {
					$query_params[ $clean_key ] = array_map( 'sanitize_text_field', wp_unslash( $value ) );
				} else {
					$query_params[ $clean_key ] = sanitize_text_field( wp_unslash( $value ) );
				}
			}
		}
		$request->set_query_params( $query_params );

		if ( 'POST' === $request_method ) {
			$input = file_get_contents( 'php://input' );
			if ( false === $input ) {
				Rest_Response::render_error( 400, 'Unable to read request body', 'rest_invalid_request' );
				return;
			}
			
			// Validate JSON if not empty
			if ( ! empty( $input ) ) {
				$body = json_decode( $input, true );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					Rest_Response::render_error( 400, 'Invalid JSON in request body: ' . json_last_error_msg(), 'rest_invalid_json' );
					return;
				}
				
				// Sanitize body data
				$body = self::sanitize_recursive( $body );
				$request->set_body_params( $body );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- REST API endpoint, authentication handled separately
			if ( ! empty( $_FILES['file'] ) ) {
				self::upload_media();
				return; // upload_media handles its own response
			}
		}

		$response      = rest_do_request( $request );
		$server        = rest_get_server();
		$response_data = $server->response_to_data( $response, false );

		// Save custom meta for POST requests
		if ( 'POST' === $request_method && ! empty( $body['meta'] ) ) {
			$content_type = self::get_content_type( $rest_route );
			if ( isset( $response_data['id'] ) && is_numeric( $response_data['id'] ) ) {
				self::update_meta( absint( $response_data['id'] ), $content_type, $body['meta'] );
			}
		}
		
		self::send_response( $response, $response_data );
	}

	/**
	 * Recursively sanitize array data
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	private static function sanitize_recursive( $data ) {
		if ( is_array( $data ) ) {
			$sanitized = array();
			foreach ( $data as $key => $value ) {
				$clean_key = sanitize_text_field( $key );
				$sanitized[ $clean_key ] = self::sanitize_recursive( $value );
			}
			return $sanitized;
		} elseif ( is_string( $data ) ) {
			// Check if content sanitization is enabled
			$sanitize_content = get_option( 'iwc_sanitize_post_content', '0' );
			if ( $sanitize_content === '1' ) {
				return wp_kses_post( wp_unslash( $data ) );
			} else {
				// Only apply basic unslashing without HTML stripping when disabled
				return wp_unslash( $data );
			}
		} elseif ( is_numeric( $data ) ) {
			return is_float( $data ) ? floatval( $data ) : intval( $data );
		} elseif ( is_bool( $data ) ) {
			return (bool) $data;
		}
		
		return $data;
	}


	/**
	 * @param /WP_REST_Response $response
	 * @param array            $response_data
	 */
	private static function send_response( $response, $response_data ) {
		// Add security headers
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: DENY' );
		header( 'X-XSS-Protection: 1; mode=block' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		
		if ( ! empty( $response->headers ) ) {
			foreach ( $response->headers as $key => $val ) {
				// Sanitize headers to prevent header injection attacks
				$clean_key = preg_replace('/[^\w-]/', '', $key);
				$clean_val = preg_replace('/[\r\n]/', '', $val);
				if ( ! empty( $clean_key ) && ! empty( $clean_val ) ) {
					header( "$clean_key: $clean_val" );
				}
			}
		}
		header( 'Content-type: application/json; charset=utf-8' );
		if ( is_object( $response_data ) && is_object( $response_data->data ) && (int) $response_data->data->status > 0 ) {
			http_response_code( $response_data->data->status );
		}
		
		// Respond with JSON-encoded data and exit
		echo wp_json_encode( $response_data );
		exit();
	}


	private static function upload_media() {
		// Add authentication check for file uploads using API-specific permissions
		if ( ! current_user_can( 'iwc_upload_files' ) ) {
			Rest_Response::render_error( 403, 'Insufficient permissions for file upload', 'rest_forbidden' );
			return;
		}

		// Validate file upload data exists and is properly formatted
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- REST API endpoint, authentication handled separately
		if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) {
			Rest_Response::render_error( 400, 'No file uploaded', 'rest_upload_no_file' );
			return;
		}

		// Check for upload errors first
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- REST API endpoint, authentication handled separately
		$upload_error = isset( $_FILES['file']['error'] ) ? absint( $_FILES['file']['error'] ) : UPLOAD_ERR_NO_FILE;
		
		if ( $upload_error !== UPLOAD_ERR_OK ) {
			$error_messages = array(
				UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive',
				UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive',
				UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
				UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
				UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
				UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
				UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
			);
			
			$error_message = isset( $error_messages[ $upload_error ] ) ? $error_messages[ $upload_error ] : 'Unknown upload error';
			Rest_Response::render_error( 400, $error_message, 'rest_upload_error' );
			return;
		}

		// Sanitize file upload data (preserve tmp_name as-is for file operations)
		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- REST API endpoint, authentication handled separately, tmp_name is a system-generated temporary file path, safe to use unsanitized
		$uploaded_file = array(
			'name'     => isset( $_FILES['file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) ) : '',
			'type'     => isset( $_FILES['file']['type'] ) ? sanitize_mime_type( wp_unslash( $_FILES['file']['type'] ) ) : '',
			'tmp_name' => isset( $_FILES['file']['tmp_name'] ) ? $_FILES['file']['tmp_name'] : '', // Keep tmp_name unsanitized for file operations
			'error'    => $upload_error,
			'size'     => isset( $_FILES['file']['size'] ) ? $_FILES['file']['size'] : 0, // Don't convert to int yet
		);
		//phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		// Validate file exists and has content using file system check
		if ( empty( $uploaded_file['tmp_name'] ) || ! file_exists( $uploaded_file['tmp_name'] ) ) {
			Rest_Response::render_error( 400, 'No file uploaded or file not found', 'rest_upload_no_file' );
			return;
		}
		
		// Get actual file size from filesystem (more reliable than $_FILES size)
		$actual_file_size = filesize( $uploaded_file['tmp_name'] );
		if ( $actual_file_size === false || $actual_file_size === 0 ) {
			Rest_Response::render_error( 400, 'File is empty or unreadable', 'rest_upload_no_file' );
			return;
		}
		
		// Update the size with actual file size
		$uploaded_file['size'] = $actual_file_size;

		// Use our enhanced file validator
		$validation_result = \Integromat\File_Validator::validate_upload( $uploaded_file );
		
		if ( is_wp_error( $validation_result ) ) {
			Rest_Response::render_error( 400, $validation_result->get_error_message(), $validation_result->get_error_code() );
			return;
		}

		// Use WordPress secure upload handling
		$upload_overrides = array(
			'test_form' => false,
			'test_size' => true,
		);

		// Move uploaded file using WordPress function
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$movefile = wp_handle_upload( $uploaded_file, $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
			// Get additional metadata
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- REST API endpoint, authentication handled separately
			$title       = isset( $_REQUEST['title'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['title'] ) ) : '';
			$description = isset( $_REQUEST['description'] ) ? sanitize_textarea_field( wp_unslash( $_REQUEST['description'] ) ) : '';
			$caption     = isset( $_REQUEST['caption'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['caption'] ) ) : '';
			$alt_text    = isset( $_REQUEST['alt_text'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['alt_text'] ) ) : '';
			$post_id     = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0;
			$filename   = basename( $movefile['file'] );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			// Prepare attachment data
			$attachment = array(
				'post_mime_type' => $movefile['type'],
				'post_title'     => ( ! empty( $title ) ? $title : sanitize_file_name( $filename ) ),
				'post_content'   => $description,
				'post_excerpt'   => $caption,
				'post_status'    => 'inherit',
			);

			// Insert attachment
			$attachment_id = wp_insert_attachment( $attachment, $movefile['file'] );
			
			if ( is_wp_error( $attachment_id ) ) {
				Rest_Response::render_error( 500, 'Failed to create attachment', 'rest_upload_attachment_error' );
				return;
			}

			// Set alt text if provided
			if ( ! empty( $alt_text ) ) {
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt_text ) );
			}

			// Generate attachment metadata
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $movefile['file'] );
			wp_update_attachment_metadata( $attachment_id, $attachment_data );

			// Relate to a post if specified
			if ( $post_id > 0 && get_post( $post_id ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}

			// Prepare response
			$meta = wp_get_attachment_metadata( $attachment_id );
			$post = get_post( $attachment_id );
			if ( is_array( $meta ) ) {
				$response_data = array_merge( $meta, (array) $post );
			} else {
				$response_data = (array) $post;
			}

			self::send_response( (object) array(), $response_data );
		} else {
			$error_message = isset( $movefile['error'] ) ? sanitize_text_field( $movefile['error'] ) : 'Unknown upload error';
			Rest_Response::render_error( 500, 'Upload failed: ' . $error_message, 'rest_upload_unknown_error' );
		}
	}

	private static function update_meta( $content_id, $content_type, $meta_fields ) {
		// Define meta function mapping for better maintainability
		$meta_functions = array(
			'comments'   => array( 'update' => 'update_comment_meta', 'delete' => 'delete_comment_meta' ),
			'tags'       => array( 'update' => 'update_term_meta', 'delete' => 'delete_term_meta' ),
			'categories' => array( 'update' => 'update_term_meta', 'delete' => 'delete_term_meta' ),
			'users'      => array( 'update' => 'update_user_meta', 'delete' => 'delete_user_meta' ),
			'default'    => array( 'update' => 'update_post_meta', 'delete' => 'delete_post_meta' ),
		);
		
		// Skip updating meta for media and pages
		if ( in_array( $content_type, array( 'media', 'pages' ), true ) ) {
			return;
		}
		
		// Get appropriate functions for this content type
		$functions = isset( $meta_functions[ $content_type ] ) ? $meta_functions[ $content_type ] : $meta_functions['default'];
		
		foreach ( $meta_fields as $meta_key => $meta_value ) {
			if ( $meta_value === 'IMT.REMOVE' ) {
				$functions['delete']( $content_id, $meta_key );
			} else {
				$functions['update']( $content_id, $meta_key, $meta_value );
			}
		}
	}

	/**
	 * @param string $rest_route
	 * @return string
	 * @throws \Exception
	 */
	private static function get_content_type( $rest_route ) {
		preg_match( '#v2/(.*)(/|$)#iU', $rest_route, $r );
		if ( isset( $r[1] ) ) {
			return $r[1];
		} else {
			throw new \Exception( 'Can not extract post type from the endpoint url.' );
		}
	}
}
