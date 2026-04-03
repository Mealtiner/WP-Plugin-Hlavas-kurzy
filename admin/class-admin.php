<?php
/**
 * Admin controller: menu, list table, edit form, sync actions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Hlavas_Terms_Repository', false ) ) {
	require_once dirname( __DIR__ ) . '/includes/class-repository.php';
}

if ( ! class_exists( 'Hlavas_Terms_Label_Builder', false ) ) {
	require_once dirname( __DIR__ ) . '/includes/class-label-builder.php';
}

if ( ! class_exists( 'Hlavas_Terms_Fluent_Sync_Service', false ) ) {
	require_once dirname( __DIR__ ) . '/includes/class-fluent-sync-service.php';
}

if ( ! class_exists( 'Hlavas_Terms_Availability_Service', false ) ) {
	require_once dirname( __DIR__ ) . '/includes/class-availability-service.php';
}

if ( ! class_exists( 'Hlavas_Terms_Qualification_Type_Repository', false ) ) {
	require_once dirname( __DIR__ ) . '/includes/class-qualification-type-repository.php';
}

if ( ! class_exists( 'Hlavas_Terms_Participant_Service', false ) ) {
	require_once dirname( __DIR__ ) . '/includes/class-participant-service.php';
}

class Hlavas_Terms_Admin {

	/**
	 * Terms repository.
	 *
	 * @var Hlavas_Terms_Repository
	 */
	private Hlavas_Terms_Repository $repo;

	/**
	 * Fluent sync service.
	 *
	 * @var Hlavas_Terms_Fluent_Sync_Service
	 */
	private Hlavas_Terms_Fluent_Sync_Service $sync;

	/**
	 * Availability service.
	 *
	 * @var Hlavas_Terms_Availability_Service
	 */
	private Hlavas_Terms_Availability_Service $availability;

	/**
	 * Qualification types repository.
	 *
	 * @var Hlavas_Terms_Qualification_Type_Repository
	 */
	private Hlavas_Terms_Qualification_Type_Repository $type_repo;

	/**
	 * Participant service.
	 *
	 * @var Hlavas_Terms_Participant_Service
	 */
	private Hlavas_Terms_Participant_Service $participants;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repo         = new Hlavas_Terms_Repository();
		$this->sync         = new Hlavas_Terms_Fluent_Sync_Service( $this->repo );
		$this->availability = new Hlavas_Terms_Availability_Service( $this->repo );
		$this->type_repo    = new Hlavas_Terms_Qualification_Type_Repository();
		$this->participants = new Hlavas_Terms_Participant_Service( $this->repo, $this->type_repo );

		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_actions' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/* ---------------------------------------------------------------
	 * MENU
	 * ------------------------------------------------------------- */

	/**
	 * Register plugin admin menu and submenus.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			'Kurzy a zkoušky',
			'Kurzy a zkoušky',
			'manage_options',
			'hlavas-terms',
			[ $this, 'page_list' ],
			'dashicons-calendar-alt',
			30
		);

		add_submenu_page(
			'hlavas-terms',
			'Přehled',
			'Přehled',
			'manage_options',
			'hlavas-terms',
			[ $this, 'page_list' ]
		);

		add_submenu_page(
			'hlavas-terms',
			'Typy kurzů',
			'Typy kurzů',
			'manage_options',
			'hlavas-terms-types',
			[ $this, 'page_types' ]
		);

		add_submenu_page(
			'hlavas-terms',
			'Termíny kurzů',
			'Termíny kurzů',
			'manage_options',
			'hlavas-terms-kurzy',
			[ $this, 'page_list_kurzy' ]
		);

		add_submenu_page(
			'hlavas-terms',
			'Termíny zkoušek',
			'Termíny zkoušek',
			'manage_options',
			'hlavas-terms-zkousky',
			[ $this, 'page_list_zkousky' ]
		);

		add_submenu_page(
			'hlavas-terms',
			'Přidat termín',
			'Přidat termín',
			'manage_options',
			'hlavas-terms-edit',
			[ $this, 'page_edit' ]
		);

		add_submenu_page(
			'hlavas-terms',
			'Obsazenost a kapacita',
			'Obsazenost a kapacita',
			'manage_options',
			'hlavas-terms-availability',
			[ $this, 'page_availability' ]
		);

		add_submenu_page(
			'hlavas-terms',
			'Účastníci',
			'Účastníci',
			'manage_options',
			'hlavas-terms-participants',
			[ $this, 'page_participants' ]
		);

		add_submenu_page(
			'hlavas-terms',
			'Synchronizace s Fluent Forms',
			'Synchronizace s Fluent Forms',
			'manage_options',
			'hlavas-terms-sync',
			[ $this, 'page_sync' ]
		);

		add_submenu_page(
			'hlavas-terms',
			'Nastavení',
			'Nastavení',
			'manage_options',
			'hlavas-terms-settings',
			[ $this, 'page_settings' ]
		);

		add_submenu_page(
			'hlavas-terms',
			'Nápověda / O pluginu',
			'Nápověda / O pluginu',
			'manage_options',
			'hlavas-terms-info',
			[ $this, 'page_info' ]
		);
	}

	/* ---------------------------------------------------------------
	 * ASSETS
	 * ------------------------------------------------------------- */

	/**
	 * Enqueue admin assets on plugin pages only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'hlavas-terms' ) ) {
			return;
		}

		wp_enqueue_style(
			'hlavas-terms-admin',
			HLAVAS_TERMS_URL . 'admin/assets/css/admin.css',
			[],
			HLAVAS_TERMS_VERSION
		);
	}

	/* ---------------------------------------------------------------
	 * ACTION HANDLER
	 * ------------------------------------------------------------- */

	/**
	 * Handle admin POST/GET actions.
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if (
			isset( $_POST['hlavas_term_save'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hlavas_nonce'] ?? '' ) ), 'hlavas_term_save' )
		) {
			$this->handle_save();
		}

		if (
			isset( $_REQUEST['hlavas_report_action'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_hlavas_report_nonce'] ?? '' ) ), 'hlavas_report_action' )
		) {
			$this->handle_report_action();
		}

		if (
			isset( $_POST['hlavas_term_sync_execute'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hlavas_term_sync_nonce'] ?? '' ) ), 'hlavas_term_sync' )
		) {
			$this->handle_term_sync();
		}

		if (
			isset( $_GET['action'], $_GET['term_id'] ) &&
			'delete' === sanitize_text_field( wp_unslash( $_GET['action'] ) )
		) {
			$term_id = (int) $_GET['term_id'];

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'hlavas_delete_' . $term_id ) ) {
				wp_die( 'Neplatný bezpečnostní token.' );
			}

			$this->repo->delete( $term_id );

			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms&message=deleted' )
			);
			exit;
		}

		if (
			isset( $_GET['action'], $_GET['term_id'] ) &&
			'toggle_visibility' === sanitize_text_field( wp_unslash( $_GET['action'] ) )
		) {
			$term_id = (int) $_GET['term_id'];

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'hlavas_visibility_' . $term_id ) ) {
				wp_die( 'Neplatný bezpečnostní token.' );
			}

			$this->repo->toggle_visibility( $term_id );

			wp_safe_redirect(
				admin_url( 'admin.php?page=' . rawurlencode( sanitize_text_field( wp_unslash( $_GET['page'] ?? 'hlavas-terms' ) ) ) . '&message=visibility_changed' )
			);
			exit;
		}

		if (
			isset( $_POST['hlavas_bulk_action'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hlavas_bulk_nonce'] ?? '' ) ), 'hlavas_bulk' )
		) {
			$this->handle_bulk();
		}

		if (
			isset( $_POST['hlavas_sync_execute'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hlavas_sync_nonce'] ?? '' ) ), 'hlavas_sync' )
		) {
			$value_mode   = sanitize_text_field( wp_unslash( $_POST['value_mode'] ?? hlavas_terms_get_sync_value_mode() ) );
			$sync_form_id = absint( $_POST['sync_form_id'] ?? 0 );
			$result       = $this->sync->execute( $value_mode, $sync_form_id > 0 ? $sync_form_id : null );

			set_transient( 'hlavas_sync_result', $result, 60 );

			wp_safe_redirect(
				admin_url(
					'admin.php?page=hlavas-terms-sync&synced=1' .
					( $sync_form_id > 0 ? '&form_id=' . $sync_form_id : '' )
				)
			);
			exit;
		}

		if (
			isset( $_POST['hlavas_terms_save_settings'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hlavas_settings_nonce'] ?? '' ) ), 'hlavas_terms_settings' )
		) {
			$this->handle_settings_save();
		}

		if (
			isset( $_POST['hlavas_terms_export_backup'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hlavas_export_nonce'] ?? '' ) ), 'hlavas_terms_export_backup' )
		) {
			$this->handle_settings_export();
		}

		if (
			isset( $_POST['hlavas_terms_import_backup'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hlavas_import_nonce'] ?? '' ) ), 'hlavas_terms_import_backup' )
		) {
			$this->handle_settings_import();
		}

		if (
			isset( $_POST['hlavas_terms_reset_sync_log'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hlavas_reset_sync_nonce'] ?? '' ) ), 'hlavas_terms_reset_sync_log' )
		) {
			$this->handle_sync_log_reset();
		}

		if (
			isset( $_POST['hlavas_type_save'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hlavas_type_nonce'] ?? '' ) ), 'hlavas_type_save' )
		) {
			$this->handle_type_save();
		}

		if (
			isset( $_GET['action'], $_GET['type_id'] ) &&
			'delete_type' === sanitize_text_field( wp_unslash( $_GET['action'] ) )
		) {
			$type_id = (int) $_GET['type_id'];

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'hlavas_delete_type_' . $type_id ) ) {
				wp_die( 'Neplatný bezpečnostní token.' );
			}

			$this->type_repo->delete( $type_id );

			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms-types&message=deleted' )
			);
			exit;
		}
	}

	/**
	 * Handle term save (create/update).
	 *
	 * @return void
	 */
	private function handle_save(): void {
		$id = (int) ( $_POST['term_id'] ?? 0 );

		$data = [
			'term_type'             => sanitize_text_field( wp_unslash( $_POST['term_type'] ?? 'kurz' ) ),
			'term_key'              => sanitize_text_field( wp_unslash( $_POST['term_key'] ?? '' ) ),
			'qualification_type_id' => absint( $_POST['qualification_type_id'] ?? 0 ),
			'title'                 => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
			'label'                 => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
			'date_start'            => sanitize_text_field( wp_unslash( $_POST['date_start'] ?? '' ) ),
			'date_end'              => sanitize_text_field( wp_unslash( $_POST['date_end'] ?? '' ) ) ?: null,
			'enrollment_deadline'   => sanitize_text_field( wp_unslash( $_POST['enrollment_deadline'] ?? '' ) ) ?: null,
			'capacity'              => absint( $_POST['capacity'] ?? 0 ),
			'is_visible'            => isset( $_POST['is_visible'] ) ? 1 : 0,
			'is_active'             => isset( $_POST['is_active'] ) ? 1 : 0,
			'is_archived'           => isset( $_POST['is_archived'] ) ? 1 : 0,
			'sort_order'            => (int) ( $_POST['sort_order'] ?? 0 ),
			'notes'                 => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
		];

		if ( empty( $data['date_start'] ) ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . $id . '&error=missing_fields' )
			);
			exit;
		}

		if ( 'kurz' === $data['term_type'] && empty( $data['date_end'] ) ) {
			$data['date_end'] = $data['date_start'];
		}

		if ( 'zkouska' === $data['term_type'] ) {
			$data['date_end'] = $data['date_start'];
		}

		if ( empty( $data['enrollment_deadline'] ) ) {
			$data['enrollment_deadline'] = $data['date_start'];
		}

		if ( empty( $data['term_key'] ) ) {
			$data['term_key'] = Hlavas_Terms_Label_Builder::build_key(
				$data['term_type'],
				$data['date_start'],
				$data['date_end']
			);
		}

		if ( empty( $data['label'] ) ) {
			$data['label'] = Hlavas_Terms_Label_Builder::build(
				$data['term_type'],
				$data['date_start'],
				$data['date_end']
			);
		}

		if ( $this->repo->key_exists( $data['term_key'], $id ?: null ) ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . $id . '&error=duplicate_key' )
			);
			exit;
		}

		if ( $id > 0 ) {
			$this->repo->update( $id, $data );
			$redirect_id = $id;
		} else {
			$redirect_id = $this->repo->insert( $data );
		}

		if ( $id > 0 ) {
			$redirect_page = 'kurz' === $data['term_type'] ? 'hlavas-terms-kurzy' : 'hlavas-terms-zkousky';

			wp_safe_redirect(
				admin_url( 'admin.php?page=' . $redirect_page . '&message=saved' )
			);
			exit;
		}

		wp_safe_redirect(
			admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . (int) $redirect_id . '&message=saved' )
		);
		exit;
	}

	/**
	 * Handle sync from term edit page.
	 *
	 * @return void
	 */
	private function handle_term_sync(): void {
		$term_id = absint( $_POST['term_id'] ?? 0 );
		$term    = $term_id > 0 ? $this->repo->find( $term_id ) : null;

		if ( ! $term ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms-edit&error=sync_missing_term' )
			);
			exit;
		}

		$form_ids = $this->sync->get_form_ids_for_term( $term_id );
		$details  = [];
		$success  = false;

		if ( empty( $form_ids ) ) {
			$details[] = 'Pro tento termin nebyl nalezen zadny navazany Fluent Forms formular.';
		} else {
			foreach ( $form_ids as $form_id ) {
				$result   = $this->sync->execute( hlavas_terms_get_sync_value_mode(), $form_id );
				$details  = array_merge( $details, $result['details'] );
				$success  = $success || ! empty( $result['success'] );
			}
		}

		set_transient(
			'hlavas_term_sync_result_' . get_current_user_id(),
			[
				'success' => $success,
				'details' => $details,
			],
			60
		);

		wp_safe_redirect(
			admin_url(
				'admin.php?page=hlavas-terms-edit&term_id=' . $term_id . '&message=' . ( $success ? 'synced_to_ff' : 'sync_failed' )
			)
		);
		exit;
	}

	/**
	 * Handle report download, email and print actions.
	 *
	 * @return void
	 */
	private function handle_report_action(): void {
		$action   = sanitize_text_field( wp_unslash( $_REQUEST['hlavas_report_action'] ?? '' ) );
		$page_slug = sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? '' ) );
		$payload  = $this->build_report_payload_from_request( $page_slug );

		if ( ! $payload ) {
			wp_safe_redirect( $this->get_report_redirect_url( $page_slug, 'report_failed' ) );
			exit;
		}

		switch ( $action ) {
			case 'download':
				$format = sanitize_text_field( wp_unslash( $_REQUEST['report_format'] ?? 'csv' ) );
				$this->output_report_download( $payload, $format );
				exit;

			case 'print':
				$this->render_printable_report( $payload );
				exit;

			case 'email':
				$sent = $this->send_report_email( $payload );
				wp_safe_redirect( $this->get_report_redirect_url( $page_slug, $sent ? 'emailed' : 'email_failed' ) );
				exit;
		}
	}

	/**
	 * Build report payload from current request context.
	 *
	 * @param string $page_slug Admin page slug.
	 * @return array<string, mixed>|null
	 */
	private function build_report_payload_from_request( string $page_slug ): ?array {
		switch ( $page_slug ) {
			case 'hlavas-terms-kurzy':
				return $this->build_terms_report_payload( 'kurz' );

			case 'hlavas-terms-zkousky':
				return $this->build_terms_report_payload( 'zkouska' );

			case 'hlavas-terms-availability':
				return $this->build_availability_report_payload();

			case 'hlavas-terms-participants':
				return $this->build_participants_report_payload();
		}

		return null;
	}

	/**
	 * Build report payload for terms list.
	 *
	 * @param string $term_type kurz|zkouska
	 * @return array<string, mixed>
	 */
	private function build_terms_report_payload( string $term_type ): array {
		$filters  = $this->get_term_filters_from_request( $term_type );
		$terms    = $this->repo->get_all( $filters );
		$sync_log = hlavas_terms_get_sync_log();
		$rows     = [];

		foreach ( $terms as $term ) {
			$rows[] = [
				'id'            => (int) $term->id,
				'typ'           => 'kurz' === $term->term_type ? 'Kurz' : 'Zkouška',
				'kvalifikace'   => ! empty( $term->qualification_name )
					? trim( ( ! empty( $term->qualification_code ) ? $term->qualification_code . ' - ' : '' ) . $term->qualification_name )
					: 'Bez návaznosti',
				'nazev'         => ! empty( $term->title ) ? (string) $term->title : (string) $term->label,
				'label'         => (string) $term->label,
				'term_key'      => (string) $term->term_key,
				'datum_od'      => (string) $term->date_start,
				'datum_do'      => (string) ( $term->date_end ?? '' ),
				'uzaverka'      => (string) ( $term->enrollment_deadline ?? '' ),
				'kapacita'      => (int) $term->capacity,
				'ff_sync'       => (string) ( $sync_log[ (string) $term->id ] ?? '' ),
				'web'           => ! empty( $term->is_visible ) ? 'Ano' : 'Ne',
				'aktivni'       => ! empty( $term->is_active ) ? 'Ano' : 'Ne',
				'archiv'        => ! empty( $term->is_archived ) ? 'Ano' : 'Ne',
				'poradi'        => (int) $term->sort_order,
			];
		}

		return [
			'title'      => 'kurz' === $term_type ? 'Termíny kurzů' : 'Termíny zkoušek',
			'filename'   => 'kurz' === $term_type ? 'terminy-kurzu' : 'terminy-zkousek',
			'columns'    => [
				'id'          => 'ID',
				'typ'         => 'Typ',
				'kvalifikace' => 'Kvalifikace',
				'nazev'       => 'Název termínu',
				'label'       => 'Label',
				'term_key'    => 'Term key',
				'datum_od'    => 'Datum od',
				'datum_do'    => 'Datum do',
				'uzaverka'    => 'Uzávěrka',
				'kapacita'    => 'Kapacita',
				'ff_sync'     => 'FF sync',
				'web'         => 'Web',
				'aktivni'     => 'Aktivní',
				'archiv'      => 'Archiv',
				'poradi'      => 'Pořadí',
			],
			'rows'       => $rows,
			'email_name' => 'výpis termínů',
		];
	}

	/**
	 * Build report payload for availability page.
	 *
	 * @return array<string, mixed>
	 */
	private function build_availability_report_payload(): array {
		$report = $this->availability->get_availability_report();
		$rows   = [];

		foreach ( $report as $item ) {
			$capacity = (int) $item['capacity'];
			$enrolled = (int) $item['enrolled'];
			$rows[]   = [
				'typ'         => 'kurz' === $item['type'] ? 'Kurz' : 'Zkouška',
				'kvalifikace' => (string) $item['qualification'],
				'nazev'       => (string) $item['title'],
				'label'       => (string) $item['label'],
				'term_key'    => (string) $item['term_key'],
				'kapacita'    => $capacity,
				'prihlaseno'  => $enrolled,
				'zbyva'       => (int) $item['remaining'],
				'obsazeni'    => $capacity > 0 ? round( $enrolled / $capacity * 100 ) . '%' : '0%',
			];
		}

		return [
			'title'      => 'Obsazenost a kapacita',
			'filename'   => 'obsazenost-a-kapacita',
			'columns'    => [
				'typ'         => 'Typ',
				'kvalifikace' => 'Kvalifikace',
				'nazev'       => 'Název termínu',
				'label'       => 'Label',
				'term_key'    => 'Term key',
				'kapacita'    => 'Kapacita',
				'prihlaseno'  => 'Přihlášeno',
				'zbyva'       => 'Zbývá',
				'obsazeni'    => 'Obsazení',
			],
			'rows'       => $rows,
			'email_name' => 'obsazenost a kapacita',
		];
	}

	/**
	 * Build report payload for participants page.
	 *
	 * @return array<string, mixed>
	 */
	private function build_participants_report_payload(): array {
		$filters      = $this->get_participant_filters_from_request();
		$participants = $this->participants->get_participants( $filters );
		$rows         = [];

		foreach ( $participants as $participant ) {
			$rows[] = [
				'jmeno'             => (string) $participant['name'],
				'email'             => (string) $participant['email'],
				'telefon'           => (string) $participant['phone'],
				'narozeni'          => (string) $participant['birthdate'],
				'adresa'            => (string) $participant['address'],
				'typ_prihlasky'     => (string) $participant['term_type_label'],
				'kvalifikace'       => (string) $participant['qualification'],
				'registrace'        => (string) $participant['registration_type'],
				'termin'            => (string) $participant['term_title'],
				'label_terminu'     => (string) $participant['term_label'],
				'term_key'          => (string) $participant['term_key'],
				'platba'            => (string) $participant['payment_type'],
				'organizace'        => (string) $participant['organization_name'],
				'ico'               => (string) $participant['organization_ico'],
				'fakturacni_email'  => (string) $participant['invoice_email'],
				'form_id'           => (int) $participant['form_id'],
				'submission_id'     => (int) $participant['submission_id'],
				'odeslano'          => (string) $participant['created_at'],
				'stav'              => (string) $participant['status'],
			];
		}

		return [
			'title'      => 'Účastníci',
			'filename'   => 'ucastnici',
			'columns'    => [
				'jmeno'            => 'Jméno',
				'email'            => 'E-mail',
				'telefon'          => 'Telefon',
				'narozeni'         => 'Narození',
				'adresa'           => 'Adresa',
				'typ_prihlasky'    => 'Kurz / zkouška',
				'kvalifikace'      => 'Kvalifikace',
				'registrace'       => 'Registrace',
				'termin'           => 'Termín',
				'label_terminu'    => 'Label termínu',
				'term_key'         => 'Term key',
				'platba'           => 'Platba',
				'organizace'       => 'Organizace',
				'ico'              => 'IČO',
				'fakturacni_email' => 'Fakturační e-mail',
				'form_id'          => 'Form ID',
				'submission_id'    => 'Submission ID',
				'odeslano'         => 'Odesláno',
				'stav'             => 'Stav',
			],
			'rows'       => $rows,
			'email_name' => 'seznam účastníků',
		];
	}

	/**
	 * Output CSV/XLS download.
	 *
	 * @param array<string, mixed> $payload Report payload.
	 * @param string               $format csv|xls
	 * @return void
	 */
	private function output_report_download( array $payload, string $format ): void {
		$format   = in_array( $format, [ 'csv', 'xls' ], true ) ? $format : 'csv';
		$filename = sanitize_file_name( (string) $payload['filename'] ) . '-' . gmdate( 'Y-m-d-His' ) . '.' . $format;

		nocache_headers();

		if ( 'xls' === $format ) {
			header( 'Content-Type: application/vnd.ms-excel; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			echo $this->serialize_report_xls( $payload );
			return;
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo $this->serialize_report_csv( $payload );
	}

	/**
	 * Send report by e-mail as CSV attachment.
	 *
	 * @param array<string, mixed> $payload Report payload.
	 * @return bool
	 */
	private function send_report_email( array $payload ): bool {
		$recipient = hlavas_terms_get_report_email();

		if ( ! is_email( $recipient ) ) {
			return false;
		}

		$subject    = 'HLAVAS export: ' . (string) $payload['title'];
		$body       = "V příloze je export dat z pluginu HLAVAS.\n\nVýpis: " . (string) $payload['title'] . "\nGenerováno: " . current_time( 'mysql' );
		$attachment = wp_tempnam( sanitize_file_name( (string) $payload['filename'] ) . '.csv' );

		if ( ! $attachment ) {
			return false;
		}

		file_put_contents( $attachment, $this->serialize_report_csv( $payload ) );
		$sent = wp_mail( $recipient, $subject, $body, [], [ $attachment ] );
		@unlink( $attachment );

		return (bool) $sent;
	}

	/**
	 * Render printable report.
	 *
	 * @param array<string, mixed> $payload Report payload.
	 * @return void
	 */
	private function render_printable_report( array $payload ): void {
		$title   = (string) $payload['title'];
		$columns = is_array( $payload['columns'] ) ? $payload['columns'] : [];
		$rows    = is_array( $payload['rows'] ) ? $payload['rows'] : [];

		include HLAVAS_TERMS_DIR . 'admin/views/print-report.php';
	}

	/**
	 * Serialize report into CSV.
	 *
	 * @param array<string, mixed> $payload Report payload.
	 * @return string
	 */
	private function serialize_report_csv( array $payload ): string {
		$columns = is_array( $payload['columns'] ) ? $payload['columns'] : [];
		$rows    = is_array( $payload['rows'] ) ? $payload['rows'] : [];
		$stream  = fopen( 'php://temp', 'r+' );

		if ( false === $stream ) {
			return '';
		}

		fputs( $stream, "\xEF\xBB\xBF" );
		fputcsv( $stream, array_values( $columns ), ';' );

		foreach ( $rows as $row ) {
			$line = [];
			foreach ( array_keys( $columns ) as $key ) {
				$line[] = is_scalar( $row[ $key ] ?? '' ) ? (string) $row[ $key ] : '';
			}
			fputcsv( $stream, $line, ';' );
		}

		rewind( $stream );
		$content = stream_get_contents( $stream );
		fclose( $stream );

		return is_string( $content ) ? $content : '';
	}

	/**
	 * Serialize report into simple Excel-compatible HTML table.
	 *
	 * @param array<string, mixed> $payload Report payload.
	 * @return string
	 */
	private function serialize_report_xls( array $payload ): string {
		$columns = is_array( $payload['columns'] ) ? $payload['columns'] : [];
		$rows    = is_array( $payload['rows'] ) ? $payload['rows'] : [];
		ob_start();
		?>
		<html>
		<head>
			<meta charset="utf-8">
		</head>
		<body>
			<table border="1">
				<thead>
					<tr>
						<?php foreach ( $columns as $label ) : ?>
							<th><?php echo esc_html( (string) $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<?php foreach ( array_keys( $columns ) as $key ) : ?>
								<td><?php echo esc_html( is_scalar( $row[ $key ] ?? '' ) ? (string) $row[ $key ] : '' ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</body>
		</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Collect filters for current terms page.
	 *
	 * @param string|null $term_type Fixed term type.
	 * @return array<string, mixed>
	 */
	private function get_term_filters_from_request( ?string $term_type ): array {
		$filter_type = $term_type ?? sanitize_text_field( wp_unslash( $_REQUEST['filter_type'] ?? '' ) );
		$filters     = [
			'term_type' => '' !== $filter_type ? $filter_type : null,
		];

		if ( isset( $_REQUEST['filter_active'] ) && '' !== (string) $_REQUEST['filter_active'] ) {
			$filters['is_active'] = (bool) absint( $_REQUEST['filter_active'] );
		}

		if ( isset( $_REQUEST['filter_archived'] ) && '' !== (string) $_REQUEST['filter_archived'] ) {
			$filters['is_archived'] = (bool) absint( $_REQUEST['filter_archived'] );
		}

		if ( isset( $_REQUEST['filter_future'] ) && '1' === (string) $_REQUEST['filter_future'] ) {
			$filters['future_only'] = true;
		}

		return $filters;
	}

	/**
	 * Collect filters for participants page.
	 *
	 * @return array<string, mixed>
	 */
	private function get_participant_filters_from_request(): array {
		$qualification_type_id = absint( $_REQUEST['qualification_type_id'] ?? 0 );
		$term_type             = sanitize_text_field( wp_unslash( $_REQUEST['participant_term_type'] ?? '' ) );
		$term_id               = absint( $_REQUEST['participant_term_id'] ?? 0 );

		return [
			'qualification_type_id' => $qualification_type_id,
			'term_type'             => in_array( $term_type, [ 'kurz', 'zkouska' ], true ) ? $term_type : '',
			'term_id'               => $term_id,
		];
	}

	/**
	 * Build redirect URL back to current report page.
	 *
	 * @param string $page_slug Page slug.
	 * @param string $report_message Message code.
	 * @return string
	 */
	private function get_report_redirect_url( string $page_slug, string $report_message ): string {
		$args = [
			'page'           => $page_slug,
			'report_message' => $report_message,
		];

		if ( in_array( $page_slug, [ 'hlavas-terms-kurzy', 'hlavas-terms-zkousky' ], true ) ) {
			if ( isset( $_REQUEST['filter_active'] ) && '' !== (string) $_REQUEST['filter_active'] ) {
				$args['filter_active'] = sanitize_text_field( wp_unslash( $_REQUEST['filter_active'] ) );
			}
			if ( isset( $_REQUEST['filter_archived'] ) && '' !== (string) $_REQUEST['filter_archived'] ) {
				$args['filter_archived'] = sanitize_text_field( wp_unslash( $_REQUEST['filter_archived'] ) );
			}
			if ( isset( $_REQUEST['filter_future'] ) && '1' === (string) $_REQUEST['filter_future'] ) {
				$args['filter_future'] = '1';
			}
		}

		if ( 'hlavas-terms-participants' === $page_slug ) {
			if ( isset( $_REQUEST['qualification_type_id'] ) && '' !== (string) $_REQUEST['qualification_type_id'] ) {
				$args['qualification_type_id'] = absint( $_REQUEST['qualification_type_id'] );
			}
			if ( isset( $_REQUEST['participant_term_type'] ) && '' !== (string) $_REQUEST['participant_term_type'] ) {
				$args['participant_term_type'] = sanitize_text_field( wp_unslash( $_REQUEST['participant_term_type'] ) );
			}
			if ( isset( $_REQUEST['participant_term_id'] ) && '' !== (string) $_REQUEST['participant_term_id'] ) {
				$args['participant_term_id'] = absint( $_REQUEST['participant_term_id'] );
			}
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Handle qualification type save.
	 *
	 * @return void
	 */
	private function handle_type_save(): void {
		$type_id = (int) ( $_POST['type_id'] ?? 0 );
		$data    = [
			'name'                 => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'description'          => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
			'notes'                => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
			'is_accredited'        => isset( $_POST['is_accredited'] ) ? 1 : 0,
			'accreditation_number' => sanitize_text_field( wp_unslash( $_POST['accreditation_number'] ?? '' ) ),
			'has_courses'          => isset( $_POST['has_courses'] ) ? 1 : 0,
			'has_exams'            => isset( $_POST['has_exams'] ) ? 1 : 0,
			'course_form_id'       => absint( $_POST['course_form_id'] ?? 0 ),
			'exam_form_id'         => absint( $_POST['exam_form_id'] ?? 0 ),
			'sort_order'           => (int) ( $_POST['sort_order'] ?? 0 ),
		];

		if ( '' === $data['name'] ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms-types&error=missing_name' )
			);
			exit;
		}

		if ( empty( $data['has_courses'] ) && empty( $data['has_exams'] ) ) {
			$data['has_courses'] = 1;
			$data['has_exams']   = 1;
		}

		if ( $type_id > 0 ) {
			$this->type_repo->update( $type_id, $data );
			$redirect = 'updated';
		} else {
			$type_id  = (int) $this->type_repo->insert( $data );
			$redirect = 'created';
		}

		wp_safe_redirect(
			admin_url( 'admin.php?page=hlavas-terms-types&type_id=' . $type_id . '&message=' . $redirect )
		);
		exit;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @return void
	 */
	private function handle_bulk(): void {
		$action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ?? '' ) );
		$ids    = array_map( 'intval', (array) ( $_POST['term_ids'] ?? [] ) );

		if ( empty( $ids ) || empty( $action ) ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms' )
			);
			exit;
		}

		switch ( $action ) {
			case 'activate':
				$this->repo->bulk_activate( $ids );
				break;

			case 'deactivate':
				$this->repo->bulk_deactivate( $ids );
				break;

			case 'archive':
				$this->repo->bulk_archive( $ids );
				break;

			case 'delete':
				$this->repo->bulk_delete( $ids );
				break;

			case 'regenerate_labels':
				foreach ( $ids as $term_id ) {
					$term = $this->repo->find( $term_id );

					if ( $term ) {
						$new_label = Hlavas_Terms_Label_Builder::build(
							$term->term_type,
							$term->date_start,
							$term->date_end
						);

						$this->repo->update(
							$term_id,
							[
								'label' => $new_label,
							]
						);
					}
				}
				break;

			case 'sync':
				$this->sync->execute( hlavas_terms_get_sync_value_mode() );
				break;
		}

		wp_safe_redirect(
			admin_url( 'admin.php?page=hlavas-terms&message=bulk_done' )
		);
		exit;
	}

	/**
	 * Handle plugin settings save.
	 *
	 * @return void
	 */
	private function handle_settings_save(): void {
		$form_id         = absint( $_POST['hlavas_terms_form_id'] ?? HLAVAS_TERMS_DEFAULT_FORM_ID );
		$debug_mode      = isset( $_POST['hlavas_terms_debug_mode'] ) ? 1 : 0;
		$sync_value_mode = sanitize_text_field( wp_unslash( $_POST['hlavas_terms_sync_value_mode'] ?? 'term_key' ) );
		$report_email    = sanitize_email( wp_unslash( $_POST['hlavas_terms_report_email'] ?? '' ) );

		if ( $form_id <= 0 ) {
			$form_id = HLAVAS_TERMS_DEFAULT_FORM_ID;
		}

		if ( ! in_array( $sync_value_mode, [ 'term_key', 'label' ], true ) ) {
			$sync_value_mode = 'term_key';
		}

		if ( ! is_email( $report_email ) ) {
			$report_email = (string) get_bloginfo( 'admin_email' );
		}

		update_option( HLAVAS_TERMS_OPTION_FORM_ID, $form_id );
		update_option( HLAVAS_TERMS_OPTION_DEBUG_MODE, $debug_mode );
		update_option( HLAVAS_TERMS_OPTION_SYNC_VALUE_MODE, $sync_value_mode );
		update_option( HLAVAS_TERMS_OPTION_REPORT_EMAIL, $report_email );

		wp_safe_redirect(
			admin_url( 'admin.php?page=hlavas-terms-settings&message=saved' )
		);
		exit;
	}

	/**
	 * Export full plugin settings and data backup.
	 *
	 * @return void
	 */
	private function handle_settings_export(): void {
		$payload  = $this->build_settings_backup_payload();
		$filename = 'hlavas-terms-backup-' . gmdate( 'Y-m-d-His' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/**
	 * Import full plugin settings and data backup.
	 *
	 * @return void
	 */
	private function handle_settings_import(): void {
		$replace_existing = ! empty( $_POST['hlavas_terms_replace_existing'] );

		if ( empty( $_FILES['hlavas_terms_backup_file']['tmp_name'] ) ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms-settings&message=import_missing_file' )
			);
			exit;
		}

		$tmp_name      = (string) ( $_FILES['hlavas_terms_backup_file']['tmp_name'] ?? '' );
		$file_contents = '' !== $tmp_name ? file_get_contents( $tmp_name ) : false;
		$payload       = is_string( $file_contents ) ? json_decode( $file_contents, true ) : null;

		if ( ! is_array( $payload ) ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms-settings&message=import_invalid_file' )
			);
			exit;
		}

		$result_message = $this->import_settings_backup_payload( $payload, $replace_existing );

		wp_safe_redirect(
			admin_url( 'admin.php?page=hlavas-terms-settings&message=' . rawurlencode( $result_message ) )
		);
		exit;
	}

	/**
	 * Reset sync log timestamps.
	 *
	 * @return void
	 */
	private function handle_sync_log_reset(): void {
		update_option( HLAVAS_TERMS_OPTION_SYNC_LOG, [], false );
		update_option( HLAVAS_TERMS_OPTION_FORM_SYNC_LOG, [], false );

		wp_safe_redirect(
			admin_url( 'admin.php?page=hlavas-terms-settings&message=sync_log_reset' )
		);
		exit;
	}

	/* ---------------------------------------------------------------
	 * PAGES
	 * ------------------------------------------------------------- */

	/**
	 * List all terms.
	 *
	 * @return void
	 */
	public function page_list(): void {
		$this->render_list_page( null );
	}

	/**
	 * List only kurzy.
	 *
	 * @return void
	 */
	public function page_list_kurzy(): void {
		$this->render_list_page( 'kurz' );
	}

	/**
	 * List only zkoušky.
	 *
	 * @return void
	 */
	public function page_list_zkousky(): void {
		$this->render_list_page( 'zkouska' );
	}

	/**
	 * Render list page.
	 *
	 * @param string|null $term_type Fixed term type filter.
	 * @return void
	 */
	private function render_list_page( ?string $term_type ): void {
		$filter_type = $term_type ?? sanitize_text_field( wp_unslash( $_GET['filter_type'] ?? '' ) );
		$filters     = [
			'term_type' => '' !== $filter_type ? $filter_type : null,
		];

		if ( isset( $_GET['filter_active'] ) && '' !== (string) $_GET['filter_active'] ) {
			$filters['is_active'] = (bool) absint( $_GET['filter_active'] );
		}

		if ( isset( $_GET['filter_archived'] ) && '' !== (string) $_GET['filter_archived'] ) {
			$filters['is_archived'] = (bool) absint( $_GET['filter_archived'] );
		}

		if ( isset( $_GET['filter_future'] ) && '1' === (string) $_GET['filter_future'] ) {
			$filters['future_only'] = true;
		}

		$terms   = $this->repo->get_all( $filters );
		$sync_log       = hlavas_terms_get_sync_log();
		$message        = sanitize_text_field( wp_unslash( $_GET['message'] ?? '' ) );
		$report_message = sanitize_text_field( wp_unslash( $_GET['report_message'] ?? '' ) );

		include HLAVAS_TERMS_DIR . 'admin/views/list.php';
	}

	/**
	 * Render edit/add page.
	 *
	 * @return void
	 */
	public function page_edit(): void {
		$term_id             = (int) ( $_GET['term_id'] ?? 0 );
		$term                = $term_id > 0 ? $this->repo->find( $term_id ) : null;
		$error               = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );
		$message             = sanitize_text_field( wp_unslash( $_GET['message'] ?? '' ) );
		$qualification_types = $this->type_repo->get_all();
		$last_synced_at      = $term_id > 0 ? hlavas_terms_get_term_last_synced_at( $term_id ) : '';
		$term_sync_result    = get_transient( 'hlavas_term_sync_result_' . get_current_user_id() );

		if ( $term_sync_result ) {
			delete_transient( 'hlavas_term_sync_result_' . get_current_user_id() );
		}

		include HLAVAS_TERMS_DIR . 'admin/views/edit.php';
	}

	/**
	 * Render sync page.
	 *
	 * @return void
	 */
	public function page_sync(): void {
		$selected_form_id = absint( $_GET['form_id'] ?? 0 );
		$show_debug       = hlavas_terms_is_debug_enabled() || isset( $_GET['debug'] );
		$form_sections    = $this->sync->get_form_sections();
		$debug            = $show_debug ? $this->sync->debug( $selected_form_id > 0 ? $selected_form_id : null ) : null;
		$sync_result      = get_transient( 'hlavas_sync_result' );

		if ( $sync_result ) {
			delete_transient( 'hlavas_sync_result' );
		}

		include HLAVAS_TERMS_DIR . 'admin/views/sync.php';
	}

	/**
	 * Render availability page.
	 *
	 * @return void
	 */
	public function page_availability(): void {
		$report         = $this->availability->get_availability_report();
		$report_message = sanitize_text_field( wp_unslash( $_GET['report_message'] ?? '' ) );

		include HLAVAS_TERMS_DIR . 'admin/views/availability.php';
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function page_settings(): void {
		$message           = sanitize_text_field( wp_unslash( $_GET['message'] ?? '' ) );
		$form_id           = hlavas_terms_get_form_id();
		$debug_mode        = hlavas_terms_is_debug_enabled();
		$sync_value_mode   = hlavas_terms_get_sync_value_mode();
		$report_email      = hlavas_terms_get_report_email();
		$plugin_info       = hlavas_terms_get_plugin_info();
		$types             = $this->type_repo->get_all();
		$settings_status   = $this->get_settings_status();
		$form_registry     = $this->get_settings_form_registry( $types, $form_id );
		$sync_log          = hlavas_terms_get_sync_log();

		include HLAVAS_TERMS_DIR . 'admin/views/settings.php';
	}

	/**
	 * Build status summary for settings page.
	 *
	 * @return array<string, mixed>
	 */
	private function get_settings_status(): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$terms_table               = hlavas_terms_get_table_name();
		$types_table               = hlavas_terms_get_types_table_name();
		$fluent_forms_table        = $wpdb->prefix . 'fluentform_forms';
		$fluent_submissions_table  = $wpdb->prefix . 'fluentform_submissions';
		$terms_table_exists        = $this->table_exists( $terms_table );
		$types_table_exists        = $this->table_exists( $types_table );
		$fluent_forms_table_exists = $this->table_exists( $fluent_forms_table );
		$fluent_subs_table_exists  = $this->table_exists( $fluent_submissions_table );
		$types_count               = $types_table_exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$types_table}" ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$terms_count               = $terms_table_exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$terms_table}" ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$db_version                = (string) get_option( 'hlavas_terms_db_version', '0' );

		return [
			'terms_table_exists'        => $terms_table_exists,
			'types_table_exists'        => $types_table_exists,
			'fluent_forms_table_exists' => $fluent_forms_table_exists,
			'fluent_subs_table_exists'  => $fluent_subs_table_exists,
			'terms_count'               => $terms_count,
			'types_count'               => $types_count,
			'sync_log_count'            => count( hlavas_terms_get_sync_log() ),
			'db_version'                => $db_version,
			'plugin_version'            => HLAVAS_TERMS_VERSION,
		];
	}

	/**
	 * Build registry of configured forms across plugin and qualification types.
	 *
	 * @param array<int, object> $types Qualification types.
	 * @param int                $default_form_id Default form ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_settings_form_registry( array $types, int $default_form_id ): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$form_ids = [];

		if ( $default_form_id > 0 ) {
			$form_ids[] = $default_form_id;
		}

		foreach ( $types as $type ) {
			$form_ids[] = (int) ( $type->course_form_id ?? 0 );
			$form_ids[] = (int) ( $type->exam_form_id ?? 0 );
		}

		$form_ids = array_values( array_unique( array_filter( array_map( 'intval', $form_ids ) ) ) );
		$forms    = [];

		if ( ! empty( $form_ids ) && $this->table_exists( $wpdb->prefix . 'fluentform_forms' ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
			$rows         = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, title, status FROM {$wpdb->prefix}fluentform_forms WHERE id IN ({$placeholders})",
					...$form_ids
				)
			);

			if ( is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					$forms[ (int) $row->id ] = [
						'title'  => (string) ( $row->title ?? '' ),
						'status' => (string) ( $row->status ?? '' ),
					];
				}
			}
		}

		$registry = [];

		if ( $default_form_id > 0 ) {
			$registry[] = [
				'form_id'    => $default_form_id,
				'usage'      => 'Výchozí formulář pluginu',
				'exists'     => isset( $forms[ $default_form_id ] ),
				'form_title' => (string) ( $forms[ $default_form_id ]['title'] ?? '' ),
				'status'     => (string) ( $forms[ $default_form_id ]['status'] ?? '' ),
			];
		}

		foreach ( $types as $type ) {
			$qualification_label = ! empty( $type->accreditation_number )
				? $type->accreditation_number . ' - ' . $type->name
				: $type->name;

			$course_form_id = (int) ( $type->course_form_id ?? 0 );
			$exam_form_id   = (int) ( $type->exam_form_id ?? 0 );

			if ( $course_form_id > 0 ) {
				$registry[] = [
					'form_id'    => $course_form_id,
					'usage'      => $qualification_label . ' / kurz',
					'exists'     => isset( $forms[ $course_form_id ] ),
					'form_title' => (string) ( $forms[ $course_form_id ]['title'] ?? '' ),
					'status'     => (string) ( $forms[ $course_form_id ]['status'] ?? '' ),
				];
			}

			if ( $exam_form_id > 0 ) {
				$registry[] = [
					'form_id'    => $exam_form_id,
					'usage'      => $qualification_label . ' / zkouška',
					'exists'     => isset( $forms[ $exam_form_id ] ),
					'form_title' => (string) ( $forms[ $exam_form_id ]['title'] ?? '' ),
					'status'     => (string) ( $forms[ $exam_form_id ]['status'] ?? '' ),
				];
			}
		}

		return $registry;
	}

	/**
	 * Build full JSON backup payload.
	 *
	 * @return array<string, mixed>
	 */
	private function build_settings_backup_payload(): array {
		global $wpdb;
		/** @var wpdb $wpdb */

		$terms_table = hlavas_terms_get_table_name();
		$types_table = hlavas_terms_get_types_table_name();
		$terms       = $this->table_exists( $terms_table ) ? $wpdb->get_results( "SELECT * FROM {$terms_table} ORDER BY id ASC", ARRAY_A ) : []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$types       = $this->table_exists( $types_table ) ? $wpdb->get_results( "SELECT * FROM {$types_table} ORDER BY id ASC", ARRAY_A ) : []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return [
			'meta'     => [
				'plugin'       => HLAVAS_TERMS_PLUGIN_SLUG,
				'version'      => HLAVAS_TERMS_VERSION,
				'exported_at'  => current_time( 'mysql' ),
				'site_url'     => home_url(),
				'db_version'   => (string) get_option( 'hlavas_terms_db_version', HLAVAS_TERMS_VERSION ),
			],
			'options'  => [
				HLAVAS_TERMS_OPTION_FORM_ID        => hlavas_terms_get_form_id(),
				HLAVAS_TERMS_OPTION_DEBUG_MODE     => hlavas_terms_is_debug_enabled() ? 1 : 0,
				HLAVAS_TERMS_OPTION_SYNC_VALUE_MODE => hlavas_terms_get_sync_value_mode(),
				HLAVAS_TERMS_OPTION_REPORT_EMAIL   => hlavas_terms_get_report_email(),
				HLAVAS_TERMS_OPTION_SYNC_LOG       => hlavas_terms_get_sync_log(),
				HLAVAS_TERMS_OPTION_FORM_SYNC_LOG  => hlavas_terms_get_form_sync_log(),
			],
			'database' => [
				'qualification_types' => is_array( $types ) ? $types : [],
				'terms'               => is_array( $terms ) ? $terms : [],
			],
		];
	}

	/**
	 * Import JSON backup payload into plugin settings and tables.
	 *
	 * @param array<string, mixed> $payload Backup payload.
	 * @param bool                 $replace_existing Whether to replace current records.
	 * @return string
	 */
	private function import_settings_backup_payload( array $payload, bool $replace_existing ): string {
		global $wpdb;
		/** @var wpdb $wpdb */

		if ( empty( $payload['database'] ) || ! is_array( $payload['database'] ) ) {
			return 'import_invalid_file';
		}

		require_once HLAVAS_TERMS_DIR . 'includes/class-activator.php';
		Hlavas_Terms_Activator::activate();

		$types_rows = is_array( $payload['database']['qualification_types'] ?? null ) ? $payload['database']['qualification_types'] : [];
		$term_rows  = is_array( $payload['database']['terms'] ?? null ) ? $payload['database']['terms'] : [];
		$terms_table = hlavas_terms_get_table_name();
		$types_table = hlavas_terms_get_types_table_name();

		if ( $replace_existing ) {
			$wpdb->query( "DELETE FROM {$terms_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "DELETE FROM {$types_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		foreach ( $types_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$wpdb->replace(
				$types_table,
				[
					'id'                   => (int) ( $row['id'] ?? 0 ),
					'type_key'             => (string) ( $row['type_key'] ?? '' ),
					'name'                 => (string) ( $row['name'] ?? '' ),
					'description'          => (string) ( $row['description'] ?? '' ),
					'notes'                => (string) ( $row['notes'] ?? '' ),
					'is_accredited'        => (int) ( $row['is_accredited'] ?? 0 ),
					'accreditation_number' => (string) ( $row['accreditation_number'] ?? '' ),
					'has_courses'          => (int) ( $row['has_courses'] ?? 0 ),
					'has_exams'            => (int) ( $row['has_exams'] ?? 0 ),
					'course_form_id'       => (int) ( $row['course_form_id'] ?? 0 ),
					'exam_form_id'         => (int) ( $row['exam_form_id'] ?? 0 ),
					'sort_order'           => (int) ( $row['sort_order'] ?? 0 ),
					'created_at'           => (string) ( $row['created_at'] ?? current_time( 'mysql' ) ),
					'updated_at'           => (string) ( $row['updated_at'] ?? current_time( 'mysql' ) ),
				],
				[
					'%d', '%s', '%s', '%s', '%s', '%d', '%s',
					'%d', '%d', '%d', '%d', '%d', '%s', '%s',
				]
			);
		}

		foreach ( $term_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$wpdb->replace(
				$terms_table,
				[
					'id'                    => (int) ( $row['id'] ?? 0 ),
					'term_type'             => (string) ( $row['term_type'] ?? 'kurz' ),
					'term_key'              => (string) ( $row['term_key'] ?? '' ),
					'qualification_type_id' => ! empty( $row['qualification_type_id'] ) ? (int) $row['qualification_type_id'] : null,
					'title'                 => (string) ( $row['title'] ?? '' ),
					'label'                 => (string) ( $row['label'] ?? '' ),
					'date_start'            => (string) ( $row['date_start'] ?? '' ),
					'date_end'              => ! empty( $row['date_end'] ) ? (string) $row['date_end'] : null,
					'enrollment_deadline'   => ! empty( $row['enrollment_deadline'] ) ? (string) $row['enrollment_deadline'] : null,
					'capacity'              => (int) ( $row['capacity'] ?? 0 ),
					'is_visible'            => (int) ( $row['is_visible'] ?? 0 ),
					'is_active'             => (int) ( $row['is_active'] ?? 0 ),
					'is_archived'           => (int) ( $row['is_archived'] ?? 0 ),
					'sort_order'            => (int) ( $row['sort_order'] ?? 0 ),
					'notes'                 => (string) ( $row['notes'] ?? '' ),
					'created_at'            => (string) ( $row['created_at'] ?? current_time( 'mysql' ) ),
					'updated_at'            => (string) ( $row['updated_at'] ?? current_time( 'mysql' ) ),
				],
				[
					'%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s',
					'%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s',
				]
			);
		}

		$options = is_array( $payload['options'] ?? null ) ? $payload['options'] : [];
		update_option( HLAVAS_TERMS_OPTION_FORM_ID, (int) ( $options[ HLAVAS_TERMS_OPTION_FORM_ID ] ?? HLAVAS_TERMS_DEFAULT_FORM_ID ) );
		update_option( HLAVAS_TERMS_OPTION_DEBUG_MODE, ! empty( $options[ HLAVAS_TERMS_OPTION_DEBUG_MODE ] ) ? 1 : 0 );
		update_option(
			HLAVAS_TERMS_OPTION_SYNC_VALUE_MODE,
			in_array( (string) ( $options[ HLAVAS_TERMS_OPTION_SYNC_VALUE_MODE ] ?? 'term_key' ), [ 'term_key', 'label' ], true )
				? (string) $options[ HLAVAS_TERMS_OPTION_SYNC_VALUE_MODE ]
				: 'term_key'
		);
		update_option(
			HLAVAS_TERMS_OPTION_SYNC_LOG,
			is_array( $options[ HLAVAS_TERMS_OPTION_SYNC_LOG ] ?? null ) ? $options[ HLAVAS_TERMS_OPTION_SYNC_LOG ] : [],
			false
		);
		update_option(
			HLAVAS_TERMS_OPTION_FORM_SYNC_LOG,
			is_array( $options[ HLAVAS_TERMS_OPTION_FORM_SYNC_LOG ] ?? null ) ? $options[ HLAVAS_TERMS_OPTION_FORM_SYNC_LOG ] : [],
			false
		);
		update_option(
			HLAVAS_TERMS_OPTION_REPORT_EMAIL,
			is_email( (string) ( $options[ HLAVAS_TERMS_OPTION_REPORT_EMAIL ] ?? '' ) )
				? sanitize_email( (string) $options[ HLAVAS_TERMS_OPTION_REPORT_EMAIL ] )
				: (string) get_bloginfo( 'admin_email' )
		);
		update_option( 'hlavas_terms_db_version', HLAVAS_TERMS_VERSION );

		return 'imported';
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

	/**
	 * Render plugin info page.
	 *
	 * @return void
	 */
	public function page_info(): void {
		$plugin_info   = hlavas_terms_get_plugin_info();
		$types         = $this->type_repo->get_all();
		$form_registry = $this->get_settings_form_registry( $types, hlavas_terms_get_form_id() );

		include HLAVAS_TERMS_DIR . 'admin/views/info.php';
	}

	/**
	 * Render qualification types page.
	 *
	 * @return void
	 */
	public function page_types(): void {
		$message      = sanitize_text_field( wp_unslash( $_GET['message'] ?? '' ) );
		$error        = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );
		$type_id      = (int) ( $_GET['type_id'] ?? 0 );
		$types        = $this->type_repo->get_all();
		$current_type = $type_id > 0 ? $this->type_repo->find( $type_id ) : null;

		include HLAVAS_TERMS_DIR . 'admin/views/types.php';
	}

	/**
	 * Render participants placeholder page.
	 *
	 * @return void
	 */
	public function page_participants(): void {
		$qualification_type_id = absint( $_GET['qualification_type_id'] ?? 0 );
		$term_type             = sanitize_text_field( wp_unslash( $_GET['participant_term_type'] ?? '' ) );
		$term_id               = absint( $_GET['participant_term_id'] ?? 0 );
		$filters               = [
			'qualification_type_id' => $qualification_type_id,
			'term_type'             => in_array( $term_type, [ 'kurz', 'zkouska' ], true ) ? $term_type : '',
			'term_id'               => $term_id,
		];
		$participants          = $this->participants->get_participants( $filters );
		$qualification_types   = $this->type_repo->get_all();
		$terms                 = $this->repo->get_all(
			[
				'is_archived' => false,
				'orderby'     => 'date_start',
				'order'       => 'ASC',
			]
		);
		$report_message        = sanitize_text_field( wp_unslash( $_GET['report_message'] ?? '' ) );

		include HLAVAS_TERMS_DIR . 'admin/views/participants.php';
	}
}
