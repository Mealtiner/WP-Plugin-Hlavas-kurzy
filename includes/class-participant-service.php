<?php
/**
 * Participant reporting service backed by Fluent Forms submissions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Hlavas_Terms_Repository', false ) ) {
	require_once __DIR__ . '/class-repository.php';
}

if ( ! class_exists( 'Hlavas_Terms_Qualification_Type_Repository', false ) ) {
	require_once __DIR__ . '/class-qualification-type-repository.php';
}

class Hlavas_Terms_Participant_Service {

	/**
	 * Czech month names in genitive case for matching human-readable dates.
	 *
	 * @var array<int, string>
	 */
	private const MONTHS = [
		1  => 'ledna',
		2  => 'února',
		3  => 'března',
		4  => 'dubna',
		5  => 'května',
		6  => 'června',
		7  => 'července',
		8  => 'srpna',
		9  => 'září',
		10 => 'října',
		11 => 'listopadu',
		12 => 'prosince',
	];

	/**
	 * Possible field keys as they appear in Fluent Forms response JSON.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const FIELD_CANDIDATES = [
		'registration_type' => [
			'typ_prihlasky',
			'kam_se_chces_prihlasit',
			'input_radio',
		],
		'term_course'       => [
			'termin_kurz',
			'dropdown',
		],
		'term_exam'         => [
			'termin_zkouska',
			'dropdown_1',
		],
		'name'              => [
			'Name',
			'name',
			'names',
		],
		'birthdate'         => [
			'narozeni',
			'datetime',
		],
		'address'           => [
			'Address',
			'address',
			'address_1',
		],
		'email'             => [
			'ucastnik_email',
			'email',
			'Kam ti můžeme poslat potvrzení a materiály?',
		],
		'phone'             => [
			'ucastnik_telefon',
			'phone',
			'A tvoje telefonní číslo?',
		],
		'payment_type'      => [
			'typ_platby',
			'input_radio_1',
			'A teď k placení – kdo to vezme na sebe?',
		],
		'organization_name' => [
			'nazev_organizace',
			'input_text',
			'Jak se tvoje organizace přesně jmenuje?',
		],
		'organization_ico'  => [
			'ico_organizace',
			'numeric_field',
			'Budeme potřebovat vaše IČO.',
		],
		'invoice_email'     => [
			'fakturacni_email',
			'email_1',
			'Kam máme fakturu poslat?',
		],
	];

	/**
	 * Maps participant service logical fields to HLAVAS sync identifiers.
	 *
	 * @var array<string, string>
	 */
	private const FIELD_MAPPING_IDENTIFIERS = [
		'registration_type' => 'typ_prihlasky',
		'term_course'       => 'termin_kurz',
		'term_exam'         => 'termin_zkouska',
		'name'              => 'name',
		'birthdate'         => 'narozeni',
		'address'           => 'address',
		'email'             => 'ucastnik_email',
		'phone'             => 'ucastnik_telefon',
		'payment_type'      => 'typ_platby',
		'organization_name' => 'nazev_organizace',
		'organization_ico'  => 'ico_organizace',
		'invoice_email'     => 'fakturacni_email',
	];

	/**
	 * Terms repository.
	 *
	 * @var Hlavas_Terms_Repository
	 */
	private Hlavas_Terms_Repository $repo;

	/**
	 * Qualification types repository.
	 *
	 * @var Hlavas_Terms_Qualification_Type_Repository
	 */
	private Hlavas_Terms_Qualification_Type_Repository $type_repo;

	/**
	 * Constructor.
	 *
	 * @param Hlavas_Terms_Repository|null                    $repo Repository.
	 * @param Hlavas_Terms_Qualification_Type_Repository|null $type_repo Types repository.
	 */
	public function __construct(
		?Hlavas_Terms_Repository $repo = null,
		?Hlavas_Terms_Qualification_Type_Repository $type_repo = null
	) {
		$this->repo      = $repo ?? new Hlavas_Terms_Repository();
		$this->type_repo = $type_repo ?? new Hlavas_Terms_Qualification_Type_Repository();
	}

	/**
	 * Get participants report.
	 *
	 * @param array<string, mixed> $filters Filter set.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_participants( array $filters = [] ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$submissions_table = $wpdb->prefix . 'fluentform_submissions';
		$details_table     = $wpdb->prefix . 'fluentform_entry_details';

		if ( ! $this->table_exists( $submissions_table ) ) {
			return [];
		}

		$type_items     = $this->type_repo->get_all();
		$form_ids       = $this->get_relevant_form_ids( $type_items );
		$form_type_map  = $this->build_form_type_map( $type_items );

		if ( empty( $form_ids ) ) {
			return [];
		}

		$details_by_submission = $this->table_exists( $details_table )
			? $this->get_submission_details_map( $form_ids )
			: [];

		$terms          = $this->repo->get_all();
		$terms_by_id    = [];
		$terms_by_key   = [];
		$terms_by_label = [];
		$terms_by_title = [];

		foreach ( $terms as $term ) {
			$terms_by_id[ (int) $term->id ] = $term;
			$terms_by_key[ (string) $term->term_key ] = $term;

			if ( ! empty( $term->label ) ) {
				$terms_by_label[ $this->normalize_string( (string) $term->label ) ] = $term;
			}

			if ( ! empty( $term->title ) ) {
				$terms_by_title[ $this->normalize_string( (string) $term->title ) ] = $term;
			}
		}

		$placeholders = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
		$sql          = $wpdb->prepare(
			"SELECT id, form_id, response, status, created_at
			FROM {$submissions_table}
			WHERE form_id IN ({$placeholders})
				AND status != 'trashed'
			ORDER BY created_at DESC, id DESC",
			...$form_ids
		);
		$rows         = $wpdb->get_results( $sql );

		if ( ! is_array( $rows ) ) {
			return [];
		}

		$participants = [];

		foreach ( $rows as $row ) {
			$response = json_decode( (string) $row->response, true );
			$response = is_array( $response ) ? $response : [];
			$details  = $details_by_submission[ (int) $row->id ] ?? [];
			$context  = $this->resolve_submission_term_context( $response, $details, (int) $row->form_id );
			$manual_term_id = hlavas_terms_get_manual_participant_term_id( (int) $row->id );
			$manual_term    = $manual_term_id > 0 ? ( $terms_by_id[ $manual_term_id ] ?? null ) : null;

			$term = $manual_term instanceof \stdClass
				? $manual_term
				: $this->resolve_term_from_response(
					$response,
					$details,
					(int) $row->form_id,
					$terms,
					$terms_by_key,
					$terms_by_label,
					$terms_by_title,
					$form_type_map
				);

			$participant = $term
				? $this->build_participant_record( $row, $response, $details, $term, (int) $row->form_id, null !== $manual_term )
				: $this->build_unmatched_participant_record(
					$row,
					$response,
					$details,
					(int) $row->form_id,
					$context,
					$form_type_map,
					$type_items
				);

			if ( ! $this->matches_filters( $participant, $filters ) ) {
				continue;
			}

			$participants[] = $participant;
		}

		$this->sort_participants( $participants, $filters );

		return $participants;
	}

	/**
	 * Get relevant form IDs.
	 *
	 * @return array<int, int>
	 */
	private function get_relevant_form_ids( ?array $type_items = null ): array {
		$form_ids   = [ hlavas_terms_get_form_id() ];
		$type_items = is_array( $type_items ) ? $type_items : $this->type_repo->get_all();

		foreach ( $type_items as $type_item ) {
			$form_ids[] = (int) ( $type_item->course_form_id ?? 0 );
			$form_ids[] = (int) ( $type_item->exam_form_id ?? 0 );
		}

		$form_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $form_ids ),
					static fn( int $form_id ): bool => $form_id > 0
				)
			)
		);

		return $form_ids;
	}

	/**
	 * Build map of configured qualification type by form ID and term type.
	 *
	 * @param array<int, object> $type_items Qualification types.
	 * @return array<int, array<string, array<int, int>>>
	 */
	private function build_form_type_map( array $type_items ): array {
		$map = [];

		foreach ( $type_items as $type_item ) {
			$type_id = (int) ( $type_item->id ?? 0 );

			if ( $type_id <= 0 ) {
				continue;
			}

			$course_form_id = (int) ( $type_item->course_form_id ?? 0 );
			$exam_form_id   = (int) ( $type_item->exam_form_id ?? 0 );

			if ( $course_form_id > 0 ) {
				$map[ $course_form_id ]['kurz'][] = $type_id;
			}

			if ( $exam_form_id > 0 ) {
				$map[ $exam_form_id ]['zkouska'][] = $type_id;
			}
		}

		foreach ( $map as $form_id => $term_types ) {
			foreach ( $term_types as $term_type => $type_ids ) {
				$map[ $form_id ][ $term_type ] = array_values(
					array_unique(
						array_filter(
							array_map( 'intval', is_array( $type_ids ) ? $type_ids : [] ),
							static fn( int $type_id ): bool => $type_id > 0
						)
					)
				);
			}
		}

		return $map;
	}

	/**
	 * Resolve term object from response payload.
	 *
	 * @param array<string, mixed>  $response Response data.
	 * @param int                   $form_id Submission form ID.
	 * @param array<int, object>    $terms All terms.
	 * @param array<string, object> $terms_by_key Indexed terms by key.
	 * @param array<string, object> $terms_by_label Indexed terms by normalized label.
	 * @param array<string, object> $terms_by_title Indexed terms by normalized title.
	 * @param array<int, array<string, array<int, int>>> $form_type_map Form qualification mapping.
	 * @return object|null
	 */
	private function resolve_term_from_response(
		array $response,
		array $details,
		int $form_id,
		array $terms,
		array $terms_by_key,
		array $terms_by_label,
		array $terms_by_title,
		array $form_type_map
	): ?object {
		$values = [
			[
				'value'     => $this->extract_scalar_field( $response, 'term_course', $form_id, $details ),
				'term_type' => 'kurz',
			],
			[
				'value'     => $this->extract_scalar_field( $response, 'term_exam', $form_id, $details ),
				'term_type' => 'zkouska',
			],
		];

		foreach ( $values as $item ) {
			$value     = (string) $item['value'];
			$term_type = (string) $item['term_type'];

			if ( '' === $value ) {
				continue;
			}

			if ( isset( $terms_by_key[ $value ] ) ) {
				$term = $terms_by_key[ $value ];

				if ( $this->term_matches_type( $term, $term_type ) ) {
					return $term;
				}
			}

			$normalized                  = $this->normalize_string( $value );
			$preferred_qualification_ids = $this->get_preferred_qualification_ids( $form_type_map, $form_id, $term_type );

			if ( isset( $terms_by_label[ $normalized ] ) ) {
				$term = $terms_by_label[ $normalized ];

				if ( $this->term_matches_context( $term, $term_type, $preferred_qualification_ids ) ) {
					return $term;
				}
			}

			if ( isset( $terms_by_title[ $normalized ] ) ) {
				$term = $terms_by_title[ $normalized ];

				if ( $this->term_matches_context( $term, $term_type, $preferred_qualification_ids ) ) {
					return $term;
				}
			}

			$best_score = 0;
			$best_term  = null;

			foreach ( $terms as $term ) {
				$score = $this->score_term_match( $term, $normalized, $term_type, $preferred_qualification_ids );

				if ( $score > $best_score ) {
					$best_score = $score;
					$best_term  = $term;
				}
			}

			if ( $best_term && $best_score >= 70 ) {
				return $best_term;
			}
		}

		return $this->resolve_term_from_all_submission_values(
			$response,
			$details,
			$form_id,
			$terms,
			$terms_by_key,
			$terms_by_label,
			$terms_by_title,
			$form_type_map
		);
	}

	/**
	 * Check whether term has expected type.
	 *
	 * @param object $term Term object.
	 * @param string $term_type Expected type.
	 * @return bool
	 */
	private function term_matches_type( object $term, string $term_type ): bool {
		return $term_type === (string) ( $term->term_type ?? '' );
	}

	/**
	 * Check whether term matches submission context.
	 *
	 * @param object $term Term object.
	 * @param string $term_type Expected type.
	 * @param array<int, int> $preferred_qualification_ids Preferred qualification types.
	 * @return bool
	 */
	private function term_matches_context( object $term, string $term_type, array $preferred_qualification_ids ): bool {
		if ( ! $this->term_matches_type( $term, $term_type ) ) {
			return false;
		}

		if ( ! empty( $preferred_qualification_ids ) && ! in_array( (int) ( $term->qualification_type_id ?? 0 ), $preferred_qualification_ids, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Score likely match between submitted value and one term.
	 *
	 * @param object $term Term object.
	 * @param string $normalized_value Normalized submitted term text.
	 * @param string $term_type Expected type.
	 * @param array<int, int> $preferred_qualification_ids Preferred qualification types.
	 * @return int
	 */
	private function score_term_match( object $term, string $normalized_value, string $term_type, array $preferred_qualification_ids ): int {
		if ( ! $this->term_matches_type( $term, $term_type ) ) {
			return 0;
		}

		$score = 0;

		if ( ! empty( $preferred_qualification_ids ) ) {
			if ( ! in_array( (int) ( $term->qualification_type_id ?? 0 ), $preferred_qualification_ids, true ) ) {
				return 0;
			}

			$score += 25;
		}

		$title = $this->normalize_string( (string) ( $term->title ?? '' ) );
		$label = $this->normalize_string( (string) ( $term->label ?? '' ) );

		if ( '' !== $title && $normalized_value === $title ) {
			return 220 + $score;
		}

		if ( '' !== $label && $normalized_value === $label ) {
			return 210 + $score;
		}

		if ( '' !== $title && ( str_contains( $normalized_value, $title ) || str_contains( $title, $normalized_value ) ) ) {
			$score += 90;
		}

		if ( '' !== $label && ( str_contains( $normalized_value, $label ) || str_contains( $label, $normalized_value ) ) ) {
			$score += 70;
		}

		$qualification_code = $this->normalize_string( (string) ( $term->qualification_code ?? '' ) );
		$qualification_name = $this->normalize_string( (string) ( $term->qualification_name ?? '' ) );
		$date_matches       = 0;

		if ( '' !== $qualification_code && str_contains( $normalized_value, $qualification_code ) ) {
			$score += 20;
		}

		if ( '' !== $qualification_name && str_contains( $normalized_value, $qualification_name ) ) {
			$score += 35;
		}

		foreach ( $this->get_term_match_strings( $term ) as $match_string ) {
			if ( str_contains( $normalized_value, $match_string ) ) {
				$score += 30;
				$date_matches++;
			}
		}

		if ( $date_matches > 0 && '' !== $qualification_code && str_contains( $normalized_value, $qualification_code ) ) {
			$score += 40;
		}

		if ( $date_matches > 0 && '' !== $qualification_name && str_contains( $normalized_value, $qualification_name ) ) {
			$score += 30;
		}

		return $score;
	}

	/**
	 * Get preferred qualification IDs configured for one form and one term type.
	 *
	 * @param array<int, array<string, array<int, int>>> $form_type_map Form qualification mapping.
	 * @param int                                        $form_id Form ID.
	 * @param string                                     $term_type kurz|zkouska
	 * @return array<int, int>
	 */
	private function get_preferred_qualification_ids( array $form_type_map, int $form_id, string $term_type ): array {
		$type_ids = $form_type_map[ $form_id ][ $term_type ] ?? [];

		return array_values(
			array_unique(
				array_filter(
					array_map( 'intval', is_array( $type_ids ) ? $type_ids : [] ),
					static fn( int $type_id ): bool => $type_id > 0
				)
			)
		);
	}

	/**
	 * Get normalized date strings that can appear in Fluent Forms choices.
	 *
	 * @param object $term Term object.
	 * @return array<int, string>
	 */
	private function get_term_match_strings( object $term ): array {
		$matches = [];

		try {
			$start = new \DateTimeImmutable( (string) $term->date_start );
		} catch ( \Exception $exception ) {
			return [];
		}

		$matches[] = $this->normalize_string(
			sprintf(
				'%d. %s %s',
				(int) $start->format( 'j' ),
				self::MONTHS[ (int) $start->format( 'n' ) ] ?? '',
				$start->format( 'Y' )
			)
		);
		$matches[] = $this->normalize_string( $start->format( 'Y-m-d' ) );

		if ( 'kurz' === (string) ( $term->term_type ?? '' ) && ! empty( $term->date_end ) && $term->date_end !== $term->date_start ) {
			try {
				$end = new \DateTimeImmutable( (string) $term->date_end );
			} catch ( \Exception $exception ) {
				$end = null;
			}

			if ( $end ) {
				$matches[] = $this->normalize_string(
					sprintf(
						'%d. - %d. %s %s',
						(int) $start->format( 'j' ),
						(int) $end->format( 'j' ),
						self::MONTHS[ (int) $end->format( 'n' ) ] ?? '',
						$end->format( 'Y' )
					)
				);
				$matches[] = $this->normalize_string( $end->format( 'Y-m-d' ) );
			}
		}

		return array_values( array_filter( array_unique( $matches ) ) );
	}

	/**
	 * Build one participant row.
	 *
	 * @param object              $row DB row.
	 * @param array<string, mixed> $response Response payload.
	 * @param object              $term Resolved term.
	 * @return array<string, mixed>
	 */
	private function build_participant_record( object $row, array $response, array $details, object $term, int $form_id, bool $is_manual_match = false ): array {
		$participant_name = $this->extract_display_value( $response, 'name', $form_id, $details );
		$address          = $this->extract_display_value( $response, 'address', $form_id, $details );
		$participant_type = 'kurz' === $term->term_type ? 'Kurz' : 'Zkouška';
		$qualification    = ! empty( $term->qualification_name )
			? trim( ( ! empty( $term->qualification_code ) ? $term->qualification_code . ' – ' : '' ) . $term->qualification_name )
			: 'Bez návaznosti';

		return [
			'submission_id'      => (int) $row->id,
			'created_at'         => $this->format_datetime( (string) $row->created_at ),
			'created_at_mysql'   => (string) $row->created_at,
			'status'             => (string) $row->status,
			'form_id'            => (int) $row->form_id,
			'term_id'            => (int) $term->id,
			'term_type'          => (string) $term->term_type,
			'term_type_label'    => $participant_type,
			'term_title'         => ! empty( $term->title ) ? (string) $term->title : (string) $term->label,
			'term_label'         => (string) $term->label,
			'term_key'           => (string) $term->term_key,
			'qualification_id'   => (int) ( $term->qualification_type_id ?? 0 ),
			'qualification'      => $qualification,
			'qualification_code' => (string) ( $term->qualification_code ?? '' ),
			'source_format'      => $this->detect_submission_source_format( $response ),
			'is_manual_match'    => $is_manual_match,
			'registration_type'  => $this->extract_display_value( $response, 'registration_type', $form_id, $details ),
			'name'               => '' !== $participant_name ? $participant_name : 'Bez jména',
			'birthdate'          => $this->extract_scalar_field( $response, 'birthdate', $form_id, $details ),
			'address'            => $address,
			'email'              => $this->extract_scalar_field( $response, 'email', $form_id, $details ),
			'phone'              => $this->extract_scalar_field( $response, 'phone', $form_id, $details ),
			'payment_type'       => $this->extract_scalar_field( $response, 'payment_type', $form_id, $details ),
			'organization_name'  => $this->extract_scalar_field( $response, 'organization_name', $form_id, $details ),
			'organization_ico'   => $this->extract_scalar_field( $response, 'organization_ico', $form_id, $details ),
			'invoice_email'      => $this->extract_scalar_field( $response, 'invoice_email', $form_id, $details ),
			'is_unmatched'       => false,
			'admin_url'          => admin_url(
				'admin.php?page=fluent_forms&route=entries&form_id=' . (int) $row->form_id . '#/entries/' . (int) $row->id
			),
		];
	}

	/**
	 * Build participant row even for historical entries without current term match.
	 *
	 * @param object                                      $row DB row.
	 * @param array<string, mixed>                        $response Response payload.
	 * @param array<string, mixed>                        $details Entry details.
	 * @param int                                         $form_id Fluent Form ID.
	 * @param array<string, string>                       $context Submission term context.
	 * @param array<int, array<string, array<int, int>>>  $form_type_map Qualification map.
	 * @param array<int, object>                          $type_items Qualification types.
	 * @return array<string, mixed>
	 */
	private function build_unmatched_participant_record(
		object $row,
		array $response,
		array $details,
		int $form_id,
		array $context,
		array $form_type_map,
		array $type_items
	): array {
		$participant_name = $this->extract_display_value( $response, 'name', $form_id, $details );
		$address          = $this->extract_display_value( $response, 'address', $form_id, $details );
		$term_type        = (string) ( $context['term_type'] ?? '' );
		$term_type_label  = match ( $term_type ) {
			'kurz'    => 'Kurz',
			'zkouska' => 'Zkouška',
			default   => 'Neurčeno',
		};
		$raw_term_value   = trim( (string) ( $context['raw_term_value'] ?? '' ) );
		$qualification    = $this->resolve_unmatched_qualification( $context, $form_id, $form_type_map, $type_items );

		return [
			'submission_id'      => (int) $row->id,
			'created_at'         => $this->format_datetime( (string) $row->created_at ),
			'created_at_mysql'   => (string) $row->created_at,
			'status'             => (string) $row->status,
			'form_id'            => (int) $row->form_id,
			'term_id'            => 0,
			'term_type'          => $term_type,
			'term_type_label'    => $term_type_label,
			'term_title'         => '' !== $raw_term_value ? $raw_term_value : 'Historický / nepárovaný termín',
			'term_label'         => 'Termín se zatím nepodařilo napárovat na aktuální záznam v pluginu.',
			'term_key'           => '',
			'qualification_id'   => (int) $qualification['id'],
			'qualification'      => (string) $qualification['label'],
			'qualification_code' => (string) $qualification['code'],
			'source_format'      => $this->detect_submission_source_format( $response ),
			'is_manual_match'    => false,
			'registration_type'  => (string) ( $context['registration_type'] ?? '' ),
			'name'               => '' !== $participant_name ? $participant_name : 'Bez jména',
			'birthdate'          => $this->extract_scalar_field( $response, 'birthdate', $form_id, $details ),
			'address'            => $address,
			'email'              => $this->extract_scalar_field( $response, 'email', $form_id, $details ),
			'phone'              => $this->extract_scalar_field( $response, 'phone', $form_id, $details ),
			'payment_type'       => $this->extract_scalar_field( $response, 'payment_type', $form_id, $details ),
			'organization_name'  => $this->extract_scalar_field( $response, 'organization_name', $form_id, $details ),
			'organization_ico'   => $this->extract_scalar_field( $response, 'organization_ico', $form_id, $details ),
			'invoice_email'      => $this->extract_scalar_field( $response, 'invoice_email', $form_id, $details ),
			'is_unmatched'       => true,
			'admin_url'          => admin_url(
				'admin.php?page=fluent_forms&route=entries&form_id=' . (int) $row->form_id . '#/entries/' . (int) $row->id
			),
		];
	}

	/**
	 * Format MySQL datetime for admin listing.
	 *
	 * @param string $datetime Datetime value.
	 * @return string
	 */
	private function format_datetime( string $datetime ): string {
		if ( '' === $datetime ) {
			return '';
		}

		try {
			$value = new \DateTimeImmutable( $datetime );
		} catch ( \Exception $exception ) {
			return $datetime;
		}

		return $value->format( 'j. n. Y H:i' );
	}

	/**
	 * Determine whether participant matches filters.
	 *
	 * @param array<string, mixed> $participant Participant row.
	 * @param array<string, mixed> $filters Filter set.
	 * @return bool
	 */
	private function matches_filters( array $participant, array $filters ): bool {
		$qualification_type_id = (int) ( $filters['qualification_type_id'] ?? 0 );
		$term_type             = (string) ( $filters['term_type'] ?? '' );
		$term_id               = (int) ( $filters['term_id'] ?? 0 );

		if ( $qualification_type_id > 0 && $qualification_type_id !== (int) $participant['qualification_id'] ) {
			return false;
		}

		if ( '' !== $term_type && $term_type !== (string) $participant['term_type'] ) {
			return false;
		}

		if ( $term_id > 0 && $term_id !== (int) $participant['term_id'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Resolve the most useful term context from one submission.
	 *
	 * @param array<string, mixed> $response Response payload.
	 * @param array<string, mixed> $details Submission details.
	 * @param int                  $form_id Fluent Form ID.
	 * @return array<string, string>
	 */
	private function resolve_submission_term_context( array $response, array $details, int $form_id ): array {
		$course_value      = $this->extract_scalar_field( $response, 'term_course', $form_id, $details );
		$exam_value        = $this->extract_scalar_field( $response, 'term_exam', $form_id, $details );
		$registration_type = $this->extract_display_value( $response, 'registration_type', $form_id, $details );
		$term_type         = '';
		$raw_term_value    = '';

		if ( '' !== $course_value ) {
			$term_type      = 'kurz';
			$raw_term_value = $course_value;
		} elseif ( '' !== $exam_value ) {
			$term_type      = 'zkouska';
			$raw_term_value = $exam_value;
		} else {
			$term_type = $this->infer_term_type_from_text( $registration_type );
		}

		return [
			'term_type'         => $term_type,
			'raw_term_value'    => $raw_term_value,
			'registration_type' => $registration_type,
		];
	}

	/**
	 * Infer term type from user-facing submission text.
	 *
	 * @param string $value Source text.
	 * @return string
	 */
	private function infer_term_type_from_text( string $value ): string {
		$normalized = $this->normalize_string( $value );

		if ( '' === $normalized ) {
			return '';
		}

		if ( str_contains( $normalized, 'zkou' ) || str_contains( $normalized, 'certifik' ) ) {
			return 'zkouska';
		}

		if ( str_contains( $normalized, 'kurz' ) || str_contains( $normalized, 'naucit' ) ) {
			return 'kurz';
		}

		return '';
	}

	/**
	 * Resolve qualification info for unmatched historical entry.
	 *
	 * @param array<string, string>                      $context Submission term context.
	 * @param int                                        $form_id Fluent Form ID.
	 * @param array<int, array<string, array<int, int>>> $form_type_map Qualification map.
	 * @param array<int, object>                         $type_items Qualification types.
	 * @return array{id:int, label:string, code:string}
	 */
	private function resolve_unmatched_qualification(
		array $context,
		int $form_id,
		array $form_type_map,
		array $type_items
	): array {
		$term_type = (string) ( $context['term_type'] ?? '' );

		if ( '' !== $term_type ) {
			$preferred_ids = $this->get_preferred_qualification_ids( $form_type_map, $form_id, $term_type );

			if ( 1 === count( $preferred_ids ) ) {
				$preferred_type = $this->find_qualification_type_by_id( $type_items, (int) $preferred_ids[0] );

				if ( $preferred_type ) {
					return $this->format_qualification_type_label( $preferred_type );
				}
			}
		}

		$haystack   = $this->normalize_string(
			trim(
				(string) ( $context['registration_type'] ?? '' ) . ' ' .
				(string) ( $context['raw_term_value'] ?? '' )
			)
		);
		$best_score = 0;
		$best_type  = null;

		foreach ( $type_items as $type_item ) {
			$score = 0;
			$code  = $this->normalize_string( (string) ( $type_item->accreditation_number ?? '' ) );
			$name  = $this->normalize_string( (string) ( $type_item->name ?? '' ) );

			if ( '' !== $code && str_contains( $haystack, $code ) ) {
				$score += 100;
			}

			if ( '' !== $name && str_contains( $haystack, $name ) ) {
				$score += 50;
			}

			if ( $score > $best_score ) {
				$best_score = $score;
				$best_type  = $type_item;
			}
		}

		if ( $best_type && $best_score >= 60 ) {
			return $this->format_qualification_type_label( $best_type );
		}

		return [
			'id'    => 0,
			'label' => 'Historický záznam / bez párování',
			'code'  => '',
		];
	}

	/**
	 * Find one qualification type in preloaded collection.
	 *
	 * @param array<int, object> $type_items Qualification types.
	 * @param int                $id Type ID.
	 * @return object|null
	 */
	private function find_qualification_type_by_id( array $type_items, int $id ): ?object {
		foreach ( $type_items as $type_item ) {
			if ( (int) ( $type_item->id ?? 0 ) === $id ) {
				return $type_item;
			}
		}

		return null;
	}

	/**
	 * Format qualification type label for participant output.
	 *
	 * @param object $type_item Qualification type.
	 * @return array{id:int, label:string, code:string}
	 */
	private function format_qualification_type_label( object $type_item ): array {
		$code  = (string) ( $type_item->accreditation_number ?? '' );
		$name  = (string) ( $type_item->name ?? '' );
		$label = trim( ( '' !== $code ? $code . ' – ' : '' ) . $name );

		return [
			'id'    => (int) ( $type_item->id ?? 0 ),
			'label' => '' !== $label ? $label : 'Bez návaznosti',
			'code'  => $code,
		];
	}

	/**
	 * Extract scalar field from response by logical field name.
	 *
	 * @param array<string, mixed> $response Response payload.
	 * @param string               $field Logical field name.
	 * @return string
	 */
	private function extract_scalar_field( array $response, string $field, int $form_id = 0, array $details = [] ): string {
		$candidates = $this->get_candidates_for_form( $field, $form_id );

		$detail_value = $this->extract_from_submission_details( $details, $candidates );
		if ( '' !== $detail_value ) {
			return $detail_value;
		}

		foreach ( $candidates as $candidate ) {
			if ( ! array_key_exists( $candidate, $response ) ) {
				continue;
			}

			return $this->stringify_value( $response[ $candidate ] );
		}

		return $this->extract_from_response_recursive( $response, $candidates );
	}

	/**
	 * Extract a readable field value.
	 *
	 * @param array<string, mixed> $response Response payload.
	 * @param string               $field Logical field name.
	 * @return string
	 */
	private function extract_display_value( array $response, string $field, int $form_id = 0, array $details = [] ): string {
		return $this->extract_scalar_field( $response, $field, $form_id, $details );
	}

	/**
	 * Get field candidates enriched by manual per-form mapping.
	 *
	 * @param string $field Participant service field identifier.
	 * @param int    $form_id Fluent Form ID.
	 * @return array<int, string>
	 */
	private function get_candidates_for_form( string $field, int $form_id ): array {
		$defaults   = self::FIELD_CANDIDATES[ $field ] ?? [ $field ];
		$map_key    = self::FIELD_MAPPING_IDENTIFIERS[ $field ] ?? $field;
		$candidates = hlavas_terms_get_manual_field_aliases( $form_id, $map_key, $defaults );

		return array_values(
			array_unique(
				array_filter(
					array_map( 'strval', $candidates ),
					static fn( string $candidate ): bool => '' !== trim( $candidate )
				)
			)
		);
	}

	/**
	 * Resolve term from any scalar values found in submission payload.
	 *
	 * Used as a last-resort fallback when explicit term field mapping is missing
	 * or the selected term is stored under an unexpected key.
	 *
	 * @param array<string, mixed>                      $response Response data.
	 * @param array<string, mixed>                      $details Entry details.
	 * @param int                                       $form_id Form ID.
	 * @param array<int, object>                        $terms All terms.
	 * @param array<string, object>                     $terms_by_key Terms by key.
	 * @param array<string, object>                     $terms_by_label Terms by label.
	 * @param array<string, object>                     $terms_by_title Terms by title.
	 * @param array<int, array<string, array<int, int>>> $form_type_map Qualification map.
	 * @return object|null
	 */
	private function resolve_term_from_all_submission_values(
		array $response,
		array $details,
		int $form_id,
		array $terms,
		array $terms_by_key,
		array $terms_by_label,
		array $terms_by_title,
		array $form_type_map
	): ?object {
		$candidate_values = $this->collect_submission_string_values( $response, $details );
		$best_score       = 0;
		$best_term        = null;

		foreach ( $candidate_values as $value ) {
			if ( isset( $terms_by_key[ $value ] ) ) {
				return $terms_by_key[ $value ];
			}

			$normalized = $this->normalize_string( $value );

			if ( isset( $terms_by_label[ $normalized ] ) ) {
				return $terms_by_label[ $normalized ];
			}

			if ( isset( $terms_by_title[ $normalized ] ) ) {
				return $terms_by_title[ $normalized ];
			}

			foreach ( $terms as $term ) {
				$score = $this->score_term_match(
					$term,
					$normalized,
					(string) ( $term->term_type ?? '' ),
					$this->get_preferred_qualification_ids( $form_type_map, $form_id, (string) ( $term->term_type ?? '' ) )
				);

				if ( $score > $best_score ) {
					$best_score = $score;
					$best_term  = $term;
				}
			}
		}

		return $best_term && $best_score >= 90 ? $best_term : null;
	}

	/**
	 * Collect all readable scalar values from one submission.
	 *
	 * @param array<string, mixed> $response Response payload.
	 * @param array<string, mixed> $details Submission details.
	 * @return array<int, string>
	 */
	private function collect_submission_string_values( array $response, array $details ): array {
		$values = [];

		foreach ( $details as $field_values ) {
			$string = $this->stringify_value( $field_values );

			if ( '' !== $string ) {
				$values[] = $string;
			}
		}

		$this->collect_recursive_scalar_strings( $response, $values );

		return array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( string $value ): string => trim( $value ),
						array_map( 'strval', $values )
					),
					static fn( string $value ): bool => '' !== $value
				)
			)
		);
	}

	/**
	 * Collect scalar strings recursively from mixed submission payload.
	 *
	 * @param mixed              $node Current node.
	 * @param array<int, string> $values Output list.
	 * @return void
	 */
	private function collect_recursive_scalar_strings( mixed $node, array &$values ): void {
		if ( is_string( $node ) || is_numeric( $node ) ) {
			$string = trim( (string) $node );

			if ( '' !== $string ) {
				$values[] = $string;
			}

			return;
		}

		if ( ! is_array( $node ) ) {
			return;
		}

		foreach ( $node as $value ) {
			$this->collect_recursive_scalar_strings( $value, $values );
		}
	}

	/**
	 * Convert any response value to a readable string.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function stringify_value( mixed $value ): string {
		if ( is_string( $value ) || is_numeric( $value ) ) {
			return trim( (string) $value );
		}

		if ( is_array( $value ) ) {
			$parts = [];

			foreach ( $value as $item ) {
				$string = $this->stringify_value( $item );

				if ( '' !== $string ) {
					$parts[] = $string;
				}
			}

			return implode( ', ', $parts );
		}

		return '';
	}

	/**
	 * Extract one value from Fluent Forms entry details.
	 *
	 * @param array<string, mixed> $details Submission details map.
	 * @param array<int, string>   $candidates Candidate field names.
	 * @return string
	 */
	private function extract_from_submission_details( array $details, array $candidates ): string {
		if ( empty( $details ) ) {
			return '';
		}

		foreach ( $candidates as $candidate ) {
			if ( array_key_exists( $candidate, $details ) ) {
				return $this->stringify_value( $details[ $candidate ] );
			}
		}

		$normalized_candidates = array_map( [ $this, 'normalize_string' ], $candidates );

		foreach ( $details as $field_name => $value ) {
			$normalized_field_name = $this->normalize_string( (string) $field_name );

			if ( in_array( $normalized_field_name, $normalized_candidates, true ) ) {
				return $this->stringify_value( $value );
			}
		}

		return '';
	}

	/**
	 * Recursively scan nested response payload for candidate keys.
	 *
	 * @param array<string, mixed> $response Response payload.
	 * @param array<int, string>   $candidates Candidate field names.
	 * @return string
	 */
	private function extract_from_response_recursive( array $response, array $candidates ): string {
		$matches = [];
		$this->collect_recursive_response_matches( $response, $candidates, $matches );

		foreach ( $matches as $match ) {
			$string = $this->stringify_value( $match );

			if ( '' !== $string ) {
				return $string;
			}
		}

		return '';
	}

	/**
	 * Collect nested response values matching candidate keys.
	 *
	 * @param mixed              $node Current node.
	 * @param array<int, string> $candidates Candidate field names.
	 * @param array<int, mixed>  $matches Output list.
	 * @return void
	 */
	private function collect_recursive_response_matches( mixed $node, array $candidates, array &$matches ): void {
		if ( ! is_array( $node ) ) {
			return;
		}

		$normalized_candidates = array_map( [ $this, 'normalize_string' ], $candidates );

		foreach ( $node as $key => $value ) {
			if ( is_string( $key ) ) {
				$normalized_key = $this->normalize_string( $key );

				if ( in_array( $normalized_key, $normalized_candidates, true ) ) {
					$matches[] = $value;
				}
			}

			if ( is_array( $value ) ) {
				$this->collect_recursive_response_matches( $value, $candidates, $matches );
			}
		}
	}

	/**
	 * Sort participant rows according to active filters.
	 *
	 * @param array<int, array<string, mixed>> $participants Participant rows.
	 * @param array<string, mixed>             $filters Current filters.
	 * @return void
	 */
	private function sort_participants( array &$participants, array $filters ): void {
		$sort_by    = (string) ( $filters['sort_by'] ?? 'created_at' );
		$sort_order = 'asc' === strtolower( (string) ( $filters['sort_order'] ?? 'desc' ) ) ? 'asc' : 'desc';
		$allowed    = [ 'name', 'qualification', 'term_title', 'created_at', 'status', 'email', 'term_type' ];

		if ( ! in_array( $sort_by, $allowed, true ) ) {
			$sort_by = 'created_at';
		}

		usort(
			$participants,
			static function ( array $left, array $right ) use ( $sort_by, $sort_order ): int {
				$left_value = match ( $sort_by ) {
					'qualification' => (string) ( $left['qualification'] ?? '' ),
					'term_title'    => (string) ( $left['term_title'] ?? '' ),
					'status'        => (string) ( $left['status'] ?? '' ),
					'email'         => (string) ( $left['email'] ?? '' ),
					'term_type'     => (string) ( $left['term_type_label'] ?? '' ),
					'created_at'    => (string) ( $left['created_at_mysql'] ?? '' ),
					default         => (string) ( $left['name'] ?? '' ),
				};
				$right_value = match ( $sort_by ) {
					'qualification' => (string) ( $right['qualification'] ?? '' ),
					'term_title'    => (string) ( $right['term_title'] ?? '' ),
					'status'        => (string) ( $right['status'] ?? '' ),
					'email'         => (string) ( $right['email'] ?? '' ),
					'term_type'     => (string) ( $right['term_type_label'] ?? '' ),
					'created_at'    => (string) ( $right['created_at_mysql'] ?? '' ),
					default         => (string) ( $right['name'] ?? '' ),
				};

				$result = 'created_at' === $sort_by
					? strcmp( $left_value, $right_value )
					: strcasecmp( $left_value, $right_value );

				if ( 0 === $result ) {
					$result = (int) ( $left['submission_id'] ?? 0 ) <=> (int) ( $right['submission_id'] ?? 0 );
				}

				return 'asc' === $sort_order ? $result : -$result;
			}
		);
	}

	/**
	 * Load entry details grouped by submission ID and field name.
	 *
	 * @param array<int, int> $form_ids Relevant Fluent Forms IDs.
	 * @return array<int, array<string, array<int, mixed>>>
	 */
	private function get_submission_details_map( array $form_ids ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		if ( empty( $form_ids ) ) {
			return [];
		}

		$details_table = $wpdb->prefix . 'fluentform_entry_details';
		$placeholders  = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
		$sql           = $wpdb->prepare(
			"SELECT submission_id, field_name, sub_field_name, field_value
			FROM {$details_table}
			WHERE form_id IN ({$placeholders})
			ORDER BY submission_id ASC, id ASC",
			...$form_ids
		);
		$rows          = $wpdb->get_results( $sql );

		if ( ! is_array( $rows ) ) {
			return [];
		}

		$details_by_submission = [];

		foreach ( $rows as $row ) {
			$submission_id = (int) ( $row->submission_id ?? 0 );
			$field_name    = trim( (string) ( $row->field_name ?? '' ) );
			$sub_field     = trim( (string) ( $row->sub_field_name ?? '' ) );

			if ( $submission_id <= 0 || '' === $field_name ) {
				continue;
			}

			$value = maybe_unserialize( $row->field_value );

			$details_by_submission[ $submission_id ][ $field_name ][] = $value;

			if ( '' !== $sub_field ) {
				$details_by_submission[ $submission_id ][ $sub_field ][] = $value;
			}
		}

		return $details_by_submission;
	}

	/**
	 * Normalize string for matching.
	 *
	 * @param string $value Input string.
	 * @return string
	 */
	private function normalize_string( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = strtr(
			$value,
			[
				'–' => '-',
				'—' => '-',
				'−' => '-',
				'‑' => '-',
				' ' => ' ',
			]
		);
		$value = preg_replace( '/\s*-\s*/u', ' - ', $value );
		$value = preg_replace( '/\s+/u', ' ', trim( $value ) );

		return mb_strtolower( (string) $value );
	}

	/**
	 * Detect whether one submission already carries the new HLAVAS field names.
	 *
	 * @param array<string, mixed> $response Response payload.
	 * @return string new|legacy
	 */
	private function detect_submission_source_format( array $response ): string {
		foreach ( [ 'typ_prihlasky', 'termin_kurz', 'termin_zkouska' ] as $field_name ) {
			if ( array_key_exists( $field_name, $response ) ) {
				return 'new';
			}
		}

		return 'legacy';
	}

	/**
	 * Check whether DB table exists.
	 *
	 * @param string $table_name Full table name.
	 * @return bool
	 */
	private function table_exists( string $table_name ): bool {
		global $wpdb;
		/** @var wpdb $wpdb */

		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		return $table_name === $result;
	}
}
