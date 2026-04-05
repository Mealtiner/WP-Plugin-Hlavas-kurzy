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

if ( ! class_exists( 'Hlavas_Terms_Availability_Service', false ) ) {
	require_once __DIR__ . '/class-availability-service.php';
}

if ( ! class_exists( 'Hlavas_Terms_Participant_Service', false ) ) {
	require_once __DIR__ . '/class-participant-service.php';
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
	 * Availability service — used to compute remaining capacity during sync.
	 *
	 * @var Hlavas_Terms_Availability_Service
	 */
	private Hlavas_Terms_Availability_Service $availability;

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
	 * Legacy Fluent Forms field names mapped to modern HLAVAS identifiers.
	 *
	 * Values are only added into existing entries, never destructively replacing
	 * the original legacy keys.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const LEGACY_FIELD_MAP = [
		'typ_prihlasky'    => [ 'typ_prihlasky', 'input_radio' ],
		'termin_kurz'      => [ 'termin_kurz', 'dropdown' ],
		'termin_zkouska'   => [ 'termin_zkouska', 'dropdown_1' ],
		'Name'             => [ 'Name', 'names' ],
		'narozeni'         => [ 'narozeni', 'datetime' ],
		'Address'          => [ 'Address', 'address_1' ],
		'ucastnik_email'   => [ 'ucastnik_email', 'email' ],
		'ucastnik_telefon' => [ 'ucastnik_telefon', 'phone' ],
		'typ_platby'       => [ 'typ_platby', 'input_radio_1' ],
		'nazev_organizace' => [ 'nazev_organizace', 'input_text' ],
		'ico_organizace'   => [ 'ico_organizace', 'numeric_field' ],
		'fakturacni_email' => [ 'fakturacni_email', 'email_1' ],
	];

	/**
	 * Constructor.
	 *
	 * @param Hlavas_Terms_Repository|null                    $repo Repository instance.
	 * @param Hlavas_Terms_Qualification_Type_Repository|null $type_repo Type repository instance.
	 */
	public function __construct(
		?Hlavas_Terms_Repository $repo = null,
		?Hlavas_Terms_Qualification_Type_Repository $type_repo = null,
		?Hlavas_Terms_Availability_Service $availability = null
	) {
		$this->repo         = $repo ?? new Hlavas_Terms_Repository();
		$this->type_repo    = $type_repo ?? new Hlavas_Terms_Qualification_Type_Repository();
		$this->availability = $availability ?? new Hlavas_Terms_Availability_Service( $this->repo );
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
			'field_matches'     => $this->get_expected_field_matches( $fields, (int) $form->id ),
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
			$result   = $this->execute_for_configuration_v2( $config, $value_mode );
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
	 * Execute synchronization only for selected term IDs.
	 *
	 * Selected-term sync updates only the chosen terms inside the affected
	 * Fluent Forms fields and leaves the rest of the field options untouched.
	 *
	 * @param array<int, int> $term_ids Selected plugin term IDs.
	 * @param string          $value_mode Value mode: term_key or label.
	 * @return array{success: bool, message: string, details: array<int, string>}
	 */
	public function execute_selected_terms( array $term_ids, string $value_mode = 'term_key' ): array {
		$term_ids = array_values( array_unique( array_filter( array_map( 'intval', $term_ids ) ) ) );

		if ( empty( $term_ids ) ) {
			return [
				'success' => false,
				'message' => 'Nebyly vybrany zadne terminy pro synchronizaci.',
				'details' => [],
			];
		}

		$configs = $this->get_form_configurations();

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
			$result = $this->execute_for_configuration_v2( $config, $value_mode, $term_ids, true );

			if ( ! empty( $result['skipped'] ) ) {
				continue;
			}

			$details  = array_merge( $details, $result['details'] );
			$updated += ! empty( $result['updated'] ) ? 1 : 0;
		}

		if ( $updated <= 0 ) {
			return [
				'success' => false,
				'message' => 'Vybrane terminy se nepodarilo propsat do zadneho formularu.',
				'details' => $details,
			];
		}

		return [
			'success' => true,
			'message' => 'Synchronizace probehla pro vybrane terminy.',
			'details' => $details,
		];
	}

	/**
	 * Import current term choices from configured Fluent Forms into plugin terms table.
	 *
	 * @param bool $replace_existing Whether to clear existing plugin terms first.
	 * @return array{success: bool, message: string, details: array<int, string>, created: int, updated: int, skipped: int}
	 */
	public function import_into_plugin( bool $replace_existing = false ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$configs = $this->get_form_configurations();

		if ( empty( $configs ) ) {
			return [
				'success' => false,
				'message' => 'Neni nastaven zadny formular pro import z Fluent Forms.',
				'details' => [],
				'created' => 0,
				'updated' => 0,
				'skipped' => 0,
			];
		}

		if ( $replace_existing ) {
			$wpdb->query( 'DELETE FROM ' . hlavas_terms_get_table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$created         = 0;
		$updated         = 0;
		$skipped         = 0;
		$details         = [];
		$processed_keys  = [];

		foreach ( $configs as $config ) {
			$form_id = (int) $config['form_id'];
			$form    = $this->get_form_by_id( $form_id );

			if ( ! $form ) {
				$details[] = 'Formular #' . $form_id . ' nebyl nalezen pro import.';
				continue;
			}

			$fields  = $this->parse_form_fields( $form );
			$targets = $this->get_import_targets_for_configuration( $config );

			foreach ( $targets as $identifier => $target ) {
				$field_path = $this->find_field_path_by_aliases(
					$fields,
					$this->get_aliases_for_form( $form_id, $identifier, (array) self::SYNC_FIELDS[ $identifier ]['aliases'] )
				);

				if ( null === $field_path ) {
					$details[] = 'Formular #' . $form_id . ': pole "' . $identifier . '" nebylo nalezeno pro import.';
					continue;
				}

				$field = $this->get_field_by_path( $fields, $field_path );

				if ( ! is_array( $field ) ) {
					$details[] = 'Formular #' . $form_id . ': pole "' . $identifier . '" se nepodarilo nacist pro import.';
					continue;
				}

				$options = $field['settings']['advanced_options'] ?? [];

				if ( ! is_array( $options ) || empty( $options ) ) {
					$details[] = 'Formular #' . $form_id . ': pole "' . $identifier . '" neobsahuje zadne volby terminu.';
					continue;
				}

				$qualification_type_id = $this->resolve_import_qualification_type_id( $config, (string) $target['term_type'] );

				foreach ( array_values( $options ) as $index => $option ) {
					if ( ! is_array( $option ) ) {
						$skipped++;
						continue;
					}

					$term_data = $this->build_import_term_data(
						$option,
						(string) $target['term_type'],
						$qualification_type_id,
						( $index + 1 ) * 10
					);

					if ( null === $term_data ) {
						$skipped++;
						continue;
					}

					$term_key = (string) $term_data['term_key'];

					if ( isset( $processed_keys[ $term_key ] ) ) {
						$skipped++;
						continue;
					}

					$processed_keys[ $term_key ] = true;
					$existing                    = $this->repo->find_by_key( $term_key );

					if ( $existing ) {
						$update_data = $term_data;

						if ( ! empty( $existing->title ) ) {
							unset( $update_data['title'] );
						}

						unset( $update_data['notes'] );
						$this->repo->update( (int) $existing->id, $update_data );
						$updated++;
						continue;
					}

					$inserted_id = $this->repo->insert( $term_data );

					if ( false !== $inserted_id ) {
						$created++;
					} else {
						$skipped++;
					}
				}
			}
		}

		$success = $created > 0 || $updated > 0;
		$message = $success
			? 'Import z Fluent Forms do pluginu byl dokoncen.'
			: 'Import z Fluent Forms nenasel zadne pouzitelne terminy.';

		return [
			'success' => $success,
			'message' => $message,
			'details' => $details,
			'created' => $created,
			'updated' => $updated,
			'skipped' => $skipped,
		];
	}

	/**
	 * Force a fresh scan of participants and capacities from Fluent Forms.
	 *
	 * The plugin computes these values on the fly, so rebuild means
	 * re-reading current FF submissions and returning a summary.
	 *
	 * @return array{success: bool, message: string, details: array<int, string>}
	 */
	public function rebuild_participants_and_capacities(): array {
		$participants_service = new Hlavas_Terms_Participant_Service( $this->repo, $this->type_repo );
		$participants         = $participants_service->get_participants();
		$availability_report  = $this->availability->get_availability_report();
		$total_participants   = count( $participants );
		$legacy_count         = 0;
		$new_count            = 0;
		$unmatched_count      = 0;
		$active_terms         = 0;
		$total_enrolled       = 0;

		foreach ( $participants as $participant ) {
			if ( 'new' === (string) ( $participant['source_format'] ?? '' ) ) {
				$new_count++;
			} else {
				$legacy_count++;
			}

			if ( ! empty( $participant['is_unmatched'] ) ) {
				$unmatched_count++;
			}
		}

		foreach ( $availability_report as $item ) {
			$enrolled = (int) ( $item['enrolled'] ?? 0 );

			if ( $enrolled > 0 ) {
				$active_terms++;
				$total_enrolled += $enrolled;
			}
		}

		return [
			'success' => true,
			'message' => 'Rebuild účastníků a kapacit byl dokončen.',
			'details' => [
				'Načteno účastníků: ' . $total_participants,
				'Nový formát: ' . $new_count,
				'Legacy formát: ' . $legacy_count,
				'Nepárované historické záznamy: ' . $unmatched_count,
				'Termíny s obsazeností: ' . $active_terms,
				'Celkem započtených registrací do kapacit: ' . $total_enrolled,
			],
		];
	}

	/**
	 * Non-destructively augment legacy Fluent Forms entries with modern HLAVAS keys.
	 *
	 * Original legacy field names remain untouched. The method only appends
	 * the new field names and, where possible, converts term values to term_key.
	 *
	 * @param int|null $form_id Optional specific form ID.
	 * @return array{success: bool, message: string, details: array<int, string>, scanned: int, updated: int, converted: int, skipped: int}
	 */
	public function migrate_legacy_entries_to_new_format( ?int $form_id = null ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$submission_table = $wpdb->prefix . 'fluentform_submissions';

		if ( ! $this->table_exists( $submission_table ) ) {
			return [
				'success'   => false,
				'message'   => 'Tabulka Fluent Forms submissions nebyla nalezena.',
				'details'   => [],
				'scanned'   => 0,
				'updated'   => 0,
				'converted' => 0,
				'skipped'   => 0,
			];
		}

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
				'success'   => false,
				'message'   => 'Není nastaven žádný formulář pro migraci legacy záznamů.',
				'details'   => [],
				'scanned'   => 0,
				'updated'   => 0,
				'converted' => 0,
				'skipped'   => 0,
			];
		}

		$has_user_inputs_column = $this->table_has_column( $submission_table, 'user_inputs' );
		$form_ids               = array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( array $config ): int => (int) ( $config['form_id'] ?? 0 ),
						$configs
					)
				)
			)
		);
		$placeholders           = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
		$select_columns         = $has_user_inputs_column ? 'id, form_id, response, user_inputs' : 'id, form_id, response';
		$sql                    = $wpdb->prepare(
			"SELECT {$select_columns}
			FROM {$submission_table}
			WHERE form_id IN ({$placeholders}) AND status != 'trashed'
			ORDER BY id DESC",
			...$form_ids
		);
		$rows                   = $wpdb->get_results( $sql );

		if ( ! is_array( $rows ) ) {
			return [
				'success'   => false,
				'message'   => 'Nepodařilo se načíst záznamy z Fluent Forms.',
				'details'   => [],
				'scanned'   => 0,
				'updated'   => 0,
				'converted' => 0,
				'skipped'   => 0,
			];
		}

		$scanned         = 0;
		$updated         = 0;
		$converted       = 0;
		$skipped         = 0;
		$details         = [];
		$legacy_detected = 0;

		foreach ( $rows as $row ) {
			$scanned++;
			$response = json_decode( (string) ( $row->response ?? '' ), true );

			if ( ! is_array( $response ) ) {
				$skipped++;
				continue;
			}

			$user_inputs = [];
			if ( $has_user_inputs_column ) {
				$raw_user_inputs = json_decode( (string) ( $row->user_inputs ?? '' ), true );
				$user_inputs     = is_array( $raw_user_inputs ) ? $raw_user_inputs : [];
			}

			$response_result    = $this->normalize_submission_container_to_modern( $response, (int) $row->form_id );
			$user_inputs_result = $this->normalize_submission_container_to_modern( $user_inputs, (int) $row->form_id );

			if ( ! empty( $response_result['legacy_detected'] ) || ! empty( $user_inputs_result['legacy_detected'] ) ) {
				$legacy_detected++;
			}

			$converted += (int) ( $response_result['converted'] ?? 0 ) + (int) ( $user_inputs_result['converted'] ?? 0 );

			if ( empty( $response_result['changed'] ) && empty( $user_inputs_result['changed'] ) ) {
				$this->sync_submission_term_details(
					(int) $row->id,
					(int) $row->form_id,
					$response_result['container'],
					$user_inputs_result['container']
				);

				$skipped++;
				continue;
			}

			$update_data   = [
				'response' => wp_json_encode( $response_result['container'], JSON_UNESCAPED_UNICODE ),
			];
			$update_format = [ '%s' ];

			if ( $has_user_inputs_column ) {
				$update_data['user_inputs'] = wp_json_encode( $user_inputs_result['container'], JSON_UNESCAPED_UNICODE );
				$update_format[]            = '%s';
			}

			$update_result = $wpdb->update(
				$submission_table,
				$update_data,
				[ 'id' => (int) $row->id ],
				$update_format,
				[ '%d' ]
			);

			if ( false === $update_result ) {
				$details[] = 'Submission #' . (int) $row->id . ' se nepodařilo aktualizovat.';
				continue;
			}

			$this->sync_submission_term_details(
				(int) $row->id,
				(int) $row->form_id,
				$response_result['container'],
				$user_inputs_result['container']
			);

			$updated++;
		}

		$details[] = 'Prohledáno záznamů: ' . $scanned;
		$details[] = 'Legacy záznamů k úpravě: ' . $legacy_detected;
		$details[] = 'Aktualizováno záznamů: ' . $updated;
		$details[] = 'Převedených hodnot termínů: ' . $converted;
		$details[] = 'Přeskočeno bez změny: ' . $skipped;
		$details[] = 'Migrace je ne-destruktivní: původní legacy pole zůstala zachována.';

		return [
			'success'   => $updated > 0 || $legacy_detected > 0,
			'message'   => $updated > 0
				? 'Legacy záznamy byly doplněny o nový HLAVAS formát.'
				: 'Migrace proběhla, ale nebyly potřeba žádné změny.',
			'details'   => $details,
			'scanned'   => $scanned,
			'updated'   => $updated,
			'converted' => $converted,
			'skipped'   => $skipped,
		];
	}

	/**
	 * Get all form IDs whose synchronized targets currently include the term.
	 *
	 * @param int $term_id Term ID.
	 * @return array<int, int>
	 */
	/**
	 * Rewrite one Fluent Forms submission term value to match a selected plugin term.
	 *
	 * @param int $submission_id Fluent Forms submission ID.
	 * @param int $term_id Plugin term ID.
	 * @return array{success: bool, message: string}
	 */
	public function sync_submission_term_selection( int $submission_id, int $term_id ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$submission_table = $wpdb->prefix . 'fluentform_submissions';
		$term             = $this->repo->find( $term_id );

		if ( $submission_id <= 0 || ! $term ) {
			return [
				'success' => false,
				'message' => 'Submission nebo termín nebyl nalezen.',
			];
		}

		if ( ! $this->table_exists( $submission_table ) ) {
			return [
				'success' => false,
				'message' => 'Tabulka Fluent Forms submissions nebyla nalezena.',
			];
		}

		$has_user_inputs_column = $this->table_has_column( $submission_table, 'user_inputs' );
		$select_columns         = $has_user_inputs_column ? 'id, form_id, response, user_inputs' : 'id, form_id, response';
		$row                    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT {$select_columns} FROM {$submission_table} WHERE id = %d",
				$submission_id
			)
		);

		if ( ! $row ) {
			return [
				'success' => false,
				'message' => 'Submission nebyl nalezen.',
			];
		}

		$response = json_decode( (string) ( $row->response ?? '' ), true );
		$response = is_array( $response ) ? $response : [];

		$user_inputs = [];
		if ( $has_user_inputs_column ) {
			$decoded_user_inputs = json_decode( (string) ( $row->user_inputs ?? '' ), true );
			$user_inputs         = is_array( $decoded_user_inputs ) ? $decoded_user_inputs : [];
		}

		$response    = $this->apply_term_to_submission_container( $response, $term, (int) $row->form_id );
		$user_inputs = $this->apply_term_to_submission_container( $user_inputs, $term, (int) $row->form_id );

		$update_data   = [
			'response' => wp_json_encode( $response, JSON_UNESCAPED_UNICODE ),
		];
		$update_format = [ '%s' ];

		if ( $has_user_inputs_column ) {
			$update_data['user_inputs'] = wp_json_encode( $user_inputs, JSON_UNESCAPED_UNICODE );
			$update_format[]            = '%s';
		}

		$updated = $wpdb->update(
			$submission_table,
			$update_data,
			[ 'id' => $submission_id ],
			$update_format,
			[ '%d' ]
		);

		if ( false === $updated ) {
			return [
				'success' => false,
				'message' => 'Nepodařilo se uložit změnu do Fluent Forms submissions.',
			];
		}

		$this->sync_submission_term_details( $submission_id, (int) $row->form_id, $response, $user_inputs );

		return [
			'success' => true,
			'message' => 'Submission byl upraven podle nového termínu.',
		];
	}

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

		$fields = $this->parse_form_fields( $form );

		if ( isset( self::SYNC_FIELDS[ $identifier ] ) ) {
			$aliases = $this->get_aliases_for_form( (int) $config['form_id'], $identifier, (array) self::SYNC_FIELDS[ $identifier ]['aliases'] );
		} elseif ( isset( self::EXPECTED_FIELDS[ $identifier ] ) ) {
			$aliases = $this->get_aliases_for_form( (int) $config['form_id'], $identifier, (array) self::EXPECTED_FIELDS[ $identifier ]['aliases'] );
		} else {
			$aliases = [ $identifier ];
		}

		$field_path = $this->find_field_path_by_aliases( $fields, $aliases );

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
			'field_matches'   => $this->get_expected_field_matches( $fields, (int) $form->id ),
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
		$form_id = (int) ( $config['form_id'] ?? 0 );

		foreach ( $targets as $identifier => $target ) {
			$output[ $identifier ] = [
				'identifier'      => $identifier,
				'label'           => self::SYNC_FIELDS[ $identifier ]['label'],
				'term_type'       => $target['term_type'],
				'found'           => false,
				'field'           => null,
				'terms_count'     => count( $target['terms'] ),
				'manual_mapping'  => $this->get_manual_mapping_value( $form_id, $identifier ),
				'configured_keys' => $this->get_aliases_for_form( $form_id, $identifier, (array) self::SYNC_FIELDS[ $identifier ]['aliases'] ),
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
		$form_id = (int) ( $config['form_id'] ?? 0 );

		foreach ( $targets as $identifier => $target ) {
			$aliases    = $this->get_aliases_for_form( $form_id, $identifier, (array) self::SYNC_FIELDS[ $identifier ]['aliases'] );
			$field_path = $this->find_field_path_by_aliases( $fields, $aliases );
			$field      = null !== $field_path ? $this->get_field_by_path( $fields, $field_path ) : null;

			$output[ $identifier ] = [
				'identifier'      => $identifier,
				'label'           => self::SYNC_FIELDS[ $identifier ]['label'],
				'term_type'       => (string) $target['term_type'],
				'found'           => is_array( $field ),
				'field'           => is_array( $field ) ? $this->summarize_field( $field ) : null,
				'terms_count'     => count( $target['terms'] ),
				'manual_mapping'  => $this->get_manual_mapping_value( $form_id, $identifier ),
				'configured_keys' => $aliases,
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
	private function get_expected_field_matches( array $fields, int $form_id ): array {
		$output = [];

		foreach ( self::EXPECTED_FIELDS as $identifier => $definition ) {
			$aliases    = $this->get_aliases_for_form( $form_id, $identifier, (array) $definition['aliases'] );
			$field_path = $this->find_field_path_by_aliases( $fields, $aliases );
			$field      = null !== $field_path ? $this->get_field_by_path( $fields, $field_path ) : null;

			$output[ $identifier ] = [
				'identifier'      => $identifier,
				'label'           => (string) $definition['label'],
				'description'     => (string) $definition['description'],
				'found'           => is_array( $field ),
				'field'           => is_array( $field ) ? $this->summarize_field( $field ) : null,
				'manual_mapping'  => $this->get_manual_mapping_value( $form_id, $identifier ),
				'configured_keys' => $aliases,
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
	private function execute_for_configuration( array $config, string $value_mode, array $selected_term_ids = [], bool $selected_only = false ): array {
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
				'skipped' => false,
			];
		}

		$fields          = $this->parse_form_fields( $form );
		$targets         = $this->get_targets_for_configuration( $config, $selected_term_ids, $selected_only );
		$changed         = false;
		$details         = [];
		$synced_term_ids = [];

		if ( $selected_only && empty( $targets ) ) {
			return [
				'updated' => false,
				'details' => [],
				'skipped' => true,
			];
		}

		if ( $selected_only ) {
			foreach ( $targets as $identifier => $target ) {
				$field_path = $this->find_field_path_by_aliases(
					$fields,
					$this->get_aliases_for_form( $form_id, $identifier, (array) self::SYNC_FIELDS[ $identifier ]['aliases'] )
				);

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

				$existing_options = is_array( $field['settings']['advanced_options'] ?? null )
					? $field['settings']['advanced_options']
					: [];
				$public_terms     = array_values(
					array_filter(
						(array) $target['terms'],
						fn( object $term ): bool => $this->is_term_publicly_syncable( $term )
					)
				);
				$selected_options = [];

				foreach ( $target['terms'] as $term ) {
					$synced_term_ids[] = (int) $term->id;
				}

				foreach ( $public_terms as $term ) {
					$selected_options[] = $this->build_term_option( $term, $value_mode );
				}

				$field['settings']['advanced_options'] = $this->merge_selected_term_options(
					$existing_options,
					(array) $target['terms'],
					$public_terms,
					$selected_options
				);
				$field['settings']['inventory_type']      = 'simple';
				$field['settings']['inventory_stockout_message']  = (string) ( $field['settings']['inventory_stockout_message'] ?? 'Tenhle termin uz je bohuzel plny.' );
				$field['settings']['hide_choice_when_stockout']   = (string) ( $field['settings']['hide_choice_when_stockout'] ?? 'no' );
				$field['settings']['hide_input_when_stockout']    = (string) ( $field['settings']['hide_input_when_stockout'] ?? 'no' );
				$field['settings']['disable_input_when_stockout'] = (string) ( $field['settings']['disable_input_when_stockout'] ?? 'no' );
				$field['settings']['show_stock']                  = (string) ( $field['settings']['show_stock'] ?? 'yes' );
				$field['settings']['simple_inventory']            = (string) ( $field['settings']['simple_inventory'] ?? '' );
				$field['settings']['stock_quantity_label']        = (string) ( $field['settings']['stock_quantity_label'] ?? ' - {remaining_quantity} available' );
				unset( $field['settings']['inventory_settings'] );

				$this->set_field_by_path( $fields, $field_path, $field );

				$details[] = 'Formular #' . $form_id . ': pole "' . $identifier . '" synchronizovano (' . count( $public_terms ) . ' zobrazeno / ' . count( $target['terms'] ) . ' vybranych zpracovano).';
				$changed   = true;
			}
		} else {

		foreach ( $targets as $identifier => $target ) {
			$field_path = $this->find_field_path_by_aliases(
				$fields,
				$this->get_aliases_for_form( $form_id, $identifier, (array) self::SYNC_FIELDS[ $identifier ]['aliases'] )
			);

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

			foreach ( $target['terms'] as $term ) {
				$value    = 'label' === $value_mode ? (string) $term->label : (string) $term->term_key;
				$capacity = (int) $term->capacity;

				// Use remaining spots (capacity − already enrolled) so that
				// synchronization never resets a partially-filled inventory back
				// to the full capacity in Fluent Forms.
				$options[] = [
					'label'      => (string) $term->label,
					'value'      => $value,
					'calc_value' => (string) $capacity,
					'image'      => '',
					'quantity'   => $capacity,
				];

				$synced_term_ids[] = (int) $term->id;
			}

			$field['settings']['advanced_options']            = $options;
			$field['settings']['inventory_type']              = 'simple';
			$field['settings']['inventory_stockout_message']  = (string) ( $field['settings']['inventory_stockout_message'] ?? 'Tenhle termin uz je bohuzel plny.' );
			$field['settings']['hide_choice_when_stockout']   = (string) ( $field['settings']['hide_choice_when_stockout'] ?? 'no' );
			$field['settings']['hide_input_when_stockout']    = (string) ( $field['settings']['hide_input_when_stockout'] ?? 'no' );
			$field['settings']['disable_input_when_stockout'] = (string) ( $field['settings']['disable_input_when_stockout'] ?? 'no' );
			$field['settings']['show_stock']                  = (string) ( $field['settings']['show_stock'] ?? 'yes' );
			$field['settings']['simple_inventory']            = (string) ( $field['settings']['simple_inventory'] ?? '' );
			$field['settings']['stock_quantity_label']        = (string) ( $field['settings']['stock_quantity_label'] ?? ' - {remaining_quantity} available' );
			unset( $field['settings']['inventory_settings'] );

			$this->set_field_by_path( $fields, $field_path, $field );

			$details[] = 'Formular #' . $form_id . ': pole "' . $identifier . '" synchronizovano (' . count( $options ) . ' terminu).';
			$changed   = true;
		}
		}

		if ( ! $changed ) {
			return [
				'updated' => false,
				'details' => $details,
				'skipped' => false,
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
				'skipped' => false,
			];
		}

		$this->sync_inventory_meta( $form_id, $fields );
		hlavas_terms_mark_terms_synced( $synced_term_ids );
		hlavas_terms_mark_forms_synced( [ $form_id ] );

		return [
			'updated' => true,
			'details' => $details,
			'skipped' => false,
		];
	}

	/**
	 * Updated sync executor.
	 *
	 * Public sync exports only syncable terms. Selected sync can also remove
	 * one hidden / archived / expired term from an already synchronized field.
	 *
	 * @param array<string, mixed> $config Configuration.
	 * @param string               $value_mode Value mode.
	 * @param array<int, int>      $selected_term_ids Selected term IDs.
	 * @param bool                 $selected_only Whether to sync only selected terms.
	 * @return array{updated: bool, details: array<int, string>, skipped: bool}
	 */
	private function execute_for_configuration_v2( array $config, string $value_mode, array $selected_term_ids = [], bool $selected_only = false ): array {
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
				'skipped' => false,
			];
		}

		$fields          = $this->parse_form_fields( $form );
		$targets         = $this->get_targets_for_configuration( $config, $selected_term_ids, $selected_only );
		$changed         = false;
		$details         = [];
		$synced_term_ids = [];

		if ( $selected_only && empty( $targets ) ) {
			return [
				'updated' => false,
				'details' => [],
				'skipped' => true,
			];
		}

		foreach ( $targets as $identifier => $target ) {
			$field_path = $this->find_field_path_by_aliases(
				$fields,
				$this->get_aliases_for_form( $form_id, $identifier, (array) self::SYNC_FIELDS[ $identifier ]['aliases'] )
			);

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

			if ( $selected_only ) {
				$selected_terms = array_values( array_filter( (array) ( $target['terms'] ?? [] ) ) );

				if ( empty( $selected_terms ) ) {
					continue;
				}

				$public_terms = array_values(
					array_filter(
						$selected_terms,
						fn( object $term ): bool => $this->is_term_publicly_syncable( $term )
					)
				);

				foreach ( $selected_terms as $term ) {
					$synced_term_ids[] = (int) $term->id;
				}

				$field['settings']['advanced_options'] = $this->sync_selected_term_options(
					is_array( $field['settings']['advanced_options'] ?? null ) ? $field['settings']['advanced_options'] : [],
					$selected_terms,
					$public_terms,
					$value_mode
				);

				$details[] = 'Formular #' . $form_id . ': pole "' . $identifier . '" synchronizovano (' . count( $public_terms ) . ' zobrazeno / ' . count( $selected_terms ) . ' vybranych zpracovano).';
			} else {
				$options = [];

				foreach ( (array) ( $target['terms'] ?? [] ) as $term ) {
					$options[]         = $this->build_public_term_option( $term, $value_mode );
					$synced_term_ids[] = (int) $term->id;
				}

				$field['settings']['advanced_options'] = $options;
				$details[]                             = 'Formular #' . $form_id . ': pole "' . $identifier . '" synchronizovano (' . count( $options ) . ' verejnych terminu).';
			}

			$field['settings']['inventory_type']              = 'simple';
			$field['settings']['inventory_stockout_message']  = (string) ( $field['settings']['inventory_stockout_message'] ?? 'Tenhle termin uz je bohuzel plny.' );
			$field['settings']['hide_choice_when_stockout']   = (string) ( $field['settings']['hide_choice_when_stockout'] ?? 'no' );
			$field['settings']['hide_input_when_stockout']    = (string) ( $field['settings']['hide_input_when_stockout'] ?? 'no' );
			$field['settings']['disable_input_when_stockout'] = (string) ( $field['settings']['disable_input_when_stockout'] ?? 'no' );
			$field['settings']['show_stock']                  = (string) ( $field['settings']['show_stock'] ?? 'yes' );
			$field['settings']['simple_inventory']            = (string) ( $field['settings']['simple_inventory'] ?? '' );
			$field['settings']['stock_quantity_label']        = (string) ( $field['settings']['stock_quantity_label'] ?? ' - {remaining_quantity} available' );
			unset( $field['settings']['inventory_settings'] );

			$this->set_field_by_path( $fields, $field_path, $field );
			$changed = true;
		}

		if ( ! $changed ) {
			return [
				'updated' => false,
				'details' => $details,
				'skipped' => false,
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
				'skipped' => false,
			];
		}

		$this->sync_inventory_meta( $form_id, $fields );
		hlavas_terms_mark_terms_synced( $synced_term_ids );
		hlavas_terms_mark_forms_synced( [ $form_id ] );

		return [
			'updated' => true,
			'details' => $details,
			'skipped' => false,
		];
	}

	/**
	 * Get target terms per sync field for one configuration.
	 *
	 * @param array<string, mixed> $config Configuration.
	 * @return array<string, array<string, mixed>>
	 */
	private function get_targets_for_configuration( array $config, array $selected_term_ids = [], bool $selected_only = false ): array {
		$assignments = is_array( $config['assignments'] ?? null ) ? $config['assignments'] : [];
		$targets     = [];
		$selected_term_ids = array_values( array_unique( array_filter( array_map( 'intval', $selected_term_ids ) ) ) );

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

			$terms = $selected_only
				? $this->get_selected_terms( $term_type, $include_all_for_type ? [] : $qualification_ids, $selected_term_ids )
				: $this->get_public_sync_terms( $term_type, $include_all_for_type ? [] : $qualification_ids );

			if ( $selected_only && empty( $terms ) ) {
				continue;
			}

			$targets[ $identifier ] = [
				'identifier'         => $identifier,
				'term_type'          => $term_type,
				'qualification_ids'  => $include_all_for_type ? [] : array_values( array_unique( $qualification_ids ) ),
				'terms'              => $terms,
			];
		}

		return $targets;
	}

	/**
	 * Get import targets per configuration without requiring existing plugin terms.
	 *
	 * @param array<string, mixed> $config Configuration.
	 * @return array<string, array<string, string>>
	 */
	private function get_import_targets_for_configuration( array $config ): array {
		$assignments = is_array( $config['assignments'] ?? null ) ? $config['assignments'] : [];
		$targets     = [];

		foreach ( self::SYNC_FIELDS as $identifier => $definition ) {
			$term_type        = (string) $definition['term_type'];
			$allow_for_target = false;

			foreach ( $assignments as $assignment ) {
				$assignment_term_type = (string) ( $assignment['term_type'] ?? '' );

				if ( 'mixed' === $assignment_term_type || $assignment_term_type === $term_type ) {
					$allow_for_target = true;
					break;
				}
			}

			if ( $allow_for_target ) {
				$targets[ $identifier ] = [
					'identifier' => $identifier,
					'term_type'  => $term_type,
				];
			}
		}

		return $targets;
	}

	/**
	 * Resolve one qualification type for imported terms when mapping is unambiguous.
	 *
	 * @param array<string, mixed> $config Configuration.
	 * @param string               $term_type kurz|zkouska
	 * @return int
	 */
	private function resolve_import_qualification_type_id( array $config, string $term_type ): int {
		$assignments = is_array( $config['assignments'] ?? null ) ? $config['assignments'] : [];
		$type_ids    = [];

		foreach ( $assignments as $assignment ) {
			if ( $term_type !== (string) ( $assignment['term_type'] ?? '' ) ) {
				continue;
			}

			$type_id = (int) ( $assignment['qualification_type_id'] ?? 0 );

			if ( $type_id > 0 ) {
				$type_ids[] = $type_id;
			}
		}

		$type_ids = array_values( array_unique( $type_ids ) );

		return 1 === count( $type_ids ) ? (int) $type_ids[0] : 0;
	}

	/**
	 * Build term row payload from one existing Fluent Forms option.
	 *
	 * @param array<string, mixed> $option One advanced option.
	 * @param string               $term_type kurz|zkouska
	 * @param int                  $qualification_type_id Qualification type ID.
	 * @param int                  $sort_order Sort order.
	 * @return array<string, mixed>|null
	 */
	private function build_import_term_data( array $option, string $term_type, int $qualification_type_id, int $sort_order ): ?array {
		$label    = trim( (string) ( $option['label'] ?? '' ) );
		$value    = trim( (string) ( $option['value'] ?? '' ) );
		$capacity = $this->extract_option_capacity( $option );

		if ( '' === $label ) {
			return null;
		}

		$dates = $this->parse_import_term_dates( $term_type, $value, $label );

		if ( null === $dates ) {
			return null;
		}

		$term_key = $this->determine_import_term_key( $term_type, $value, $dates['date_start'], $dates['date_end'] );

		return [
			'term_type'             => $term_type,
			'term_key'              => $term_key,
			'qualification_type_id' => $qualification_type_id,
			'title'                 => $label,
			'label'                 => $label,
			'date_start'            => $dates['date_start'],
			'date_end'              => $dates['date_end'],
			'enrollment_deadline'   => $dates['date_start'],
			'capacity'              => $capacity,
			'is_visible'            => 1,
			'is_active'             => 1,
			'is_archived'           => 0,
			'sort_order'            => $sort_order,
			'notes'                 => 'Importovano z Fluent Forms.',
		];
	}

	/**
	 * Extract one numeric capacity from existing FF option.
	 *
	 * @param array<string, mixed> $option Form option.
	 * @return int
	 */
	private function extract_option_capacity( array $option ): int {
		$capacity = $option['quantity'] ?? $option['calc_value'] ?? 0;

		return is_numeric( $capacity ) ? max( 0, (int) $capacity ) : 0;
	}

	/**
	 * Parse term dates from existing option value or label.
	 *
	 * @param string $term_type kurz|zkouska
	 * @param string $value Current option value.
	 * @param string $label Current option label.
	 * @return array<string, string>|null
	 */
	private function parse_import_term_dates( string $term_type, string $value, string $label ): ?array {
		$from_key = $this->parse_dates_from_term_key( $term_type, $value );

		if ( null !== $from_key ) {
			return $from_key;
		}

		$normalized_label = trim(
			preg_replace(
				'~^(kurz|zkouška|zkouska)\s*:\s*~iu',
				'',
				$label
			)
		);
		$normalized_ascii = $this->normalize_ascii( $normalized_label );
		$months           = $this->get_month_number_map();

		if ( preg_match( '~(\d{1,2})\.\s*-\s*(\d{1,2})\.\s*([[:alpha:]]+)\s+(\d{4})~u', $normalized_ascii, $matches ) ) {
			$month = $months[ $matches[3] ] ?? 0;

			if ( $month > 0 ) {
				return [
					'date_start' => sprintf( '%04d-%02d-%02d', (int) $matches[4], $month, (int) $matches[1] ),
					'date_end'   => sprintf( '%04d-%02d-%02d', (int) $matches[4], $month, (int) $matches[2] ),
				];
			}
		}

		if ( preg_match( '~(\d{1,2})\.\s*([[:alpha:]]+)\s*-\s*(\d{1,2})\.\s*([[:alpha:]]+)\s+(\d{4})~u', $normalized_ascii, $matches ) ) {
			$start_month = $months[ $matches[2] ] ?? 0;
			$end_month   = $months[ $matches[4] ] ?? 0;

			if ( $start_month > 0 && $end_month > 0 ) {
				return [
					'date_start' => sprintf( '%04d-%02d-%02d', (int) $matches[5], $start_month, (int) $matches[1] ),
					'date_end'   => sprintf( '%04d-%02d-%02d', (int) $matches[5], $end_month, (int) $matches[3] ),
				];
			}
		}

		if ( preg_match( '~(\d{1,2})\.\s*([[:alpha:]]+)\s+(\d{4})~u', $normalized_ascii, $matches ) ) {
			$month = $months[ $matches[2] ] ?? 0;

			if ( $month > 0 ) {
				$date = sprintf( '%04d-%02d-%02d', (int) $matches[3], $month, (int) $matches[1] );

				return [
					'date_start' => $date,
					'date_end'   => 'kurz' === $term_type ? $date : $date,
				];
			}
		}

		return null;
	}

	/**
	 * Parse dates from machine term key.
	 *
	 * @param string $term_type kurz|zkouska
	 * @param string $value Option value.
	 * @return array<string, string>|null
	 */
	private function parse_dates_from_term_key( string $term_type, string $value ): ?array {
		if ( ! preg_match( '~^(kurz|zkouska)_(\d{4})_(\d{2})_(\d{2})(?:_(\d{2}))?$~', $value, $matches ) ) {
			return null;
		}

		$prefix    = (string) $matches[1];
		$date_start = sprintf( '%04d-%02d-%02d', (int) $matches[2], (int) $matches[3], (int) $matches[4] );
		$date_end   = $date_start;

		if ( 'kurz' === $prefix && ! empty( $matches[5] ) ) {
			$date_end = sprintf( '%04d-%02d-%02d', (int) $matches[2], (int) $matches[3], (int) $matches[5] );
		}

		if ( ( 'kurz' === $term_type && 'kurz' !== $prefix ) || ( 'zkouska' === $term_type && 'zkouska' !== $prefix ) ) {
			return null;
		}

		return [
			'date_start' => $date_start,
			'date_end'   => $date_end,
		];
	}

	/**
	 * Determine imported term key.
	 *
	 * @param string      $term_type kurz|zkouska
	 * @param string      $value Option value.
	 * @param string      $date_start Date start.
	 * @param string|null $date_end Date end.
	 * @return string
	 */
	private function determine_import_term_key( string $term_type, string $value, string $date_start, ?string $date_end ): string {
		if ( preg_match( '~^(kurz|zkouska)_\d{4}_\d{2}_\d{2}(?:_\d{2})?$~', $value ) ) {
			return $value;
		}

		return Hlavas_Terms_Label_Builder::build_key( $term_type, $date_start, $date_end );
	}

	/**
	 * Normalize string to ASCII lowercase for matching Czech month names.
	 *
	 * @param string $value Input string.
	 * @return string
	 */
	private function normalize_ascii( string $value ): string {
		$value = remove_accents( wp_strip_all_tags( $value ) );
		$value = preg_replace( '/\s+/u', ' ', trim( $value ) );

		return mb_strtolower( (string) $value );
	}

	/**
	 * Map Czech month names to month numbers.
	 *
	 * @return array<string, int>
	 */
	private function get_month_number_map(): array {
		return [
			'ledna'     => 1,
			'unora'     => 2,
			'brezna'    => 3,
			'dubna'     => 4,
			'kvetna'    => 5,
			'cervna'    => 6,
			'cervence'  => 7,
			'srpna'     => 8,
			'zari'      => 9,
			'rijna'     => 10,
			'listopadu' => 11,
			'prosince'  => 12,
		];
	}

	/**
	 * Add modern HLAVAS field names into one stored FF submission container.
	 *
	 * Original legacy fields remain untouched. The method only fills in
	 * modern aliases and normalizes term values to term_key where possible.
	 *
	 * @param array<string, mixed> $container Response or user_inputs container.
	 * @return array{container: array<string, mixed>, changed: bool, legacy_detected: bool, converted: int}
	 */
	private function normalize_submission_container_to_modern( array $container, int $form_id = 0 ): array {
		$changed         = false;
		$legacy_detected = false;
		$converted       = 0;

		foreach ( self::LEGACY_FIELD_MAP as $modern_key => $default_candidate_keys ) {
			$candidate_keys = $form_id > 0
				? $this->get_aliases_for_form( $form_id, $modern_key, (array) $default_candidate_keys )
				: (array) $default_candidate_keys;
			$existing_value = $container[ $modern_key ] ?? null;
			$legacy_value   = null;

			foreach ( $candidate_keys as $candidate_key ) {
				if ( ! array_key_exists( $candidate_key, $container ) ) {
					continue;
				}

				if ( $candidate_key !== $modern_key ) {
					$legacy_detected = true;
				}

				if ( null === $legacy_value || '' === trim( $this->stringify_mixed_value( $legacy_value ) ) ) {
					$legacy_value = $container[ $candidate_key ];
				}
			}

			$value_to_store = $existing_value;

			if ( null === $value_to_store || '' === trim( $this->stringify_mixed_value( $value_to_store ) ) ) {
				$value_to_store = $legacy_value;
			}

			if ( 'termin_kurz' === $modern_key || 'termin_zkouska' === $modern_key ) {
				$term_type            = 'termin_kurz' === $modern_key ? 'kurz' : 'zkouska';
				$original_term_string = trim( $this->stringify_mixed_value( $value_to_store ) );
				$converted_term_value = $this->convert_legacy_term_value_to_new_key( $original_term_string, $term_type );

				if ( '' !== $converted_term_value && $converted_term_value !== $original_term_string ) {
					$converted++;
				}

				$value_to_store = $converted_term_value;
			}

			$current_serialized = $this->stringify_mixed_value( $container[ $modern_key ] ?? null );
			$next_serialized    = $this->stringify_mixed_value( $value_to_store );

			if ( $next_serialized !== $current_serialized ) {
				$container[ $modern_key ] = $value_to_store ?? '';
				$changed                  = true;
			}
		}

		return [
			'container'       => $container,
			'changed'         => $changed,
			'legacy_detected' => $legacy_detected,
			'converted'       => $converted,
		];
	}

	/**
	 * Convert a human-readable legacy term label into the internal term_key.
	 *
	 * @param string $value Original submission value.
	 * @param string $term_type kurz|zkouska
	 * @return string
	 */
	private function convert_legacy_term_value_to_new_key( string $value, string $term_type ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/^(kurz|zkouska)_\d{4}_\d{2}_\d{2}(?:_\d{2})?$/', $value ) ) {
			return $value;
		}

		$dates = $this->parse_import_term_dates( $term_type, $value, $value );

		if ( null === $dates ) {
			return $value;
		}

		return $this->determine_import_term_key( $term_type, $value, $dates['date_start'], $dates['date_end'] );
	}

	/**
	 * Convert mixed data to a compact comparable string.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	/**
	 * Apply one selected term into stored FF submission containers.
	 *
	 * @param array<string, mixed> $container Response or user_inputs payload.
	 * @param object               $term Plugin term object.
	 * @return array<string, mixed>
	 */
	private function apply_term_to_submission_container( array $container, object $term, int $form_id = 0 ): array {
		$term_type   = 'zkouska' === (string) ( $term->term_type ?? '' ) ? 'termin_zkouska' : 'termin_kurz';
		$field_names = $form_id > 0
			? $this->get_aliases_for_form( $form_id, $term_type, self::LEGACY_FIELD_MAP[ $term_type ] ?? [ $term_type ] )
			: ( self::LEGACY_FIELD_MAP[ $term_type ] ?? [ $term_type ] );
		$term_value  = 'label' === hlavas_terms_get_sync_value_mode()
			? (string) ( $term->label ?? '' )
			: (string) ( $term->term_key ?? '' );

		if ( '' === $term_value ) {
			return $container;
		}

		foreach ( $field_names as $field_name ) {
			$container[ $field_name ] = $term_value;
		}

		$container[ $term_type ] = $term_value;

		return $container;
	}

	/**
	 * Synchronize entry_details rows used by FF inventory counting.
	 *
	 * @param int                  $submission_id Submission ID.
	 * @param int                  $form_id Form ID.
	 * @param array<string, mixed> $response Response payload.
	 * @param array<string, mixed> $user_inputs User inputs payload.
	 * @return void
	 */
	private function sync_submission_term_details( int $submission_id, int $form_id, array $response, array $user_inputs = [] ): void {
		global $wpdb;
		/** @var wpdb $wpdb */

		$details_table = $wpdb->prefix . 'fluentform_entry_details';

		if ( $submission_id <= 0 || $form_id <= 0 || ! $this->table_exists( $details_table ) ) {
			return;
		}

		foreach ( [ 'termin_kurz' => 'kurz', 'termin_zkouska' => 'zkouska' ] as $modern_field => $term_type ) {
			$term_value = trim(
				(string) (
					$response[ $modern_field ]
					?? $user_inputs[ $modern_field ]
					?? ''
				)
			);

			if ( '' === $term_value ) {
				continue;
			}

			$aliases = $this->get_aliases_for_form( $form_id, $modern_field, self::LEGACY_FIELD_MAP[ $modern_field ] ?? [ $modern_field ] );

			foreach ( $aliases as $field_name ) {
				$existing_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$details_table} WHERE submission_id = %d AND form_id = %d AND field_name = %s ORDER BY id ASC LIMIT 1",
						$submission_id,
						$form_id,
						$field_name
					)
				);

				if ( $existing_id > 0 ) {
					$wpdb->update(
						$details_table,
						[
							'field_value' => $term_value,
						],
						[
							'id' => $existing_id,
						],
						[
							'%s',
						],
						[
							'%d',
						]
					);

					continue;
				}

				$wpdb->insert(
					$details_table,
					[
						'form_id'       => $form_id,
						'submission_id' => $submission_id,
						'field_name'    => $field_name,
						'field_value'   => $term_value,
					],
					[
						'%d',
						'%d',
						'%s',
						'%s',
					]
				);
			}
		}
	}

	private function stringify_mixed_value( mixed $value ): string {
		if ( is_string( $value ) || is_numeric( $value ) ) {
			return trim( (string) $value );
		}

		if ( is_array( $value ) ) {
			$parts = [];

			foreach ( $value as $item ) {
				$item_string = $this->stringify_mixed_value( $item );

				if ( '' !== $item_string ) {
					$parts[] = $item_string;
				}
			}

			return implode( ' | ', $parts );
		}

		return '';
	}

	/**
	 * Check if DB table contains a specific column.
	 *
	 * @param string $table_name Full table name.
	 * @param string $column_name Column name.
	 * @return bool
	 */
	private function table_has_column( string $table_name, string $column_name ): bool {
		global $wpdb;
		/** @var wpdb $wpdb */

		$column = $wpdb->get_var(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table_name} LIKE %s",
				$column_name
			)
		);

		return ! empty( $column );
	}

	/**
	 * Check whether a DB table exists.
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

	/**
	 * Get all exportable terms filtered optionally by qualification type IDs.
	 *
	 * Full synchronization exports all plugin terms for the given scope,
	 * including past, hidden and archived items.
	 *
	 * @param string          $term_type Term type.
	 * @param array<int, int> $qualification_ids Qualification IDs.
	 * @return array<int, object>
	 */
	private function get_export_terms( string $term_type, array $qualification_ids = [] ): array {
		$terms = $this->repo->get_all(
			[
				'term_type' => $term_type,
				'orderby'   => 'sort_order',
				'order'     => 'ASC',
			]
		);

		if ( ! empty( $qualification_ids ) ) {
			$qualification_ids = array_values( array_filter( array_map( 'intval', $qualification_ids ) ) );

			$terms = array_values(
				array_filter(
					$terms,
					static function ( object $term ) use ( $qualification_ids ): bool {
						return in_array( (int) ( $term->qualification_type_id ?? 0 ), $qualification_ids, true );
					}
				)
			);
		}

		usort(
			$terms,
			static function ( object $left, object $right ): int {
				$sort_compare = (int) ( $left->sort_order ?? 0 ) <=> (int) ( $right->sort_order ?? 0 );

				if ( 0 !== $sort_compare ) {
					return $sort_compare;
				}

				return strcmp( (string) ( $left->date_start ?? '' ), (string) ( $right->date_start ?? '' ) );
			}
		);

		return array_values( $terms );
	}

	/**
	 * Get only terms that should appear in public Fluent Forms fields.
	 *
	 * @param string          $term_type kurz|zkouska
	 * @param array<int, int> $qualification_ids Qualification IDs.
	 * @return array<int, object>
	 */
	private function get_public_sync_terms( string $term_type, array $qualification_ids = [] ): array {
		$terms = $this->repo->get_syncable( $term_type );

		if ( ! empty( $qualification_ids ) ) {
			$qualification_ids = array_values( array_filter( array_map( 'intval', $qualification_ids ) ) );

			$terms = array_values(
				array_filter(
					$terms,
					static function ( object $term ) use ( $qualification_ids ): bool {
						return in_array( (int) ( $term->qualification_type_id ?? 0 ), $qualification_ids, true );
					}
				)
			);
		}

		return array_values( $terms );
	}

	/**
	 * Get only selected terms for one target scope.
	 *
	 * @param string          $term_type Term type.
	 * @param array<int, int> $qualification_ids Qualification IDs.
	 * @param array<int, int> $selected_term_ids Selected term IDs.
	 * @return array<int, object>
	 */
	private function get_selected_terms( string $term_type, array $qualification_ids, array $selected_term_ids ): array {
		if ( empty( $selected_term_ids ) ) {
			return [];
		}

		return array_values(
			array_filter(
				$this->get_export_terms( $term_type, $qualification_ids ),
				static function ( object $term ) use ( $selected_term_ids ): bool {
					return in_array( (int) ( $term->id ?? 0 ), $selected_term_ids, true );
				}
			)
		);
	}

	/**
	 * Build one Fluent Forms option row for a plugin term.
	 *
	 * @param object $term Plugin term object.
	 * @param string $value_mode term_key|label
	 * @return array<string, mixed>
	 */
	private function build_term_option( object $term, string $value_mode ): array {
		$value    = 'label' === $value_mode ? (string) $term->label : (string) $term->term_key;
		$capacity = (int) $term->capacity;

		return [
			'label'      => (string) $term->label,
			'value'      => $value,
			'calc_value' => (string) $capacity,
			'image'      => '',
			'quantity'   => $capacity,
		];
	}

	/**
	 * Build one public Fluent Forms option row using remaining capacity.
	 *
	 * @param object $term Plugin term object.
	 * @param string $value_mode term_key|label
	 * @return array<string, mixed>
	 */
	private function build_public_term_option( object $term, string $value_mode ): array {
		$value     = 'label' === $value_mode ? (string) $term->label : (string) $term->term_key;
		$remaining = $this->availability->get_remaining( (string) ( $term->term_key ?? '' ) );

		return [
			'label'      => (string) $term->label,
			'value'      => $value,
			'calc_value' => (string) $remaining,
			'image'      => '',
			'quantity'   => $remaining,
		];
	}

	/**
	 * Merge selected synchronized terms into existing form options.
	 *
	 * @param array<int, array<string, mixed>>  $existing_options Existing FF options.
	 * @param array<int, object>                $terms Selected plugin terms.
	 * @param array<int, array<string, mixed>>  $new_options New options for selected terms.
	 * @return array<int, array<string, mixed>>
	 */
	private function merge_selected_term_options( array $existing_options, array $terms, array $new_options ): array {
		foreach ( $terms as $index => $term ) {
			$matched_index = $this->find_matching_option_index( $existing_options, $term );

			if ( null === $matched_index ) {
				$existing_options[] = $new_options[ $index ];
				continue;
			}

			$existing_options[ $matched_index ] = array_merge(
				is_array( $existing_options[ $matched_index ] ) ? $existing_options[ $matched_index ] : [],
				$new_options[ $index ]
			);
		}

		return array_values( $existing_options );
	}

	/**
	 * Synchronize only selected terms inside one already existing FF options list.
	 *
	 * Selected terms are first removed from the existing options and then only
	 * currently public terms are added back. This lets one targeted sync both
	 * update visible terms and remove hidden / archived / expired ones.
	 *
	 * @param array<int, array<string, mixed>> $existing_options Existing FF options.
	 * @param array<int, object>               $selected_terms Selected plugin terms.
	 * @param array<int, object>               $public_terms Publicly syncable selected terms.
	 * @param string                           $value_mode term_key|label
	 * @return array<int, array<string, mixed>>
	 */
	private function sync_selected_term_options( array $existing_options, array $selected_terms, array $public_terms, string $value_mode ): array {
		foreach ( $selected_terms as $term ) {
			$matched_index = $this->find_matching_option_index( $existing_options, $term );

			if ( null !== $matched_index ) {
				unset( $existing_options[ $matched_index ] );
			}
		}

		$existing_options = array_values( $existing_options );

		foreach ( $public_terms as $term ) {
			$existing_options[] = $this->build_public_term_option( $term, $value_mode );
		}

		return array_values( $existing_options );
	}

	/**
	 * Check whether one term should currently be visible in the public form.
	 *
	 * @param object $term Plugin term.
	 * @return bool
	 */
	private function is_term_publicly_syncable( object $term ): bool {
		if ( empty( $term->is_active ) || ! empty( $term->is_archived ) || empty( $term->is_visible ) ) {
			return false;
		}

		$cutoff = (string) ( $term->enrollment_deadline ?? $term->date_start ?? $term->date_end ?? '' );

		if ( '' === $cutoff ) {
			return true;
		}

		return $cutoff >= current_time( 'Y-m-d' );
	}

	/**
	 * Find the current option row that belongs to the given term.
	 *
	 * @param array<int, array<string, mixed>> $options Existing FF options.
	 * @param object                           $term Plugin term object.
	 * @return int|null
	 */
	private function find_matching_option_index( array $options, object $term ): ?int {
		$candidates = array_filter(
			[
				trim( (string) ( $term->term_key ?? '' ) ),
				trim( (string) ( $term->label ?? '' ) ),
				trim( (string) ( $term->title ?? '' ) ),
			]
		);

		foreach ( $options as $index => $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			$option_value = trim( (string) ( $option['value'] ?? '' ) );
			$option_label = trim( (string) ( $option['label'] ?? '' ) );

			if ( in_array( $option_value, $candidates, true ) || in_array( $option_label, $candidates, true ) ) {
				return $index;
			}
		}

		return null;
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
	 * Get form-specific aliases enriched by manually configured mapping.
	 *
	 * @param int                $form_id Fluent Form ID.
	 * @param string             $identifier Logical HLAVAS field identifier.
	 * @param array<int, string> $default_aliases Default aliases.
	 * @return array<int, string>
	 */
	private function get_aliases_for_form( int $form_id, string $identifier, array $default_aliases ): array {
		return hlavas_terms_get_manual_field_aliases( $form_id, $identifier, $default_aliases );
	}

	/**
	 * Get current manual mapping value for one logical field.
	 *
	 * @param int    $form_id Fluent Form ID.
	 * @param string $identifier Logical field identifier.
	 * @return string
	 */
	private function get_manual_mapping_value( int $form_id, string $identifier ): string {
		$field_map = hlavas_terms_get_form_field_map( $form_id );

		return trim( (string) ( $field_map[ sanitize_key( $identifier ) ] ?? '' ) );
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
