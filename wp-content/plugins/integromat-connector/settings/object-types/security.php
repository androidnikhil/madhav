<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

function add_security_menu() {
	// Register security settings
	register_setting( 'integromat_security_options', 'iwc_rate_limit_enabled', array(
		'sanitize_callback' => 'Integromat\\iwc_sanitize_checkbox_value',
		'default' => '0',
	) );
	
	register_setting( 'integromat_security_options', 'iwc_rate_limit_requests', array(
		'sanitize_callback' => 'Integromat\\iwc_conditional_save_rate_limit_requests',
		'default' => 100,
	) );
	
	register_setting( 'integromat_security_options', 'iwc_payload_limit_enabled', array(
		'sanitize_callback' => 'Integromat\\iwc_sanitize_checkbox_value',
		'default' => '0',
	) );
	
	register_setting( 'integromat_security_options', 'iwc_max_payload_size', array(
		'sanitize_callback' => 'Integromat\\iwc_conditional_save_max_payload_size',
		'default' => 10,
	) );
	
	register_setting( 'integromat_security_options', 'iwc_strict_file_validation', array(
		'sanitize_callback' => 'Integromat\\iwc_sanitize_checkbox_value',
		'default' => '0',
	) );
	
	register_setting( 'integromat_security_options', 'iwc_allowed_file_extensions', array(
		'sanitize_callback' => 'Integromat\\iwc_conditional_save_allowed_extensions',
		'default' => 'jpg,jpeg,png,gif,webp,svg,bmp,ico,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,odt,ods,zip,rar,7z,tar,gz,mp3,wav,mp4,avi,mov,wmv,flv,webm,json,xml,csv',
	) );
	
	register_setting( 'integromat_security_options', 'iwc_log_security_events', array(
		'sanitize_callback' => 'Integromat\\iwc_sanitize_checkbox_value',
		'default' => '0',
	) );
	
	register_setting( 'integromat_security_options', 'iwc_sanitize_post_content', array(
		'sanitize_callback' => 'Integromat\\iwc_sanitize_checkbox_value',
		'default' => '0',
	) );

	// Security settings section
	add_settings_section(
		'integromat_security_section',
		'',
		function ( $args ) {
		},
		'integromat_security_options'
	);

	add_settings_field(
		'security_logging_control',
		'Security Logging',
		function ( $args ) {
			$log_security_events = get_option( 'iwc_log_security_events', '0' );
			?>
			<div class="iwc-security-logging-container">
				<label>
					<input type="checkbox" name="iwc_log_security_events" value="1" <?php checked( $log_security_events, '1' ); ?> />
					Log security events (recommended)
				</label>
				<p class="description">Log rate limiting violations and permission denials for security monitoring.</p>
			</div>
			<?php
		},
		'integromat_security_options',
		'integromat_security_section',
		array()
	);

	add_settings_field(
		'sanitize_post_content_control',
		'Content Sanitization',
		function ( $args ) {
			$sanitize_post_content = get_option( 'iwc_sanitize_post_content', '0' );
			?>
			<div class="iwc-sanitize-content-container">
				<label>
					<input type="checkbox" name="iwc_sanitize_post_content" value="1" <?php checked( $sanitize_post_content, '1' ); ?> />
					Sanitize post content (recommended)
				</label>
				<p class="description">Strip potentially harmful HTML tags and attributes from incoming content.</p>
				
				<div class="notice notice-warning" style="margin: 10px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-left: 4px solid #ffb900;">
					<p style="margin: 0 0 8px 0; font-size: 13px;">
						<strong>⚠️ Warning:</strong> Disabling this may allow dangerous HTML/scripts to be stored. Only disable if you trust your API clients completely.
					</p>
					<p style="margin: 0; font-size: 12px; color: #666;">
						<strong>Examples of tags that will be stripped when enabled:</strong> &lt;script&gt;, &lt;iframe&gt;, &lt;object&gt;, &lt;embed&gt;, &lt;form&gt;, &lt;input&gt;, &lt;style&gt;, &lt;link&gt;, &lt;meta&gt;
					</p>
				</div>
			</div>
			<?php
		},
		'integromat_security_options',
		'integromat_security_section',
		array()
	);

	add_settings_field(
		'rate_limiting_control',
		'Rate Limiting',
		function ( $args ) {
			$rate_limit_enabled = get_option( 'iwc_rate_limit_enabled', '0' );
			$rate_limit_requests = get_option( 'iwc_rate_limit_requests', '100' );
			?>
			<div class="iwc-rate-limiting-container">
				<label>
					<input type="checkbox" name="iwc_rate_limit_enabled" value="1" <?php checked( $rate_limit_enabled, '1' ); ?> />
					Enable rate limiting
				</label>
				<p class="description">Limit the number of API requests per minute to prevent abuse.</p>
				
				<div id="iwc-rate-limit-details" style="margin-top: 15px; <?php echo $rate_limit_enabled === '1' ? '' : 'display:none;'; ?>">
					<label for="iwc_rate_limit_requests" style="display: block; margin-bottom: 5px;">
						<strong>Requests Per Minute:</strong>
					</label>
					<input type="number" 
						id="iwc_rate_limit_requests" 
						name="iwc_rate_limit_requests" 
						value="<?php echo esc_attr( $rate_limit_requests ); ?>" 
						min="1" max="1000"
						style="width: 80px;" 
						<?php echo $rate_limit_enabled === '1' ? 'required' : '' ?> />
					<p class="description">Maximum number of API requests allowed per minute (default: 100).</p>
				</div>
			</div>

			<script>
			jQuery(document).ready(function($) {
				$('input[name="iwc_rate_limit_enabled"]').change(function() {
					if ($(this).is(':checked')) {
						$('#iwc-rate-limit-details').show();
						$('#iwc_rate_limit_requests').attr('required', true);
					} else {
						$('#iwc-rate-limit-details').hide();
						$('#iwc_rate_limit_requests').attr('required', false);
					}
				});
			});
			</script>
			<?php
		},
		'integromat_security_options',
		'integromat_security_section',
		array()
	);

	add_settings_field(
		'payload_size_control',
		'Request Size Limits',
		function ( $args ) {
			$payload_limit_enabled = get_option( 'iwc_payload_limit_enabled', '0' );
			$max_payload_size = get_option( 'iwc_max_payload_size', '10' );
			?>
			<div class="iwc-payload-container">
				<label>
					<input type="checkbox" name="iwc_payload_limit_enabled" value="1" <?php checked( $payload_limit_enabled, '1' ); ?> />
					Enable request size limits
				</label>
				<p class="description">Limit the maximum size of API request payloads to prevent abuse.</p>
				
				<div id="iwc-payload-details" style="margin-top: 15px; <?php echo $payload_limit_enabled === '1' ? '' : 'display:none;'; ?>">
					<label for="iwc_max_payload_size" style="display: block; margin-bottom: 5px;">
						<strong>Maximum Payload Size (MB):</strong>
					</label>
					<input type="number" 
						id="iwc_max_payload_size" 
						name="iwc_max_payload_size" 
						value="<?php echo esc_attr( $max_payload_size ); ?>" 
						min="1" max="100" 
						style="width: 80px;"
						<?php echo $payload_limit_enabled === '1' ? 'required' : '' ?> />
					<p class="description">Maximum size for API request payloads in megabytes (default: 10MB).</p>
				</div>
			</div>

			<script>
			jQuery(document).ready(function($) {
				$('input[name="iwc_payload_limit_enabled"]').change(function() {
					if ($(this).is(':checked')) {
						$('#iwc-payload-details').show();
						$('#iwc_max_payload_size').attr('required', true);
					} else {
						$('#iwc-payload-details').hide();
						$('#iwc_max_payload_size').attr('required', false);
					}
				});
			});
			</script>
			<?php
		},
		'integromat_security_options',
		'integromat_security_section',
		array()
	);

	add_settings_field(
		'file_validation_control',
		'File Upload Security',
		function ( $args ) {
			$strict_file_validation = get_option( 'iwc_strict_file_validation', '0' );
			$allowed_extensions = get_option( 'iwc_allowed_file_extensions', 'jpg,jpeg,png,gif,webp,svg,bmp,ico,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,odt,ods,zip,rar,7z,tar,gz,mp3,wav,mp4,avi,mov,wmv,flv,webm,json,xml,csv' );
			?>
			<div class="iwc-file-validation-container">
				<label>
					<input type="checkbox" name="iwc_strict_file_validation" value="1" <?php checked( $strict_file_validation, '1' ); ?> />
					Enable strict file validation
				</label>
				<p class="description">Enhanced file type validation for uploads based on the whitelist below.</p>
				
				<div id="iwc-file-validation-details" style="margin-top: 15px; <?php echo $strict_file_validation === '1' ? '' : 'display:none;'; ?>">
					<label for="iwc_allowed_file_extensions" style="display: block; margin-bottom: 5px;">
						<strong>Allowed File Extensions:</strong>
					</label>
					<textarea 
						id="iwc_allowed_file_extensions" 
						name="iwc_allowed_file_extensions" 
						rows="3"
						style="width: 100%; max-width: 600px;"
						placeholder="Enter allowed file extensions separated by commas"
						<?php echo $strict_file_validation === '1' ? 'required' : '' ?>
					><?php echo esc_textarea( $allowed_extensions ); ?></textarea>
					<p class="description">
						Enter file extensions separated by commas (e.g., jpg,png,pdf,doc). Do not include dots or spaces.
						<br><strong>Common extensions:</strong> Images (jpg,jpeg,png,gif,webp,svg), Documents (pdf,doc,docx,txt), Archives (zip,rar), Media (mp3,mp4)
					</p>
					
					<div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 3px;">
						<strong style="color: #0073aa;">💡 Security Tips:</strong>
						<ul style="margin: 5px 0 0 20px; font-size: 12px;">
							<li>Avoid executable file types (exe, php, js, bat, sh, etc.)</li>
							<li>Be cautious with script files that could be executed by the server</li>
							<li>Consider your site's specific needs when setting allowed extensions</li>
						</ul>
					</div>
				</div>
			</div>

			<script>
			jQuery(document).ready(function($) {
				$('input[name="iwc_strict_file_validation"]').change(function() {
					if ($(this).is(':checked')) {
						$('#iwc-file-validation-details').show();
						$('#iwc_allowed_file_extensions').attr('required', true);
					} else {
						$('#iwc-file-validation-details').hide();
						$('#iwc_allowed_file_extensions').attr('required', false);
					}
				});
			});
			</script>
			<?php
		},
		'integromat_security_options',
		'integromat_security_section',
		array()
	);
}

// Conditional save functions to preserve values when checkboxes are unchecked
function iwc_conditional_save_rate_limit_requests( $value ) {
	// Safety check: only process during actual form submission
	if ( ! is_admin() || ! isset( $_POST['option_page'] ) || $_POST['option_page'] !== 'integromat_security_options' ) {
		return absint( $value );
	}
	
	// Verify nonce for security
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'integromat_security_options-options' ) ) {
		return absint( $value );
	}
	
	// Only save if rate limiting is enabled
	if ( isset( $_POST['iwc_rate_limit_enabled'] ) && $_POST['iwc_rate_limit_enabled'] === '1' ) {
		$sanitized_value = absint( $value );
		if ( $sanitized_value <= 0 ) {
			add_settings_error(
				'integromat_security_options',
				'iwc_rate_limit_requests',
				'Requests Per Minute must be greater than 0 when Rate Limiting is enabled.',
				'error'
			);
			// Return current saved value instead of the invalid one
			return get_option( 'iwc_rate_limit_requests', 100 );
		}
		return $sanitized_value;
	}
	
	// Return current saved value to preserve it
	return get_option( 'iwc_rate_limit_requests', 100 );
}

function iwc_conditional_save_max_payload_size( $value ) {
	// Safety check: only process during actual form submission
	if ( ! is_admin() || ! isset( $_POST['option_page'] ) || $_POST['option_page'] !== 'integromat_security_options' ) {
		return absint( $value );
	}
	
	// Verify nonce for security
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'integromat_security_options-options' ) ) {
		return absint( $value );
	}
	
	// Only save if payload limits are enabled
	if ( isset( $_POST['iwc_payload_limit_enabled'] ) && $_POST['iwc_payload_limit_enabled'] === '1' ) {
		$sanitized_value = absint( $value );
		if ( $sanitized_value <= 0 ) {
			add_settings_error(
				'integromat_security_options',
				'iwc_max_payload_size',
				'Maximum Payload Size must be greater than 0 when Request Size Limits are enabled.',
				'error'
			);
			// Return current saved value instead of the invalid one
			return get_option( 'iwc_max_payload_size', 10 );
		}
		return $sanitized_value;
	}
	
	// Return current saved value to preserve it
	return get_option( 'iwc_max_payload_size', 10 );
}

function iwc_conditional_save_allowed_extensions( $value ) {
	// Safety check: only process during actual form submission
	if ( ! is_admin() || ! isset( $_POST['option_page'] ) || $_POST['option_page'] !== 'integromat_security_options' ) {
		return sanitize_text_field( $value );
	}
	
	// Verify nonce for security
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'integromat_security_options-options' ) ) {
		return sanitize_text_field( $value );
	}
	
	// Only save if strict file validation is enabled
	if ( isset( $_POST['iwc_strict_file_validation'] ) && $_POST['iwc_strict_file_validation'] === '1' ) {
		$sanitized_value = trim( sanitize_text_field( $value ) );
		if ( empty( $sanitized_value ) ) {
			add_settings_error(
				'integromat_security_options',
				'iwc_allowed_file_extensions',
				'Allowed File Extensions cannot be empty when Strict File Validation is enabled.',
				'error'
			);
			// Return current saved value instead of the empty one
			return get_option( 'iwc_allowed_file_extensions', 'jpg,jpeg,png,gif,webp,svg,bmp,ico,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,odt,ods,zip,rar,7z,tar,gz,mp3,wav,mp4,avi,mov,wmv,flv,webm,json,xml,csv' );
		}
		return $sanitized_value;
	}
	
	// Return current saved value to preserve it
	return get_option( 'iwc_allowed_file_extensions', 'jpg,jpeg,png,gif,webp,svg,bmp,ico,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,odt,ods,zip,rar,7z,tar,gz,mp3,wav,mp4,avi,mov,wmv,flv,webm,json,xml,csv' );
}

function iwc_sanitize_checkbox_value( $value ) {
	// Ensure only '0' or '1' values are accepted for checkbox settings
	return ( $value === '1' || $value === 1 || $value === true ) ? '1' : '0';
}
