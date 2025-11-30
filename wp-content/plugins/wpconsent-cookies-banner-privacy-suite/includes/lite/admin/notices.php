<?php
/**
 * Lite-specific admin notices.
 *
 * @package WPConsent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wpconsent_admin_page', 'wpconsent_maybe_add_lite_top_bar_notice', 4 );

/**
 * Add a notice to consider more features with offer.
 *
 * @return void
 */
function wpconsent_maybe_add_lite_top_bar_notice() {
	// Only add this to the WPConsent pages.
	if ( ! isset( $_GET['page'] ) || 0 !== strpos( $_GET['page'], 'wpconsent' ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		return;
	}

	// Don't show on the onboarding page.
	if ( 'wpconsent-onboarding' === $_GET['page'] ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		return;
	}

	$screen = get_current_screen();
	if ( isset( $screen->id ) && false !== strpos( $screen->id, 'wpconsent_page_wpconsent-' ) ) {
		$screen = str_replace( 'wpconsent_page_wpconsent-', '', $screen->id );
	} elseif ( isset( $screen->id ) && false !== strpos( $screen->id, 'toplevel_page_wpconsent' ) ) {
		$screen = 'dashboard';
	} else {
		$screen = 'dashboard';
	}

	$upgrade_url = wpconsent_utm_url(
		'https://wpconsent.com/lite/',
		'top-notice',
		$screen
	);

	WPConsent_Notice::top(
		sprintf(
		// Translators: %1$s and %2$s add a link to the upgrade page. %3$s and %4$s make the text bold.
			__( '%3$sYou\'re using WPConsent Lite%4$s. To unlock more features consider %1$supgrading to Pro%2$s.', 'wpconsent-cookies-banner-privacy-suite' ),
			'<a href="' . $upgrade_url . '" target="_blank" rel="noopener noreferrer">',
			'</a>',
			'<strong>',
			'</strong>'
		),
		array(
			'dismiss' => WPConsent_Notice::DISMISS_USER,
			'slug'    => 'consider-upgrading',
		)
	);
}
