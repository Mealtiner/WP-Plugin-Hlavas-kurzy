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
			$value_mode = sanitize_text_field( wp_unslash( $_POST['value_mode'] ?? 'term_key' ) );
			$result     = $this->sync->execute( $value_mode );

			set_transient( 'hlavas_sync_result', $result, 60 );

			wp_safe_redirect(
				admin_url( 'admin.php?page=hlavas-terms-sync&synced=1' )
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
				$this->sync->execute();
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
		$form_id    = absint( $_POST['hlavas_terms_form_id'] ?? HLAVAS_TERMS_DEFAULT_FORM_ID );
		$debug_mode = isset( $_POST['hlavas_terms_debug_mode'] ) ? 1 : 0;

		if ( $form_id <= 0 ) {
			$form_id = HLAVAS_TERMS_DEFAULT_FORM_ID;
		}

		update_option( HLAVAS_TERMS_OPTION_FORM_ID, $form_id );
		update_option( HLAVAS_TERMS_OPTION_DEBUG_MODE, $debug_mode );

		wp_safe_redirect(
			admin_url( 'admin.php?page=hlavas-terms-settings&message=saved' )
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
		$message = sanitize_text_field( wp_unslash( $_GET['message'] ?? '' ) );

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

		include HLAVAS_TERMS_DIR . 'admin/views/edit.php';
	}

	/**
	 * Render sync page.
	 *
	 * @return void
	 */
	public function page_sync(): void {
		$preview     = $this->sync->preview();
		$debug       = ( hlavas_terms_is_debug_enabled() || isset( $_GET['debug'] ) ) ? $this->sync->debug() : null;
		$sync_result = get_transient( 'hlavas_sync_result' );

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
		$report = $this->availability->get_availability_report();

		include HLAVAS_TERMS_DIR . 'admin/views/availability.php';
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function page_settings(): void {
		$message    = sanitize_text_field( wp_unslash( $_GET['message'] ?? '' ) );
		$form_id    = hlavas_terms_get_form_id();
		$debug_mode = hlavas_terms_is_debug_enabled();

		include HLAVAS_TERMS_DIR . 'admin/views/settings.php';
	}

	/**
	 * Render plugin info page.
	 *
	 * @return void
	 */
	public function page_info(): void {
		$plugin_info = hlavas_terms_get_plugin_info();

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

		include HLAVAS_TERMS_DIR . 'admin/views/participants.php';
	}
}
