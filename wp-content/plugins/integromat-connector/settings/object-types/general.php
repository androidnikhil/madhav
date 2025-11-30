<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

function add_general_menu() {
	register_setting( 'integromat_main', 'iwc-logging-enabled', array(
		'sanitize_callback' => 'sanitize_text_field',
	) ); // register the same name in settings as before to pick it up in old installations.

	// Register API permissions settings in main group for general page
	register_setting( 'integromat_main', 'iwc_api_permissions_enabled', array(
		'sanitize_callback' => 'sanitize_text_field',
		'default' => '0',
	) );

	// Register individual API permission settings in main group
	$api_permissions = array(
		'iwc_read_posts', 'iwc_create_posts', 'iwc_edit_posts', 'iwc_delete_posts',
		'iwc_read_users', 'iwc_create_users', 'iwc_edit_users', 'iwc_delete_users',
		'iwc_read_comments', 'iwc_create_comments', 'iwc_edit_comments', 'iwc_delete_comments',
		'iwc_upload_files', 'iwc_read_media', 'iwc_edit_media', 'iwc_delete_media',
		'iwc_read_terms', 'iwc_create_terms', 'iwc_edit_terms', 'iwc_delete_terms',
	);
	
	foreach ($api_permissions as $permission) {
		register_setting( 'integromat_main', 'iwc_permission_' . $permission, array(
			'sanitize_callback' => 'sanitize_text_field',
			'default' => '0',
		) );
	}

	add_settings_section(
		'integromat_main_section',
		'',
		function ( $args ) {
		},
		'integromat_main'
	);

	add_settings_field(
		'api_key',
		'API Key',
		function ( $args ) {
			$api_token = $args['api_key'];
			$masked_token = str_repeat('•', 20) . substr($api_token, -4); // Show last 4 characters
			?>
				<div class="iwc-api-key-container">
					<input type="text" 
						id="iwc-api-key-value" 
						readonly="readonly" 
						value="<?php echo esc_attr( $masked_token ); ?>" 
						data-masked="<?php echo esc_attr( $masked_token ); ?>"
						class="w-300">
					<button type="button" 
						id="iwc-api-key-toggle" 
						class="button"
						data-state="masked">
						Reveal
					</button>
					<button type="button" 
						id="iwc-api-key-regenerate" 
						class="button iwc-confirm-btn"
						title="Generate a new API key (this will break existing connections)">
						Regenerate
					</button>
				</div>
				<p class="comment">Use this token when creating a new connection in the WordPress app.</p>
			<?php
		},
		'integromat_main',
		'integromat_main_section',
		array(
			'api_key' => \Integromat\Api_Token::get(),
		)
	);

	add_settings_field(
		'api_permissions_control',
		'API Permissions',
		function ( $args ) {
			$api_permissions_enabled = get_option( 'iwc_api_permissions_enabled', '0' );
			?>
			<div class="iwc-api-permissions-container">
				<label>
					<input type="checkbox" name="iwc_api_permissions_enabled" value="1" <?php checked( $api_permissions_enabled, '1' ); ?> />
					Enable granular API permissions (recommended)
				</label>
				
				<div class="notice notice-info" style="margin: 10px 0; padding: 10px; background: #e7f3ff; border: 1px solid #72aee6; border-left: 4px solid #0073aa;">
					<p style="margin: 0; font-size: 13px;">
						<strong>ℹ️ Important:</strong> If you are using the "Make an API Call" module in your scenarios, DO NOT enable granular permissions.
					</p>
				</div>
				
				<div class="notice notice-warning" style="margin: 10px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-left: 4px solid #ffb900;">
					<p style="margin: 0; font-size: 13px;">
						<strong>⚠️ Warning:</strong> Changing API permissions may break existing Make scenarios. 
						Test your scenarios after making changes and ensure required permissions are enabled for your integrations to work properly.
					</p>
				</div>
				
				<div id="iwc-permissions-details" style="margin-top: 15px; <?php echo $api_permissions_enabled === '1' ? '' : 'display:none;'; ?>">
					<div style="margin-bottom: 10px;">
						<button type="button" class="button button-small iwc-perm-enable-all">All</button>
						<button type="button" class="button button-small iwc-perm-disable-all">None</button>
					</div>
					
					<div class="iwc-permissions-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
						<?php
						$permission_groups = array(
							'Posts' => array('read', 'create', 'update', 'delete'),
							'Users' => array('read', 'create', 'update', 'delete'), 
							'Comments' => array('read', 'create', 'update', 'delete'),
							'Media' => array('read', 'upload', 'update', 'delete'),
							'Terms' => array('read', 'create', 'update', 'delete'),
						);
						
						foreach ($permission_groups as $group_name => $operations) {
							echo '<div class="iwc-permission-group">';
							echo '<h4 style="margin: 0 0 8px 0; font-size: 13px;">' . esc_html($group_name) . '</h4>';
							
							foreach ($operations as $operation) {
								// Map display name to actual capability name
								$capability_operation = ($operation === 'update') ? 'edit' : $operation;
								$capability = ($group_name === 'Media' && $operation === 'upload') ? 'iwc_upload_files' : 'iwc_' . $capability_operation . '_' . strtolower($group_name);
								
								$permission_enabled = get_option('iwc_permission_' . $capability, '0');
								$is_dangerous = in_array($operation, array('delete', 'upload'));
								$color = $is_dangerous ? 'color: #d63384;' : '';
								
								echo '<label style="display: block; margin-bottom: 4px; font-size: 12px; ' . esc_attr($color) . '">';
								echo '<input type="checkbox" name="iwc_permission_' . esc_attr($capability) . '" value="1" ' . checked($permission_enabled, '1', false) . ' style="margin-right: 5px;" />';
								echo esc_html(ucfirst($operation));
								echo '</label>';
							}
							echo '</div>';
						}
						?>
					</div>
					
					<p class="description" style="margin-top: 10px;">
						<strong style="color: #d63384;">Dangerous permissions:</strong> Delete and upload operations.
					</p>
				</div>
			</div>

			<script>
			jQuery(document).ready(function($) {
				// Toggle permissions detail visibility
				$('input[name="iwc_api_permissions_enabled"]').change(function() {
					if ($(this).is(':checked')) {
						$('#iwc-permissions-details').show();
					} else {
						$('#iwc-permissions-details').hide();
					}
				});
				
				// Bulk controls
				$('.iwc-perm-enable-all').click(function() {
					$('.iwc-permissions-grid input[type="checkbox"]').prop('checked', true);
				});
				
				$('.iwc-perm-disable-all').click(function() {
					$('.iwc-permissions-grid input[type="checkbox"]').prop('checked', false);
				});
			});
			</script>
			<?php
		},
		'integromat_main',
		'integromat_main_section',
		array()
	);

	add_settings_field(
		'enable_logging',
		'Logs',
		function ( $args ) {
			$val        = get_option( 'iwc-logging-enabled' ); // this option is a bool value in previous versions.
			$is_enabled = ( 'true' === $val ) ? true : false;

			$checked = $is_enabled ? 'checked' : '';
			$name    = $args['label_for']; // use for id as well.
			?>
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="true" id="<?php echo esc_attr( $name ); ?>" <?php echo esc_attr( $checked ); ?> >
				<label>Logging enabled</label>
			<?php
		},
		'integromat_main',
		'integromat_main_section',
		array(
			'label_for' => 'iwc-logging-enabled',
		)
	);
	add_settings_field(
		'get_log_btn_id',
		'Log File',
		function ( $args ) {
			$enabled = $args['enabled'] ? '' : 'disabled';
			$url     = $args['url'];
			$nonce   = wp_create_nonce( 'log-nonce' );
			$href    = $args['enabled'] ? "href={$url}&_wpnonce={$nonce}" : '';
			?>
				<div class="iwc-log-actions">
					<a class="button <?php echo esc_attr( $enabled ); ?>" <?php echo esc_url( $href ); ?> >Download</a>
					<button type="button" 
						id="iwc-log-purge" 
						class="button iwc-confirm-btn <?php echo esc_attr( $enabled ); ?>"
						<?php echo $args['enabled'] ? '' : 'disabled'; ?>
						title="Delete all stored log data">
						Purge
					</button>
				</div>
				<p class="iwc-comment ">
					Although we try to remove them, there could still be some potentially sensitive information (like authentication tokens or passwords) contained in the downloaded file.
					The downloaded file includes server information and CSV headers that are generated at download time. Please check the section between  =SERVER INFO START=  and  =SERVER INFO END= delimiters (located at the start of the downloaded file) and possibly remove the sensitive data (or whole section) before sending this file to someone else.
				</p>
			<?php
		},
		'integromat_main',
		'integromat_main_section',
		array(
			'enabled' => \Integromat\Logger::file_exists(),
			'url'     => '?page=integromat&iwcdlogf',
		)
	);
}
