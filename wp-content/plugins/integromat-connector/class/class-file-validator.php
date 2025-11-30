<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class File_Validator {
	/**
	 * Validate uploaded file
	 *
	 * @param array $file $_FILES array element
	 * @return array|\WP_Error Validation result or error
	 */
	public static function validate_upload( $file ) {
		// Basic file validation
		if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'invalid_upload', 'Invalid file upload.' );
		}

		// Check file size
		if ( ! isset( $file['size'] ) || $file['size'] <= 0 ) {
			return new \WP_Error( 'empty_file', 'Uploaded file is empty.' );
		}

		// Get file name and sanitize it
		$filename = isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';

		// Check file type against allowed types
		$wp_file_type = wp_check_filetype( $filename );
		if ( ! $wp_file_type['type'] ) {
			return new \WP_Error( 'unsupported_file_type', 'Unsupported file type.' );
		}

		// Get file extension
		$extension = $wp_file_type['ext'];

		// Check user defined file type whitelist
		$strict_file_validation = get_option( 'iwc_strict_file_validation' );
		$allowed_file_extensions = get_option( 'iwc_allowed_file_extensions' );
		if ( $strict_file_validation == '1' && !empty( $allowed_file_extensions ) ) {
			$allowed_extensions = explode( ',', get_option( 'iwc_allowed_file_extensions' ) );
			$allowed_extensions = array_map( 'trim', $allowed_extensions );
			if ( ! in_array( $extension, $allowed_extensions ) ) {
				return new \WP_Error( 'disallowed_file_type', 'File type is not allowed.' );
			}
		}
		$mime = $wp_file_type['type'];

		// Check against WordPress upload size limit
		$wp_max_size = wp_max_upload_size();
		if ( $file['size'] > $wp_max_size ) {
			$wp_max_formatted = size_format( $wp_max_size );
			return new \WP_Error( 'exceeds_wp_limit', "File exceeds WordPress upload limit of {$wp_max_formatted}." );
		}

		return array(
			'valid' => true,
			'filename' => $filename,
			'extension' => $extension,
			'mime_type' => $mime,
			'size' => $file['size'],
		);
	}
}
