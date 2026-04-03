<?php
/**
 * Plugin Name: HLAVAS – Správa termínů kurzů a zkoušek
 * Plugin URI:  https://hlavas.cz
 * Description: Centrální správa termínů kurzů a zkoušek se synchronizací do Fluent Forms pro projekt HLAVAS.cz realizovaný Jihomoravskou radou dětí a mládeže (JRDM).
 * Version:     1.1.2
 * Author:      Michal "Mealtiner" Truhlář
 * Author URI:  https://mealtiner.cz
 * Text Domain: hlavas-terms
 * Domain Path: /languages
 * Requires PHP: 8.4
 * Requires at least: 6.0
 * License:     GPL v2 or later
 */

// Plugin vyvinut pro projekt HLAVAS.cz
// Zadavatel / projektový rámec: Jihomoravská rada dětí a mládeže (JRDM)

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'HLAVAS_TERMS_VERSION', '1.1.2' );
define( 'HLAVAS_TERMS_FILE', __FILE__ );
define( 'HLAVAS_TERMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'HLAVAS_TERMS_URL', plugin_dir_url( __FILE__ ) );
define( 'HLAVAS_TERMS_TABLE', 'hlavas_terms' );
define( 'HLAVAS_TERMS_TYPES_TABLE', 'hlavas_term_types' );
define( 'HLAVAS_TERMS_OPTION_FORM_ID', 'hlavas_terms_fluent_form_id' );
define( 'HLAVAS_TERMS_OPTION_DEBUG_MODE', 'hlavas_terms_debug_mode' );
define( 'HLAVAS_TERMS_DEFAULT_FORM_ID', 3 );
define( 'HLAVAS_TERMS_PLUGIN_NAME', 'HLAVAS – Správa termínů kurzů a zkoušek' );
define( 'HLAVAS_TERMS_PLUGIN_SLUG', 'hlavas-terms' );
define( 'HLAVAS_TERMS_AUTHOR', 'Michal "Mealtiner" Truhlář' );
define( 'HLAVAS_TERMS_AUTHOR_EMAIL', 'mealtiner@mealtiner.net' );
define( 'HLAVAS_TERMS_ORGANIZATION', 'Jihomoravská rada dětí a mládeže (JRDM)' );
define( 'HLAVAS_TERMS_ORGANIZATION_EMAIL', 'info@jrdm.cz' );
define( 'HLAVAS_TERMS_AUTHOR_URL', 'https://mealtiner.cz' );
define( 'HLAVAS_TERMS_PLUGIN_URL', 'https://hlavas.cz' );
define( 'HLAVAS_TERMS_LICENSE', 'GPL v2 or later' );
define( 'HLAVAS_TERMS_MIN_PHP', '8.4' );
define( 'HLAVAS_TERMS_MIN_WP', '6.0' );

/**
 * Returns the full plugin table name including WP prefix.
 *
 * @return string
 */
function hlavas_terms_get_table_name(): string {
	global $wpdb;
	/** @var wpdb $wpdb */

	return $wpdb->prefix . HLAVAS_TERMS_TABLE;
}

/**
 * Returns the full qualification types table name including WP prefix.
 *
 * @return string
 */
function hlavas_terms_get_types_table_name(): string {
	global $wpdb;
	/** @var wpdb $wpdb */

	return $wpdb->prefix . HLAVAS_TERMS_TYPES_TABLE;
}

/**
 * Returns Fluent Forms form ID from plugin settings.
 * Falls back to default if not yet configured.
 *
 * @return int
 */
function hlavas_terms_get_form_id(): int {
	$form_id = (int) get_option( HLAVAS_TERMS_OPTION_FORM_ID, HLAVAS_TERMS_DEFAULT_FORM_ID );

	return $form_id > 0 ? $form_id : HLAVAS_TERMS_DEFAULT_FORM_ID;
}

/**
 * Returns whether plugin debug mode is enabled.
 *
 * @return bool
 */
function hlavas_terms_is_debug_enabled(): bool {
	return (bool) get_option( HLAVAS_TERMS_OPTION_DEBUG_MODE, false );
}

/**
 * Returns plugin metadata for admin info page.
 *
 * @return array<string, string>
 */
function hlavas_terms_get_plugin_info(): array {
	return [
		'name'             => HLAVAS_TERMS_PLUGIN_NAME,
		'slug'             => HLAVAS_TERMS_PLUGIN_SLUG,
		'version'          => HLAVAS_TERMS_VERSION,
		'plugin_url'       => HLAVAS_TERMS_PLUGIN_URL,
		'author'           => HLAVAS_TERMS_AUTHOR,
		'author_email'     => HLAVAS_TERMS_AUTHOR_EMAIL,
		'author_url'       => HLAVAS_TERMS_AUTHOR_URL,
		'organization'     => HLAVAS_TERMS_ORGANIZATION,
		'organization_email' => HLAVAS_TERMS_ORGANIZATION_EMAIL,
		'license'          => HLAVAS_TERMS_LICENSE,
		'text_domain'      => 'hlavas-terms',
		'table'            => hlavas_terms_get_table_name(),
		'types_table'      => hlavas_terms_get_types_table_name(),
		'min_php'          => HLAVAS_TERMS_MIN_PHP,
		'min_wp'           => HLAVAS_TERMS_MIN_WP,
		'current_php'      => PHP_VERSION,
		'current_wp'       => get_bloginfo( 'version' ),
		'configured_form'  => (string) hlavas_terms_get_form_id(),
		'debug_mode'       => hlavas_terms_is_debug_enabled() ? 'Zapnuto' : 'Vypnuto',
		'plugin_file'      => HLAVAS_TERMS_FILE,
		'plugin_directory' => HLAVAS_TERMS_DIR,
	];
}

/**
 * Autoload plugin classes from /includes.
 */
spl_autoload_register(
	function ( string $class ): void {
		$prefix   = 'Hlavas_Terms_';
		$base_dir = HLAVAS_TERMS_DIR . 'includes/';

		if ( strpos( $class, $prefix ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );
		$file           = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Runs on plugin activation.
 *
 * @return void
 */
function hlavas_terms_activate(): void {
	require_once HLAVAS_TERMS_DIR . 'includes/class-activator.php';
	Hlavas_Terms_Activator::activate();
}
register_activation_hook( __FILE__, 'hlavas_terms_activate' );

/**
 * Runs on plugin deactivation.
 *
 * @return void
 */
function hlavas_terms_deactivate(): void {
	delete_transient( 'hlavas_terms_sync_preview' );
}
register_deactivation_hook( __FILE__, 'hlavas_terms_deactivate' );

/**
 * Loads plugin textdomain.
 *
 * @return void
 */
function hlavas_terms_load_textdomain(): void {
	load_plugin_textdomain(
		'hlavas-terms',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}

/**
 * Runs database upgrade routine if needed.
 *
 * @return void
 */
function hlavas_terms_maybe_upgrade_db(): void {
	$db_version = get_option( 'hlavas_terms_db_version', '0' );

	if ( version_compare( $db_version, HLAVAS_TERMS_VERSION, '<' ) ) {
		require_once HLAVAS_TERMS_DIR . 'includes/class-activator.php';
		Hlavas_Terms_Activator::activate();
	}
}

/**
 * Boots admin classes.
 *
 * @return void
 */
function hlavas_terms_boot_admin(): void {
	if ( ! is_admin() ) {
		return;
	}

	require_once HLAVAS_TERMS_DIR . 'admin/class-admin.php';
	new Hlavas_Terms_Admin();
}

/**
 * Boots shared/frontend services.
 *
 * @return void
 */
function hlavas_terms_boot_services(): void {
	require_once HLAVAS_TERMS_DIR . 'includes/class-repository.php';
	require_once HLAVAS_TERMS_DIR . 'includes/class-label-builder.php';
	require_once HLAVAS_TERMS_DIR . 'includes/class-fluent-sync-service.php';
	require_once HLAVAS_TERMS_DIR . 'includes/class-availability-service.php';
	require_once HLAVAS_TERMS_DIR . 'includes/class-submit-validator.php';

	$validator = new Hlavas_Terms_Submit_Validator();
	$validator->register();
}

/**
 * Main plugin bootstrap.
 *
 * @return void
 */
function hlavas_terms_init(): void {
	hlavas_terms_load_textdomain();
	hlavas_terms_maybe_upgrade_db();
	hlavas_terms_boot_admin();
	hlavas_terms_boot_services();
}
add_action( 'plugins_loaded', 'hlavas_terms_init' );
