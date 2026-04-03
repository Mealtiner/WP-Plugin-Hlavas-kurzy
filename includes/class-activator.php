<?php
/**
 * Plugin activation: DB table creation, upgrades and seed data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hlavas_Terms_Activator {

	/**
	 * Run activation tasks.
	 *
	 * @param string|null $from_version Previously installed version.
	 * @return void
	 */
	public static function activate( ?string $from_version = null ): void {
		$from_version = is_string( $from_version ) && '' !== $from_version ? $from_version : '0';

		self::create_tables();
		self::maybe_run_data_migrations( $from_version );
		self::maybe_seed_qualification_types();
		self::maybe_seed_terms();
		self::update_db_version();
		hlavas_terms_set_installed_version( HLAVAS_TERMS_VERSION );
		hlavas_terms_ensure_log_storage();
	}

	/**
	 * Update stored DB/plugin version.
	 *
	 * @return void
	 */
	private static function update_db_version(): void {
		update_option( 'hlavas_terms_db_version', HLAVAS_TERMS_VERSION );
	}

	/**
	 * Get the full table name.
	 *
	 * @return string
	 */
	private static function get_table_name(): string {
		return hlavas_terms_get_table_name();
	}

	/**
	 * Get qualification types table name.
	 *
	 * @return string
	 */
	private static function get_types_table_name(): string {
		return hlavas_terms_get_types_table_name();
	}

	/**
	 * Create or update plugin tables.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;
		/** @var wpdb $wpdb */

		$terms_table = self::get_table_name();
		$types_table = self::get_types_table_name();
		$charset     = $wpdb->get_charset_collate();

		$sql_terms = "CREATE TABLE {$terms_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			term_type varchar(20) NOT NULL DEFAULT 'kurz',
			term_key varchar(100) NOT NULL,
			qualification_type_id bigint(20) unsigned DEFAULT NULL,
			title varchar(255) NOT NULL DEFAULT '',
			label varchar(255) NOT NULL DEFAULT '',
			date_start date DEFAULT NULL,
			date_end date DEFAULT NULL,
			enrollment_deadline date DEFAULT NULL,
			capacity int unsigned NOT NULL DEFAULT 0,
			is_visible tinyint(1) NOT NULL DEFAULT 1,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			is_archived tinyint(1) NOT NULL DEFAULT 0,
			sort_order int NOT NULL DEFAULT 0,
			notes text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY term_key (term_key),
			KEY idx_qualification_type (qualification_type_id),
			KEY idx_type_active (term_type, is_active, is_archived),
			KEY idx_visible_active (is_visible, is_active, is_archived),
			KEY idx_date_start (date_start),
			KEY idx_enrollment_deadline (enrollment_deadline),
			KEY idx_sort_order (sort_order)
		) {$charset};";

		$sql_types = "CREATE TABLE {$types_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type_key varchar(120) NOT NULL,
			name varchar(255) NOT NULL,
			description longtext DEFAULT NULL,
			notes text DEFAULT NULL,
			is_accredited tinyint(1) NOT NULL DEFAULT 0,
			accreditation_number varchar(100) NOT NULL DEFAULT '',
			has_courses tinyint(1) NOT NULL DEFAULT 1,
			has_exams tinyint(1) NOT NULL DEFAULT 1,
			course_form_id bigint(20) unsigned DEFAULT NULL,
			exam_form_id bigint(20) unsigned DEFAULT NULL,
			sort_order int NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY type_key (type_key),
			KEY idx_sort_order (sort_order),
			KEY idx_accreditation_number (accreditation_number)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_terms );
		dbDelta( $sql_types );
	}

	/**
	 * Seed qualification types only if the table is empty.
	 *
	 * @return void
	 */
	private static function maybe_seed_qualification_types(): void {
		global $wpdb;
		/** @var wpdb $wpdb */

		$table = self::get_types_table_name();
		$now  = current_time( 'mysql' );
		$seed = self::get_seed_qualification_types();

		foreach ( $seed as $index => $row ) {
			$existing_id = self::find_existing_seed_type_id( $row );

			if ( $existing_id > 0 ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array_merge(
					$row,
					[
						'sort_order' => ( $index + 1 ) * 10,
						'created_at' => $now,
						'updated_at' => $now,
					]
				),
				[
					'%s', // type_key
					'%s', // name
					'%s', // description
					'%s', // notes
					'%d', // is_accredited
					'%s', // accreditation_number
					'%d', // has_courses
					'%d', // has_exams
					'%d', // course_form_id
					'%d', // exam_form_id
					'%d', // sort_order
					'%s', // created_at
					'%s', // updated_at
				]
			);
		}
	}

	/**
	 * Find existing seeded qualification type by key or accreditation number.
	 *
	 * @param array<string, int|string> $row Seed row.
	 * @return int
	 */
	private static function find_existing_seed_type_id( array $row ): int {
		global $wpdb;
		/** @var wpdb $wpdb */

		$table                = self::get_types_table_name();
		$type_key             = (string) ( $row['type_key'] ?? '' );
		$accreditation_number = (string) ( $row['accreditation_number'] ?? '' );

		if ( '' !== $type_key ) {
			$found = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE type_key = %s LIMIT 1",
					$type_key
				)
			);

			if ( $found > 0 ) {
				return $found;
			}
		}

		if ( '' !== $accreditation_number ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE accreditation_number = %s LIMIT 1",
					$accreditation_number
				)
			);
		}

		return 0;
	}

	/**
	 * Seed default term data only if the terms table is empty.
	 *
	 * @return void
	 */
	private static function maybe_seed_terms(): void {
		global $wpdb;
		/** @var wpdb $wpdb */

		$table = self::get_table_name();
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $count > 0 ) {
			return;
		}

		$now  = current_time( 'mysql' );
		$seed = self::get_seed_terms();

		foreach ( $seed as $index => $row ) {
			$wpdb->insert(
				$table,
				array_merge(
					$row,
					[
						'sort_order' => ( $index + 1 ) * 10,
						'created_at' => $now,
						'updated_at' => $now,
					]
				),
				[
					'%s', // term_type
					'%s', // term_key
					'%d', // qualification_type_id
					'%s', // title
					'%s', // label
					'%s', // date_start
					'%s', // date_end
					'%s', // enrollment_deadline
					'%d', // capacity
					'%d', // is_visible
					'%d', // is_active
					'%d', // is_archived
					'%d', // sort_order
					'%s', // created_at
					'%s', // updated_at
				]
			);
		}
	}

	/**
	 * Runs versioned data migrations without overwriting user-managed content.
	 *
	 * @param string $from_version Installed version before update.
	 * @return void
	 */
	private static function maybe_run_data_migrations( string $from_version ): void {
		if ( version_compare( $from_version, '1.1.2', '<=' ) ) {
			self::normalize_empty_qualification_ids();
		}
	}

	/**
	 * Normalize empty qualification references to 0 for compatibility.
	 *
	 * @return void
	 */
	private static function normalize_empty_qualification_ids(): void {
		global $wpdb;
		/** @var wpdb $wpdb */

		$table = self::get_table_name();

		$wpdb->query(
			"UPDATE {$table}
			SET qualification_type_id = 0
			WHERE qualification_type_id IS NULL"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Default qualification types.
	 *
	 * @return array<int, array<string, int|string>>
	 */
	private static function get_seed_qualification_types(): array {
		return [
			[
				'type_key'             => '75-007-m',
				'name'                 => 'Vedoucí volnočasových aktivit dětí a mládeže',
				'description'          => 'Základní kvalifikace pro ty, kteří přímo vedou aktivity v oddílech nebo na táborech.',
				'notes'                => '',
				'is_accredited'        => 1,
				'accreditation_number' => '75-007-M',
				'has_courses'          => 1,
				'has_exams'            => 1,
				'course_form_id'       => 0,
				'exam_form_id'         => 0,
			],
			[
				'type_key'             => '75-008-n',
				'name'                 => 'Hlavní vedoucí zotavovací akce dětí a mládeže',
				'description'          => 'Klíčová kvalifikace pro zodpovědné vedení celých táborů a zotavovacích akcí.',
				'notes'                => '',
				'is_accredited'        => 1,
				'accreditation_number' => '75-008-N',
				'has_courses'          => 1,
				'has_exams'            => 1,
				'course_form_id'       => 0,
				'exam_form_id'         => 0,
			],
			[
				'type_key'             => '75-009-n',
				'name'                 => 'Samostatný vedoucí volnočasových aktivit dětí a mládeže',
				'description'          => 'Pokročilá úroveň pro koncepční přípravu a realizaci výchovně vzdělávacích programů.',
				'notes'                => '',
				'is_accredited'        => 1,
				'accreditation_number' => '75-009-N',
				'has_courses'          => 1,
				'has_exams'            => 1,
				'course_form_id'       => 0,
				'exam_form_id'         => 0,
			],
			[
				'type_key'             => '75-001-t',
				'name'                 => 'Lektor dalšího vzdělávání',
				'description'          => 'Kvalifikace určená pro ty, kteří chtějí své zkušenosti předávat dál a vzdělávat dospělé v oblasti volného času.',
				'notes'                => '',
				'is_accredited'        => 1,
				'accreditation_number' => '75-001-T',
				'has_courses'          => 1,
				'has_exams'            => 1,
				'course_form_id'       => 0,
				'exam_form_id'         => 0,
			],
		];
	}

	/**
	 * Default term data definitions.
	 *
	 * @return array<int, array<string, int|string|null>>
	 */
	private static function get_seed_terms(): array {
		return [
			// Kurzy.
			[
				'term_type'   => 'kurz',
				'term_key'    => 'kurz_2026_03_06_08',
				'qualification_type_id' => null,
				'title'       => 'Kurz březen 2026',
				'label'       => 'kurz: 6. - 8. března 2026',
				'date_start'  => '2026-03-06',
				'date_end'    => '2026-03-08',
				'enrollment_deadline' => null,
				'capacity'    => 16,
				'is_visible'  => 1,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'kurz',
				'term_key'    => 'kurz_2026_04_17_19',
				'qualification_type_id' => null,
				'title'       => 'Kurz duben 2026',
				'label'       => 'kurz: 17. - 19. dubna 2026',
				'date_start'  => '2026-04-17',
				'date_end'    => '2026-04-19',
				'enrollment_deadline' => null,
				'capacity'    => 16,
				'is_visible'  => 1,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'kurz',
				'term_key'    => 'kurz_2026_05_15_17',
				'qualification_type_id' => null,
				'title'       => 'Kurz květen 2026',
				'label'       => 'kurz: 15. - 17. května 2026',
				'date_start'  => '2026-05-15',
				'date_end'    => '2026-05-17',
				'enrollment_deadline' => null,
				'capacity'    => 16,
				'is_visible'  => 1,
				'is_active'   => 1,
				'is_archived' => 0,
			],

			// Zkoušky.
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_03_21',
				'qualification_type_id' => null,
				'title'       => 'Zkouška 21. března 2026',
				'label'       => 'zkouška: 21. března 2026',
				'date_start'  => '2026-03-21',
				'date_end'    => '2026-03-21',
				'enrollment_deadline' => null,
				'capacity'    => 4,
				'is_visible'  => 1,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_03_22',
				'qualification_type_id' => null,
				'title'       => 'Zkouška 22. března 2026',
				'label'       => 'zkouška: 22. března 2026',
				'date_start'  => '2026-03-22',
				'date_end'    => '2026-03-22',
				'enrollment_deadline' => null,
				'capacity'    => 4,
				'is_visible'  => 1,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_04_25',
				'qualification_type_id' => null,
				'title'       => 'Zkouška 25. dubna 2026',
				'label'       => 'zkouška: 25. dubna 2026',
				'date_start'  => '2026-04-25',
				'date_end'    => '2026-04-25',
				'enrollment_deadline' => null,
				'capacity'    => 4,
				'is_visible'  => 1,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_04_26',
				'qualification_type_id' => null,
				'title'       => 'Zkouška 26. dubna 2026',
				'label'       => 'zkouška: 26. dubna 2026',
				'date_start'  => '2026-04-26',
				'date_end'    => '2026-04-26',
				'enrollment_deadline' => null,
				'capacity'    => 4,
				'is_visible'  => 1,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_05_30',
				'qualification_type_id' => null,
				'title'       => 'Zkouška 30. května 2026',
				'label'       => 'zkouška: 30. května 2026',
				'date_start'  => '2026-05-30',
				'date_end'    => '2026-05-30',
				'enrollment_deadline' => null,
				'capacity'    => 4,
				'is_visible'  => 1,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_05_31',
				'qualification_type_id' => null,
				'title'       => 'Zkouška 31. května 2026',
				'label'       => 'zkouška: 31. května 2026',
				'date_start'  => '2026-05-31',
				'date_end'    => '2026-05-31',
				'enrollment_deadline' => null,
				'capacity'    => 4,
				'is_visible'  => 1,
				'is_active'   => 1,
				'is_archived' => 0,
			],
		];
	}
}
