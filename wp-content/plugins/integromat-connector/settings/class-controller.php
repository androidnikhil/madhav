<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Controller {
	public function init() {
		/**
		 * Custom Fields initialization
		 */
		add_action(
			'admin_init',
			function () {
				global $pagenow;
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin page check, no form processing
				if ( 'options.php' === $pagenow || ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === IWC_MENUITEM_IDENTIFIER ) ) {
					// Posts.
					require_once __DIR__ . '/object-types/class-post-meta.php';
					$posts_meta = new Posts_Meta();
					$posts_meta->init();

					// Comments.
					require_once __DIR__ . '/object-types/class-comments-meta.php';
					$comments_meta = new Comments_Meta();
					$comments_meta->init();

					// Users.
					require_once __DIR__ . '/object-types/class-user-meta.php';
					$users_meta = new Users_Meta();
					$users_meta->init();

					// Terms.
					require_once __DIR__ . '/object-types/class-term-meta.php';
					$terms_meta = new Terms_Meta();
					$terms_meta->init();
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin page check, no form processing
				if ( 'options.php' === $pagenow || ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'integromat_custom_toxonomies' ) ) {
					// Taxonomies.
					require_once __DIR__ . '/object-types/custom-taxonomy.php';
					add_taxonomies();
				}
				require_once __DIR__ . '/object-types/general.php';
				add_general_menu();
				
				// Security settings
				require_once __DIR__ . '/object-types/security.php';
				add_security_menu();
			}
		);

		/**
		 * Connector initialization
		 */
		add_action(
			'init',
			function () {
				Api_Token::initiate();
			}
		);
	}
}

add_filter( "pre_update_option_integromat_api_options_taxonomy", function ($new_value, $old_value) {

	$updated_taxonomies = empty( $new_value ) ? array() : $new_value;

	$taxonomies = get_taxonomies( array( '_builtin' => false, 'public' => true ) );
	foreach ( $taxonomies as $key ) {
		if ( ! in_array( $key, array_keys( $updated_taxonomies ) ) ) {
			$updated_taxonomies[ $key ] = "0";
		}
	}

	return $updated_taxonomies;

}, 10, 2 );

add_filter(
	'register_taxonomy_args',
	function ($args, $taxonomy_name) {

		if ( ( isset( $args['_builtin'] ) && $args['_builtin'] ) || ( isset( $args['public'] ) && ! $args['public'] ) ) {
			return $args;
		}

		$optn = get_option( 'integromat_api_options_taxonomy' );

		if ( false === $optn ) {
			// option doesn't exist in db, run with everything default.
			return $args;
		}

		$saved_taxonomies = empty( $optn ) ? array() : array_keys( $optn );

		if ( in_array( $taxonomy_name, $saved_taxonomies ) ) {
			$args['show_in_rest'] = (boolean) $optn[ $taxonomy_name ];
		}
		return $args;
	},
	10,
	2
);