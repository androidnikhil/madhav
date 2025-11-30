<?php defined('ABSPATH') || die('No direct access allowed');

/**
 * @package Integromat_Connector
 * @version 1.6.5
 */

/**
Plugin Name: Make Connector
Description: Safely connect your site to make.com, work with custom meta fields through the REST API.
Author: Celonis s.r.o.
Author URI: https://www.make.com/en?utm_source=wordpress&utm_medium=partner&utm_campaign=wordpress-partner-make
Version: 1.6.5
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

define('IWC_FIELD_PREFIX', 'integromat_api_field_');
define('IWC_PLUGIN_NAME_SAFE', 'integromat-wordpress-connector');
define('IWC_MENUITEM_IDENTIFIER', 'integromat_custom_fields');
define('IWC_PLUGIN_VERSION', '1.6.5');

require __DIR__ . '/class/class-user.php';
require __DIR__ . '/class/class-rest-request.php';
require __DIR__ . '/class/class-rest-response.php';
require __DIR__ . '/class/class-api-token.php';
require __DIR__ . '/class/class-guard.php';
require __DIR__ . '/class/class-logger.php';
require __DIR__ . '/class/class-api-permissions.php';
require __DIR__ . '/class/class-rate-limiter.php';
require __DIR__ . '/class/class-file-validator.php';

require __DIR__ . '/api/authentication.php';
require __DIR__ . '/api/response.php';
require __DIR__ . '/settings/render.php';
require __DIR__ . '/settings/class-controller.php';
require __DIR__ . '/settings/class-meta-object.php';
require __DIR__ . '/settings/events.php';

$controller = new \Integromat\Controller();
$controller->init();

// Initialize API permissions
\Integromat\Api_Permissions::init();

// Custom CSS, JS.
add_action(
	'admin_enqueue_scripts',
	function ($hook) {
		// Only enqueue scripts for Make plugin
		$pos = strpos($hook, 'integromat');
		if ($pos === false) {
			return;
		}
		wp_enqueue_style(
			'integromat_css',
			plugin_dir_url(__FILE__) . 'assets/iwc.css',
			[],
			IWC_PLUGIN_VERSION
		);
		wp_enqueue_script(
			'integromat_js',
			plugin_dir_url(__FILE__) . 'assets/iwc.js',
			['jquery'],
			IWC_PLUGIN_VERSION,
			true
		);
		
		// Localize script for AJAX
		wp_localize_script('integromat_js', 'iwc_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'regenerate_nonce' => wp_create_nonce('iwc_regenerate_nonce'),
			'purge_nonce' => wp_create_nonce('iwc_purge_nonce'),
			'reveal_nonce' => wp_create_nonce('iwc_reveal_nonce')
		));
	}
);

// AJAX handler for API key regeneration
add_action('wp_ajax_iwc_regenerate_api_key', function() {
	// Verify nonce
	if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'iwc_regenerate_nonce')) {
		wp_send_json_error('Security check failed', 403);
		return;
	}
	
	// Verify current user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Insufficient permissions', 403);
		return;
	}
	
	// Verify confirmation text
	$confirmation = sanitize_text_field(wp_unslash($_POST['confirmation'] ?? ''));
	if (strtolower($confirmation) !== 'regenerate') {
		wp_send_json_error('Confirmation text does not match');
		return;
	}
	
	try {
		// Regenerate the API key
		$new_token = \Integromat\Api_Token::regenerate();
		$masked_token = str_repeat('•', 20) . substr($new_token, -4);

		wp_send_json_success(array(
			'message' => 'API key regenerated successfully',
			'new_token' => $new_token,
			'masked_token' => $masked_token
		));
	} catch (Exception $e) {
		wp_send_json_error('Failed to regenerate API key: ' . $e->getMessage());
	}
});

// AJAX handler for revealing API key
add_action('wp_ajax_iwc_reveal_api_key', function() {
	// Verify nonce
	if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'iwc_reveal_nonce')) {
		wp_send_json_error('Security check failed', 403);
		return;
	}
	
	// Verify current user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Insufficient permissions', 403);
		return;
	}
	
	try {
		// Log the API key reveal action for security audit
		if (class_exists('\\Integromat\\Logger')) {
			$current_user = wp_get_current_user();
			\Integromat\Logger::write('API key revealed by user: ' . $current_user->user_login . ' (ID: ' . $current_user->ID . ')');
		}
		
		// Get the current API key
		$api_token = \Integromat\Api_Token::get();
		
		if (empty($api_token)) {
			wp_send_json_error('No API key found');
			return;
		}

		wp_send_json_success(array(
			'api_key' => $api_token
		));
	} catch (Exception $e) {
		wp_send_json_error('Failed to retrieve API key: ' . $e->getMessage());
	}
});

// AJAX handler for log purging
add_action('wp_ajax_iwc_purge_logs', function() {
	// Verify nonce
	if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'iwc_purge_nonce')) {
		wp_send_json_error('Security check failed', 403);
		return;
	}
	
	// Verify current user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Insufficient permissions', 403);
		return;
	}
	
	try {
		// Purge the logs
		$result = \Integromat\Logger::purge();
		
		if ($result) {
			wp_send_json_success('All log data has been successfully purged');
		} else {
			wp_send_json_error('Failed to purge log data');
		}
	} catch (Exception $e) {
		wp_send_json_error('Failed to purge logs: ' . $e->getMessage());
	}
});

// Activation and deactivation hooks for API permissions
register_activation_hook( __FILE__, function() {
	\Integromat\Api_Permissions::add_api_capabilities();
	iwc_set_default_settings();
});

register_deactivation_hook( __FILE__, function() {
	\Integromat\Api_Permissions::remove_api_capabilities();
	iwc_cleanup_on_deactivation();
});

/**
 * Cleanup when plugin is deactivated
 */
function iwc_cleanup_on_deactivation() {
	// Remove version tracking
	delete_option('iwc_plugin_version');
	
	// Note: We intentionally don't remove user settings or API tokens
	// to preserve user configuration if they reactivate the plugin
}

/**
 * Set default settings when plugin is activated
 */
function iwc_set_default_settings() {
	// Check if this is a fresh installation or upgrade
	$current_version = get_option('iwc_plugin_version');
	
	// Only set defaults on fresh installation
	if (empty($current_version)) {
		// General settings - logging disabled by default
		add_option('iwc-logging-enabled', 'false');
		
		// API permissions - disabled by default
		add_option('iwc_api_permissions_enabled', '0');
		
		// Individual API permissions - all disabled by default
		$api_permissions = array(
			'iwc_read_posts', 'iwc_create_posts', 'iwc_edit_posts', 'iwc_delete_posts',
			'iwc_read_users', 'iwc_create_users', 'iwc_edit_users', 'iwc_delete_users',
			'iwc_read_comments', 'iwc_create_comments', 'iwc_edit_comments', 'iwc_delete_comments',
			'iwc_upload_files', 'iwc_read_media', 'iwc_edit_media', 'iwc_delete_media',
			'iwc_read_terms', 'iwc_create_terms', 'iwc_edit_terms', 'iwc_delete_terms',
		);
		
		foreach ($api_permissions as $permission) {
			add_option('iwc_permission_' . $permission, '0');
		}
		
		// Security settings - all disabled by default for backward compatibility
		add_option('iwc_rate_limit_enabled', '0');
		add_option('iwc_rate_limit_requests', '100');
		add_option('iwc_payload_limit_enabled', '0');
		add_option('iwc_max_payload_size', '10');
		add_option('iwc_strict_file_validation', '0');
		add_option('iwc_allowed_file_extensions', 'jpg,jpeg,png,gif,webp,svg,bmp,ico,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,odt,ods,zip,rar,7z,tar,gz,mp3,wav,mp4,avi,mov,wmv,flv,webm,json,xml,csv');
		add_option('iwc_log_security_events', '0');
		add_option('iwc_sanitize_post_content', '0');
	}
	
	// Generate API token if it doesn't exist (always check this)
	if (empty(\Integromat\Api_Token::get())) {
		\Integromat\Api_Token::initiate();
	}
	
	// Update plugin version
	update_option('iwc_plugin_version', IWC_PLUGIN_VERSION);
}
