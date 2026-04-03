<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $terms */
/** @var string $message */
/** @var array $filters */
?>
<div class="wrap hlavas-terms-wrap">
    <h1 class="wp-heading-inline">
        <?php
        $type_label = $filters['term_type'] ?? null;
        if ( $type_label === 'kurz' ) {
            echo 'Termíny kurzů';
        } elseif ( $type_label === 'zkouska' ) {
            echo 'Termíny zkoušek';
        } else {
            echo 'Správa termínů';
        }
        ?>
    </h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-edit' ) ); ?>" class="page-title-action">Přidat termín</a>
    <hr class="wp-header-end">

    <?php if ( $message === 'deleted' ) : ?>
        <div class="notice notice-success is-dismissible"><p>Termín byl smazán.</p></div>
    <?php elseif ( $message === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible"><p>Termín byl uložen.</p></div>
    <?php elseif ( $message === 'bulk_done' ) : ?>
        <div class="notice notice-success is-dismissible"><p>Hromadná akce byla provedena.</p></div>
    <?php elseif ( $message === 'visibility_changed' ) : ?>
        <div class="notice notice-success is-dismissible"><p>Viditelnost termínu na webu byla změněna.</p></div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="get" class="hlavas-filters">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ?? 'hlavas-terms' ); ?>">

        <?php if ( ! isset( $filters['term_type'] ) || $filters['term_type'] === null ) : ?>
        <label>
            Typ:
            <select name="filter_type">
                <option value="">Vše</option>
                <option value="kurz" <?php selected( $_GET['filter_type'] ?? '', 'kurz' ); ?>>Kurz</option>
                <option value="zkouska" <?php selected( $_GET['filter_type'] ?? '', 'zkouska' ); ?>>Zkouška</option>
            </select>
        </label>
        <?php endif; ?>

        <label>
            Aktivní:
            <select name="filter_active">
                <option value="">Vše</option>
                <option value="1" <?php selected( $_GET['filter_active'] ?? '', '1' ); ?>>Aktivní</option>
                <option value="0" <?php selected( $_GET['filter_active'] ?? '', '0' ); ?>>Neaktivní</option>
            </select>
        </label>

        <label>
            Archivováno:
            <select name="filter_archived">
                <option value="">Vše</option>
                <option value="0" <?php selected( $_GET['filter_archived'] ?? '', '0' ); ?>>Nearchivované</option>
                <option value="1" <?php selected( $_GET['filter_archived'] ?? '', '1' ); ?>>Archivované</option>
            </select>
        </label>

        <label>
            <input type="checkbox" name="filter_future" value="1" <?php checked( $_GET['filter_future'] ?? '', '1' ); ?>>
            Jen budoucí
        </label>

        <button type="submit" class="button">Filtrovat</button>
    </form>

    <!-- Bulk Actions Form -->
    <form method="post">
        <?php wp_nonce_field( 'hlavas_bulk', '_hlavas_bulk_nonce' ); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action">
                    <option value="">Hromadné akce</option>
                    <option value="activate">Aktivovat</option>
                    <option value="deactivate">Deaktivovat</option>
                    <option value="archive">Archivovat</option>
                    <option value="regenerate_labels">Přegenerovat label</option>
                    <option value="sync">Synchronizovat vybrané</option>
                    <option value="delete">Smazat</option>
                </select>
                <button type="submit" name="hlavas_bulk_action" value="1" class="button action">Použít</button>
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo count( $terms ); ?> položek</span>
            </div>
        </div>

        <table class="wp-list-table widefat striped hlavas-terms-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </td>
                    <th class="column-id">ID</th>
                    <th class="column-type">Typ</th>
                    <th class="column-qualification">Kvalifikace</th>
                    <th class="column-title">Název termínu</th>
                    <th class="column-date">Datum od</th>
                    <th class="column-date">Datum do</th>
                    <th class="column-deadline">Uzávěrka</th>
                    <th class="column-capacity">Kapacita</th>
                    <th class="column-visible">Web</th>
                    <th class="column-active">Aktivní</th>
                    <th class="column-archived">Archiv</th>
                    <th class="column-sort">Pořadí</th>
                    <th class="column-actions">Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $terms ) ) : ?>
                    <tr><td colspan="13">Žádné termíny nebyly nalezeny.</td></tr>
                <?php else : ?>
                    <?php
                    $today = current_time( 'Y-m-d' );
                    foreach ( $terms as $term ) :
                        $cutoff = $term->enrollment_deadline ?: ( $term->date_start ?: $term->date_end );
                        $is_past = $cutoff < $today;
                        $row_class = '';
                        if ( $is_past ) $row_class .= ' hlavas-past';
                        if ( ! $term->is_active ) $row_class .= ' hlavas-inactive';
                        if ( $term->is_archived ) $row_class .= ' hlavas-archived';
                        $title = ! empty( $term->title ) ? $term->title : $term->label;
                        $qualification = ! empty( $term->qualification_name ) ? $term->qualification_name : 'Bez návaznosti';
                        $qualification_code = ! empty( $term->qualification_code ) ? $term->qualification_code : '';
                        $visibility_url = wp_nonce_url(
                            admin_url( 'admin.php?page=' . rawurlencode( $_GET['page'] ?? 'hlavas-terms' ) . '&action=toggle_visibility&term_id=' . $term->id ),
                            'hlavas_visibility_' . $term->id
                        );
                    ?>
                    <tr class="<?php echo esc_attr( trim( $row_class ) ); ?>">
                        <th class="check-column">
                            <input type="checkbox" name="term_ids[]" value="<?php echo esc_attr( $term->id ); ?>">
                        </th>
                        <td><?php echo esc_html( $term->id ); ?></td>
                        <td>
                            <span class="hlavas-badge hlavas-badge-<?php echo esc_attr( $term->term_type ); ?>">
                                <?php echo $term->term_type === 'kurz' ? 'Kurz' : 'Zkouška'; ?>
                            </span>
                        </td>
                        <td class="column-qualification">
                            <span class="hlavas-qualification-main">
                                <?php echo esc_html( $qualification_code ? $qualification_code . ' – ' . $qualification : $qualification ); ?>
                            </span>
                        </td>
                        <td class="column-title">
                            <strong>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . $term->id ) ); ?>">
                                    <?php echo esc_html( $title ); ?>
                                </a>
                            </strong>
                            <div class="hlavas-subline">
                                <?php echo esc_html( $term->label ); ?>
                            </div>
                            <code class="hlavas-subline-code"><?php echo esc_html( $term->term_key ); ?></code>
                        </td>
                        <td class="column-date"><?php echo esc_html( $term->date_start ); ?></td>
                        <td class="column-date"><?php echo esc_html( $term->date_end ?? '—' ); ?></td>
                        <td class="column-deadline"><?php echo esc_html( $term->enrollment_deadline ?: '—' ); ?></td>
                        <td class="column-capacity"><?php echo esc_html( $term->capacity ); ?></td>
                        <td class="column-visible">
                            <a
                                href="<?php echo esc_url( $visibility_url ); ?>"
                                class="hlavas-visibility-toggle"
                                title="<?php echo ! empty( $term->is_visible ) ? esc_attr( 'Skrýt z webu' ) : esc_attr( 'Zobrazit na webu' ); ?>"
                                aria-label="<?php echo ! empty( $term->is_visible ) ? esc_attr( 'Skrýt z webu' ) : esc_attr( 'Zobrazit na webu' ); ?>"
                            >
                                <span class="dashicons <?php echo ! empty( $term->is_visible ) ? 'dashicons-visibility hlavas-status-yes' : 'dashicons-hidden hlavas-status-no'; ?>"></span>
                            </a>
                        </td>
                        <td>
                            <?php if ( $term->is_active ) : ?>
                                <span class="hlavas-status-yes">✓</span>
                            <?php else : ?>
                                <span class="hlavas-status-no">✗</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $term->is_archived ) : ?>
                                <span class="hlavas-status-archived">📦</span>
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $term->sort_order ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . $term->id ) ); ?>"
                               class="button button-small">Upravit</a>
                            <a href="<?php echo esc_url( wp_nonce_url(
                                admin_url( 'admin.php?page=hlavas-terms&action=delete&term_id=' . $term->id ),
                                'hlavas_delete_' . $term->id
                            ) ); ?>"
                               class="button button-small button-link-delete"
                               onclick="return confirm('Opravdu smazat tento termín?');">Smazat</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>
