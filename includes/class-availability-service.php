<?php
/**
 * Availability service: computes enrollment counts and remaining capacity.
 *
 * This service queries Fluent Forms submissions to count how many people
 * have already enrolled for a given term_key, and compares against the
 * capacity defined in our terms table.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Hlavas_Terms_Repository', false ) ) {
	require_once __DIR__ . '/class-repository.php';
}

class Hlavas_Terms_Availability_Service {

    /**
     * Supported Fluent Forms field names that carry term selections.
     *
     * @var array<int, string>
     */
    private const TERM_FIELDS = [
        'termin_kurz',
        'termin_zkouska',
    ];

    private Hlavas_Terms_Repository $repo;

    public function __construct( ?Hlavas_Terms_Repository $repo = null ) {
        $this->repo = $repo ?? new Hlavas_Terms_Repository();
    }

    /**
     * Count active submissions for a given term_key across ALL configured forms.
     *
     * Searches in fluentform_entry_details for field_name = 'termin_kurz' or
     * 'termin_zkouska' with field_value matching the term_key (or legacy label).
     *
     * Covers the default form AND every per-qualification-type course/exam form
     * so that typed forms are not missed.
     *
     * @param string $term_key The internal term key.
     * @return int Number of active enrollments.
     */
    public function count_enrollments( string $term_key ): int {
        global $wpdb;
        /** @var wpdb $wpdb */

        $entry_table      = $wpdb->prefix . 'fluentform_entry_details';
        $submission_table = $wpdb->prefix . 'fluentform_submissions';

        if ( ! $this->table_exists( $entry_table ) || ! $this->table_exists( $submission_table ) ) {
            return 0;
        }

        $form_ids = hlavas_terms_get_all_form_ids();

        // Also check legacy label value for backward compatibility.
        $term            = $this->repo->find_by_key( $term_key );
        $values_to_check = [ $term_key ];

        if ( $term && $term->label ) {
            $values_to_check[] = $term->label;
        }

        $form_placeholders  = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
        $field_placeholders = implode( ',', array_fill( 0, count( self::TERM_FIELDS ), '%s' ) );
        $value_placeholders = implode( ',', array_fill( 0, count( $values_to_check ), '%s' ) );

        // Count entries that:
        // 1. Belong to any plugin-configured Fluent Form
        // 2. Have field_name 'termin_kurz' or 'termin_zkouska'
        // 3. Have field_value matching our term_key or legacy label
        // 4. The parent submission is not trashed
        $primary = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT e.submission_id)
                 FROM {$entry_table} e
                 INNER JOIN {$submission_table} s ON e.submission_id = s.id
                 WHERE e.form_id IN ({$form_placeholders})
                   AND e.field_name IN ({$field_placeholders})
                   AND e.field_value IN ({$value_placeholders})
                   AND s.status != 'trashed'",
                ...$form_ids,
                ...self::TERM_FIELDS,
                ...$values_to_check
            )
        );

        if ( $primary > 0 ) {
            return $primary;
        }

        // Some Fluent Forms setups do not fully populate entry_details for choice fields.
        return $this->count_enrollments_from_responses( $term_key );
    }

    /**
     * Alternative counting method: query the submissions.response JSON directly.
     *
     * This is a fallback if entry_details is not populated for these fields.
     * Fluent Forms stores the full response as JSON in fluentform_submissions.
     * Covers ALL configured forms, not only the default one.
     */
    public function count_enrollments_from_responses( string $term_key ): int {
        global $wpdb;
        /** @var wpdb $wpdb */

        $submission_table = $wpdb->prefix . 'fluentform_submissions';

        if ( ! $this->table_exists( $submission_table ) ) {
            return 0;
        }

        $form_ids = hlavas_terms_get_all_form_ids();

        $term            = $this->repo->find_by_key( $term_key );
        $values_to_check = [ $term_key ];
        if ( $term && $term->label ) {
            $values_to_check[] = $term->label;
        }

        $form_placeholders = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );

        // Query all non-trashed submissions across all configured forms.
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT response FROM {$submission_table}
                 WHERE form_id IN ({$form_placeholders}) AND status != 'trashed'",
                ...$form_ids
            )
        );

        $count = 0;
        foreach ( $submissions as $sub ) {
            $response = json_decode( $sub->response, true );
            if ( ! is_array( $response ) ) {
                continue;
            }

            // Check both field names.
            foreach ( self::TERM_FIELDS as $field_name ) {
                $value = $response[ $field_name ] ?? $this->find_value_recursive( $response, $field_name );

                if ( in_array( $value, $values_to_check, true ) ) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    /**
     * Get remaining capacity for a term.
     *
     * @return int Remaining spots (0 = full, negative should not happen).
     */
    public function get_remaining( string $term_key ): int {
        $term = $this->repo->find_by_key( $term_key );
        if ( ! $term ) {
            return 0;
        }

        $enrolled  = $this->count_enrollments( $term_key );
        $remaining = max( 0, (int) $term->capacity - $enrolled );

        return $remaining;
    }

    /**
     * Check if a term still has available spots.
     */
    public function is_available( string $term_key ): bool {
        return $this->get_remaining( $term_key ) > 0;
    }

    /**
     * Get a summary of all terms with their availability.
     *
     * @return array Each element: { term_key, label, type, capacity, enrolled, remaining }
     */
    public function get_availability_report( ?string $term_type = null ): array {
        $args = [
            'is_active'   => true,
            'is_archived' => false,
        ];
        if ( $term_type ) {
            $args['term_type'] = $term_type;
        }

        $terms  = $this->repo->get_all( $args );
        $report = [];

        foreach ( $terms as $term ) {
            $enrolled  = $this->count_enrollments( $term->term_key );
            $remaining = max( 0, (int) $term->capacity - $enrolled );

            $report[] = [
                'id'            => (int) $term->id,
                'term_key'      => $term->term_key,
                'title'         => ! empty( $term->title ) ? $term->title : $term->label,
                'qualification' => ! empty( $term->qualification_name )
                    ? trim( ( ! empty( $term->qualification_code ) ? $term->qualification_code . ' – ' : '' ) . $term->qualification_name )
                    : 'Bez návaznosti',
                'label'         => $term->label,
                'type'          => $term->term_type,
                'capacity'      => (int) $term->capacity,
                'enrolled'      => $enrolled,
                'remaining'     => $remaining,
            ];
        }

        return $report;
    }

    /**
     * Check whether a given DB table exists.
     *
     * @param string $table_name Full table name including WP prefix.
     * @return bool
     */
    private function table_exists( string $table_name ): bool {
        global $wpdb;
        /** @var wpdb $wpdb */

        return (bool) $wpdb->get_var(
            $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
        );
    }

    /**
     * Recursively search nested response payload for one field name.
     *
     * @param array<string, mixed> $response Response payload.
     * @param string               $field_name Target field name.
     * @return string
     */
    private function find_value_recursive( array $response, string $field_name ): string {
        foreach ( $response as $key => $value ) {
            if ( is_string( $key ) && $field_name === $key ) {
                return is_scalar( $value ) ? (string) $value : '';
            }

            if ( is_array( $value ) ) {
                $nested = $this->find_value_recursive( $value, $field_name );

                if ( '' !== $nested ) {
                    return $nested;
                }
            }
        }

        return '';
    }
}
