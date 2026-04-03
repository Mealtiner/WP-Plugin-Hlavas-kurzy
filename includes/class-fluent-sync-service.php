<?php
/**
 * Synchronization service: writes term options + inventory into Fluent Forms.
 *
 * Strategy:
 * We directly modify the form_fields JSON stored in fluentform_forms.form_fields.
 * This keeps Fluent Forms standard rendering intact and does not require
 * frontend hacks.
 *
 * IMPORTANT:
 * This class accesses Fluent Forms internal data structures.
 * Changes in Fluent Forms versions may require adjustments here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Hlavas_Terms_Repository', false ) ) {
	require_once __DIR__ . '/class-repository.php';
}

class Hlavas_Terms_Fluent_Sync_Service {

	/**
	 * Terms repository.
	 *
	 * @var Hlavas_Terms_Repository
	 */
	private Hlavas_Terms_Repository $repo;

	/**
	 * Fluent Forms field identifier => term_type mapping.
	 *
	 * @var array<string, string>
	 */
	private const FIELD_MAP = [
		'termin_kurz'    => 'kurz',
		'termin_zkouska' => 'zkouska',
	];

	/**
	 * Constructor.
	 *
	 * @param Hlavas_Terms_Repository|null $repo Repository instance.
	 */
	public function __construct( ?Hlavas_Terms_Repository $repo = null ) {
		$this->repo = $repo ?? new Hlavas_Terms_Repository();
	}

	/* ---------------------------------------------------------------
	 * PREVIEW
	 * ------------------------------------------------------------- */

	/**
	 * Build a preview of what would be synced.
	 *
	 * @return array{
	 *     kurz: array<int, array<string, mixed>>,
	 *     zkouska: array<int, array<string, mixed>>,
	 *     form_found: bool,
	 *     fields_found: array<string, bool>
	 * }
	 */
	public function preview(): array {
		$result = [
			'form_found'   => false,
			'fields_found' => [],
			'kurz'         => [],
			'zkouska'      => [],
		];

		$form = $this->get_form();

		if ( ! $form ) {
			return $result;
		}

		$result['form_found'] = true;

		$fields = $this->parse_form_fields( $form );

		foreach ( self::FIELD_MAP as $admin_label => $term_type ) {
			$field_path = $this->find_field_path( $fields, $admin_label );

			if ( null !== $field_path ) {
				$result['fields_found'][ $admin_label ] = true;
			}

			$terms = $this->repo->get_syncable( $term_type );

			foreach ( $terms as $term ) {
				$result[ $term_type ][] = [
					'term_key' => $term->term_key,
					'label'    => $term->label,
					'capacity' => (int) $term->capacity,
				];
			}
		}

		return $result;
	}

	/* ---------------------------------------------------------------
	 * DEBUG
	 * ------------------------------------------------------------- */

	/**
	 * Dump internal Fluent Forms structure for debugging.
	 *
	 * @return array<string, mixed>
	 */
	public function debug(): array {
		$form = $this->get_form();

		if ( ! $form ) {
			return [
				'error'   => 'Formulář ID ' . hlavas_terms_get_form_id() . ' nebyl nalezen.',
				'form_id' => hlavas_terms_get_form_id(),
			];
		}

		$fields = $this->parse_form_fields( $form );

		$debug = [
			'form_id'        => $form->id,
			'configured_id'  => hlavas_terms_get_form_id(),
			'form_title'     => $form->title,
			'fields'         => [],
			'inventory_meta' => $this->get_inventory_meta(),
		];

		$this->collect_debug_fields( $fields, $debug['fields'] );

		return $debug;
	}

	/* ---------------------------------------------------------------
	 * EXECUTE
	 * ------------------------------------------------------------- */

	/**
	 * Execute synchronization into Fluent Forms.
	 *
	 * @param string $value_mode 'term_key' or 'label'
	 * @return array{success: bool, message: string, details: array<int, string>}
	 */
	public function execute( string $value_mode = 'term_key' ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$form = $this->get_form();

		if ( ! $form ) {
			return [
				'success' => false,
				'message' => 'Formulář ID ' . hlavas_terms_get_form_id() . ' nebyl nalezen.',
				'details' => [],
			];
		}

		$fields  = $this->parse_form_fields( $form );
		$changed = false;
		$details = [];

		foreach ( self::FIELD_MAP as $admin_label => $term_type ) {
			$field_path = $this->find_field_path( $fields, $admin_label );

			if ( null === $field_path ) {
				$details[] = "Pole '{$admin_label}' nebylo nalezeno ve formuláři.";
				continue;
			}

			$terms   = $this->repo->get_syncable( $term_type );
			$options = [];
			$stock   = [];

			foreach ( $terms as $term ) {
				$value = 'label' === $value_mode ? $term->label : $term->term_key;

				$options[] = [
					'label'      => $term->label,
					'value'      => $value,
					'calc_value' => (string) $term->capacity,
					'image'      => '',
				];

				$stock[] = [
					'value'    => $value,
					'quantity' => (int) $term->capacity,
				];
			}

			$field = $this->get_field_by_path( $fields, $field_path );

			if ( ! is_array( $field ) ) {
				$details[] = "Pole '{$admin_label}' bylo nalezeno, ale nepodařilo se jej načíst.";
				continue;
			}

			if ( ! isset( $field['settings'] ) || ! is_array( $field['settings'] ) ) {
				$field['settings'] = [];
			}

			$field['settings']['advanced_options'] = $options;
			$field['settings']['inventory_settings'] = [
				'enabled'           => 'simple',
				'stock_quantity'    => $stock,
				'stock_out_message' => 'Tenhle termín už je bohužel plný.',
				'hide_choice'       => false,
			];

			$this->set_field_by_path( $fields, $field_path, $field );

			$details[] = "Pole '{$admin_label}': synchronizováno " . count( $options ) . ' termínů.';
			$changed   = true;
		}

		if ( ! $changed ) {
			return [
				'success' => false,
				'message' => 'Žádné pole nebylo aktualizováno.',
				'details' => $details,
			];
		}

		$form_fields_json = wp_json_encode( $fields );
		$table_forms      = $wpdb->prefix . 'fluentform_forms';

		$updated = $wpdb->update(
			$table_forms,
			[
				'form_fields' => $form_fields_json,
				'updated_at'  => current_time( 'mysql', true ),
			],
			[
				'id' => hlavas_terms_get_form_id(),
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
			return [
				'success' => false,
				'message' => 'Chyba při ukládání formuláře: ' . $wpdb->last_error,
				'details' => $details,
			];
		}

		$this->sync_inventory_meta( $fields );

		return [
			'success' => true,
			'message' => 'Synchronizace dokončena úspěšně.',
			'details' => $details,
		];
	}

	/* ---------------------------------------------------------------
	 * INTERNAL HELPERS
	 * ------------------------------------------------------------- */

	/**
	 * Get configured Fluent Form.
	 *
	 * @return object|null
	 */
	private function get_form(): ?object {
		global $wpdb;
		/** @var wpdb $wpdb */

		$table = $wpdb->prefix . 'fluentform_forms';

		$form = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				hlavas_terms_get_form_id()
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
		$fields = json_decode( $form->form_fields, true );

		return is_array( $fields ) ? $fields : [];
	}

	/**
	 * Find field path by field identifier.
	 *
	 * Path format:
	 * - top level: [3]
	 * - nested:    [1, 'columns', 0, 'fields', 2]
	 *
	 * @param array<int, array<string, mixed>> $fields Fields array.
	 * @param string                            $identifier Supported identifier.
	 * @return array<int|string>|null
	 */
	private function find_field_path( array $fields, string $identifier ): ?array {
		foreach ( $fields as $index => $field ) {
			if ( $this->field_matches_identifier( $field, $identifier ) ) {
				return [ $index ];
			}

			if ( isset( $field['columns'] ) && is_array( $field['columns'] ) ) {
				foreach ( $field['columns'] as $column_index => $column ) {
					if ( empty( $column['fields'] ) || ! is_array( $column['fields'] ) ) {
						continue;
					}

					foreach ( $column['fields'] as $field_index => $nested_field ) {
						if ( $this->field_matches_identifier( $nested_field, $identifier ) ) {
							return [ $index, 'columns', $column_index, 'fields', $field_index ];
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Check whether a field matches the expected identifier.
	 *
	 * Some Fluent Forms setups expose the internal field name in
	 * attributes.name, others only via admin_field_label.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $identifier Expected identifier.
	 * @return bool
	 */
	private function field_matches_identifier( array $field, string $identifier ): bool {
		$attributes        = $field['attributes'] ?? [];
		$settings          = $field['settings'] ?? [];
		$attribute_name    = is_array( $attributes ) ? (string) ( $attributes['name'] ?? '' ) : '';
		$admin_field_label = is_array( $settings ) ? (string) ( $settings['admin_field_label'] ?? '' ) : '';

		return $identifier === $attribute_name || $identifier === $admin_field_label;
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
	 * Set a field in array by path.
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
	 * Get inventory meta from fluentform_form_meta.
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_inventory_meta(): ?array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$meta_table = $wpdb->prefix . 'fluentform_form_meta';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$meta_table} WHERE form_id = %d AND meta_key = %s",
				hlavas_terms_get_form_id(),
				'inventory'
			)
		);

		if ( $row && ! empty( $row->value ) ) {
			$value = json_decode( $row->value, true );

			return is_array( $value ) ? $value : null;
		}

		return null;
	}

	/**
	 * Sync inventory meta if needed in future.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields array.
	 * @return void
	 */
	private function sync_inventory_meta( array $fields ): void {
		unset( $fields );
		// Fluent Forms Pro currently uses field-level inventory settings for this setup.
		// Left intentionally as a placeholder for future compatibility handling.
	}

	/**
	 * Get current options from a Fluent Forms field.
	 *
	 * @param string $admin_label Field admin label.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_current_options( string $admin_label ): array {
		$form = $this->get_form();

		if ( ! $form ) {
			return [];
		}

		$fields     = $this->parse_form_fields( $form );
		$field_path = $this->find_field_path( $fields, $admin_label );

		if ( null === $field_path ) {
			return [];
		}

		$field = $this->get_field_by_path( $fields, $field_path );

		if ( ! is_array( $field ) ) {
			return [];
		}

		return $field['settings']['advanced_options'] ?? [];
	}

	/**
	 * Collect field info recursively for debug output.
	 *
	 * @param array<int, array<string, mixed>> $fields Source fields.
	 * @param array<int, array<string, mixed>> $output Output accumulator.
	 * @return void
	 */
	private function collect_debug_fields( array $fields, array &$output ): void {
		foreach ( $fields as $field ) {
			$attrs    = $field['attributes'] ?? [];
			$settings = $field['settings'] ?? [];

			$info = [
				'element'           => $field['element'] ?? 'unknown',
				'name'              => $attrs['name'] ?? '',
				'admin_field_label' => $settings['admin_field_label'] ?? '',
				'label'             => $settings['label'] ?? '',
			];

			if ( isset( $settings['advanced_options'] ) ) {
				$info['advanced_options'] = $settings['advanced_options'];
			}

			if ( isset( $settings['conditional_logics'] ) ) {
				$info['conditional_logics'] = $settings['conditional_logics'];
			}

			if ( isset( $settings['inventory_settings'] ) ) {
				$info['inventory_settings'] = $settings['inventory_settings'];
			}

			$output[] = $info;

			if ( isset( $field['columns'] ) && is_array( $field['columns'] ) ) {
				foreach ( $field['columns'] as $column ) {
					if ( ! empty( $column['fields'] ) && is_array( $column['fields'] ) ) {
						$this->collect_debug_fields( $column['fields'], $output );
					}
				}
			}
		}
	}
}
