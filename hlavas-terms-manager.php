<?php
/**
 * Plugin Name: HLAVAS – Správa termínů kurzů a zkoušek
 * Plugin URI:  https://hlavas.cz
 * Description: Centrální správa termínů kurzů a zkoušek se synchronizací do Fluent Forms pro projekt HLAVAS.cz realizovaný Jihomoravskou radou dětí a mládeže (JRDM).
 * Version:     1.3.4
 * Author:      Michal "Mealtiner" Truhlář
 * Author URI:  https://mealtiner.cz
 * Text Domain: hlavas-terms
 * Domain Path: /languages
 * Requires Plugins: fluentform
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
define( 'HLAVAS_TERMS_VERSION', '1.3.4' );
define( 'HLAVAS_TERMS_FILE', __FILE__ );
define( 'HLAVAS_TERMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'HLAVAS_TERMS_URL', plugin_dir_url( __FILE__ ) );
define( 'HLAVAS_TERMS_TABLE', 'hlavas_terms' );
define( 'HLAVAS_TERMS_TYPES_TABLE', 'hlavas_term_types' );
define( 'HLAVAS_TERMS_LOG_DIR_NAME', 'logs' );
define( 'HLAVAS_TERMS_LOG_FILE_NAME', 'activity.log' );
define( 'HLAVAS_TERMS_OPTION_FORM_ID', 'hlavas_terms_fluent_form_id' );
define( 'HLAVAS_TERMS_OPTION_DEBUG_MODE', 'hlavas_terms_debug_mode' );
define( 'HLAVAS_TERMS_OPTION_SYNC_LOG', 'hlavas_terms_sync_log' );
define( 'HLAVAS_TERMS_OPTION_FORM_SYNC_LOG', 'hlavas_terms_form_sync_log' );
define( 'HLAVAS_TERMS_OPTION_SYNC_VALUE_MODE', 'hlavas_terms_sync_value_mode' );
define( 'HLAVAS_TERMS_OPTION_REPORT_EMAIL', 'hlavas_terms_report_email' );
define( 'HLAVAS_TERMS_OPTION_FIELD_MAP', 'hlavas_terms_field_map' );
define( 'HLAVAS_TERMS_OPTION_PARTICIPANT_TERM_MAP', 'hlavas_terms_participant_term_map' );
define( 'HLAVAS_TERMS_OPTION_PLUGIN_VERSION', 'hlavas_terms_plugin_version' );
define( 'HLAVAS_TERMS_TRANSIENT_DEPENDENCY_NOTICE', 'hlavas_terms_dependency_notice' );
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
 * Returns plugin log directory path.
 *
 * @return string
 */
function hlavas_terms_get_log_dir(): string {
	return trailingslashit( HLAVAS_TERMS_DIR . HLAVAS_TERMS_LOG_DIR_NAME );
}

/**
 * Returns plugin activity log file path.
 *
 * @return string
 */
function hlavas_terms_get_log_file_path(): string {
	return hlavas_terms_get_log_dir() . HLAVAS_TERMS_LOG_FILE_NAME;
}

/**
 * Ensures log storage exists inside the plugin directory.
 *
 * @return bool
 */
function hlavas_terms_ensure_log_storage(): bool {
	$log_dir = hlavas_terms_get_log_dir();

	if ( ! is_dir( $log_dir ) && ! wp_mkdir_p( $log_dir ) ) {
		return false;
	}

	$index_file = $log_dir . 'index.php';

	if ( ! file_exists( $index_file ) ) {
		file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
	}

	return true;
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
 * Returns Fluent Forms dependency state for admin checks and notices.
 *
 * @return array<string, bool|string>
 */
function hlavas_terms_get_fluent_forms_dependency_state(): array {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$free_plugin = 'fluentform/fluentform.php';
	$pro_plugin  = 'fluentformpro/fluentformpro.php';
	$plugins     = get_plugins();

	$free_installed = isset( $plugins[ $free_plugin ] );
	$pro_installed  = isset( $plugins[ $pro_plugin ] );
	$free_active    = $free_installed && is_plugin_active( $free_plugin );
	$pro_active     = $pro_installed && is_plugin_active( $pro_plugin );
	$runtime_ready  = function_exists( 'wpFluent' ) || $free_active || $pro_active;

	return [
		'free_plugin'    => $free_plugin,
		'pro_plugin'     => $pro_plugin,
		'free_installed' => $free_installed,
		'pro_installed'  => $pro_installed,
		'free_active'    => $free_active,
		'pro_active'     => $pro_active,
		'runtime_ready'  => $runtime_ready,
	];
}

/**
 * Whether the Fluent Forms dependency is currently ready.
 *
 * @return bool
 */
function hlavas_terms_is_fluent_forms_ready(): bool {
	$state = hlavas_terms_get_fluent_forms_dependency_state();

	return ! empty( $state['runtime_ready'] );
}

/**
 * Returns install URL for the free Fluent Forms plugin.
 *
 * @return string
 */
function hlavas_terms_get_fluent_forms_install_url(): string {
	return wp_nonce_url(
		self_admin_url( 'update.php?action=install-plugin&plugin=fluentform' ),
		'install-plugin_fluentform'
	);
}

/**
 * Returns activation URL for a plugin file.
 *
 * @param string $plugin_file Plugin basename.
 * @return string
 */
function hlavas_terms_get_plugin_activation_url( string $plugin_file ): string {
	return wp_nonce_url(
		self_admin_url(
			add_query_arg(
				[
					'action' => 'activate',
					'plugin' => $plugin_file,
				],
				'plugins.php'
			)
		),
		'activate-plugin_' . $plugin_file
	);
}

/**
 * Stores a one-time dependency notice to be shown after activation.
 *
 * @param string $reason Notice reason.
 * @return void
 */
function hlavas_terms_store_dependency_notice( string $reason = 'missing' ): void {
	set_transient(
		HLAVAS_TERMS_TRANSIENT_DEPENDENCY_NOTICE,
		[
			'reason' => sanitize_key( $reason ),
			'time'   => time(),
		],
		5 * MINUTE_IN_SECONDS
	);
}

/**
 * Renders admin notice when Fluent Forms is missing or inactive.
 *
 * @return void
 */
function hlavas_terms_render_dependency_notice(): void {
	if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$state        = hlavas_terms_get_fluent_forms_dependency_state();
	$notice_once  = get_transient( HLAVAS_TERMS_TRANSIENT_DEPENDENCY_NOTICE );
	$must_display = ! empty( $notice_once ) || ! hlavas_terms_is_fluent_forms_ready();

	if ( ! $must_display ) {
		return;
	}

	if ( ! empty( $notice_once ) ) {
		delete_transient( HLAVAS_TERMS_TRANSIENT_DEPENDENCY_NOTICE );
	}

	$action_label = '';
	$action_url   = '';

	if ( ! $state['free_installed'] ) {
		$action_label = 'Nainstalovat Fluent Forms';
		$action_url   = hlavas_terms_get_fluent_forms_install_url();
	} elseif ( ! $state['free_active'] ) {
		$action_label = 'Aktivovat Fluent Forms';
		$action_url   = hlavas_terms_get_plugin_activation_url( (string) $state['free_plugin'] );
	}
	?>
	<div class="notice notice-warning">
		<p><strong>HLAVAS – Správa termínů kurzů a zkoušek vyžaduje Fluent Forms.</strong></p>
		<p>
			Pro správný chod pluginu HLAVAS je potřeba mít nainstalovaný a aktivní plugin
			<strong>Fluent Forms</strong>. Pokud používáš <strong>Fluent Forms Pro Add On Pack</strong>,
			měj aktivní i základní Fluent Forms plugin. Po aktivaci pak ještě zkontroluj správné Form ID,
			pole formuláře a návazné mapování v nastavení HLAVAS pluginu.
		</p>
		<?php if ( '' !== $action_url && '' !== $action_label ) : ?>
			<p>
				<a href="<?php echo esc_url( $action_url ); ?>" class="button button-primary">
					<?php echo esc_html( $action_label ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button">
					Přejít na pluginy
				</a>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'admin_notices', 'hlavas_terms_render_dependency_notice' );

/**
 * Returns all Fluent Forms form IDs configured in the plugin:
 * the default form plus any per-qualification-type course/exam forms.
 *
 * Used by the availability service and submit validator so that
 * enrollments and capacity checks cover every configured form,
 * not just the single default one.
 *
 * @return array<int, int> Unique, positive form IDs.
 */
function hlavas_terms_get_all_form_ids(): array {
	global $wpdb;
	/** @var wpdb $wpdb */

	$form_ids    = [ hlavas_terms_get_form_id() ];
	$types_table = hlavas_terms_get_types_table_name();

	// Only query when the table already exists (avoids errors on first activation).
	$table_check = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $types_table ) );

	if ( $table_check ) {
		$typed_ids = $wpdb->get_col(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT course_form_id FROM {$types_table} WHERE course_form_id > 0
			 UNION
			 SELECT exam_form_id   FROM {$types_table} WHERE exam_form_id   > 0"
		);

		foreach ( $typed_ids as $id ) {
			$form_ids[] = (int) $id;
		}
	}

	return array_values(
		array_unique(
			array_filter(
				array_map( 'intval', $form_ids ),
				static fn( int $id ): bool => $id > 0
			)
		)
	);
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
 * Returns default sync value mode.
 *
 * @return string
 */
function hlavas_terms_get_sync_value_mode(): string {
	$value_mode = (string) get_option( HLAVAS_TERMS_OPTION_SYNC_VALUE_MODE, 'term_key' );

	return in_array( $value_mode, [ 'term_key', 'label' ], true ) ? $value_mode : 'term_key';
}

/**
 * Returns recipient e-mail for exported reports.
 *
 * @return string
 */
function hlavas_terms_get_report_email(): string {
	$default = (string) get_bloginfo( 'admin_email' );
	$email   = sanitize_email( (string) get_option( HLAVAS_TERMS_OPTION_REPORT_EMAIL, $default ) );

	return is_email( $email ) ? $email : $default;
}

/**
 * Returns saved manual Fluent Forms field mapping.
 *
 * Structure:
 * [
 *   form_id => [
 *     logical_identifier => ff_field_name_or_label
 *   ]
 * ]
 *
 * @return array<int, array<string, string>>
 */
function hlavas_terms_get_field_map(): array {
	$map = get_option( HLAVAS_TERMS_OPTION_FIELD_MAP, [] );

	if ( ! is_array( $map ) ) {
		return [];
	}

	$output = [];

	foreach ( $map as $form_id => $fields ) {
		$form_id = (int) $form_id;

		if ( $form_id <= 0 || ! is_array( $fields ) ) {
			continue;
		}

		foreach ( $fields as $identifier => $value ) {
			$identifier = sanitize_key( (string) $identifier );
			$value      = trim( sanitize_text_field( (string) $value ) );

			if ( '' === $identifier || '' === $value ) {
				continue;
			}

			$output[ $form_id ][ $identifier ] = $value;
		}
	}

	return $output;
}

/**
 * Returns manual mapping for one specific form.
 *
 * @param int $form_id Fluent Form ID.
 * @return array<string, string>
 */
function hlavas_terms_get_form_field_map( int $form_id ): array {
	$map = hlavas_terms_get_field_map();

	return $map[ $form_id ] ?? [];
}

/**
 * Returns manual participant-term pairing overrides keyed by submission ID.
 *
 * @return array<int, int>
 */
function hlavas_terms_get_participant_term_map(): array {
	$map = get_option( HLAVAS_TERMS_OPTION_PARTICIPANT_TERM_MAP, [] );

	if ( ! is_array( $map ) ) {
		return [];
	}

	$output = [];

	foreach ( $map as $submission_id => $term_id ) {
		$submission_id = (int) $submission_id;
		$term_id       = (int) $term_id;

		if ( $submission_id > 0 && $term_id > 0 ) {
			$output[ $submission_id ] = $term_id;
		}
	}

	return $output;
}

/**
 * Returns manual paired term ID for one participant submission.
 *
 * @param int $submission_id Fluent Forms submission ID.
 * @return int
 */
function hlavas_terms_get_manual_participant_term_id( int $submission_id ): int {
	$map = hlavas_terms_get_participant_term_map();

	return (int) ( $map[ $submission_id ] ?? 0 );
}

/**
 * Saves one manual participant-term pairing override.
 *
 * @param int $submission_id Fluent Forms submission ID.
 * @param int $term_id Plugin term ID.
 * @return void
 */
function hlavas_terms_set_manual_participant_term_id( int $submission_id, int $term_id ): void {
	$map = hlavas_terms_get_participant_term_map();

	if ( $submission_id > 0 && $term_id > 0 ) {
		$map[ $submission_id ] = $term_id;
	}

	update_option( HLAVAS_TERMS_OPTION_PARTICIPANT_TERM_MAP, $map, false );
}

/**
 * Removes one manual participant-term pairing override.
 *
 * @param int $submission_id Fluent Forms submission ID.
 * @return void
 */
function hlavas_terms_clear_manual_participant_term_id( int $submission_id ): void {
	$map = hlavas_terms_get_participant_term_map();

	if ( isset( $map[ $submission_id ] ) ) {
		unset( $map[ $submission_id ] );
		update_option( HLAVAS_TERMS_OPTION_PARTICIPANT_TERM_MAP, $map, false );
	}
}

/**
 * Returns aliases enriched by a manually configured mapping for one form.
 *
 * The manual value is prepended so it is preferred over automatic fallbacks.
 *
 * @param int                $form_id Fluent Form ID.
 * @param string             $identifier Logical HLAVAS field identifier.
 * @param array<int, string> $default_aliases Default aliases.
 * @return array<int, string>
 */
function hlavas_terms_get_manual_field_aliases( int $form_id, string $identifier, array $default_aliases = [] ): array {
	$aliases           = [];
	$manual_mappings   = hlavas_terms_get_form_field_map( $form_id );
	$normalized_key    = sanitize_key( $identifier );
	$manual_value      = trim( (string) ( $manual_mappings[ $normalized_key ] ?? '' ) );

	if ( '' !== $manual_value ) {
		$aliases[] = $manual_value;
	}

	$aliases[] = $identifier;

	foreach ( $default_aliases as $alias ) {
		$alias = trim( (string) $alias );

		if ( '' !== $alias ) {
			$aliases[] = $alias;
		}
	}

	$aliases = array_values(
		array_unique(
			array_filter(
				$aliases,
				static fn( string $alias ): bool => '' !== $alias
			)
		)
	);

	return $aliases;
}

/**
 * Returns installed plugin version stored in options.
 *
 * @return string
 */
function hlavas_terms_get_installed_version(): string {
	return (string) get_option( HLAVAS_TERMS_OPTION_PLUGIN_VERSION, '0' );
}

/**
 * Stores current plugin version as installed version.
 *
 * @param string|null $version Optional explicit version.
 * @return void
 */
function hlavas_terms_set_installed_version( ?string $version = null ): void {
	update_option( HLAVAS_TERMS_OPTION_PLUGIN_VERSION, $version ?: HLAVAS_TERMS_VERSION );
}

/**
 * Returns raw sync log keyed by term ID.
 *
 * @return array<string, string>
 */
function hlavas_terms_get_sync_log(): array {
	$log = get_option( HLAVAS_TERMS_OPTION_SYNC_LOG, [] );

	return is_array( $log ) ? $log : [];
}

/**
 * Returns raw sync log keyed by form ID.
 *
 * @return array<string, string>
 */
function hlavas_terms_get_form_sync_log(): array {
	$log = get_option( HLAVAS_TERMS_OPTION_FORM_SYNC_LOG, [] );

	return is_array( $log ) ? $log : [];
}

/**
 * Returns last sync datetime for one term.
 *
 * @param int $term_id Term ID.
 * @return string
 */
function hlavas_terms_get_term_last_synced_at( int $term_id ): string {
	$log = hlavas_terms_get_sync_log();

	return (string) ( $log[ (string) $term_id ] ?? '' );
}

/**
 * Returns last sync datetime for one Fluent Form.
 *
 * @param int $form_id Form ID.
 * @return string
 */
function hlavas_terms_get_form_last_synced_at( int $form_id ): string {
	$log = hlavas_terms_get_form_sync_log();

	return (string) ( $log[ (string) $form_id ] ?? '' );
}

/**
 * Marks terms as synchronized at provided datetime.
 *
 * @param array<int, int> $term_ids Term IDs.
 * @param string|null     $datetime Optional datetime in mysql format.
 * @return void
 */
function hlavas_terms_mark_terms_synced( array $term_ids, ?string $datetime = null ): void {
	$term_ids = array_values( array_filter( array_map( 'intval', $term_ids ) ) );

	if ( empty( $term_ids ) ) {
		return;
	}

	$log      = hlavas_terms_get_sync_log();
	$datetime = $datetime ?: current_time( 'mysql' );

	foreach ( $term_ids as $term_id ) {
		$log[ (string) $term_id ] = $datetime;
	}

	update_option( HLAVAS_TERMS_OPTION_SYNC_LOG, $log, false );
}

/**
 * Marks forms as synchronized at provided datetime.
 *
 * @param array<int, int> $form_ids Form IDs.
 * @param string|null     $datetime Optional datetime in mysql format.
 * @return void
 */
function hlavas_terms_mark_forms_synced( array $form_ids, ?string $datetime = null ): void {
	$form_ids = array_values( array_filter( array_map( 'intval', $form_ids ) ) );

	if ( empty( $form_ids ) ) {
		return;
	}

	$log      = hlavas_terms_get_form_sync_log();
	$datetime = $datetime ?: current_time( 'mysql' );

	foreach ( $form_ids as $form_id ) {
		$log[ (string) $form_id ] = $datetime;
	}

	update_option( HLAVAS_TERMS_OPTION_FORM_SYNC_LOG, $log, false );
}

/**
 * Writes one activity record into plugin log file.
 *
 * @param string               $action  Action identifier.
 * @param string               $message Human-readable summary.
 * @param array<string, mixed> $context Extra structured context.
 * @param string               $level   Log level.
 * @return void
 */
function hlavas_terms_log_event(
	string $action,
	string $message,
	array $context = [],
	string $level = 'info'
): void {
	if ( ! hlavas_terms_ensure_log_storage() ) {
		return;
	}

	$user      = wp_get_current_user();
	$user_id   = $user instanceof WP_User ? (int) $user->ID : 0;
	$user_name = $user instanceof WP_User && '' !== $user->display_name ? $user->display_name : ( $user->user_login ?? 'system' );
	$user_mail = $user instanceof WP_User ? (string) $user->user_email : '';
	$timestamp = current_time( 'mysql' );
	$context   = array_merge(
		[
			'user_id'    => $user_id,
			'user_name'  => $user_name,
			'user_email' => $user_mail,
		],
		$context
	);

	$line = sprintf(
		"[%s] [%s] action=%s message=\"%s\" context=%s%s",
		$timestamp,
		strtoupper( sanitize_key( $level ) ?: 'INFO' ),
		sanitize_key( $action ),
		str_replace( '"', '\"', wp_strip_all_tags( $message ) ),
		wp_json_encode( $context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
		PHP_EOL
	);

	file_put_contents( hlavas_terms_get_log_file_path(), $line, FILE_APPEND | LOCK_EX );
}

/**
 * Reads the last N log lines from activity log.
 *
 * @param int $limit Number of lines to return.
 * @return array<int, string>
 */
function hlavas_terms_get_log_lines( int $limit = 200 ): array {
	$log_file = hlavas_terms_get_log_file_path();

	if ( ! file_exists( $log_file ) || ! is_readable( $log_file ) ) {
		return [];
	}

	$lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

	if ( ! is_array( $lines ) ) {
		return [];
	}

	$lines = array_values( array_filter( array_map( 'strval', $lines ) ) );

	if ( $limit > 0 && count( $lines ) > $limit ) {
		$lines = array_slice( $lines, -$limit );
	}

	return array_reverse( $lines );
}

/**
 * Clears plugin activity log file.
 *
 * @return bool
 */
function hlavas_terms_clear_log_file(): bool {
	if ( ! hlavas_terms_ensure_log_storage() ) {
		return false;
	}

	return false !== file_put_contents( hlavas_terms_get_log_file_path(), '' );
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
		'sync_value_mode'  => hlavas_terms_get_sync_value_mode(),
		'report_email'     => hlavas_terms_get_report_email(),
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
 * Schedule plugin WP-Cron events if not already scheduled.
 *
 * @return void
 */
function hlavas_terms_schedule_cron(): void {
	if ( ! wp_next_scheduled( 'hlavas_terms_auto_archive' ) ) {
		wp_schedule_event( strtotime( 'tomorrow midnight' ), 'daily', 'hlavas_terms_auto_archive' );
	}
}

/**
 * Unschedule all plugin WP-Cron events.
 *
 * @return void
 */
function hlavas_terms_unschedule_cron(): void {
	$timestamp = wp_next_scheduled( 'hlavas_terms_auto_archive' );

	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'hlavas_terms_auto_archive' );
	}
}

/**
 * Cron callback: archive terms whose end date is in the past.
 *
 * @return void
 */
function hlavas_terms_run_auto_archive(): void {
	require_once HLAVAS_TERMS_DIR . 'includes/class-repository.php';
	$repo    = new Hlavas_Terms_Repository();
	$count   = $repo->archive_past_terms();

	if ( $count > 0 ) {
		hlavas_terms_log_event(
			'auto_archive',
			'Automaticky archivovano ' . $count . ' terminu s propadlym datem.',
			[ 'archived_count' => $count ]
		);
	}
}
add_action( 'hlavas_terms_auto_archive', 'hlavas_terms_run_auto_archive' );

/**
 * Runs on plugin activation.
 *
 * @return void
 */
function hlavas_terms_activate(): void {
	if ( ! hlavas_terms_is_fluent_forms_ready() ) {
		hlavas_terms_store_dependency_notice( 'missing_fluent_forms' );
	}

	require_once HLAVAS_TERMS_DIR . 'includes/class-activator.php';
	Hlavas_Terms_Activator::activate( hlavas_terms_get_installed_version() );
	hlavas_terms_ensure_log_storage();
	hlavas_terms_schedule_cron();
	hlavas_terms_log_event( 'plugin_activated', 'Plugin byl aktivovan.', [], 'info' );
}
register_activation_hook( __FILE__, 'hlavas_terms_activate' );

/**
 * Runs on plugin deactivation.
 *
 * @return void
 */
function hlavas_terms_deactivate(): void {
	delete_transient( 'hlavas_terms_sync_preview' );
	delete_transient( HLAVAS_TERMS_TRANSIENT_DEPENDENCY_NOTICE );
	hlavas_terms_unschedule_cron();
	hlavas_terms_log_event( 'plugin_deactivated', 'Plugin byl deaktivovan.', [], 'warning' );
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
	$db_version      = (string) get_option( 'hlavas_terms_db_version', '0' );
	$plugin_version  = hlavas_terms_get_installed_version();
	$tracked_version = version_compare( $db_version, $plugin_version, '>=' ) ? $db_version : $plugin_version;

	if ( version_compare( $tracked_version, HLAVAS_TERMS_VERSION, '<' ) ) {
		require_once HLAVAS_TERMS_DIR . 'includes/class-activator.php';
		Hlavas_Terms_Activator::activate( $tracked_version );
		hlavas_terms_log_event(
			'plugin_upgraded',
			'Plugin byl aktualizovan a probehly upgrade kroky.',
			[
				'from_version' => $tracked_version,
				'to_version'   => HLAVAS_TERMS_VERSION,
			]
		);
	}
}

/**
 * Runs after WordPress upgrader finishes plugin update.
 *
 * @param WP_Upgrader $upgrader_object Upgrader instance.
 * @param array       $hook_extra Upgrade metadata.
 * @return void
 */
function hlavas_terms_handle_upgrader_complete( $upgrader_object, array $hook_extra ): void {
	unset( $upgrader_object );

	if ( empty( $hook_extra['type'] ) || 'plugin' !== $hook_extra['type'] ) {
		return;
	}

	if ( empty( $hook_extra['action'] ) || 'update' !== $hook_extra['action'] ) {
		return;
	}

	$plugins = is_array( $hook_extra['plugins'] ?? null ) ? $hook_extra['plugins'] : [];
	$self    = plugin_basename( __FILE__ );

	if ( ! in_array( $self, $plugins, true ) ) {
		return;
	}

	require_once HLAVAS_TERMS_DIR . 'includes/class-activator.php';
	Hlavas_Terms_Activator::activate( hlavas_terms_get_installed_version() );
	hlavas_terms_log_event(
		'plugin_upgrade_hook',
		'WordPress upgrader dokoncil aktualizaci pluginu.',
		[
			'plugin' => $self,
			'to_version' => HLAVAS_TERMS_VERSION,
		]
	);
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
	require_once HLAVAS_TERMS_DIR . 'includes/class-shortcodes.php';

	$validator = new Hlavas_Terms_Submit_Validator();
	$validator->register();

	$shortcodes = new Hlavas_Terms_Shortcodes();
	$shortcodes->register();
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
add_action( 'upgrader_process_complete', 'hlavas_terms_handle_upgrader_complete', 10, 2 );
