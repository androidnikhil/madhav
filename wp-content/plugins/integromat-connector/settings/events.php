<?php defined( 'ABSPATH' ) || die( 'No direct access allowed' );

add_action(
	'admin_menu',
	function () {
		// Download a log file.
		if ( isset( $_GET['iwcdlogf'] ) ) {
			if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'log-nonce' ) ) {
				\Integromat\Logger::download();
			} else {
				wp_die( esc_html__( 'Security check failed', 'integromat-connector' ), esc_html__( 'Error', 'integromat-connector' ), array( 'response' => 403 ) );
			}
		}
	}
);
