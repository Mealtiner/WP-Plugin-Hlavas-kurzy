<?php
/**
 * Clean uninstall for HLAVAS Terms plugin.
 *
 * Removes only plugin-owned data:
 * - plugin DB tables
 * - plugin options / transients
 * - generated log files
 *
 * Does not touch Fluent Forms tables or submissions.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Drop plugin tables for current site.
 *
 * @param wpdb $wpdb WordPress database object.
 * @return void
 */
function hlavas_terms_uninstall_drop_tables( wpdb $wpdb ): void {
	$tables = [
		$wpdb->prefix . 'hlavas_terms',
		$wpdb->prefix . 'hlavas_term_types',
	];

	foreach ( $tables as $table_name ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}

/**
 * Remove plugin options and transients for current site.
 *
 * @param wpdb $wpdb WordPress database object.
 * @return void
 */
function hlavas_terms_uninstall_delete_options( wpdb $wpdb ): void {
	$options_table = $wpdb->options;

	$exact_options = [
		'hlavas_terms_fluent_form_id',
		'hlavas_terms_debug_mode',
		'hlavas_terms_sync_log',
		'hlavas_terms_form_sync_log',
		'hlavas_terms_sync_value_mode',
		'hlavas_terms_report_email',
		'hlavas_terms_db_version',
		'hlavas_terms_plugin_version',
	];

	foreach ( $exact_options as $option_name ) {
		delete_option( $option_name );
	}

	delete_transient( 'hlavas_terms_sync_preview' );
	delete_transient( 'hlavas_sync_result' );

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$options_table}
			WHERE option_name LIKE %s
				OR option_name LIKE %s
				OR option_name LIKE %s
				OR option_name LIKE %s",
			'_transient_hlavas_term_sync_result_%',
			'_transient_timeout_hlavas_term_sync_result_%',
			'_transient_hlavas_terms_%',
			'_transient_timeout_hlavas_terms_%'
		)
	);
}

/**
 * Remove generated log files from plugin directory.
 *
 * WordPress itself removes the plugin directory on Delete. This cleanup makes
 * sure generated files are also gone if uninstall runs before the final delete.
 *
 * @return void
 */
function hlavas_terms_uninstall_delete_log_dir(): void {
	$log_dir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';

	if ( ! is_dir( $log_dir ) ) {
		return;
	}

	$items = scandir( $log_dir );

	if ( ! is_array( $items ) ) {
		return;
	}

	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}

		$path = $log_dir . DIRECTORY_SEPARATOR . $item;

		if ( is_file( $path ) ) {
			@unlink( $path );
		}
	}

	@rmdir( $log_dir );
}

/**
 * Run uninstall cleanup for current site.
 *
 * @return void
 */
function hlavas_terms_uninstall_current_site(): void {
	global $wpdb;
	/** @var wpdb $wpdb */

	hlavas_terms_uninstall_drop_tables( $wpdb );
	hlavas_terms_uninstall_delete_options( $wpdb );
}

if ( is_multisite() ) {
	$site_ids = get_sites(
		[
			'fields' => 'ids',
		]
	);

	if ( is_array( $site_ids ) ) {
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			hlavas_terms_uninstall_current_site();
			restore_current_blog();
		}
	}
} else {
	hlavas_terms_uninstall_current_site();
}

hlavas_terms_uninstall_delete_log_dir();
