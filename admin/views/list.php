<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array<int, object> $terms */
/** @var string $message */
/** @var string $report_message */
/** @var array<string, mixed> $filters */
/** @var array<string, string> $sync_log */
/** @var array<int, int> $enrolled_counts */

$current_page   = sanitize_key( (string) ( $_GET['page'] ?? 'hlavas-terms' ) );
$report_actions = in_array( $current_page, [ 'hlavas-terms-kurzy', 'hlavas-terms-zkousky' ], true );
$report_args    = [
	'page' => $current_page,
];

if ( isset( $_GET['filter_active'] ) && '' !== (string) $_GET['filter_active'] ) {
	$report_args['filter_active'] = sanitize_text_field( wp_unslash( $_GET['filter_active'] ) );
}

if ( isset( $_GET['filter_archived'] ) && '' !== (string) $_GET['filter_archived'] ) {
	$report_args['filter_archived'] = sanitize_text_field( wp_unslash( $_GET['filter_archived'] ) );
}

if ( isset( $_GET['filter_future'] ) && '1' === (string) $_GET['filter_future'] ) {
	$report_args['filter_future'] = '1';
}

$form_sync_log        = hlavas_terms_get_form_sync_log();
$last_form_synced_at  = '';
$form_sync_timestamps = array_values( array_filter( array_map( 'strval', $form_sync_log ) ) );

if ( ! empty( $form_sync_timestamps ) ) {
	rsort( $form_sync_timestamps, SORT_STRING );
	$last_form_synced_at = (string) $form_sync_timestamps[0];
}
?>
<div class="wrap hlavas-terms-wrap">
	<div class="hlavas-list-header">
		<h1 class="wp-heading-inline">
			<?php
			$type_label = $filters['term_type'] ?? null;
			if ( 'kurz' === $type_label ) {
				echo 'Termíny kurzů';
			} elseif ( 'zkouska' === $type_label ) {
				echo 'Termíny zkoušek';
			} else {
				echo 'Správa termínů';
			}
			?>
		</h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-edit' ) ); ?>" class="page-title-action">Přidat termín</a>
		<div class="hlavas-inline-header-form">
			<button
				type="button"
				id="hlavas-sync-selected-button"
				class="page-title-action"
				onclick="return confirm('Opravdu spustit FF synchronizaci pro všechny nakonfigurované formuláře?');"
			>
				FF synchronizace
			</button>
		</div>
		<span class="hlavas-header-sync-info">
			Poslední FF synchronizace:
			<strong><?php echo '' !== $last_form_synced_at ? esc_html( $last_form_synced_at ) : 'Nikdy'; ?></strong>
		</span>
	</div>
	<hr class="wp-header-end">

	<?php if ( 'deleted' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Termín byl smazán.</p></div>
	<?php elseif ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Termín byl uložen.</p></div>
	<?php elseif ( 'bulk_done' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Hromadná akce byla provedena.</p></div>
	<?php elseif ( 'visibility_changed' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Viditelnost termínu na webu byla změněna.</p></div>
	<?php elseif ( 'synced_to_ff' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Termín byl synchronizován do Fluent Forms.</p></div>
	<?php endif; ?>

	<?php if ( 'emailed' === $report_message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Report byl odeslán na e-mail <?php echo esc_html( hlavas_terms_get_report_email() ); ?>.</p></div>
	<?php elseif ( 'email_failed' === $report_message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Report se nepodařilo odeslat. Zkontroluj e-mail pro reporty v nastavení pluginu.</p></div>
	<?php elseif ( 'report_failed' === $report_message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Report se nepodařilo vygenerovat.</p></div>
	<?php endif; ?>

	<form method="get" class="hlavas-filters">
		<input type="hidden" name="page" value="<?php echo esc_attr( $current_page ); ?>">

		<?php if ( ! isset( $filters['term_type'] ) || null === $filters['term_type'] ) : ?>
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

	<?php if ( $report_actions ) : ?>
		<div class="hlavas-report-actions">
			<a
				href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $report_args, [ 'hlavas_report_action' => 'download', 'report_format' => 'csv' ] ), admin_url( 'admin.php' ) ), 'hlavas_report_action', '_hlavas_report_nonce' ) ); ?>"
				class="button"
			>Download CSV</a>
			<a
				href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $report_args, [ 'hlavas_report_action' => 'download', 'report_format' => 'xls' ] ), admin_url( 'admin.php' ) ), 'hlavas_report_action', '_hlavas_report_nonce' ) ); ?>"
				class="button"
			>Download XLS</a>
			<a
				href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $report_args, [ 'hlavas_report_action' => 'print' ] ), admin_url( 'admin.php' ) ), 'hlavas_report_action', '_hlavas_report_nonce' ) ); ?>"
				class="button"
				target="_blank"
				rel="noopener noreferrer"
			>Tisk</a>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="hlavas-report-inline-form">
				<?php wp_nonce_field( 'hlavas_report_action', '_hlavas_report_nonce' ); ?>
				<?php foreach ( $report_args as $report_key => $report_value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $report_key ); ?>" value="<?php echo esc_attr( (string) $report_value ); ?>">
				<?php endforeach; ?>
				<input type="hidden" name="hlavas_report_action" value="email">
				<button type="submit" class="button">Odeslat data na e-mail</button>
			</form>
			<span class="hlavas-report-actions-note">Odeslání proběhne na adresu <?php echo esc_html( hlavas_terms_get_report_email() ); ?>.</span>
		</div>
	<?php endif; ?>

	<form method="post" id="hlavas-terms-bulk-form">
		<?php wp_nonce_field( 'hlavas_bulk', '_hlavas_bulk_nonce' ); ?>

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<select name="bulk_action" id="hlavas-bulk-action-select">
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
				<span class="displaying-num"><?php echo esc_html( (string) count( $terms ) ); ?> položek</span>
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
					<th class="column-enrolled">Přihlášeno</th>
					<th class="column-synced">FF sync</th>
					<th class="column-visible">Web</th>
					<th class="column-active">Aktivní</th>
					<th class="column-archived">Archiv</th>
					<th class="column-sort">Pořadí</th>
					<th class="column-actions">Akce</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $terms ) ) : ?>
					<tr><td colspan="16">Žádné termíny nebyly nalezeny.</td></tr>
				<?php else : ?>
					<?php
					$today = current_time( 'Y-m-d' );
					foreach ( $terms as $term ) :
						$cutoff             = $term->enrollment_deadline ?: ( $term->date_start ?: $term->date_end );
						$is_past            = $cutoff < $today;
						$row_class          = '';
						$row_class         .= $is_past ? ' hlavas-past' : '';
						$row_class         .= ! $term->is_active ? ' hlavas-inactive' : '';
						$row_class         .= $term->is_archived ? ' hlavas-archived' : '';
						$title              = ! empty( $term->title ) ? $term->title : $term->label;
						$qualification      = ! empty( $term->qualification_name ) ? $term->qualification_name : 'Bez návaznosti';
						$qualification_code = ! empty( $term->qualification_code ) ? $term->qualification_code : '';
						$enrolled_count     = (int) ( $enrolled_counts[ (int) $term->id ] ?? 0 );
						$last_synced_at     = (string) ( $sync_log[ (string) $term->id ] ?? '' );
						$visibility_url     = wp_nonce_url(
							admin_url( 'admin.php?page=' . rawurlencode( $current_page ) . '&action=toggle_visibility&term_id=' . $term->id ),
							'hlavas_visibility_' . $term->id
						);
						?>
						<tr class="<?php echo esc_attr( trim( $row_class ) ); ?>">
							<th class="check-column">
								<input type="checkbox" name="term_ids[]" value="<?php echo esc_attr( (string) $term->id ); ?>">
							</th>
							<td><?php echo esc_html( (string) $term->id ); ?></td>
							<td>
								<span class="hlavas-badge hlavas-badge-<?php echo esc_attr( (string) $term->term_type ); ?>">
									<?php echo 'kurz' === $term->term_type ? 'Kurz' : 'Zkouška'; ?>
								</span>
							</td>
							<td class="column-qualification">
								<span class="hlavas-qualification-main">
									<?php echo esc_html( $qualification_code ? $qualification_code . ' - ' . $qualification : $qualification ); ?>
								</span>
							</td>
							<td class="column-title">
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . $term->id ) ); ?>">
										<?php echo esc_html( $title ); ?>
									</a>
								</strong>
								<div class="hlavas-subline"><?php echo esc_html( (string) $term->label ); ?></div>
								<code class="hlavas-subline-code"><?php echo esc_html( (string) $term->term_key ); ?></code>
							</td>
							<td class="column-date"><?php echo esc_html( (string) $term->date_start ); ?></td>
							<td class="column-date"><?php echo esc_html( (string) ( $term->date_end ?? '—' ) ); ?></td>
							<td class="column-deadline"><?php echo esc_html( (string) ( $term->enrollment_deadline ?: '—' ) ); ?></td>
							<td class="column-capacity"><?php echo esc_html( (string) $term->capacity ); ?></td>
							<td class="column-enrolled"><?php echo esc_html( (string) $enrolled_count ); ?></td>
							<td class="column-synced">
								<?php if ( '' !== $last_synced_at ) : ?>
									<?php echo esc_html( $last_synced_at ); ?>
								<?php else : ?>
									<span class="hlavas-subline">Nikdy</span>
								<?php endif; ?>
							</td>
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
									<span class="hlavas-status-no">✕</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $term->is_archived ) : ?>
									<span class="hlavas-status-archived">📦</span>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( (string) $term->sort_order ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-edit&term_id=' . $term->id ) ); ?>" class="button button-small">Upravit</a>
								<a
									href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=hlavas-terms&action=delete&term_id=' . $term->id ), 'hlavas_delete_' . $term->id ) ); ?>"
									class="button button-small button-link-delete"
									onclick="return confirm('Opravdu smazat tento termín?');"
								>Smazat</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
	const bulkForm = document.getElementById('hlavas-terms-bulk-form');
	const syncButton = document.getElementById('hlavas-sync-selected-button');
	const bulkActionSelect = document.getElementById('hlavas-bulk-action-select');

	if (!bulkForm || !syncButton || !bulkActionSelect) {
		return;
	}

	syncButton.removeAttribute('onclick');
	syncButton.textContent = 'FF synchronizace vybraných';

	syncButton.addEventListener('click', function() {
		const checked = bulkForm.querySelectorAll('input[name="term_ids[]"]:checked');

		if (!checked.length) {
			window.alert('Nejprve zaškrtni alespoň jeden termín, který chceš synchronizovat.');
			return;
		}

		if (!window.confirm('Opravdu synchronizovat jen zaškrtnuté termíny do navázaných Fluent Forms formulářů?')) {
			return;
		}

		bulkActionSelect.value = 'sync';
		bulkForm.submit();
	});
});
</script>
