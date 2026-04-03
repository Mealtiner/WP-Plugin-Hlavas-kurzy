<?php
/**
 * Repository for qualification/course types.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hlavas_Terms_Qualification_Type_Repository {

	/**
	 * Full DB table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Terms DB table name.
	 *
	 * @var string
	 */
	private string $terms_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->table       = hlavas_terms_get_types_table_name();
		$this->terms_table = hlavas_terms_get_table_name();
	}

	/**
	 * Get all qualification types.
	 *
	 * @return array<int, object>
	 */
	public function get_all(): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$sql = "SELECT t.*,
			(
				SELECT COUNT(*)
				FROM {$this->terms_table} term
				WHERE term.qualification_type_id = t.id
			) AS linked_terms
			FROM {$this->table} t
			ORDER BY t.sort_order ASC, t.name ASC";

		$results = $wpdb->get_results( $sql );

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Get types available for a specific term kind.
	 *
	 * @param string $term_type kurz|zkouska
	 * @return array<int, object>
	 */
	public function get_for_term_type( string $term_type ): array {
		$all = $this->get_all();

		return array_values(
			array_filter(
				$all,
				static function ( object $type ) use ( $term_type ): bool {
					if ( 'kurz' === $term_type ) {
						return ! empty( $type->has_courses );
					}

					return ! empty( $type->has_exams );
				}
			)
		);
	}

	/**
	 * Find one type by ID.
	 *
	 * @param int $id Type ID.
	 * @return object|null
	 */
	public function find( int $id ): ?object {
		global $wpdb;
		/** @var wpdb $wpdb */

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d",
				$id
			)
		);

		return $result instanceof \stdClass ? $result : null;
	}

	/**
	 * Insert type.
	 *
	 * @param array<string, mixed> $data Type data.
	 * @return int|false
	 */
	public function insert( array $data ): int|false {
		global $wpdb;
		/** @var wpdb $wpdb */

		$data['type_key']   = $this->build_unique_key( $data );
		$data['created_at'] = current_time( 'mysql' );
		$data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->insert( $this->table, $data, $this->get_formats( $data ) );

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update type.
	 *
	 * @param int                 $id Type ID.
	 * @param array<string, mixed> $data Type data.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;
		/** @var wpdb $wpdb */

		$data['type_key']   = $this->build_unique_key( $data, $id );
		$data['updated_at'] = current_time( 'mysql' );

		return false !== $wpdb->update(
			$this->table,
			$data,
			[ 'id' => $id ],
			$this->get_formats( $data ),
			[ '%d' ]
		);
	}

	/**
	 * Delete type and unlink assigned terms.
	 *
	 * @param int $id Type ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		/** @var wpdb $wpdb */

		$wpdb->update(
			$this->terms_table,
			[
				'qualification_type_id' => null,
				'updated_at'            => current_time( 'mysql' ),
			],
			[
				'qualification_type_id' => $id,
			],
			[
				'%d',
				'%s',
			],
			[
				'%d',
			]
		);

		return false !== $wpdb->delete(
			$this->table,
			[ 'id' => $id ],
			[ '%d' ]
		);
	}

	/**
	 * Build unique internal key.
	 *
	 * @param array<string, mixed> $data Type data.
	 * @param int|null             $exclude_id Optional excluded type ID.
	 * @return string
	 */
	private function build_unique_key( array $data, ?int $exclude_id = null ): string {
		$raw_key = ! empty( $data['accreditation_number'] ) ? (string) $data['accreditation_number'] : (string) ( $data['name'] ?? '' );
		$key     = sanitize_title( $raw_key );

		if ( '' === $key ) {
			$key = 'qualification-type';
		}

		$base_key = $key;
		$suffix   = 2;

		while ( $this->type_key_exists( $key, $exclude_id ) ) {
			$key = $base_key . '-' . $suffix;
			$suffix++;
		}

		return $key;
	}

	/**
	 * Check whether internal key already exists.
	 *
	 * @param string   $type_key Internal key.
	 * @param int|null $exclude_id Optional excluded type ID.
	 * @return bool
	 */
	private function type_key_exists( string $type_key, ?int $exclude_id = null ): bool {
		global $wpdb;
		/** @var wpdb $wpdb */

		if ( null !== $exclude_id ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE type_key = %s AND id != %d",
					$type_key,
					$exclude_id
				)
			);
		} else {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE type_key = %s",
					$type_key
				)
			);
		}

		return (int) $count > 0;
	}

	/**
	 * Get wpdb format strings.
	 *
	 * @param array<string, mixed> $data Row data.
	 * @return array<int, string>
	 */
	private function get_formats( array $data ): array {
		$int_columns = [
			'is_accredited',
			'has_courses',
			'has_exams',
			'course_form_id',
			'exam_form_id',
			'sort_order',
		];

		$formats = [];

		foreach ( array_keys( $data ) as $column ) {
			$formats[] = in_array( $column, $int_columns, true ) ? '%d' : '%s';
		}

		return $formats;
	}
}
