<?php
/**
 * Plugin Name: HLAVAS – Správa termínů kurzů a zkoušek
 * Plugin URI:  https://hlavas.cz
 * Description: Centrální správa termínů kurzů a zkoušek se synchronizací do Fluent Forms pro projekt HLAVAS.cz realizovaný Jihomoravskou radou dětí a mládeže (JRDM).
 * Version:     1.0.1
 * Author:      Michal "Mealtiner" Truhlář pro JRDM / HLAVAS.cz
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

// Plugin constants
define( 'HLAVAS_TERMS_VERSION', '1.0.0' );
define( 'HLAVAS_TERMS_FILE', __FILE__ );
define( 'HLAVAS_TERMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'HLAVAS_TERMS_URL', plugin_dir_url( __FILE__ ) );
define( 'HLAVAS_TERMS_TABLE', 'hlavas_terms' );
define( 'HLAVAS_TERMS_FLUENT_FORM_ID', 3 );

/**
 * Autoload plugin classes.
 */
spl_autoload_register( function ( string $class ) {
    $prefix    = 'Hlavas_Terms_';
    $base_dir  = HLAVAS_TERMS_DIR . 'includes/';

    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $relative = substr( $class, strlen( $prefix ) );
    $file     = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
});

/**
 * Plugin activation.
 */
function hlavas_terms_activate(): void {
    require_once HLAVAS_TERMS_DIR . 'includes/class-activator.php';
    Hlavas_Terms_Activator::activate();
}
register_activation_hook( __FILE__, 'hlavas_terms_activate' );

/**
 * Plugin deactivation.
 */
function hlavas_terms_deactivate(): void {
    // Clean up transients if needed
    delete_transient( 'hlavas_terms_sync_preview' );
}
register_deactivation_hook( __FILE__, 'hlavas_terms_deactivate' );

/**
 * Initialize plugin after plugins are loaded (so Fluent Forms is available).
 */
add_action( 'plugins_loaded', function () {
    // Load text domain
    load_plugin_textdomain( 'hlavas-terms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Check DB version and upgrade if needed
    $db_version = get_option( 'hlavas_terms_db_version', '0' );
    if ( version_compare( $db_version, HLAVAS_TERMS_VERSION, '<' ) ) {
        require_once HLAVAS_TERMS_DIR . 'includes/class-activator.php';
        Hlavas_Terms_Activator::activate();
    }

    // Boot core classes
    require_once HLAVAS_TERMS_DIR . 'includes/class-repository.php';
    require_once HLAVAS_TERMS_DIR . 'includes/class-label-builder.php';
    require_once HLAVAS_TERMS_DIR . 'includes/class-fluent-sync-service.php';
    require_once HLAVAS_TERMS_DIR . 'includes/class-availability-service.php';
    require_once HLAVAS_TERMS_DIR . 'includes/class-submit-validator.php';

    // Boot admin
    if ( is_admin() ) {
        require_once HLAVAS_TERMS_DIR . 'admin/class-admin.php';
        new Hlavas_Terms_Admin();
    }

    // Register submission validation hook (frontend + admin AJAX)
    $validator = new Hlavas_Terms_Submit_Validator();
    $validator->register();
});
