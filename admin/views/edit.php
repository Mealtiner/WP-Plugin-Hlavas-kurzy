<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var object|null $term */
/** @var int $term_id */
/** @var string $error */
/** @var string $message */
/** @var array<int, object> $qualification_types */
/** @var string $last_synced_at */
/** @var array<string, mixed>|null $term_sync_result */

$is_edit    = null !== $term;
$page_title = $is_edit ? 'Upravit termín' : 'Přidat nový termín';

$defaults = (object) [
	'id'                    => 0,
	'term_type'             => 'kurz',
	'term_key'              => '',
	'qualification_type_id' => 0,
	'title'                 => '',
	'label'                 => '',
	'date_start'            => '',
	'date_end'              => '',
	'enrollment_deadline'   => '',
	'capacity'              => 16,
	'is_visible'            => 1,
	'is_active'             => 1,
	'is_archived'           => 0,
	'sort_order'            => 0,
	'notes'                 => '',
];

$t = $term ?? $defaults;
?>
<div class="wrap hlavas-terms-wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Termín byl uložen.</p></div>
	<?php elseif ( 'synced_to_ff' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Synchronizace do Fluent Forms byla dokončena.</p></div>
	<?php elseif ( 'sync_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Synchronizaci do Fluent Forms se nepodařilo dokončit.</p></div>
	<?php endif; ?>

	<?php if ( 'missing_fields' === $error ) : ?>
		<div class="notice notice-error"><p>Vyplňte prosím všechna povinná pole (datum od).</p></div>
	<?php elseif ( 'duplicate_key' === $error ) : ?>
		<div class="notice notice-error"><p>Termín s tímto klíčem již existuje. Zvolte jiný klíč.</p></div>
	<?php elseif ( 'sync_missing_term' === $error ) : ?>
		<div class="notice notice-error"><p>Termín pro synchronizaci nebyl nalezen.</p></div>
	<?php endif; ?>

	<?php if ( ! empty( $term_sync_result['details'] ) && is_array( $term_sync_result['details'] ) ) : ?>
		<div class="notice notice-<?php echo ! empty( $term_sync_result['success'] ) ? 'success' : 'warning'; ?> is-dismissible">
			<ul>
				<?php foreach ( $term_sync_result['details'] as $detail ) : ?>
					<li><?php echo esc_html( (string) $detail ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="hlavas-term-form">
		<?php wp_nonce_field( 'hlavas_term_save', '_hlavas_nonce' ); ?>
		<input type="hidden" name="term_id" value="<?php echo esc_attr( (string) $t->id ); ?>">

		<table class="form-table">
			<tr>
				<th><label for="term_type">Typ *</label></th>
				<td>
					<select name="term_type" id="term_type" required>
						<option value="kurz" <?php selected( $t->term_type, 'kurz' ); ?>>Kurz</option>
						<option value="zkouska" <?php selected( $t->term_type, 'zkouska' ); ?>>Zkouška</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="title">Administrativní název</label></th>
				<td>
					<input
						type="text"
						name="title"
						id="title"
						class="regular-text"
						value="<?php echo esc_attr( (string) $t->title ); ?>"
						placeholder="Interní popis pro administraci"
					>
				</td>
			</tr>
			<tr>
				<th><label for="qualification_type_id">Typ kvalifikace</label></th>
				<td>
					<select name="qualification_type_id" id="qualification_type_id">
						<option value="0">Bez návaznosti na typ kvalifikace</option>
						<?php foreach ( $qualification_types as $qualification_type ) : ?>
							<option
								value="<?php echo esc_attr( (string) $qualification_type->id ); ?>"
								data-has-kurz="<?php echo esc_attr( (string) (int) $qualification_type->has_courses ); ?>"
								data-has-zkouska="<?php echo esc_attr( (string) (int) $qualification_type->has_exams ); ?>"
								<?php selected( (int) $t->qualification_type_id, (int) $qualification_type->id ); ?>
							>
								<?php
								$prefix = ! empty( $qualification_type->accreditation_number ) ? $qualification_type->accreditation_number . ' - ' : '';
								echo esc_html( $prefix . $qualification_type->name );
								?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">Vyberte typ kurzu nebo zkoušky, pokud má termín návaznost na jednu z definovaných kvalifikací.</p>
				</td>
			</tr>
			<tr>
				<th><label for="date_start">Datum od *</label></th>
				<td><input type="date" name="date_start" id="date_start" required value="<?php echo esc_attr( (string) $t->date_start ); ?>"></td>
			</tr>
			<tr id="row_date_end">
				<th><label for="date_end">Datum do</label></th>
				<td>
					<input type="date" name="date_end" id="date_end" value="<?php echo esc_attr( (string) $t->date_end ); ?>">
					<p class="description">Pro zkoušky se vyplní automaticky. Pro kurzy je povinné.</p>
				</td>
			</tr>
			<tr>
				<th><label for="enrollment_deadline">Přihlášky do</label></th>
				<td>
					<input type="date" name="enrollment_deadline" id="enrollment_deadline" value="<?php echo esc_attr( (string) $t->enrollment_deadline ); ?>">
					<p class="description">Po tomto datu se termín automaticky přestane nabízet pro přihlášení a skryje se ze synchronizace do formulářů.</p>
				</td>
			</tr>
			<tr>
				<th><label for="term_key">Interní klíč *</label></th>
				<td>
					<input
						type="text"
						name="term_key"
						id="term_key"
						class="regular-text"
						value="<?php echo esc_attr( (string) $t->term_key ); ?>"
						placeholder="např. kurz_2026_04_17_19"
					>
					<button type="button" id="btn_generate_key" class="button button-small">Vygenerovat z datumů</button>
					<p class="description">Stabilní identifikátor. Po vytvoření záznamů neměňte.</p>
				</td>
			</tr>
			<tr>
				<th><label for="label">Label pro uživatele *</label></th>
				<td>
					<input
						type="text"
						name="label"
						id="label"
						class="regular-text"
						value="<?php echo esc_attr( (string) $t->label ); ?>"
						placeholder="např. kurz: 17. - 19. dubna 2026"
					>
					<button type="button" id="btn_generate_label" class="button button-small">Vygenerovat z datumů</button>
					<p class="description">Tento text se zobrazí v dropdownu formuláře.</p>
				</td>
			</tr>
			<tr>
				<th><label for="capacity">Kapacita</label></th>
				<td><input type="number" name="capacity" id="capacity" min="0" step="1" value="<?php echo esc_attr( (string) $t->capacity ); ?>"></td>
			</tr>
			<tr>
				<th>Stav</th>
				<td>
					<label><input type="checkbox" name="is_visible" value="1" <?php checked( $t->is_visible, 1 ); ?>> Zobrazené na webu</label><br>
					<label><input type="checkbox" name="is_active" value="1" <?php checked( $t->is_active, 1 ); ?>> Aktivní</label><br>
					<label><input type="checkbox" name="is_archived" value="1" <?php checked( $t->is_archived, 1 ); ?>> Archivováno</label>
				</td>
			</tr>
			<tr>
				<th><label for="sort_order">Pořadí</label></th>
				<td><input type="number" name="sort_order" id="sort_order" value="<?php echo esc_attr( (string) $t->sort_order ); ?>"></td>
			</tr>
			<tr>
				<th><label for="notes">Poznámky</label></th>
				<td><textarea name="notes" id="notes" rows="4" class="large-text"><?php echo esc_textarea( (string) ( $t->notes ?? '' ) ); ?></textarea></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="hlavas_term_save" value="1" class="button button-primary">
				<?php echo $is_edit ? 'Uložit změny' : 'Vytvořit termín'; ?>
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms' ) ); ?>" class="button">Zpět na seznam</a>
		</p>
	</form>

	<div class="hlavas-sync-term-box">
		<h2>Synchronizace do FF</h2>
		<p>
			Poslední synchronizace:
			<strong><?php echo '' !== $last_synced_at ? esc_html( $last_synced_at ) : 'Nikdy'; ?></strong>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="hlavas-term-sync-form">
			<?php wp_nonce_field( 'hlavas_term_sync', '_hlavas_term_sync_nonce' ); ?>
			<input type="hidden" name="term_id" value="<?php echo esc_attr( (string) $t->id ); ?>">

			<button
				type="submit"
				name="hlavas_term_sync_execute"
				value="1"
				class="button <?php echo $is_edit ? 'button-secondary' : ''; ?>"
				<?php echo $is_edit ? '' : 'disabled'; ?>
				onclick="return confirm('Opravdu synchronizovat navazany Fluent Forms formular? Dojde k prepisu voleb terminu a kapacit ve formulari.');"
			>
				Synchronizace do FF
			</button>

			<p class="description">
				Upozornění: tímto se provede změna ve formuláři Fluent Forms. Přepíšou se dostupné volby termínů a jejich kapacity.
				<?php if ( ! $is_edit ) : ?>
					Tlačítko je aktivní až po prvním uložení termínu.
				<?php endif; ?>
			</p>
		</form>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const months = {
		1: 'ledna',
		2: 'února',
		3: 'března',
		4: 'dubna',
		5: 'května',
		6: 'června',
		7: 'července',
		8: 'srpna',
		9: 'září',
		10: 'října',
		11: 'listopadu',
		12: 'prosince'
	};

	function getValues() {
		return {
			type: document.getElementById('term_type').value,
			start: document.getElementById('date_start').value,
			end: document.getElementById('date_end').value,
		};
	}

	function syncQualificationOptions() {
		const termType = document.getElementById('term_type').value;
		const select = document.getElementById('qualification_type_id');
		const options = Array.from(select.options);

		options.forEach(function(option, index) {
			if (index === 0) {
				option.hidden = false;
				option.disabled = false;
				return;
			}

			const hasKurz = option.dataset.hasKurz === '1';
			const hasZkouska = option.dataset.hasZkouska === '1';
			const allowed = termType === 'kurz' ? hasKurz : hasZkouska;

			option.hidden = !allowed;
			option.disabled = !allowed;

			if (!allowed && option.selected) {
				select.value = '0';
			}
		});
	}

	document.getElementById('btn_generate_key').addEventListener('click', function() {
		const v = getValues();
		if (!v.start) {
			alert('Vyplňte datum od.');
			return;
		}
		const s = new Date(v.start);
		const prefix = v.type === 'kurz' ? 'kurz' : 'zkouska';
		let key = prefix + '_' + v.start.replace(/-/g, '_');
		if (v.type === 'kurz' && v.end && v.end !== v.start) {
			const e = new Date(v.end);
			key += '_' + String(e.getDate()).padStart(2, '0');
		}
		document.getElementById('term_key').value = key;
	});

	document.getElementById('btn_generate_label').addEventListener('click', function() {
		const v = getValues();
		if (!v.start) {
			alert('Vyplňte datum od.');
			return;
		}
		const s = new Date(v.start);
		const prefix = v.type === 'kurz' ? 'kurz' : 'zkouška';
		const dayS = s.getDate();
		const monthS = s.getMonth() + 1;
		const yearS = s.getFullYear();

		if (v.type === 'kurz' && v.end && v.end !== v.start) {
			const e = new Date(v.end);
			const dayE = e.getDate();
			const monthE = e.getMonth() + 1;
			if (monthS === monthE && yearS === e.getFullYear()) {
				document.getElementById('label').value =
					prefix + ': ' + dayS + '. - ' + dayE + '. ' + months[monthE] + ' ' + yearS;
			} else {
				document.getElementById('label').value =
					prefix + ': ' + dayS + '. ' + months[monthS] + ' - ' + dayE + '. ' + months[monthE] + ' ' + yearS;
			}
		} else {
			document.getElementById('label').value =
				prefix + ': ' + dayS + '. ' + months[monthS] + ' ' + yearS;
		}
	});

	document.getElementById('term_type').addEventListener('change', syncQualificationOptions);
	syncQualificationOptions();
});
</script>
