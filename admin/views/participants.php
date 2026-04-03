<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array<string, mixed> $filters */
/** @var array<int, array<string, mixed>> $participants */
/** @var array<int, object> $qualification_types */
/** @var array<int, object> $terms */
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Účastníci</h1>
	<p>
		Přehled přihlášek načtených z Fluent Forms. Plugin dál spravuje hlavně termíny, typ přihlášky a kapacity,
		osobní údaje zůstávají uložené ve Fluent Forms a tady se zobrazují jen pro práci administrace.
	</p>

	<form method="get" class="hlavas-filters">
		<input type="hidden" name="page" value="hlavas-terms-participants">

		<label>
			Akreditace:
			<select name="qualification_type_id">
				<option value="0">Všechny</option>
				<?php foreach ( $qualification_types as $qualification_type ) : ?>
					<option value="<?php echo esc_attr( $qualification_type->id ); ?>" <?php selected( (int) $filters['qualification_type_id'], (int) $qualification_type->id ); ?>>
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

		<label>
			Kurz / zkouška:
			<select name="participant_term_type">
				<option value="">Vše</option>
				<option value="kurz" <?php selected( $filters['term_type'], 'kurz' ); ?>>Kurzy</option>
				<option value="zkouska" <?php selected( $filters['term_type'], 'zkouska' ); ?>>Zkoušky</option>
			</select>
		</label>

		<label>
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
					<option value="<?php echo esc_attr( $term->id ); ?>" <?php selected( (int) $filters['term_id'], (int) $term->id ); ?>>
						<?php echo esc_html( $term_type_label . ': ' . $term_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<button type="submit" class="button">Filtrovat</button>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-participants' ) ); ?>" class="button">Reset</a>
	</form>

	<p class="description">
		Nalezeno záznamů: <strong><?php echo esc_html( (string) count( $participants ) ); ?></strong>
	</p>

	<?php if ( empty( $participants ) ) : ?>
		<div class="notice notice-info">
			<p>V aktuálním filtru nebyli nalezeni žádní účastníci. Pokud data čekáš, zkontroluj spárované Form ID a hodnoty termínů ve Fluent Forms.</p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat striped hlavas-participants-table">
			<thead>
				<tr>
					<th class="column-participant">Účastník</th>
					<th class="column-registration">Přihláška</th>
					<th class="column-term">Termín</th>
					<th class="column-created">Odesláno</th>
					<th class="column-status">Stav</th>
					<th class="column-actions">Akce</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $participants as $participant ) : ?>
					<tr>
						<td class="column-participant">
							<details class="hlavas-participant-details">
								<summary>
									<span class="hlavas-participant-name"><?php echo esc_html( $participant['name'] ); ?></span>
									<span class="hlavas-subline"><?php echo esc_html( $participant['email'] ?: 'Bez e-mailu' ); ?></span>
								</summary>
								<div class="hlavas-participant-card">
									<p><strong>Narození:</strong> <?php echo esc_html( $participant['birthdate'] ?: '—' ); ?></p>
									<p><strong>Telefon:</strong> <?php echo esc_html( $participant['phone'] ?: '—' ); ?></p>
									<p><strong>Adresa:</strong> <?php echo esc_html( $participant['address'] ?: '—' ); ?></p>
									<p><strong>Platba:</strong> <?php echo esc_html( $participant['payment_type'] ?: '—' ); ?></p>
									<p><strong>Organizace:</strong> <?php echo esc_html( $participant['organization_name'] ?: '—' ); ?></p>
									<p><strong>IČO:</strong> <?php echo esc_html( $participant['organization_ico'] ?: '—' ); ?></p>
									<p><strong>Fakturační e-mail:</strong> <?php echo esc_html( $participant['invoice_email'] ?: '—' ); ?></p>
									<p><strong>Form ID:</strong> <?php echo esc_html( (string) $participant['form_id'] ); ?> | <strong>Submission ID:</strong> <?php echo esc_html( (string) $participant['submission_id'] ); ?></p>
								</div>
							</details>
						</td>
						<td class="column-registration">
							<span class="hlavas-badge <?php echo 'kurz' === $participant['term_type'] ? 'hlavas-badge-kurz' : 'hlavas-badge-zkouska'; ?>">
								<?php echo esc_html( $participant['term_type_label'] ); ?>
							</span>
							<div class="hlavas-subline"><?php echo esc_html( $participant['qualification'] ); ?></div>
							<?php if ( ! empty( $participant['registration_type'] ) ) : ?>
								<div class="hlavas-subline"><?php echo esc_html( $participant['registration_type'] ); ?></div>
							<?php endif; ?>
						</td>
						<td class="column-term">
							<strong><?php echo esc_html( $participant['term_title'] ); ?></strong>
							<?php if ( ! empty( $participant['term_label'] ) ) : ?>
								<div class="hlavas-subline"><?php echo esc_html( $participant['term_label'] ); ?></div>
							<?php endif; ?>
							<code class="hlavas-subline-code"><?php echo esc_html( $participant['term_key'] ); ?></code>
						</td>
						<td class="column-created">
							<?php echo esc_html( $participant['created_at'] ); ?>
						</td>
						<td class="column-status">
							<span class="hlavas-entry-status hlavas-entry-status-<?php echo esc_attr( sanitize_html_class( strtolower( (string) $participant['status'] ) ) ); ?>">
								<?php echo esc_html( ucfirst( (string) $participant['status'] ) ); ?>
							</span>
						</td>
						<td class="column-actions">
							<a href="<?php echo esc_url( $participant['admin_url'] ); ?>" class="button button-small" target="_blank" rel="noopener noreferrer">Fluent detail</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
