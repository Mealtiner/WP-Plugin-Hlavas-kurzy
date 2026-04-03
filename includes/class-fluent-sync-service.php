<?php
/**
 * Synchronization service: writes term options + inventory into Fluent Forms.
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

class Hlavas_Terms_Fluent_Sync_Service {

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
	 * Fields that receive synchronized term options.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private const SYNC_FIELDS = [
		'termin_kurz' => [
			'term_type' => 'kurz',
			'aliases'   => [ 'termin_kurz', 'Vyber termín kurzu' ],
			'label'     => 'Vyber termín kurzu',
		],
		'termin_zkouska' => [
			'term_type' => 'zkouska',
			'aliases'   => [ 'termin_zkouska', 'Vyber termín zkoušky' ],
			'label'     => 'Vyber termín zkoušky',
		],
	];

	/**
	 * Fields useful for participant mapping and diagnostics.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private const EXPECTED_FIELDS = [
		'typ_prihlasky' => [
			'aliases'     => [ 'typ_prihlasky', 'Kam se chceš přihlásit' ],
			'label'       => 'Kam se chceš přihlásit',
			'description' => 'Volba kurz / zkouška a akreditace.',
		],
		'termin_kurz' => [
			'aliases'     => [ 'termin_kurz', 'Vyber termín kurzu' ],
			'label'       => 'Vyber termín kurzu',
			'description' => 'Sem plugin synchronizuje termíny kurzů.',
		],
		'termin_zkouska' => [
			'aliases'     => [ 'termin_zkouska', 'Vyber termín zkoušky' ],
			'label'       => 'Vyber termín zkoušky',
			'description' => 'Sem plugin synchronizuje termíny zkoušek.',
		],
		'Name' => [
			'aliases'     => [ 'Name', 'name', 'Jméno a pod' ],
			'label'       => 'Jméno a příjmení',
			'description' => 'Skupina jméno / přezdívka / příjmení.',
		],
		'narozeni' => [
			'aliases'     => [ 'narozeni', 'Kdy jsi se narodil' ],
			'label'       => 'Datum narození',
			'description' => 'Datum ve formátu YYYY-MM-DD.',
		],
		'Address' => [
			'aliases'     => [ 'Address', 'address', 'Kde jsi doma' ],
			'label'       => 'Adresa',
			'description' => 'Skupina ulice / obec / PSČ / kraj.',
		],
		'ucastnik_email' => [
			'aliases'     => [ 'ucastnik_email', 'Kam ti budeme posílat zprávy' ],
			'label'       => 'E-mail účastníka',
			'description' => 'Kontaktní e-mail pro potvrzení a materiály.',
		],
		'ucastnik_telefon' => [
			'aliases'     => [ 'ucastnik_telefon', 'Jaký je tvůj telefon' ],
			'label'       => 'Telefon účastníka',
			'description' => 'Telefonní číslo účastníka.',
		],
		'typ_platby' => [
			'aliases'     => [ 'typ_platby', 'Kdo kurz platí' ],
			'label'       => 'Typ platby',
			'description' => 'Platí sám / organizace.',
		],
		'nazev_organizace' => [
			'aliases'     => [ 'nazev_organizace', 'Jak se organizace jmenuje' ],
			'label'       => 'Název organizace',
			'description' => 'Pouze pokud platí organizace.',
		],
		'ico_organizace' => [
			'aliases'     => [ 'ico_organizace', 'IČO organizace' ],
			'label'       => 'IČO organizace',
			'description' => 'Pouze pokud platí organizace.',
		],
		'fakturacni_email' => [
			'aliases'     => [ 'fakturacni_email', 'Fakturační email' ],
			'label'       => 'Fakturační e-mail',
			'description' => 'E-mail pro zaslání faktury.',
		],
	];

	/**
	 * Constructor.
	 *
	 * @param Hlavas_Terms_Repository|null                    $repo Repository instance.
	 * @param Hlavas_Terms_Qualification_Type_Repository|null $type_repo Type repository instance.
	 */
	public function __construct(
		?Hlavas_Terms_Repository $repo = null,
		?Hlavas_Terms_Qualification_Type_Repository $type_repo = null
	) {
		$this->repo      = $repo ?? new Hlavas_Terms_Repository();
		$this->type_repo = $type_repo ?? new Hlavas_Terms_Qualification_Type_Repository();
	}

	/**
	 * Build sections for configured Fluent Forms.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_form_sections(): array {
		$configs  = $this->get_form_configurations();
		$sections = [];

		foreach ( $configs as $config ) {
			$sections[] = $this->build_form_section( $config );
		}

		return $sections;
	}

	/**
	 * Build preview for backwards compatibility.
	 *
	 * @return array<string, mixed>
	 */
	public function preview(): array {
		$sections = $this->get_form_sections();
		$first    = $sections[0] ?? null;

		if ( ! $first ) {
			return [
				'form_found'   => false,
				'fields_found' => [],
				'kurz'         => [],
				'zkouska'      => [],
			];
		}

		return [
			'form_found'   => ! empty( $first['form_found'] ),
			'fields_found' => $this->extract_field_status_map( $first['sync_fields'] ?? [] ),
			'kurz'         => $first['term_previews']['kurz']['terms'] ?? [],
			'zkouska'      => $first['term_previews']['zkouska']['terms'] ?? [],
		];
	}

	/**
	 * Dump internal Fluent Forms structure.
	 *
	 * @param int|null $form_id Optional specific form ID.
	 * @return array<string, mixed>
	 */
	public function debug( ?int $form_id = null ): array {
		$config = $this->get_debug_configuration( $form_id );

		if ( ! $config ) {
			return [
				'error'   => 'Nebyl nalezen zadny nakonfigurovany formular pro debug.',
				'form_id' => $form_id ?? hlavas_terms_get_form_id(),
			];
		}

		$form = $this->get_form_by_id( (int) $config['form_id'] );

		if ( ! $form ) {
			return [
				'error'   => 'Formular ID ' . (int) $config['form_id'] . ' nebyl nalezen.',
				'form_id' => (int) $config['form_id'],
			];
		}

		$fields = $this->parse_form_fields( $form );
		$debug  = [
			'form_id'           => (int) $form->id,
			'form_title'        => (string) $form->title,
			'configuration'     => $config,
			'field_catalog'     => $this->collect_field_catalog( $fields ),
			'field_matches'     => $this->get_expected_field_matches( $fields ),
			'inventory_meta'    => $this->get_inventory_meta( (int) $form->id ),
			'raw_fields'        => $fields,
		];

		return $debug;
	}

	/**
	 * Execute synchronization for one or all configured forms.
	 *
	 * @param string   $value_mode Value mode: term_key or label.
	 * @param int|null $form_id Optional specific form ID.
	 * @return array{success: bool, message: string, details: array<int, string>}
	 */
	public function execute( string $value_mode = 'term_key', ?int $form_id = null ): array {
		$configs = $this->get_form_configurations();

		if ( null !== $form_id ) {
			$configs = array_values(
				array_filter(
					$configs,
					static fn( array $config ): bool => (int) $config['form_id'] === $form_id
				)
			);
		}

		if ( empty( $configs ) ) {
			return [
				'success' => false,
				'message' => 'Neni nastaven zadny formular pro synchronizaci.',
				'details' => [],
			];
		}

		$details = [];
		$updated = 0;

		foreach ( $configs as $config ) {
			$result   = $this->execute_for_configuration( $config, $value_mode );
			$details  = array_merge( $details, $result['details'] );
			$updated += ! empty( $result['updated'] ) ? 1 : 0;
		}

		if ( $updated <= 0 ) {
			return [
				'success' => false,
				'message' => 'Nepodarilo se aktualizovat zadny formular.',
				'details' => $details,
			];
		}

		$message = null === $form_id
			? 'Synchronizace probehla pro ' . $updated . ' formular(e).'
			: 'Synchronizace formulare probehla uspesne.';

		return [
			'success' => true,
			'message' => $message,
			'details' => $details,
		];
	}

	/**
	 * Get all form IDs whose synchronized targets currently include the term.
	 *
	 * @param int $term_id Term ID.
	 * @return array<int, int>
	 */
	public function get_form_ids_for_term( int $term_id ): array {
		$term = $this->repo->find( $term_id );

		if ( ! $term ) {
			return [];
		}

		$form_ids = $this->get_direct_form_ids_for_term( $term );

		foreach ( $this->get_form_configurations() as $config ) {
			foreach ( $this->get_targets_for_configuration( $config ) as $target ) {
				foreach ( $target['terms'] as $target_term ) {
					if ( (int) $target_term->id === $term_id ) {
						$form_ids[] = (int) $config['form_id'];
						break 2;
					}
				}
			}
		}

		return array_values( array_unique( array_filter( array_map( 'intval', $form_ids ) ) ) );
	}

	/**
	 * Resolve directly assigned form IDs for one term.
	 *
	 * This is used mainly from the term detail page, where synchronization
	 * should still be possible even if the term is currently hidden, inactive
	 * or already outside the public syncable range.
	 *
	 * @param object $term Term object.
	 * @return array<int, int>
	 */
	private function get_direct_form_ids_for_term( object $term ): array {
		$form_ids        = [];
		$default_form_id = hlavas_terms_get_form_id();

		if ( $default_form_id > 0 ) {
			$form_ids[] = $default_form_id;
		}

		$qualification_type_id = (int) ( $term->qualification_type_id ?? 0 );

		if ( $qualification_type_id <= 0 ) {
			return array_values( array_unique( array_filter( array_map( 'intval', $form_ids ) ) ) );
		}

		$type_item = $this->type_repo->find( $qualification_type_id );

		if ( ! $type_item ) {
			return array_values( array_unique( array_filter( array_map( 'intval', $form_ids ) ) ) );
		}

		if ( 'kurz' === (string) $term->term_type ) {
			$form_ids[] = (int) ( $type_item->course_form_id ?? 0 );
		}

		if ( 'zkouska' === (string) $term->term_type ) {
			$form_ids[] = (int) ( $type_item->exam_form_id ?? 0 );
		}

		return array_values( array_unique( array_filter( array_map( 'intval', $form_ids ) ) ) );
	}

	/**
	 * Get current options from one field of one form.
	 *
	 * @param string   $identifier Field identifier.
	 * @param int|null $form_id Optional form ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_current_options( string $identifier, ?int $form_id = null ): array {
		$config = $this->get_debug_configuration( $form_id );

		if ( ! $config ) {
			return [];
		}

		$form = $this->get_form_by_id( (int) $config['form_id'] );

		if ( ! $form ) {
			return [];
		}

		$fields     = $this->parse_form_fields( $form );
		$field_path = $this->find_field_path_by_aliases( $fields, [ $identifier ] );

		if ( null === $field_path ) {
			return [];
		}

		$field = $this->get_field_by_path( $fields, $field_path );

		if ( ! is_array( $field ) ) {
			return [];
		}

		$options = $field['settings']['advanced_options'] ?? [];

		return is_array( $options ) ? $options : [];
	}

	/**
	 * Build one form section.
	 *
	 * @param array<string, mixed> $config Form configuration.
	 * @return array<string, mixed>
	 */
	private function build_form_section( array $config ): array {
		$form = $this->get_form_by_id( (int) $config['form_id'] );

		if ( ! $form ) {
			return [
				'form_id'         => (int) $config['form_id'],
				'form_title'      => '',
				'form_found'      => false,
				'config'          => $config,
				'field_catalog'   => [],
				'field_matches'   => [],
				'sync_fields'     => $this->build_empty_sync_fields( $config ),
				'term_previews'   => $this->build_term_previews( $config ),
				'inventory_meta'  => null,
			];
		}

		$fields = $this->parse_form_fields( $form );

		return [
			'form_id'         => (int) $form->id,
			'form_title'      => (string) $form->title,
			'form_found'      => true,
			'config'          => $config,
			'field_catalog'   => $this->collect_field_catalog( $fields ),
			'field_matches'   => $this->get_expected_field_matches( $fields ),
			'sync_fields'     => $this->get_sync_field_matches( $fields, $config ),
			'term_previews'   => $this->build_term_previews( $config ),
			'inventory_meta'  => $this->get_inventory_meta( (int) $form->id ),
		];
	}

	/**
	 * Build distinct configured forms from settings and type definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_form_configurations(): array {
		$configs         = [];
		$default_form_id = hlavas_terms_get_form_id();

		if ( $default_form_id > 0 ) {
			$configs[ $default_form_id ] = [
				'form_id'      => $default_form_id,
				'context'      => 'default',
				'title'        => 'Vychozi formular pluginu',
				'assignments'  => [
					[
						'label'                 => 'Vychozi formular pluginu',
						'qualification_type_id' => 0,
						'qualification_name'    => '',
						'qualification_code'    => '',
						'term_type'             => 'mixed',
					],
				],
			];
		}

		foreach ( $this->type_repo->get_all() as $type_item ) {
			$qualification_label = ! empty( $type_item->accreditation_number )
				? $type_item->accreditation_number . ' - ' . $type_item->name
				: $type_item->name;

			$course_form_id = (int) ( $type_item->course_form_id ?? 0 );
			$exam_form_id   = (int) ( $type_item->exam_form_id ?? 0 );

			if ( $course_form_id > 0 ) {
				if ( ! isset( $configs[ $course_form_id ] ) ) {
					$configs[ $course_form_id ] = [
						'form_id'     => $course_form_id,
						'context'     => 'typed',
						'title'       => '',
						'assignments' => [],
					];
				}

				$configs[ $course_form_id ]['assignments'][] = [
					'label'                 => $qualification_label,
					'qualification_type_id' => (int) $type_item->id,
					'qualification_name'    => (string) $type_item->name,
					'qualification_code'    => (string) ( $type_item->accreditation_number ?? '' ),
					'term_type'             => 'kurz',
				];
			}

			if ( $exam_form_id > 0 ) {
				if ( ! isset( $configs[ $exam_form_id ] ) ) {
					$configs[ $exam_form_id ] = [
						'form_id'     => $exam_form_id,
						'context'     => 'typed',
						'title'       => '',
						'assignments' => [],
					];
				}

				$configs[ $exam_form_id ]['assignments'][] = [
					'label'                 => $qualification_label,
					'qualification_type_id' => (int) $type_item->id,
					'qualification_name'    => (string) $type_item->name,
					'qualification_code'    => (string) ( $type_item->accreditation_number ?? '' ),
					'term_type'             => 'zkouska',
				];
			}
		}

		$configs = array_values( $configs );

		usort(
			$configs,
			static function ( array $left, array $right ): int {
				return (int) $left['form_id'] <=> (int) $right['form_id'];
			}
		);

		return $configs;
	}

	/**
	 * Get debug configuration.
	 *
	 * @param int|null $form_id Specific form ID.
	 * @return array<string, mixed>|null
	 */
	private function get_debug_configuration( ?int $form_id ): ?array {
		$configs = $this->get_form_configurations();

		if ( null === $form_id ) {
			return $configs[0] ?? null;
		}

		foreach ( $configs as $config ) {
			if ( (int) $config['form_id'] === $form_id ) {
				return $config;
			}
		}

		return null;
	}

	/**
	 * Build empty sync field statuses for missing form.
	 *
	 * @param array<string, mixed> $config Configuration.
	 * @return array<string, array<string, mixed>>
	 */
	private function build_empty_sync_fields( array $config ): array {
		$targets = $this->get_targets_for_configuration( $config );
		$output  = [];

		foreach ( $targets as $identifier => $target ) {
			$output[ $identifier ] = [
				'identifier'   => $identifier,
				'label'        => self::SYNC_FIELDS[ $identifier ]['label'],
				'term_type'    => $target['term_type'],
				'found'        => false,
				'field'        => null,
				'terms_count'  => count( $target['terms'] ),
			];
		}

		return $output;
	}

	/**
	 * Build preview terms for one form configuration.
	 *
	 * @param array<string, mixed> $config Form configuration.
	 * @return array<string, array<string, mixed>>
	 */
	private function build_term_previews( array $config ): array {
		$targets = $this->get_targets_for_configuration( $config );
		$output  = [
			'kurz'    => [
				'label' => 'Terminy kurzu',
				'terms' => [],
			],
			'zkouska' => [
				'label' => 'Terminy zkousek',
				'terms' => [],
			],
		];

		foreach ( $targets as $target ) {
			$term_type = (string) $target['term_type'];

			foreach ( $target['terms'] as $term ) {
				$output[ $term_type ]['terms'][] = [
					'id'                 => (int) $term->id,
					'term_key'           => (string) $term->term_key,
					'label'              => (string) $term->label,
					'title'              => ! empty( $term->title ) ? (string) $term->title : (string) $term->label,
					'capacity'           => (int) $term->capacity,
					'qualification_name' => (string) ( $term->qualification_name ?? '' ),
					'qualification_code' => (string) ( $term->qualification_code ?? '' ),
				];
			}
		}

		return $output;
	}

	/**
	 * Get sync field statuses from real form fields.
	 *
	 * @param array<int, array<string, mixed>> $fields Form fields.
	 * @param array<string, mixed>             $config Configuration.
	 * @return array<string, array<string, mixed>>
	 */
	private function get_sync_field_matches( array $fields, array $config ): array {
		$targets = $this->get_targets_for_configuration( $config );
		$output  = [];

		foreach ( $targets as $identifier => $target ) {
			$aliases    = self::SYNC_FIELDS[ $identifier ]['aliases'];
			$field_path = $this->find_field_path_by_aliases( $fields, $aliases );
			$field      = null !== $field_path ? $this->get_field_by_path( $fields, $field_path ) : null;

			$output[ $identifier ] = [
				'identifier'   => $identifier,
				'label'        => self::SYNC_FIELDS[ $identifier ]['label'],
				'term_type'    => (string) $target['term_type'],
				'found'        => is_array( $field ),
				'field'        => is_array( $field ) ? $this->summarize_field( $field ) : null,
				'terms_count'  => count( $target['terms'] ),
			];
		}

		return $output;
	}

	/**
	 * Get expected field matches for mapping diagnostics.
	 *
	 * @param array<int, array<string, mixed>> $fields Form fields.
	 * @return array<string, array<string, mixed>>
	 */
	private function get_expected_field_matches( array $fields ): array {
		$output = [];

		foreach ( self::EXPECTED_FIELDS as $identifier => $definition ) {
			$field_path = $this->find_field_path_by_aliases( $fields, (array) $definition['aliases'] );
			$field      = null !== $field_path ? $this->get_field_by_path( $fields, $field_path ) : null;

			$output[ $identifier ] = [
				'identifier'  => $identifier,
				'label'       => (string) $definition['label'],
				'description' => (string) $definition['description'],
				'found'       => is_array( $field ),
				'field'       => is_array( $field ) ? $this->summarize_field( $field ) : null,
			];
		}

		return $output;
	}

	/**
	 * Execute sync for one configured form.
	 *
	 * @param array<string, mixed> $config Configuration.
	 * @param string               $value_mode Value mode.
	 * @return array{updated: bool, details: array<int, string>}
	 */
	private function execute_for_configuration( array $config, string $value_mode ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$form_id = (int) $config['form_id'];
		$form    = $this->get_form_by_id( $form_id );

		if ( ! $form ) {
			return [
				'updated' => false,
				'details' => [
					'Formular ID ' . $form_id . ' nebyl nalezen.',
				],
			];
		}

		$fields   = $this->parse_form_fields( $form );
		$targets  = $this->get_targets_for_configuration( $config );
		$changed  = false;
		$details  = [];
		$synced_term_ids = [];

		foreach ( $targets as $identifier => $target ) {
			$field_path = $this->find_field_path_by_aliases( $fields, (array) self::SYNC_FIELDS[ $identifier ]['aliases'] );

			if ( null === $field_path ) {
				$details[] = 'Formular #' . $form_id . ': pole "' . $identifier . '" nebylo nalezeno.';
				continue;
			}

			$field = $this->get_field_by_path( $fields, $field_path );

			if ( ! is_array( $field ) ) {
				$details[] = 'Formular #' . $form_id . ': pole "' . $identifier . '" se nepodarilo nacist.';
				continue;
			}

			if ( ! isset( $field['settings'] ) || ! is_array( $field['settings'] ) ) {
				$field['settings'] = [];
			}

			$options = [];
			$stock   = [];

			foreach ( $target['terms'] as $term ) {
				$value = 'label' === $value_mode ? (string) $term->label : (string) $term->term_key;

				$options[] = [
					'label'      => (string) $term->label,
					'value'      => $value,
					'calc_value' => (string) (int) $term->capacity,
					'image'      => '',
				];

				$stock[] = [
					'value'    => $value,
					'quantity' => (int) $term->capacity,
				];

				$synced_term_ids[] = (int) $term->id;
			}

			$field['settings']['advanced_options']   = $options;
			$field['settings']['inventory_settings'] = [
				'enabled'           => 'simple',
				'stock_quantity'    => $stock,
				'stock_out_message' => 'Tenhle termin uz je bohuzel plny.',
				'hide_choice'       => false,
			];

			$this->set_field_by_path( $fields, $field_path, $field );

			$details[] = 'Formular #' . $form_id . ': pole "' . $identifier . '" synchronizovano (' . count( $options ) . ' terminu).';
			$changed   = true;
		}

		if ( ! $changed ) {
			return [
				'updated' => false,
				'details' => $details,
			];
		}

		$table_forms      = $wpdb->prefix . 'fluentform_forms';
		$form_fields_json = wp_json_encode( $fields );
		$updated          = $wpdb->update(
			$table_forms,
			[
				'form_fields' => $form_fields_json,
				'updated_at'  => current_time( 'mysql', true ),
			],
			[
				'id' => $form_id,
			],
			[
				'%s',
				'%s',
			],
			[
				'%d',
			]
		);

		if ( false === $updated ) {
			$details[] = 'Formular #' . $form_id . ': chyba pri ukladani - ' . $wpdb->last_error;

			return [
				'updated' => false,
				'details' => $details,
			];
		}

		$this->sync_inventory_meta( $form_id, $fields );
		hlavas_terms_mark_terms_synced( $synced_term_ids );
		hlavas_terms_mark_forms_synced( [ $form_id ] );

		return [
			'updated' => true,
			'details' => $details,
		];
	}

	/**
	 * Get target terms per sync field for one configuration.
	 *
	 * @param array<string, mixed> $config Configuration.
	 * @return array<string, array<string, mixed>>
	 */
	private function get_targets_for_configuration( array $config ): array {
		$assignments = is_array( $config['assignments'] ?? null ) ? $config['assignments'] : [];
		$targets     = [];

		foreach ( self::SYNC_FIELDS as $identifier => $definition ) {
			$term_type           = (string) $definition['term_type'];
			$qualification_ids   = [];
			$include_all_for_type = false;

			foreach ( $assignments as $assignment ) {
				$assignment_term_type = (string) ( $assignment['term_type'] ?? '' );

				if ( 'mixed' === $assignment_term_type ) {
					$include_all_for_type = true;
					break;
				}

				if ( $assignment_term_type === $term_type && ! empty( $assignment['qualification_type_id'] ) ) {
					$qualification_ids[] = (int) $assignment['qualification_type_id'];
				}
			}

			if ( ! $include_all_for_type && empty( $qualification_ids ) ) {
				continue;
			}

			$targets[ $identifier ] = [
				'identifier'         => $identifier,
				'term_type'          => $term_type,
				'qualification_ids'  => $include_all_for_type ? [] : array_values( array_unique( $qualification_ids ) ),
				'terms'              => $this->get_syncable_terms( $term_type, $include_all_for_type ? [] : $qualification_ids ),
			];
		}

		return $targets;
	}

	/**
	 * Get syncable terms filtered optionally by qualification type IDs.
	 *
	 * @param string         $term_type Term type.
	 * @param array<int, int> $qualification_ids Qualification IDs.
	 * @return array<int, object>
	 */
	private function get_syncable_terms( string $term_type, array $qualification_ids = [] ): array {
		$terms = $this->repo->get_syncable( $term_type );

		if ( empty( $qualification_ids ) ) {
			return $terms;
		}

		$qualification_ids = array_values( array_filter( array_map( 'intval', $qualification_ids ) ) );

		return array_values(
			array_filter(
				$terms,
				static function ( object $term ) use ( $qualification_ids ): bool {
					return in_array( (int) ( $term->qualification_type_id ?? 0 ), $qualification_ids, true );
				}
			)
		);
	}

	/**
	 * Get one Fluent Form by ID.
	 *
	 * @param int $form_id Form ID.
	 * @return object|null
	 */
	private function get_form_by_id( int $form_id ): ?object {
		global $wpdb;
		/** @var wpdb $wpdb */

		if ( $form_id <= 0 ) {
			return null;
		}

		$table = $wpdb->prefix . 'fluentform_forms';
		$form  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$form_id
			)
		);

		return $form instanceof \stdClass ? $form : null;
	}

	/**
	 * Parse form_fields JSON into array.
	 *
	 * @param object $form Fluent form row.
	 * @return array<int, array<string, mixed>>
	 */
	private function parse_form_fields( object $form ): array {
		$fields = json_decode( (string) $form->form_fields, true );

		return is_array( $fields ) ? $fields : [];
	}

	/**
	 * Find field path by list of aliases.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields array.
	 * @param array<int, string>               $aliases Supported aliases.
	 * @return array<int|string>|null
	 */
	private function find_field_path_by_aliases( array $fields, array $aliases ): ?array {
		return $this->search_field_path_recursive( $fields, $aliases, [] );
	}

	/**
	 * Recursively search one field path in arbitrary Fluent Forms structure.
	 *
	 * @param mixed              $node Current node.
	 * @param array<int, string> $aliases Supported aliases.
	 * @param array<int|string>  $path Current path.
	 * @return array<int|string>|null
	 */
	private function search_field_path_recursive( mixed $node, array $aliases, array $path ): ?array {
		if ( ! is_array( $node ) ) {
			return null;
		}

		if ( $this->is_field_node( $node ) && $this->field_matches_aliases( $node, $aliases ) ) {
			return $path;
		}

		foreach ( $node as $key => $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			$child_path = $path;
			$child_path[] = $key;

			$result = $this->search_field_path_recursive( $child, $aliases, $child_path );

			if ( null !== $result ) {
				return $result;
			}
		}

		return null;
	}

	/**
	 * Check whether field matches any alias.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param array<int, string>   $aliases Supported aliases.
	 * @return bool
	 */
	private function field_matches_aliases( array $field, array $aliases ): bool {
		$attributes        = $field['attributes'] ?? [];
		$settings          = $field['settings'] ?? [];
		$attribute_name    = is_array( $attributes ) ? (string) ( $attributes['name'] ?? '' ) : '';
		$admin_field_label = is_array( $settings ) ? (string) ( $settings['admin_field_label'] ?? '' ) : '';
		$label             = is_array( $settings ) ? (string) ( $settings['label'] ?? '' ) : '';
		$haystack          = [
			$this->normalize_string( $attribute_name ),
			$this->normalize_string( $admin_field_label ),
			$this->normalize_string( $label ),
		];

		foreach ( $aliases as $alias ) {
			$normalized_alias = $this->normalize_string( $alias );

			foreach ( $haystack as $candidate ) {
				if ( '' === $candidate || '' === $normalized_alias ) {
					continue;
				}

				if (
					$candidate === $normalized_alias ||
					str_contains( $candidate, $normalized_alias ) ||
					str_contains( $normalized_alias, $candidate )
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determine whether array looks like a field node.
	 *
	 * @param array<mixed> $node Node.
	 * @return bool
	 */
	private function is_field_node( array $node ): bool {
		return isset( $node['element'] ) || isset( $node['attributes'] ) || isset( $node['settings'] );
	}

	/**
	 * Get a field from array by path.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields array.
	 * @param array<int|string>                $path Path.
	 * @return array<string, mixed>|null
	 */
	private function get_field_by_path( array $fields, array $path ): ?array {
		$current = $fields;

		foreach ( $path as $segment ) {
			if ( ! isset( $current[ $segment ] ) ) {
				return null;
			}

			$current = $current[ $segment ];
		}

		return is_array( $current ) ? $current : null;
	}

	/**
	 * Set one field in array by path.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields array.
	 * @param array<int|string>                $path Path.
	 * @param array<string, mixed>             $value Field value.
	 * @return void
	 */
	private function set_field_by_path( array &$fields, array $path, array $value ): void {
		$current = &$fields;

		foreach ( $path as $segment ) {
			$current = &$current[ $segment ];
		}

		$current = $value;
	}

	/**
	 * Collect compact field map for UI.
	 *
	 * @param array<int, array<string, mixed>> $fields Source fields.
	 * @return array<int, array<string, string>>
	 */
	private function collect_field_catalog( array $fields ): array {
		$output = [];
		$this->walk_fields(
			$fields,
			static function ( array $field ) use ( &$output ): void {
				$output[] = [
					'element'           => (string) ( $field['element'] ?? 'unknown' ),
					'name'              => (string) ( is_array( $field['attributes'] ?? null ) ? ( $field['attributes']['name'] ?? '' ) : '' ),
					'admin_field_label' => (string) ( is_array( $field['settings'] ?? null ) ? ( $field['settings']['admin_field_label'] ?? '' ) : '' ),
					'label'             => (string) ( is_array( $field['settings'] ?? null ) ? ( $field['settings']['label'] ?? '' ) : '' ),
				];
			}
		);

		return $output;
	}

	/**
	 * Walk fields recursively.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields.
	 * @param callable                         $callback Callback.
	 * @return void
	 */
	private function walk_fields( array $fields, callable $callback ): void {
		$this->walk_fields_recursive( $fields, $callback );
	}

	/**
	 * Recursively walk all field-like nodes in arbitrary structure.
	 *
	 * @param mixed    $node Current node.
	 * @param callable $callback Callback.
	 * @return void
	 */
	private function walk_fields_recursive( mixed $node, callable $callback ): void {
		if ( ! is_array( $node ) ) {
			return;
		}

		if ( $this->is_field_node( $node ) ) {
			$callback( $node );
		}

		foreach ( $node as $child ) {
			if ( is_array( $child ) ) {
				$this->walk_fields_recursive( $child, $callback );
			}
		}
	}

	/**
	 * Summarize field for UI.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return array<string, string>
	 */
	private function summarize_field( array $field ): array {
		$attributes = is_array( $field['attributes'] ?? null ) ? $field['attributes'] : [];
		$settings   = is_array( $field['settings'] ?? null ) ? $field['settings'] : [];

		return [
			'element'           => (string) ( $field['element'] ?? 'unknown' ),
			'name'              => (string) ( $attributes['name'] ?? '' ),
			'admin_field_label' => (string) ( $settings['admin_field_label'] ?? '' ),
			'label'             => (string) ( $settings['label'] ?? '' ),
		];
	}

	/**
	 * Read inventory meta.
	 *
	 * @param int $form_id Form ID.
	 * @return array<string, mixed>|null
	 */
	private function get_inventory_meta( int $form_id ): ?array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$meta_table = $wpdb->prefix . 'fluentform_form_meta';
		$row        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$meta_table} WHERE form_id = %d AND meta_key = %s",
				$form_id,
				'inventory'
			)
		);

		if ( $row && ! empty( $row->value ) ) {
			$value = json_decode( (string) $row->value, true );

			return is_array( $value ) ? $value : null;
		}

		return null;
	}

	/**
	 * Sync inventory meta placeholder.
	 *
	 * @param int                              $form_id Form ID.
	 * @param array<int, array<string, mixed>> $fields Fields array.
	 * @return void
	 */
	private function sync_inventory_meta( int $form_id, array $fields ): void {
		unset( $form_id, $fields );
		// Fluent Forms Pro currently uses field-level inventory settings in this setup.
	}

	/**
	 * Extract simple found/not-found map from sync fields.
	 *
	 * @param array<string, array<string, mixed>> $sync_fields Sync field list.
	 * @return array<string, bool>
	 */
	private function extract_field_status_map( array $sync_fields ): array {
		$output = [];

		foreach ( $sync_fields as $identifier => $field ) {
			$output[ $identifier ] = ! empty( $field['found'] );
		}

		return $output;
	}

	/**
	 * Normalize string for comparisons.
	 *
	 * @param string $value Input string.
	 * @return string
	 */
	private function normalize_string( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = preg_replace( '/\s+/u', ' ', trim( $value ) );

		return mb_strtolower( (string) $value );
	}
}
