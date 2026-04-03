<?php
/**
 * Data repository for terms CRUD.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hlavas_Terms_Repository {

	/**
	 * Full DB table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Repository constructor.
	 */
	public function __construct() {
		$this->table = hlavas_terms_get_table_name();
	}

	/* ---------------------------------------------------------------
	 * READ
	 * ------------------------------------------------------------- */

	/**
	 * Get a single term by ID.
	 *
	 * @param int $id Term ID.
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
	 * Get a single term by term_key.
	 *
	 * @param string $term_key Unique term key.
	 * @return object|null
	 */
	public function find_by_key( string $term_key ): ?object {
		global $wpdb;
		/** @var wpdb $wpdb */

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE term_key = %s",
				$term_key
			)
		);

		return $result instanceof \stdClass ? $result : null;
	}

	/**
	 * Get all terms with optional filters.
	 *
	 * @param array $args {
	 *   @type string|null $term_type   'kurz' | 'zkouska' | null
	 *   @type bool|null   $is_active   true/false/null
	 *   @type bool|null   $is_archived true/false/null
	 *   @type bool        $future_only If true, only upcoming terms
	 *   @type string      $orderby     Column to sort by
	 *   @type string      $order       ASC|DESC
	 * }
	 * @return array<int, object>
	 */
	public function get_all( array $args = [] ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$defaults = [
			'term_type'   => null,
			'is_active'   => null,
			'is_archived' => null,
			'future_only' => false,
			'orderby'     => 'sort_order',
			'order'       => 'ASC',
		];

		$args = wp_parse_args( $args, $defaults );

		$where  = [];
		$values = [];

		if ( null !== $args['term_type'] ) {
			$where[]  = 'term_type = %s';
			$values[] = $args['term_type'];
		}

		if ( null !== $args['is_active'] ) {
			$where[]  = 'is_active = %d';
			$values[] = (int) $args['is_active'];
		}

		if ( null !== $args['is_archived'] ) {
			$where[]  = 'is_archived = %d';
			$values[] = (int) $args['is_archived'];
		}

		if ( ! empty( $args['future_only'] ) ) {
			$today    = current_time( 'Y-m-d' );
			$where[]  = 'COALESCE(date_end, date_start) >= %s';
			$values[] = $today;
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$allowed_orderby = [
			'id',
			'term_type',
			'label',
			'date_start',
			'date_end',
			'capacity',
			'is_active',
			'is_archived',
			'sort_order',
			'created_at',
			'updated_at',
		];

		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'sort_order';
		$order   = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

		$sql = "SELECT * FROM {$this->table} {$where_sql} ORDER BY {$orderby} {$order}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, ...$values );
		}

		$results = $wpdb->get_results( $sql );

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Get terms eligible for Fluent Forms sync.
	 *
	 * @param string $term_type Term type.
	 * @return array<int, object>
	 */
	public function get_syncable( string $term_type ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$today      = current_time( 'Y-m-d' );
		$date_field = 'kurz' === $term_type ? 'date_end' : 'date_start';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE term_type = %s
					AND is_active = 1
					AND is_archived = 0
					AND {$date_field} >= %s
				ORDER BY sort_order ASC, date_start ASC",
				$term_type,
				$today
			)
		);

		return is_array( $results ) ? $results : [];
	}

	/* ---------------------------------------------------------------
	 * CREATE / UPDATE
	 * ------------------------------------------------------------- */

	/**
	 * Insert a new term.
	 *
	 * @param array $data Term data.
	 * @return int|false Inserted ID or false on failure.
	 */
	public function insert( array $data ): int|false {
		global $wpdb;
		/** @var wpdb $wpdb */

		$data['created_at'] = current_time( 'mysql' );
		$data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->insert(
			$this->table,
			$data,
			$this->get_formats( $data )
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update a term by ID.
	 *
	 * @param int   $id   Term ID.
	 * @param array $data Data to update.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;
		/** @var wpdb $wpdb */

		$data['updated_at'] = current_time( 'mysql' );

		return false !== $wpdb->update(
			$this->table,
			$data,
			[ 'id' => $id ],
			$this->get_formats( $data ),
			[ '%d' ]
		);
	}

	/* ---------------------------------------------------------------
	 * DELETE / STATUS
	 * ------------------------------------------------------------- */

	/**
	 * Delete a term by ID.
	 *
	 * @param int $id Term ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		/** @var wpdb $wpdb */

		return false !== $wpdb->delete(
			$this->table,
			[ 'id' => $id ],
			[ '%d' ]
		);
	}

	/**
	 * Bulk activate terms.
	 *
	 * @param array<int> $ids Term IDs.
	 * @return int
	 */
	public function bulk_activate( array $ids ): int {
		return $this->bulk_update_field( $ids, 'is_active', 1 );
	}

	/**
	 * Bulk deactivate terms.
	 *
	 * @param array<int> $ids Term IDs.
	 * @return int
	 */
	public function bulk_deactivate( array $ids ): int {
		return $this->bulk_update_field( $ids, 'is_active', 0 );
	}

	/**
	 * Bulk archive terms.
	 *
	 * @param array<int> $ids Term IDs.
	 * @return int
	 */
	public function bulk_archive( array $ids ): int {
		return $this->bulk_update_field( $ids, 'is_archived', 1 );
	}

	/**
	 * Bulk delete terms.
	 *
	 * @param array<int> $ids Term IDs.
	 * @return int
	 */
	public function bulk_delete( array $ids ): int {
		global $wpdb;
		/** @var wpdb $wpdb */

		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE id IN ({$placeholders})",
				...$ids
			)
		);
	}

	/* ---------------------------------------------------------------
	 * HELPERS
	 * ------------------------------------------------------------- */

	/**
	 * Check if a term_key already exists.
	 *
	 * @param string   $term_key   Term key.
	 * @param int|null $exclude_id Optional ID to exclude.
	 * @return bool
	 */
	public function key_exists( string $term_key, ?int $exclude_id = null ): bool {
		global $wpdb;
		/** @var wpdb $wpdb */

		if ( null !== $exclude_id ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE term_key = %s AND id != %d",
					$term_key,
					$exclude_id
				)
			);
		} else {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE term_key = %s",
					$term_key
				)
			);
		}

		return (int) $count > 0;
	}

	/**
	 * Get wpdb format strings for given data array.
	 *
	 * @param array $data Row data.
	 * @return array<int, string>
	 */
	private function get_formats( array $data ): array {
		$int_columns = [
			'capacity',
			'is_active',
			'is_archived',
			'sort_order',
		];

		$formats = [];

		foreach ( array_keys( $data ) as $column ) {
			$formats[] = in_array( $column, $int_columns, true ) ? '%d' : '%s';
		}

		return $formats;
	}

	/**
	 * Bulk update a single field for given IDs.
	 *
	 * @param array<int> $ids   Term IDs.
	 * @param string     $field DB field.
	 * @param int        $value New value.
	 * @return int
	 */
	private function bulk_update_field( array $ids, string $field, int $value ): int {
		global $wpdb;
		/** @var wpdb $wpdb */

		$allowed_fields = [ 'is_active', 'is_archived' ];

		if ( ! in_array( $field, $allowed_fields, true ) ) {
			return 0;
		}

		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$now          = current_time( 'mysql' );

		return (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table}
				SET {$field} = %d, updated_at = %s
				WHERE id IN ({$placeholders})",
				$value,
				$now,
				...$ids
			)
		);
	}
}
