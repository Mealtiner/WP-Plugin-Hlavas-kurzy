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
	 * @return void
	 */
	public static function activate(): void {
		self::create_table();
		self::maybe_seed();
		self::update_db_version();
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
	 * Create or update the terms table.
	 *
	 * @return void
	 */
	private static function create_table(): void {
		global $wpdb;
		/** @var wpdb $wpdb */

		$table   = self::get_table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			term_type varchar(20) NOT NULL DEFAULT 'kurz',
			term_key varchar(100) NOT NULL,
			title varchar(255) NOT NULL DEFAULT '',
			label varchar(255) NOT NULL DEFAULT '',
			date_start date DEFAULT NULL,
			date_end date DEFAULT NULL,
			capacity int unsigned NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			is_archived tinyint(1) NOT NULL DEFAULT 0,
			sort_order int NOT NULL DEFAULT 0,
			notes text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY term_key (term_key),
			KEY idx_type_active (term_type, is_active, is_archived),
			KEY idx_date_start (date_start),
			KEY idx_sort_order (sort_order)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Seed default data only if the table is empty.
	 *
	 * @return void
	 */
	private static function maybe_seed(): void {
		global $wpdb;
		/** @var wpdb $wpdb */

		$table = self::get_table_name();
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $count > 0 ) {
			return;
		}

		$now  = current_time( 'mysql' );
		$seed = self::get_seed_data();

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
					'%s', // title
					'%s', // label
					'%s', // date_start
					'%s', // date_end
					'%d', // capacity
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
	 * Default seed data definitions.
	 *
	 * @return array<int, array<string, int|string>>
	 */
	private static function get_seed_data(): array {
		return [
			// Kurzy.
			[
				'term_type'   => 'kurz',
				'term_key'    => 'kurz_2026_03_06_08',
				'title'       => 'Kurz březen 2026',
				'label'       => 'kurz: 6. - 8. března 2026',
				'date_start'  => '2026-03-06',
				'date_end'    => '2026-03-08',
				'capacity'    => 16,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'kurz',
				'term_key'    => 'kurz_2026_04_17_19',
				'title'       => 'Kurz duben 2026',
				'label'       => 'kurz: 17. - 19. dubna 2026',
				'date_start'  => '2026-04-17',
				'date_end'    => '2026-04-19',
				'capacity'    => 16,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'kurz',
				'term_key'    => 'kurz_2026_05_15_17',
				'title'       => 'Kurz květen 2026',
				'label'       => 'kurz: 15. - 17. května 2026',
				'date_start'  => '2026-05-15',
				'date_end'    => '2026-05-17',
				'capacity'    => 16,
				'is_active'   => 1,
				'is_archived' => 0,
			],

			// Zkoušky.
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_03_21',
				'title'       => 'Zkouška 21. března 2026',
				'label'       => 'zkouška: 21. března 2026',
				'date_start'  => '2026-03-21',
				'date_end'    => '2026-03-21',
				'capacity'    => 4,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_03_22',
				'title'       => 'Zkouška 22. března 2026',
				'label'       => 'zkouška: 22. března 2026',
				'date_start'  => '2026-03-22',
				'date_end'    => '2026-03-22',
				'capacity'    => 4,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_04_25',
				'title'       => 'Zkouška 25. dubna 2026',
				'label'       => 'zkouška: 25. dubna 2026',
				'date_start'  => '2026-04-25',
				'date_end'    => '2026-04-25',
				'capacity'    => 4,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_04_26',
				'title'       => 'Zkouška 26. dubna 2026',
				'label'       => 'zkouška: 26. dubna 2026',
				'date_start'  => '2026-04-26',
				'date_end'    => '2026-04-26',
				'capacity'    => 4,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_05_30',
				'title'       => 'Zkouška 30. května 2026',
				'label'       => 'zkouška: 30. května 2026',
				'date_start'  => '2026-05-30',
				'date_end'    => '2026-05-30',
				'capacity'    => 4,
				'is_active'   => 1,
				'is_archived' => 0,
			],
			[
				'term_type'   => 'zkouska',
				'term_key'    => 'zkouska_2026_05_31',
				'title'       => 'Zkouška 31. května 2026',
				'label'       => 'zkouška: 31. května 2026',
				'date_start'  => '2026-05-31',
				'date_end'    => '2026-05-31',
				'capacity'    => 4,
				'is_active'   => 1,
				'is_archived' => 0,
			],
		];
	}
}
