<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Rest_Response {
	/**
	 * Includes users Custom fields into the REST API response payload
	 */
	public static function include_custom_fields() {
		$content_types = array( 'post', 'comment', 'user', 'term' );
		$args          = array(
			'show_in_rest' => true,
		);
		$post_types    = get_post_types( $args );

		foreach ( $content_types as $object_type ) {

			$options = get_option( 'integromat_api_options_' . $object_type );

			if ( empty( $options ) ) {
				continue;
			}

			foreach ( $options as $option => $val ) {

				$meta_key = str_replace( IWC_FIELD_PREFIX, '', $option );

				if ( 'post' === $object_type ) {
					foreach ( $post_types as $post_type ) {
						register_rest_field(
							$post_type,
							$meta_key,
							array(
								'get_callback' => function ( $object, $field_name ) use ( $object_type ) {
									$post_meta_keys = get_post_meta( $object['id'] );
									// see if the field to register belongs to the post type.
									if ( ! isset( $post_meta_keys[ $field_name ] ) ) {
										return;
									}
									return get_metadata( $object_type, $object['id'], $field_name, true );
								},
							)
						);
					}
				} else {
					register_rest_field(
						$object_type,
						$meta_key,
						array(
							'get_callback' => function ( $object, $field_name ) use ( $object_type ) {
								return get_metadata( $object_type, $object['id'], $field_name, true );
							},
						)
					);
				}
			}
		}
	}

	/**
	 * @param int    $status_code
	 * @param string $error_message
	 * @param string $error_code
	 * @param array  $headers Optional headers to include
	 */
	public static function render_error( $status_code, $error_message, $error_code, $headers = array(), $details = array() ) {
		http_response_code( $status_code );
		header( 'Content-type: application/json' );

		$error = array(
			"code" => $error_code,
			"message" => $error_message,
			"data" => array_merge( array ( "status" => $status_code ), $details ),
		);
		
		foreach ( $headers as $name => $value ) {
			$clean_name = preg_replace( '/[^\w-]/', '', $name );
			$clean_value = preg_replace( '/[\r\n]/', '', $value );
			if ( ! empty( $clean_name ) && ! empty( $clean_value ) ) {
				header( "$clean_name: $clean_value" );
			}
		}
		
		echo wp_json_encode( $error );
		exit;
	}
}
