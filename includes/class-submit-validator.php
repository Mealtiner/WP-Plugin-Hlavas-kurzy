<?php
/**
 * Submit validator: hooks into Fluent Forms submission to validate capacity.
 *
 * This is the server-side safety net – if Fluent Forms inventory does not
 * correctly enforce capacity, this validator prevents overbooking.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Hlavas_Terms_Repository', false ) ) {
	require_once __DIR__ . '/class-repository.php';
}

if ( ! class_exists( 'Hlavas_Terms_Availability_Service', false ) ) {
	require_once __DIR__ . '/class-availability-service.php';
}

class Hlavas_Terms_Submit_Validator {

	/**
	 * Availability service.
	 *
	 * @var Hlavas_Terms_Availability_Service
	 */
	private Hlavas_Terms_Availability_Service $availability;

	/**
	 * Repository.
	 *
	 * @var Hlavas_Terms_Repository
	 */
	private Hlavas_Terms_Repository $repo;

	/**
	 * Constructor.
	 *
	 * @param Hlavas_Terms_Availability_Service|null $availability Availability service.
	 * @param Hlavas_Terms_Repository|null           $repo Repository.
	 */
	public function __construct(
		?Hlavas_Terms_Availability_Service $availability = null,
		?Hlavas_Terms_Repository $repo = null
	) {
		$this->availability = $availability ?? new Hlavas_Terms_Availability_Service();
		$this->repo         = $repo ?? new Hlavas_Terms_Repository();
	}

	/**
	 * Register validation hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'fluentform/validation_errors', [ $this, 'validate' ], 20, 4 );
	}

	/**
	 * Validate capacity on form submission.
	 *
	 * @param array  $errors Existing validation errors.
	 * @param array  $data Submitted form data.
	 * @param object $form Fluent form object.
	 * @param array  $fields Form field definitions.
	 * @return array
	 */
	public function validate( array $errors, array $data, object $form, array $fields ): array {
		unset( $fields );

		// Validate all configured forms (default + per-qualification-type course/exam forms),
		// not just the single default form, so typed forms also get capacity enforcement.
		if ( ! in_array( (int) $form->id, hlavas_terms_get_all_form_ids(), true ) ) {
			return $errors;
		}

		$map = [
			'termin_kurz'    => 'Tento termín kurzu je bohužel již plně obsazen. Vyberte prosím jiný termín.',
			'termin_zkouska' => 'Tento termín zkoušky je bohužel již plně obsazen. Vyberte prosím jiný termín.',
		];

		foreach ( $map as $field_name => $message ) {
			if ( empty( $data[ $field_name ] ) ) {
				continue;
			}

			$term_value = sanitize_text_field( $data[ $field_name ] );

			if ( ! $this->check_capacity( $term_value ) ) {
				$errors[ $field_name ] = [ $message ];
			}
		}

		return $errors;
	}

	/**
	 * Check whether a selected term still has capacity.
	 *
	 * Accepts either:
	 * - current value = term_key
	 * - legacy value  = label
	 *
	 * @param string $value Submitted value.
	 * @return bool
	 */
	private function check_capacity( string $value ): bool {
		$term = $this->repo->find_by_key( $value );

		if ( ! $term ) {
			$all_terms = $this->repo->get_all();

			foreach ( $all_terms as $candidate ) {
				if ( isset( $candidate->label ) && $candidate->label === $value ) {
					$term = $candidate;
					break;
				}
			}
		}

		if ( ! $term || empty( $term->term_key ) ) {
			// Unknown submitted value – do not block submission here.
			return true;
		}

		return $this->availability->is_available( $term->term_key );
	}
}
