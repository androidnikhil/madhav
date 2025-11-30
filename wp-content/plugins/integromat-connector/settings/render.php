<?php defined( 'ABSPATH' ) || die( 'No direct access allowed' );

add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'General Settings',
			'Make',
			'manage_options',
			'integromat',
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				settings_errors( 'integromat_api_messages' );

				include_once __DIR__ . '/template/general_menu.phtml';
			},
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDIwMDEwOTA0Ly9FTiIKICJodHRwOi8vd3d3LnczLm9yZy9UUi8yMDAxL1JFQy1TVkctMjAwMTA5MDQvRFREL3N2ZzEwLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4wIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiB3aWR0aD0iNTEyLjAwMDAwMHB0IiBoZWlnaHQ9IjUxMi4wMDAwMDBwdCIgdmlld0JveD0iMCAwIDUxMi4wMDAwMDAgNTEyLjAwMDAwMCIKIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIG1lZXQiPgoKPGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMC4wMDAwMDAsNTEyLjAwMDAwMCkgc2NhbGUoMC4xMDAwMDAsLTAuMTAwMDAwKSIKZmlsbD0iIzAwMDAwMCIgc3Ryb2tlPSJub25lIj4KPHBhdGggZD0iTTAgMjU2MCBsMCAtMjU2MCAyNTYwIDAgMjU2MCAwIDAgMjU2MCAwIDI1NjAgLTI1NjAgMCAtMjU2MCAwIDAKLTI1NjB6IG0yNzkwIDEwMDUgYzI2OSAtNTQgMzAzIC02NSAzMTQgLTEwNCAzIC05IC03OSAtNDQ1IC0xODMgLTk2OSAtMTQwCi03MDkgLTE5MyAtOTU3IC0yMDYgLTk3MiAtMTAgLTExIC0zMCAtMjAgLTQ0IC0yMCAtNDcgMCAtNTM2IDEwMSAtNTU4IDExNgotMTMgOCAtMjUgMjQgLTI4IDM3IC0zIDEyIDgwIDQ1MSAxODMgOTc2IDE3MCA4NTYgMTkxIDk1NiAyMTIgOTczIDEyIDEwIDI1CjE4IDI5IDE4IDQgMCAxMzAgLTI1IDI4MSAtNTV6IG0tNzIzIC04OSBjMjIwIC0xMTAgMjYzIC0xMzggMjYzIC0xNzYgMCAtMjQKLTg3MCAtMTc1MyAtODkyIC0xNzcyIC0xMSAtMTAgLTMwIC0xOCAtNDIgLTE4IC0yMyAwIC00ODcgMjMxIC01MTggMjU4IC0xMCA4Ci0xOCAyOSAtMTggNDUgMCAyMCAxNTAgMzI4IDQzNiA4OTYgMjQwIDQ3NiA0NDAgODcxIDQ0NiA4NzggMTIgMTUgNDcgMjEgNzQKMTIgMTEgLTQgMTI0IC01OSAyNTEgLTEyM3ogbTE3NTggODkgbDI1IC0yNCAwIC05NzUgYzAgLTY5MyAtMyAtOTgyIC0xMSAtOTk5Ci0yMCAtNDQgLTQ0IC00NyAtMzI1IC00NyAtMjg2IDAgLTMxNSA1IC0zMjggNTIgLTMgMTMgLTYgNDYxIC02IDk5NiBsMCA5NzMKMjUgMjQgMjQgMjUgMjg2IDAgMjg2IDAgMjQgLTI1eiIvPgo8L2c+Cjwvc3ZnPg=='
		);

		// Override the default submenu item to change "Make" to "General"
		add_submenu_page(
			'integromat',
			'General Settings',
			'General',
			'manage_options',
			'integromat',
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				settings_errors( 'integromat_api_messages' );

				include_once __DIR__ . '/template/general_menu.phtml';
			}
		);

		// REST API Custom Fields settings page.
		add_submenu_page(
			'integromat',
			'Custom API Fields',
			'Custom API Fields',
			'manage_options',
			IWC_MENUITEM_IDENTIFIER,
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				settings_errors( 'integromat_api_messages' );
				include_once __DIR__ . '/template/customFields.phtml';
			}
		);

		add_submenu_page(
			'integromat',
			'Custom Taxonomies',
			'Custom Taxonomies',
			'manage_options',
			'integromat_custom_toxonomies',
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				settings_errors( 'integromat_api_messages' );
				include_once __DIR__ . '/template/custom_taxonomies.phtml';
			}
		);

		// Security settings page
		add_submenu_page(
			'integromat',
			'Security Settings',
			'Security',
			'manage_options',
			'integromat_security',
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				settings_errors( 'integromat_security_messages' );
				include_once __DIR__ . '/template/security_settings.phtml';
			}
		);
	}
);
