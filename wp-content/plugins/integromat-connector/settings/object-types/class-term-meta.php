<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Terms_Meta extends Meta_Object {
	private $meta_item_keys = array();
	public function init() {
		global $wpdb;
		$this->meta_item_keys = $this->get_meta_items( $wpdb->termmeta );
		register_setting( 'integromat_api_term', 'integromat_api_options_term', array(
			'sanitize_callback' => array( $this, 'sanitize_term_options' ),
		) );

		add_settings_section(
			'integromat_api_section_terms',
			__( 'Terms Metadata Settings', 'integromat-connector' ),
			function () {
				?>
				<p><?php esc_html_e( 'Select terms metadata to include in REST API response', 'integromat-connector' ); ?></p>
				<p><a class="uncheck_all" data-status="0">Un/check all</a></p>
				<?php
			},
			'integromat_api_term'
		);

		foreach ( $this->meta_item_keys as $meta_item ) {
			add_settings_field(
				IWC_FIELD_PREFIX . $meta_item,
				esc_html( $meta_item ),
				function ( $args ) use ( $meta_item ) {
					$options = get_option( 'integromat_api_options_term' );
					?>
					<input type="checkbox" 
						name="integromat_api_options_term[<?php echo esc_attr( $args['label_for'] ); ?>]" 
						value="1" <?php echo ( isset( $options[ $args['label_for'] ] ) && $options[ $args['label_for'] ] == 1 ) ? 'checked' : ''; ?>
						id="<?php echo esc_attr( IWC_FIELD_PREFIX . $meta_item ); ?>">
					<?php
				},
				'integromat_api_term',
				'integromat_api_section_terms',
				array(
					'label_for'                  => IWC_FIELD_PREFIX . $meta_item,
					'class'                      => 'integromat_api_row',
					'integromat_api_custom_data' => 'custom',
				)
			);
		}
	}

	/**
	 * Sanitize term options
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_term_options( $input ) {
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
