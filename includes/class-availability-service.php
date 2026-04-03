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
     * Count active submissions for a given term_key.
     *
     * Searches in fluentform_entry_details for field_name = 'termin_kurz' or
     * 'termin_zkouska' with field_value matching the term_key (or legacy label).
     *
     * @param string $term_key The internal term key.
     * @return int Number of active enrollments.
     */
    public function count_enrollments( string $term_key ): int {
        global $wpdb;
        /** @var wpdb $wpdb */

        $entry_table       = $wpdb->prefix . 'fluentform_entry_details';
        $submission_table  = $wpdb->prefix . 'fluentform_submissions';
        $form_id           = hlavas_terms_get_form_id();

        // Also check legacy label value for backward compatibility
        $term            = $this->repo->find_by_key( $term_key );
        $values_to_check = [ $term_key ];

        if ( $term && $term->label ) {
            $values_to_check[] = $term->label;
        }

        $placeholders = implode( ',', array_fill( 0, count( $values_to_check ), '%s' ) );
        $field_names  = implode( "', '", self::TERM_FIELDS );

        // Count entries that:
        // 1. Belong to the configured Fluent Form
        // 2. Have field_name 'termin_kurz' or 'termin_zkouska'
        // 3. Have field_value matching our term_key or legacy label
        // 4. The parent submission is not trashed
        $primary = (int) $wpdb->get_var(
            $wpdb->prepare(
            "SELECT COUNT(DISTINCT e.submission_id) FROM {$entry_table} e
             INNER JOIN {$submission_table} s ON e.submission_id = s.id
             WHERE e.form_id = %d
               AND e.field_name IN ('{$field_names}')
               AND e.field_value IN ({$placeholders})
               AND s.status != 'trashed'",
                $form_id,
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
     */
    public function count_enrollments_from_responses( string $term_key ): int {
        global $wpdb;
        /** @var wpdb $wpdb */

        $submission_table = $wpdb->prefix . 'fluentform_submissions';
        $form_id          = hlavas_terms_get_form_id();

        $term            = $this->repo->find_by_key( $term_key );
        $values_to_check = [ $term_key ];
        if ( $term && $term->label ) {
            $values_to_check[] = $term->label;
        }

        // Query all non-trashed submissions for the configured form.
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT response FROM {$submission_table}
             WHERE form_id = %d AND status != 'trashed'",
                $form_id
            )
        );

        $count = 0;
        foreach ( $submissions as $sub ) {
            $response = json_decode( $sub->response, true );
            if ( ! is_array( $response ) ) {
                continue;
            }

            // Check both field names
            foreach ( self::TERM_FIELDS as $field_name ) {
                $value = $response[ $field_name ] ?? '';

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
}
