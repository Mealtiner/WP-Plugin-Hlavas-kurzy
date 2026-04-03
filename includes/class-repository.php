<?php
/**
 * Data repository for terms CRUD.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hlavas_Terms_Repository {

    /** @var string */
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . HLAVAS_TERMS_TABLE;
    }

    /* ---------------------------------------------------------------
     * READ
     * ------------------------------------------------------------- */

    /**
     * Get a single term by ID.
     */
    public function find( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
        );
    }

    /**
     * Get a single term by term_key.
     */
    public function find_by_key( string $term_key ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE term_key = %s", $term_key )
        );
    }

    /**
     * Get all terms with optional filters.
     *
     * @param array $args {
     *   @type string|null $term_type   'kurz' | 'zkouska' | null (all)
     *   @type bool|null   $is_active   true/false/null
     *   @type bool|null   $is_archived true/false/null
     *   @type bool|null   $future_only If true, only terms with date >= today
     *   @type string      $orderby     Column to sort
     *   @type string      $order       ASC|DESC
     * }
     */
    public function get_all( array $args = [] ): array {
        global $wpdb;

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

        if ( $args['term_type'] !== null ) {
            $where[]  = 'term_type = %s';
            $values[] = $args['term_type'];
        }

        if ( $args['is_active'] !== null ) {
            $where[]  = 'is_active = %d';
            $values[] = (int) $args['is_active'];
        }

        if ( $args['is_archived'] !== null ) {
            $where[]  = 'is_archived = %d';
            $values[] = (int) $args['is_archived'];
        }

        if ( $args['future_only'] ) {
            $today = current_time( 'Y-m-d' );
            // For kurz: date_end >= today, for zkouska: date_start >= today
            // We use COALESCE so if date_end is null, we check date_start
            $where[]  = 'COALESCE(date_end, date_start) >= %s';
            $values[] = $today;
        }

        $where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

        // Sanitize orderby
        $allowed_cols = [ 'id', 'term_type', 'label', 'date_start', 'date_end', 'capacity', 'is_active', 'is_archived', 'sort_order', 'created_at' ];
        $orderby = in_array( $args['orderby'], $allowed_cols, true ) ? $args['orderby'] : 'sort_order';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM {$this->table} {$where_sql} ORDER BY {$orderby} {$order}";

        if ( ! empty( $values ) ) {
            $sql = $wpdb->prepare( $sql, ...$values );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get terms eligible for Fluent Forms sync.
     */
    public function get_syncable( string $term_type ): array {
        $today = current_time( 'Y-m-d' );

        if ( $term_type === 'kurz' ) {
            $date_field = 'date_end';
        } else {
            $date_field = 'date_start';
        }

        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table}
             WHERE term_type = %s
               AND is_active = 1
               AND is_archived = 0
               AND {$date_field} >= %s
             ORDER BY sort_order ASC, date_start ASC",
            $term_type,
            $today
        ) );
    }

    /* ---------------------------------------------------------------
     * CREATE / UPDATE
     * ------------------------------------------------------------- */

    /**
     * Insert a new term.
     *
     * @return int|false Inserted ID or false on failure.
     */
    public function insert( array $data ) {
        global $wpdb;

        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );

        $result = $wpdb->insert( $this->table, $data, $this->get_formats( $data ) );
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a term by ID.
     */
    public function update( int $id, array $data ): bool {
        global $wpdb;

        $data['updated_at'] = current_time( 'mysql' );

        return (bool) $wpdb->update(
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
     */
    public function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
    }

    /**
     * Bulk activate terms.
     */
    public function bulk_activate( array $ids ): int {
        return $this->bulk_update_field( $ids, 'is_active', 1 );
    }

    /**
     * Bulk deactivate terms.
     */
    public function bulk_deactivate( array $ids ): int {
        return $this->bulk_update_field( $ids, 'is_active', 0 );
    }

    /**
     * Bulk archive terms.
     */
    public function bulk_archive( array $ids ): int {
        return $this->bulk_update_field( $ids, 'is_archived', 1 );
    }

    /**
     * Bulk delete terms.
     */
    public function bulk_delete( array $ids ): int {
        global $wpdb;
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
     * Check if a term_key already exists (optionally excluding an ID).
     */
    public function key_exists( string $term_key, ?int $exclude_id = null ): bool {
        global $wpdb;
        if ( $exclude_id ) {
            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE term_key = %s AND id != %d",
                $term_key,
                $exclude_id
            ) );
        } else {
            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE term_key = %s",
                $term_key
            ) );
        }
        return (int) $count > 0;
    }

    /**
     * Get format strings for wpdb based on column types.
     */
    private function get_formats( array $data ): array {
        $int_cols = [ 'capacity', 'is_active', 'is_archived', 'sort_order' ];
        $formats  = [];
        foreach ( array_keys( $data ) as $col ) {
            $formats[] = in_array( $col, $int_cols, true ) ? '%d' : '%s';
        }
        return $formats;
    }

    /**
     * Bulk update a single field for given IDs.
     */
    private function bulk_update_field( array $ids, string $field, $value ): int {
        global $wpdb;
        $ids          = array_map( 'intval', $ids );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $now          = current_time( 'mysql' );

        return (int) $wpdb->query( $wpdb->prepare(
            "UPDATE {$this->table}
             SET {$field} = %d, updated_at = %s
             WHERE id IN ({$placeholders})",
            $value,
            $now,
            ...$ids
        ) );
    }
}
