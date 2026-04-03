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
		],
		'term_course'       => [
			'termin_kurz',
		],
		'term_exam'         => [
			'termin_zkouska',
		],
		'name'              => [
			'Name',
			'name',
			'names',
		],
		'birthdate'         => [
			'narozeni',
		],
		'address'           => [
			'Address',
			'address',
		],
		'email'             => [
			'ucastnik_email',
			'Kam ti můžeme poslat potvrzení a materiály?',
		],
		'phone'             => [
			'ucastnik_telefon',
			'A tvoje telefonní číslo?',
		],
		'payment_type'      => [
			'typ_platby',
			'A teď k placení – kdo to vezme na sebe?',
		],
		'organization_name' => [
			'nazev_organizace',
			'Jak se tvoje organizace přesně jmenuje?',
		],
		'organization_ico'  => [
			'ico_organizace',
			'Budeme potřebovat vaše IČO.',
		],
		'invoice_email'     => [
			'fakturacni_email',
			'Kam máme fakturu poslat?',
		],
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

		if ( ! $this->table_exists( $submissions_table ) ) {
			return [];
		}

		$type_items     = $this->type_repo->get_all();
		$form_ids       = $this->get_relevant_form_ids( $type_items );
		$form_type_map  = $this->build_form_type_map( $type_items );

		if ( empty( $form_ids ) ) {
			return [];
		}

		$terms          = $this->repo->get_all();
		$terms_by_key   = [];
		$terms_by_label = [];
		$terms_by_title = [];

		foreach ( $terms as $term ) {
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

			if ( ! is_array( $response ) ) {
				continue;
			}

			$term = $this->resolve_term_from_response(
				$response,
				(int) $row->form_id,
				$terms,
				$terms_by_key,
				$terms_by_label,
				$terms_by_title,
				$form_type_map
			);

			if ( ! $term ) {
				continue;
			}

			$participant = $this->build_participant_record( $row, $response, $term );

			if ( ! $this->matches_filters( $participant, $filters ) ) {
				continue;
			}

			$participants[] = $participant;
		}

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
	 * @return array<int, array<string, int>>
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
				$map[ $course_form_id ]['kurz'] = $type_id;
			}

			if ( $exam_form_id > 0 ) {
				$map[ $exam_form_id ]['zkouska'] = $type_id;
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
	 * @param array<int, array<string, int>> $form_type_map Form qualification mapping.
	 * @return object|null
	 */
	private function resolve_term_from_response(
		array $response,
		int $form_id,
		array $terms,
		array $terms_by_key,
		array $terms_by_label,
		array $terms_by_title,
		array $form_type_map
	): ?object {
		$values = [
			[
				'value'     => $this->extract_scalar_field( $response, 'term_course' ),
				'term_type' => 'kurz',
			],
			[
				'value'     => $this->extract_scalar_field( $response, 'term_exam' ),
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

			$normalized = $this->normalize_string( $value );
			$preferred_qualification_id = (int) ( $form_type_map[ $form_id ][ $term_type ] ?? 0 );

			if ( isset( $terms_by_label[ $normalized ] ) ) {
				$term = $terms_by_label[ $normalized ];

				if ( $this->term_matches_context( $term, $term_type, $preferred_qualification_id ) ) {
					return $term;
				}
			}

			if ( isset( $terms_by_title[ $normalized ] ) ) {
				$term = $terms_by_title[ $normalized ];

				if ( $this->term_matches_context( $term, $term_type, $preferred_qualification_id ) ) {
					return $term;
				}
			}

			$best_score = 0;
			$best_term  = null;

			foreach ( $terms as $term ) {
				$score = $this->score_term_match( $term, $normalized, $term_type, $preferred_qualification_id );

				if ( $score > $best_score ) {
					$best_score = $score;
					$best_term  = $term;
				}
			}

			if ( $best_term && $best_score >= 70 ) {
				return $best_term;
			}
		}

		return null;
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
	 * @param int    $preferred_qualification_id Preferred qualification type.
	 * @return bool
	 */
	private function term_matches_context( object $term, string $term_type, int $preferred_qualification_id ): bool {
		if ( ! $this->term_matches_type( $term, $term_type ) ) {
			return false;
		}

		if ( $preferred_qualification_id > 0 && (int) ( $term->qualification_type_id ?? 0 ) !== $preferred_qualification_id ) {
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
	 * @param int    $preferred_qualification_id Preferred qualification type.
	 * @return int
	 */
	private function score_term_match( object $term, string $normalized_value, string $term_type, int $preferred_qualification_id ): int {
		if ( ! $this->term_matches_type( $term, $term_type ) ) {
			return 0;
		}

		$score = 0;

		if ( $preferred_qualification_id > 0 ) {
			if ( (int) ( $term->qualification_type_id ?? 0 ) !== $preferred_qualification_id ) {
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

		if ( '' !== $qualification_code && str_contains( $normalized_value, $qualification_code ) ) {
			$score += 20;
		}

		foreach ( $this->get_term_match_strings( $term ) as $match_string ) {
			if ( str_contains( $normalized_value, $match_string ) ) {
				$score += 30;
				break;
			}
		}

		return $score;
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
	private function build_participant_record( object $row, array $response, object $term ): array {
		$participant_name = $this->extract_display_value( $response, 'name' );
		$address          = $this->extract_display_value( $response, 'address' );
		$participant_type = 'kurz' === $term->term_type ? 'Kurz' : 'Zkouška';
		$qualification    = ! empty( $term->qualification_name )
			? trim( ( ! empty( $term->qualification_code ) ? $term->qualification_code . ' – ' : '' ) . $term->qualification_name )
			: 'Bez návaznosti';

		return [
			'submission_id'      => (int) $row->id,
			'created_at'         => $this->format_datetime( (string) $row->created_at ),
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
			'registration_type'  => $this->extract_display_value( $response, 'registration_type' ),
			'name'               => '' !== $participant_name ? $participant_name : 'Bez jména',
			'birthdate'          => $this->extract_scalar_field( $response, 'birthdate' ),
			'address'            => $address,
			'email'              => $this->extract_scalar_field( $response, 'email' ),
			'phone'              => $this->extract_scalar_field( $response, 'phone' ),
			'payment_type'       => $this->extract_scalar_field( $response, 'payment_type' ),
			'organization_name'  => $this->extract_scalar_field( $response, 'organization_name' ),
			'organization_ico'   => $this->extract_scalar_field( $response, 'organization_ico' ),
			'invoice_email'      => $this->extract_scalar_field( $response, 'invoice_email' ),
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
	 * Extract scalar field from response by logical field name.
	 *
	 * @param array<string, mixed> $response Response payload.
	 * @param string               $field Logical field name.
	 * @return string
	 */
	private function extract_scalar_field( array $response, string $field ): string {
		$candidates = self::FIELD_CANDIDATES[ $field ] ?? [ $field ];

		foreach ( $candidates as $candidate ) {
			if ( ! array_key_exists( $candidate, $response ) ) {
				continue;
			}

			return $this->stringify_value( $response[ $candidate ] );
		}

		return '';
	}

	/**
	 * Extract a readable field value.
	 *
	 * @param array<string, mixed> $response Response payload.
	 * @param string               $field Logical field name.
	 * @return string
	 */
	private function extract_display_value( array $response, string $field ): string {
		return $this->extract_scalar_field( $response, $field );
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
	 * Normalize string for matching.
	 *
	 * @param string $value Input string.
	 * @return string
	 */
	private function normalize_string( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = preg_replace( '/\s+/u', ' ', trim( $value ) );

		return mb_strtolower( (string) $value );
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
