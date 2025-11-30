<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Users_Meta extends Meta_Object {


	private $meta_item_keys = array();


	public function init() {
		global $wpdb;
		$this->meta_item_keys = $this->get_meta_items( $wpdb->usermeta );
		register_setting( 'integromat_api_user', 'integromat_api_options_user', array(
			'sanitize_callback' => array( $this, 'sanitize_user_options' ),
		) );

		add_settings_section(
			'integromat_api_section_users',
			__( 'Users Metadata Settings', 'integromat-connector' ),
			function () {
				?>
				<p><?php esc_html_e( 'Select users metadata to include in REST API response', 'integromat-connector' ); ?></p>
				<p><a class="uncheck_all" data-status="0">Un/check all</a></p>
				<?php
			},
			'integromat_api_user'
		);

		foreach ( $this->meta_item_keys as $meta_item ) {
			add_settings_field(
				IWC_FIELD_PREFIX . $meta_item,
				esc_html( $meta_item ),
				function ( $args ) use ( $meta_item ) {
					$options = get_option( 'integromat_api_options_user' );
					?>
					<input type="checkbox" 
					name="integromat_api_options_user[<?php echo esc_attr( $args['label_for'] ); ?>]" 
					value="1" <?php echo ( isset( $options[ $args['label_for'] ] ) && $options[ $args['label_for'] ] == 1 ) ? 'checked' : ''; ?>
					id="<?php echo esc_attr( IWC_FIELD_PREFIX . $meta_item ); ?>">
					<?php
				},
				'integromat_api_user',
				'integromat_api_section_users',
				array(
					'label_for'                  => IWC_FIELD_PREFIX . $meta_item,
					'class'                      => 'integromat_api_row',
					'integromat_api_custom_data' => 'custom',
				)
			);
		}
	}

	/**
	 * Sanitize user options
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_user_options( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $input as $key => $value ) {
			$sanitized[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}

		return $sanitized;
	}

}

