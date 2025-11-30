<?php

namespace Integromat;

defined( 'ABSPATH' ) || die( 'No direct access allowed' );

class Meta_Object {

	/**
	 * Gets all metadata related to the object type
	 *
	 * @param $table
	 * @return array
	 */
	public function get_meta_items( $table ) {
		global $wpdb;
		
		// Validate table name against known WordPress meta tables for security
		$allowed_tables = array(
			$wpdb->postmeta,
			$wpdb->usermeta,
			$wpdb->commentmeta,
			$wpdb->termmeta
		);
		
		if ( ! in_array( $table, $allowed_tables, true ) ) {
			return array();
		}
		
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No WordPress function exists to get distinct meta keys from meta tables, one-time admin query, table name validated against whitelist
		$query = "SELECT DISTINCT(meta_key) FROM `" . esc_sql( $table ) . "` ORDER BY meta_key";
		$result = $wpdb->get_col( $query );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		
		return is_array( $result ) ? $result : array();
	}

}

