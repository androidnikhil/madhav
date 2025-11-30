<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

function add_taxonomies() {
	register_setting( 'integromat_api_taxonomy', 'integromat_api_options_taxonomy', array(
		'sanitize_callback' => __NAMESPACE__ . '\sanitize_taxonomy_options',
	) );

	add_settings_section(
		'integromat_api_section_taxonomy',
		'',
		function () {
			?>
				<p><?php esc_html_e( 'Select taxonomies to enable or disable in REST API response.', 'integromat-connector' ); ?></p>
				<p><a class="uncheck_all" data-status="0">Un/check all</a></p>
			<?php
		},
		'integromat_api_taxonomy'
	);

	$taxonomies = get_taxonomies(array('_builtin' => false, 'public' => true));
	foreach ( $taxonomies as $tax_slug ) {
		add_settings_field(
			$tax_slug,
			$tax_slug,
			function ( $args ) {
				$taxonomy = get_taxonomy( $args['label_for'] );
				$checked  = !empty( $taxonomy->show_in_rest ) ? 'checked' : '';
				?>
					<input type="checkbox" 
						name="integromat_api_options_taxonomy[<?php echo esc_attr( $args['label_for'] ); ?>]" 
						value="1" <?php echo esc_attr( $checked ); ?> >
				<?php

			},
			'integromat_api_taxonomy',
			'integromat_api_section_taxonomy',
			array(
				'label_for' => $tax_slug,
				'class'     => 'integromat_api_row',
			)
		);
	}
}

/**
 * Sanitize taxonomy options
 *
 * @param array $input
 * @return array
 */
function sanitize_taxonomy_options( $input ) {
	if ( ! is_array( $input ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $input as $key => $value ) {
		$sanitized[ sanitize_key( $key ) ] = sanitize_text_field( $value );
	}

	return $sanitized;
}
