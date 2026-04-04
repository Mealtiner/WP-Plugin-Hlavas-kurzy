<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array<string, mixed> $filters */
/** @var array<int, array<string, mixed>> $participants */
/** @var array<int, object> $qualification_types */
/** @var array<int, object> $terms */
/** @var string $report_message */

$report_args = [
	'page' => 'hlavas-terms-participants',
];

if ( ! empty( $filters['qualification_type_id'] ) ) {
	$report_args['qualification_type_id'] = (int) $filters['qualification_type_id'];
}

if ( ! empty( $filters['term_type'] ) ) {
	$report_args['participant_term_type'] = (string) $filters['term_type'];
}

if ( ! empty( $filters['term_id'] ) ) {
	$report_args['participant_term_id'] = (int) $filters['term_id'];
}

if ( ! empty( $filters['sort_by'] ) ) {
	$report_args['participant_sort_by'] = (string) $filters['sort_by'];
}

if ( ! empty( $filters['sort_order'] ) ) {
	$report_args['participant_sort_order'] = (string) $filters['sort_order'];
}

$download_csv_url = wp_nonce_url(
	add_query_arg(
		array_merge(
			$report_args,
			[
				'hlavas_report_action' => 'download',
				'report_format'        => 'csv',
			]
		),
		admin_url( 'admin.php' )
	),
	'hlavas_report_action',
	'_hlavas_report_nonce'
);
$download_xls_url = wp_nonce_url(
	add_query_arg(
		array_merge(
			$report_args,
			[
				'hlavas_report_action' => 'download',
				'report_format'        => 'xls',
			]
		),
		admin_url( 'admin.php' )
	),
	'hlavas_report_action',
	'_hlavas_report_nonce'
);
$print_url = wp_nonce_url(
	add_query_arg(
		array_merge(
			$report_args,
			[
				'hlavas_report_action' => 'print',
			]
		),
		admin_url( 'admin.php' )
	),
	'hlavas_report_action',
	'_hlavas_report_nonce'
);

$sort_base_args = [
	'page'                  => 'hlavas-terms-participants',
	'qualification_type_id' => (int) ( $filters['qualification_type_id'] ?? 0 ),
	'participant_term_type' => (string) ( $filters['term_type'] ?? '' ),
	'participant_term_id'   => (int) ( $filters['term_id'] ?? 0 ),
];

$build_sort_url = static function ( string $sort_by ) use ( $sort_base_args, $filters ): string {
	$current_sort_by    = (string) ( $filters['sort_by'] ?? 'created_at' );
	$current_sort_order = (string) ( $filters['sort_order'] ?? 'desc' );
	$next_order         = ( $current_sort_by === $sort_by && 'asc' === $current_sort_order ) ? 'desc' : 'asc';

	return (string) add_query_arg(
		array_merge(
			$sort_base_args,
			[
				'participant_sort_by'    => $sort_by,
				'participant_sort_order' => $next_order,
			]
		),
		admin_url( 'admin.php' )
	);
};

$render_sort_label = static function ( string $label, string $sort_by ) use ( $filters ): string {
	$current_sort_by    = (string) ( $filters['sort_by'] ?? 'created_at' );
	$current_sort_order = (string) ( $filters['sort_order'] ?? 'desc' );

	if ( $current_sort_by !== $sort_by ) {
		return $label;
	}

	return $label . ( 'asc' === $current_sort_order ? ' ↑' : ' ↓' );
};
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Účastníci</h1>
	<p>
		Přehled přihlášek načtených z Fluent Forms. Plugin dál spravuje hlavně termíny, typ přihlášky a kapacity,
		osobní údaje zůstávají uložené ve Fluent Forms a tady se zobrazují jen pro práci administrace.
	</p>

	<?php if ( 'emailed' === $report_message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Report byl odeslán na e-mail <?php echo esc_html( hlavas_terms_get_report_email() ); ?>.</p></div>
	<?php elseif ( 'email_failed' === $report_message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Report se nepodařilo odeslat. Zkontroluj e-mail pro reporty v nastavení pluginu.</p></div>
	<?php elseif ( 'report_failed' === $report_message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Report se nepodařilo vygenerovat.</p></div>
	<?php endif; ?>

	<form method="get" class="hlavas-filters hlavas-participants-filters">
		<input type="hidden" name="page" value="hlavas-terms-participants">
		<input type="hidden" name="participant_sort_by" value="<?php echo esc_attr( (string) ( $filters['sort_by'] ?? 'created_at' ) ); ?>">
		<input type="hidden" name="participant_sort_order" value="<?php echo esc_attr( (string) ( $filters['sort_order'] ?? 'desc' ) ); ?>">

		<label class="hlavas-filter-field">
			Akreditace:
			<select name="qualification_type_id">
				<option value="0">Všechny</option>
				<?php foreach ( $qualification_types as $qualification_type ) : ?>
					<option value="<?php echo esc_attr( (string) $qualification_type->id ); ?>" <?php selected( (int) $filters['qualification_type_id'], (int) $qualification_type->id ); ?>>
						<?php
						$qualification_label = ! empty( $qualification_type->accreditation_number )
							? $qualification_type->accreditation_number . ' - ' . $qualification_type->name
							: $qualification_type->name;
						echo esc_html( $qualification_label );
						?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<label class="hlavas-filter-field">
			Kurz / zkouška:
			<select name="participant_term_type">
				<option value="">Vše</option>
				<option value="kurz" <?php selected( $filters['term_type'], 'kurz' ); ?>>Kurzy</option>
				<option value="zkouska" <?php selected( $filters['term_type'], 'zkouska' ); ?>>Zkoušky</option>
			</select>
		</label>

		<label class="hlavas-filter-field hlavas-filter-field-wide">
			Konkrétní termín:
			<select name="participant_term_id">
				<option value="0">Všechny termíny</option>
				<?php foreach ( $terms as $term ) : ?>
					<?php
					if ( ! empty( $filters['term_type'] ) && $filters['term_type'] !== $term->term_type ) {
						continue;
					}

					$term_type_label = 'kurz' === $term->term_type ? 'Kurz' : 'Zkouška';
					$term_title      = ! empty( $term->title ) ? $term->title : $term->label;
					?>
					<option value="<?php echo esc_attr( (string) $term->id ); ?>" <?php selected( (int) $filters['term_id'], (int) $term->id ); ?>>
						<?php echo esc_html( $term_type_label . ': ' . $term_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<div class="hlavas-filter-actions">
			<button type="submit" class="button button-primary">Filtrovat</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-participants' ) ); ?>" class="button">Reset</a>
		</div>
	</form>

	<div class="hlavas-report-actions">
		<a href="<?php echo esc_url( $download_csv_url ); ?>" class="button">Download CSV</a>
		<a href="<?php echo esc_url( $download_xls_url ); ?>" class="button">Download XLS</a>
		<a href="<?php echo esc_url( $print_url ); ?>" class="button" target="_blank" rel="noopener noreferrer">Tisk</a>
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

	<p class="description">
		Nalezeno záznamů: <strong><?php echo esc_html( (string) count( $participants ) ); ?></strong>
	</p>

	<?php if ( empty( $participants ) ) : ?>
		<div class="notice notice-info">
			<p>V aktuálním filtru nebyli nalezeni žádní účastníci. Pokud data čekáš, zkontroluj spárované Form ID, mapování polí v nastavení pluginu a hodnoty termínů ve Fluent Forms.</p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat striped hlavas-participants-table">
			<thead>
				<tr>
					<th class="column-participant">
						<a href="<?php echo esc_url( $build_sort_url( 'name' ) ); ?>" class="hlavas-sort-link"><?php echo esc_html( $render_sort_label( 'Účastník', 'name' ) ); ?></a>
					</th>
					<th class="column-registration">
						<a href="<?php echo esc_url( $build_sort_url( 'qualification' ) ); ?>" class="hlavas-sort-link"><?php echo esc_html( $render_sort_label( 'Přihláška', 'qualification' ) ); ?></a>
					</th>
					<th class="column-term">
						<a href="<?php echo esc_url( $build_sort_url( 'term_title' ) ); ?>" class="hlavas-sort-link"><?php echo esc_html( $render_sort_label( 'Termín', 'term_title' ) ); ?></a>
					</th>
					<th class="column-created">
						<a href="<?php echo esc_url( $build_sort_url( 'created_at' ) ); ?>" class="hlavas-sort-link"><?php echo esc_html( $render_sort_label( 'Odesláno', 'created_at' ) ); ?></a>
					</th>
					<th class="column-status">
						<a href="<?php echo esc_url( $build_sort_url( 'status' ) ); ?>" class="hlavas-sort-link"><?php echo esc_html( $render_sort_label( 'Stav', 'status' ) ); ?></a>
					</th>
					<th class="column-actions">Akce</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $participants as $participant ) : ?>
					<tr>
						<td class="column-participant">
							<details class="hlavas-participant-details">
								<summary>
									<span class="hlavas-participant-name">
										<?php echo esc_html( (string) $participant['name'] ); ?>
										<?php
										$source_format = (string) ( $participant['source_format'] ?? 'legacy' );
										$is_new_format = 'new' === $source_format;
										?>
										<span
											class="hlavas-participant-format hlavas-participant-format-<?php echo esc_attr( $source_format ); ?>"
											title="<?php echo esc_attr( $is_new_format ? 'Záznam je už v novém HLAVAS formátu.' : 'Záznam je ve starším legacy formátu.' ); ?>"
											aria-label="<?php echo esc_attr( $is_new_format ? 'Nový formát' : 'Legacy formát' ); ?>"
										>
											<span class="dashicons <?php echo esc_attr( $is_new_format ? 'dashicons-yes-alt' : 'dashicons-backup' ); ?>" aria-hidden="true"></span>
										</span>
									</span>
									<span class="hlavas-subline"><?php echo esc_html( ! empty( $participant['email'] ) ? (string) $participant['email'] : 'Bez e-mailu' ); ?></span>
								</summary>
								<div class="hlavas-participant-card">
									<p><strong>Narození:</strong> <?php echo esc_html( ! empty( $participant['birthdate'] ) ? (string) $participant['birthdate'] : '—' ); ?></p>
									<p><strong>Telefon:</strong> <?php echo esc_html( ! empty( $participant['phone'] ) ? (string) $participant['phone'] : '—' ); ?></p>
									<p><strong>Adresa:</strong> <?php echo esc_html( ! empty( $participant['address'] ) ? (string) $participant['address'] : '—' ); ?></p>
									<p><strong>Platba:</strong> <?php echo esc_html( ! empty( $participant['payment_type'] ) ? (string) $participant['payment_type'] : '—' ); ?></p>
									<p><strong>Organizace:</strong> <?php echo esc_html( ! empty( $participant['organization_name'] ) ? (string) $participant['organization_name'] : '—' ); ?></p>
									<p><strong>IČO:</strong> <?php echo esc_html( ! empty( $participant['organization_ico'] ) ? (string) $participant['organization_ico'] : '—' ); ?></p>
									<p><strong>Fakturační e-mail:</strong> <?php echo esc_html( ! empty( $participant['invoice_email'] ) ? (string) $participant['invoice_email'] : '—' ); ?></p>
									<p><strong>Form ID:</strong> <?php echo esc_html( (string) $participant['form_id'] ); ?> | <strong>Submission ID:</strong> <?php echo esc_html( (string) $participant['submission_id'] ); ?></p>
								</div>
							</details>
						</td>
						<td class="column-registration">
							<?php
							$badge_class = match ( (string) $participant['term_type'] ) {
								'kurz'    => 'hlavas-badge-kurz',
								'zkouska' => 'hlavas-badge-zkouska',
								default   => '',
							};
							?>
							<span class="hlavas-badge <?php echo esc_attr( $badge_class ); ?>">
								<?php echo esc_html( (string) $participant['term_type_label'] ); ?>
							</span>
							<?php if ( ! empty( $participant['is_unmatched'] ) ) : ?>
								<div class="hlavas-subline"><em>Historický / nepárovaný záznam</em></div>
							<?php endif; ?>
							<div class="hlavas-subline"><?php echo esc_html( (string) $participant['qualification'] ); ?></div>
							<?php if ( ! empty( $participant['registration_type'] ) ) : ?>
								<div class="hlavas-subline"><?php echo esc_html( (string) $participant['registration_type'] ); ?></div>
							<?php endif; ?>
						</td>
						<td class="column-term">
							<strong><?php echo esc_html( (string) $participant['term_title'] ); ?></strong>
							<?php if ( ! empty( $participant['term_label'] ) ) : ?>
								<div class="hlavas-subline"><?php echo esc_html( (string) $participant['term_label'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! empty( $participant['term_key'] ) ) : ?>
								<code class="hlavas-subline-code"><?php echo esc_html( (string) $participant['term_key'] ); ?></code>
							<?php endif; ?>
						</td>
						<td class="column-created">
							<?php echo esc_html( (string) $participant['created_at'] ); ?>
						</td>
						<td class="column-status">
							<span class="hlavas-entry-status hlavas-entry-status-<?php echo esc_attr( sanitize_html_class( strtolower( (string) $participant['status'] ) ) ); ?>">
								<?php echo esc_html( ucfirst( (string) $participant['status'] ) ); ?>
							</span>
						</td>
						<td class="column-actions">
							<a href="<?php echo esc_url( (string) $participant['admin_url'] ); ?>" class="button button-small" target="_blank" rel="noopener noreferrer">Fluent detail</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
