<?php
/**
 * Submit validator: hooks into Fluent Forms submission to validate capacity.
 *
 * This is the Varianta B fallback – if Fluent Forms inventory does not
 * correctly enforce capacity (e.g., after migration to term_key values),
 * this validator provides a server-side safety net.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hlavas_Terms_Submit_Validator {

    private Hlavas_Terms_Availability_Service $availability;

    public function __construct( ?Hlavas_Terms_Availability_Service $availability = null ) {
        $this->availability = $availability ?? new Hlavas_Terms_Availability_Service();
    }

    /**
     * Register the validation hook.
     */
    public function register(): void {
        // Hook into Fluent Forms validation before submission is saved.
        // The filter 'fluentform/validation_errors' is called for all forms.
        add_filter( 'fluentform/validation_errors', [ $this, 'validate' ], 20, 4 );
    }

    /**
     * Validate capacity on form submission.
     *
     * @param array  $errors    Existing validation errors.
     * @param array  $data      Submitted form data.
     * @param object $form      The form object.
     * @param array  $fields    Form field definitions.
     * @return array Modified errors array.
     */
    public function validate( array $errors, array $data, object $form, array $fields ): array {
        // Only validate our form
        if ( (int) $form->id !== HLAVAS_TERMS_FLUENT_FORM_ID ) {
            return $errors;
        }

        // Check termin_kurz
        if ( ! empty( $data['termin_kurz'] ) ) {
            $term_value = sanitize_text_field( $data['termin_kurz'] );
            if ( ! $this->check_capacity( $term_value ) ) {
                $errors['termin_kurz'] = [
                    'Tento termín kurzu je bohužel již plně obsazen. Vyberte prosím jiný termín.',
                ];
            }
        }

        // Check termin_zkouska
        if ( ! empty( $data['termin_zkouska'] ) ) {
            $term_value = sanitize_text_field( $data['termin_zkouska'] );
            if ( ! $this->check_capacity( $term_value ) ) {
                $errors['termin_zkouska'] = [
                    'Tento termín zkoušky je bohužel již plně obsazen. Vyberte prosím jiný termín.',
                ];
            }
        }

        return $errors;
    }

    /**
     * Check if a term value (term_key or label) still has capacity.
     */
    private function check_capacity( string $value ): bool {
        $repo = new Hlavas_Terms_Repository();

        // Try to find by term_key first
        $term = $repo->find_by_key( $value );

        if ( ! $term ) {
            // Maybe it's a legacy label value – search all terms
            $all_terms = $repo->get_all();
            foreach ( $all_terms as $t ) {
                if ( $t->label === $value ) {
                    $term = $t;
                    break;
                }
            }
        }

        if ( ! $term ) {
            // Unknown term value – let it pass (don't block unknown entries)
            return true;
        }

        return $this->availability->is_available( $term->term_key );
    }
}
