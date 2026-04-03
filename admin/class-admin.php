<?php
/**
 * Admin controller: menu, list table, edit form, sync actions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hlavas_Terms_Admin {

    private Hlavas_Terms_Repository $repo;
    private Hlavas_Terms_Fluent_Sync_Service $sync;
    private Hlavas_Terms_Availability_Service $availability;

    public function __construct() {
        $this->repo         = new Hlavas_Terms_Repository();
        $this->sync         = new Hlavas_Terms_Fluent_Sync_Service( $this->repo );
        $this->availability = new Hlavas_Terms_Availability_Service( $this->repo );

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'handle_actions' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /* ---------------------------------------------------------------
     * MENU
     * ------------------------------------------------------------- */

    public function register_menu(): void {
        // Main menu
        add_menu_page(
            'Přihlášky – Termíny',
            'Přihlášky',
            'manage_options',
            'hlavas-terms',
            [ $this, 'page_list' ],
            'dashicons-calendar-alt',
            30
        );

        // Submenu: All terms
        add_submenu_page(
            'hlavas-terms',
            'Všechny termíny',
            'Všechny termíny',
            'manage_options',
            'hlavas-terms',
            [ $this, 'page_list' ]
        );

        // Submenu: Kurzy
        add_submenu_page(
            'hlavas-terms',
            'Kurzy',
            'Kurzy',
            'manage_options',
            'hlavas-terms-kurzy',
            [ $this, 'page_list_kurzy' ]
        );

        // Submenu: Zkoušky
        add_submenu_page(
            'hlavas-terms',
            'Zkoušky',
            'Zkoušky',
            'manage_options',
            'hlavas-terms-zkousky',
            [ $this, 'page_list_zkousky' ]
        );

        // Submenu: Add new
        add_submenu_page(
            'hlavas-terms',
            'Přidat termín',
            'Přidat termín',
            'manage_options',
            'hlavas-terms-edit',
            [ $this, 'page_edit' ]
        );

        // Submenu: Sync
        add_submenu_page(
            'hlavas-terms',
            'Synchronizace',
            'Synchronizace do FF',
            'manage_options',
            'hlavas-terms-sync',
            [ $this, 'page_sync' ]
        );

        // Submenu: Obsazenost
        add_submenu_page(
            'hlavas-terms',
            'Obsazenost',
            'Obsazenost',
            'manage_options',
            'hlavas-terms-availability',
            [ $this, 'page_availability' ]
        );
    }

    /* ---------------------------------------------------------------
     * ASSETS
     * ------------------------------------------------------------- */

    public function enqueue_assets( string $hook ): void {
        // Only load on our pages
        if ( strpos( $hook, 'hlavas-terms' ) === false ) {
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
     * ACTION HANDLER (POST / GET)
     * ------------------------------------------------------------- */

    public function handle_actions(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle save (create / update)
        if ( isset( $_POST['hlavas_term_save'] ) && wp_verify_nonce( $_POST['_hlavas_nonce'] ?? '', 'hlavas_term_save' ) ) {
            $this->handle_save();
        }

        // Handle delete
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['term_id'] ) ) {
            if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'hlavas_delete_' . $_GET['term_id'] ) ) {
                wp_die( 'Neplatný bezpečnostní token.' );
            }
            $this->repo->delete( (int) $_GET['term_id'] );
            wp_redirect( admin_url( 'admin.php?page=hlavas-terms&message=deleted' ) );
            exit;
        }

        // Handle bulk actions
        if ( isset( $_POST['hlavas_bulk_action'] ) && wp_verify_nonce( $_POST['_hlavas_bulk_nonce'] ?? '', 'hlavas_bulk' ) ) {
            $this->handle_bulk();
        }

        // Handle sync execute
        if ( isset( $_POST['hlavas_sync_execute'] ) && wp_verify_nonce( $_POST['_hlavas_sync_nonce'] ?? '', 'hlavas_sync' ) ) {
            $value_mode = sanitize_text_field( $_POST['value_mode'] ?? 'term_key' );
            $result     = $this->sync->execute( $value_mode );
            set_transient( 'hlavas_sync_result', $result, 60 );
            wp_redirect( admin_url( 'admin.php?page=hlavas-terms-sync&synced=1' ) );
            exit;
        }
    }

    /**
     * Handle term save (create/update).
     */
    private function handle_save(): void {
        $id = (int) ( $_POST['term_id'] ?? 0 );

        $data = [
            'term_type'   => sanitize_text_field( $_POST['term_type'] ?? 'kurz' ),
            'term_key'    => sanitize_text_field( $_POST['term_key'] ?? '' ),
            'title'       => sanitize_text_field( $_POST['title'] ?? '' ),
            'label'       => sanitize_text_field( $_POST['label'] ?? '' ),
            'date_start'  => sanitize_text_field( $_POST['date_start'] ?? '' ),
            'date_end'    => sanitize_text_field( $_POST['date_end'] ?? '' ) ?: null,
            'capacity'    => absint( $_POST['capacity'] ?? 0 ),
            'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0,
            'is_archived' => isset( $_POST['is_archived'] ) ? 1 : 0,
            'sort_order'  => (int) ( $_POST['sort_order'] ?? 0 ),
            'notes'       => sanitize_textarea_field( $_POST['notes'] ?? '' ),
        ];

        // Validate required fields
        if ( empty( $data['term_key'] ) || empty( $data['date_start'] ) ) {
            wp_redirect( admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . $id . '&error=missing_fields' ) );
            exit;
        }

        // Check unique term_key
        if ( $this->repo->key_exists( $data['term_key'], $id ?: null ) ) {
            wp_redirect( admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . $id . '&error=duplicate_key' ) );
            exit;
        }

        // Validate term_type specific rules
        if ( $data['term_type'] === 'kurz' && empty( $data['date_end'] ) ) {
            $data['date_end'] = $data['date_start']; // Fallback
        }
        if ( $data['term_type'] === 'zkouska' ) {
            $data['date_end'] = $data['date_start'];
        }

        if ( $id > 0 ) {
            $this->repo->update( $id, $data );
            $redirect_id = $id;
        } else {
            $redirect_id = $this->repo->insert( $data );
        }

        wp_redirect( admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . $redirect_id . '&message=saved' ) );
        exit;
    }

    /**
     * Handle bulk actions.
     */
    private function handle_bulk(): void {
        $action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
        $ids    = array_map( 'intval', $_POST['term_ids'] ?? [] );

        if ( empty( $ids ) || empty( $action ) ) {
            wp_redirect( admin_url( 'admin.php?page=hlavas-terms' ) );
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
                        $this->repo->update( $term_id, [ 'label' => $new_label ] );
                    }
                }
                break;
            case 'sync':
                $this->sync->execute();
                break;
        }

        wp_redirect( admin_url( 'admin.php?page=hlavas-terms&message=bulk_done' ) );
        exit;
    }

    /* ---------------------------------------------------------------
     * PAGES
     * ------------------------------------------------------------- */

    /**
     * List all terms page.
     */
    public function page_list(): void {
        $this->render_list_page( null );
    }

    public function page_list_kurzy(): void {
        $this->render_list_page( 'kurz' );
    }

    public function page_list_zkousky(): void {
        $this->render_list_page( 'zkouska' );
    }

    private function render_list_page( ?string $term_type ): void {
        $filters = [
            'term_type' => $term_type ?? ( $_GET['filter_type'] ?? null ),
        ];

        // Additional filters
        if ( isset( $_GET['filter_active'] ) && $_GET['filter_active'] !== '' ) {
            $filters['is_active'] = (bool) $_GET['filter_active'];
        }
        if ( isset( $_GET['filter_archived'] ) && $_GET['filter_archived'] !== '' ) {
            $filters['is_archived'] = (bool) $_GET['filter_archived'];
        }
        if ( isset( $_GET['filter_future'] ) && $_GET['filter_future'] === '1' ) {
            $filters['future_only'] = true;
        }

        $terms   = $this->repo->get_all( $filters );
        $message = $_GET['message'] ?? '';

        include HLAVAS_TERMS_DIR . 'admin/views/list.php';
    }

    /**
     * Edit/Add term page.
     */
    public function page_edit(): void {
        $term_id = (int) ( $_GET['term_id'] ?? 0 );
        $term    = $term_id > 0 ? $this->repo->find( $term_id ) : null;
        $error   = $_GET['error'] ?? '';
        $message = $_GET['message'] ?? '';

        include HLAVAS_TERMS_DIR . 'admin/views/edit.php';
    }

    /**
     * Sync page.
     */
    public function page_sync(): void {
        $preview     = $this->sync->preview();
        $debug       = isset( $_GET['debug'] ) ? $this->sync->debug() : null;
        $sync_result = get_transient( 'hlavas_sync_result' );
        if ( $sync_result ) {
            delete_transient( 'hlavas_sync_result' );
        }

        include HLAVAS_TERMS_DIR . 'admin/views/sync.php';
    }

    /**
     * Availability page.
     */
    public function page_availability(): void {
        $report = $this->availability->get_availability_report();
        include HLAVAS_TERMS_DIR . 'admin/views/availability.php';
    }
}
