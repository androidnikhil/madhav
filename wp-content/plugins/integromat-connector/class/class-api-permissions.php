<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Api_Permissions {

	/**
	 * Define API-specific capabilities
	 */
	const API_CAPABILITIES = array(
		'iwc_read_posts'      => 'Read posts via API',
		'iwc_create_posts'    => 'Create posts via API',
		'iwc_edit_posts'      => 'Edit posts via API',
		'iwc_delete_posts'    => 'Delete posts via API',
		'iwc_read_users'      => 'Read users via API',
		'iwc_create_users'    => 'Create users via API',
		'iwc_edit_users'      => 'Edit users via API',
		'iwc_delete_users'    => 'Delete users via API',
		'iwc_read_comments'   => 'Read comments via API',
		'iwc_create_comments' => 'Create comments via API',
		'iwc_edit_comments'   => 'Edit comments via API',
		'iwc_delete_comments' => 'Delete comments via API',
		'iwc_upload_files'    => 'Upload files via API',
		'iwc_read_media'      => 'Read media via API',
		'iwc_edit_media'      => 'Edit media via API',
		'iwc_delete_media'    => 'Delete media via API',
		'iwc_read_terms'      => 'Read taxonomies/terms via API',
		'iwc_create_terms'    => 'Create taxonomies/terms via API',
		'iwc_edit_terms'      => 'Edit taxonomies/terms via API',
		'iwc_delete_terms'    => 'Delete taxonomies/terms via API',
	);

	/**
	 * Initialize API permissions on plugin activation
	 */
	public static function init() {
		// Add capabilities to administrator role
		add_action( 'admin_init', array( __CLASS__, 'add_api_capabilities' ) );
	}

	/**
	 * Add API capabilities to administrator role
	 */
	public static function add_api_capabilities() {
		$role = get_role( 'administrator' );
		if ( $role ) {
			foreach ( self::API_CAPABILITIES as $cap => $description ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Check if current user has required API permission for the endpoint
	 *
	 * @param string $endpoint The REST endpoint being accessed
	 * @param string $method HTTP method
	 * @return bool
	 */
	public static function check_permission( $endpoint, $method ) {
		// Only apply API permissions to requests from Make (those with IWC-API-KEY header)
		if ( ! isset( $_SERVER['HTTP_IWC_API_KEY'] ) || empty( $_SERVER['HTTP_IWC_API_KEY'] ) ) {
			// If no IWC-API-KEY header, this is not a Make request - use standard WordPress permissions
			return current_user_can( 'administrator' );
		}

		// Allow /users/me endpoint without granular permissions for API key verification
		if ( $method === 'GET' && preg_match( '#^/?wp/v2/users/me/?$#', $endpoint ) ) {
			return true; // Always allow this endpoint for API key verification
		}

		// Allow discovery endpoints without granular permissions for content type and taxonomy discovery
		if ( $method === 'GET' && preg_match( '#^/?wp/v2/(types|tags|categories)/?$#', $endpoint ) ) {
			return true; // Always allow these endpoints for content/taxonomy discovery
		}

		// Check if API permissions are enabled for Make requests
		if ( get_option( 'iwc_api_permissions_enabled', '0' ) !== '1' ) {
			// If API permissions are disabled, use legacy admin check
			return current_user_can( 'administrator' );
		}

		// Extract the entity type from the endpoint
		$entity = self::extract_entity_from_endpoint( $endpoint );
		
		if ( ! $entity ) {
			return false;
		}

		// Determine required capability based on method and entity
		$capability = self::get_required_capability( $entity, $method );
		
		if ( ! $capability ) {
			return false;
		}

		// Check if this specific permission is enabled in settings
		$permission_enabled = get_option( 'iwc_permission_' . $capability, '0' );
		if ( $permission_enabled !== '1' ) {
			return false;
		}

		// Check if current user has the required capability
		return current_user_can( $capability );
	}

	/**
	 * Extract entity type from REST endpoint
	 *
	 * @param string $endpoint
	 * @return string|false
	 */
	private static function extract_entity_from_endpoint( $endpoint ) {
		// Parse wp/v2/{entity} patterns
		if ( preg_match( '#^/?wp/v2/([^/]+)#', $endpoint, $matches ) ) {
			$entity = $matches[1];
			
			// Map plural endpoints to our capability names
			$entity_map = array(
				'posts'      => 'posts',
				'pages'      => 'posts',
				'users'      => 'users',
				'comments'   => 'comments',
				'media'      => 'media',
				'tags'       => 'terms',
				'categories' => 'terms',
			);

			return isset( $entity_map[ $entity ] ) ? $entity_map[ $entity ] : $entity;
		}

		return false;
	}

	/**
	 * Get required capability for entity and method
	 *
	 * @param string $entity
	 * @param string $method
	 * @return string|false
	 */
	private static function get_required_capability( $entity, $method ) {
		$action_map = array(
			'GET'    => 'read',
			'POST'   => 'create',
			'PUT'    => 'edit',
			'PATCH'  => 'edit',
			'DELETE' => 'delete',
		);

		$action = isset( $action_map[ $method ] ) ? $action_map[ $method ] : false;
		
		if ( ! $action ) {
			return false;
		}

		// Special case for file uploads
		if ( $entity === 'media' && $method === 'POST' ) {
			return 'iwc_upload_files';
		}

		$capability = "iwc_{$action}_{$entity}";
		
		return array_key_exists( $capability, self::API_CAPABILITIES ) ? $capability : false;
	}

	/**
	 * Remove API capabilities (for plugin deactivation)
	 */
	public static function remove_api_capabilities() {
		$role = get_role( 'administrator' );
		if ( $role ) {
			foreach ( self::API_CAPABILITIES as $cap => $description ) {
				$role->remove_cap( $cap );
			}
		}
	}
}
