<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var string $message */
/** @var string $error */
/** @var array<int, object> $types */
/** @var object|null $current_type */

$type_defaults = (object) [
	'id'                   => 0,
	'name'                 => '',
	'description'          => '',
	'notes'                => '',
	'is_accredited'        => 1,
	'accreditation_number' => '',
	'has_courses'          => 1,
	'has_exams'            => 1,
	'course_form_id'       => 0,
	'exam_form_id'         => 0,
	'sort_order'           => 0,
];

$type = $current_type ?? $type_defaults;
?>
<div class="wrap hlavas-terms-wrap">
	<h1 class="wp-heading-inline">Typy kurzů</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-types' ) ); ?>" class="page-title-action">Přidat nový typ</a>
	<hr class="wp-header-end">

	<?php if ( 'created' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Typ kvalifikace byl vytvořen.</p></div>
	<?php elseif ( 'updated' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Typ kvalifikace byl upraven.</p></div>
	<?php elseif ( 'deleted' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Typ kvalifikace byl smazán.</p></div>
	<?php endif; ?>

	<?php if ( 'missing_name' === $error ) : ?>
		<div class="notice notice-error"><p>Název typu kvalifikace je povinný.</p></div>
	<?php endif; ?>

	<div class="hlavas-admin-grid">
		<div class="hlavas-admin-main">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 70px;">Kód</th>
						<th>Název typu</th>
						<th style="width: 90px;">Kurz</th>
						<th style="width: 90px;">Zkouška</th>
						<th style="width: 90px;">Termínů</th>
						<th style="width: 150px;">Akce</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $types ) ) : ?>
						<tr><td colspan="6">Zatím nejsou založené žádné typy kvalifikací.</td></tr>
					<?php else : ?>
						<?php foreach ( $types as $row ) : ?>
							<tr>
								<td><code><?php echo esc_html( $row->accreditation_number ?: '—' ); ?></code></td>
								<td>
									<strong><?php echo esc_html( $row->name ); ?></strong>
									<?php if ( ! empty( $row->description ) ) : ?>
										<div class="hlavas-subline"><?php echo esc_html( $row->description ); ?></div>
									<?php endif; ?>
								</td>
								<td><?php echo ! empty( $row->has_courses ) ? 'Ano' : 'Ne'; ?></td>
								<td><?php echo ! empty( $row->has_exams ) ? 'Ano' : 'Ne'; ?></td>
								<td><?php echo esc_html( (string) $row->linked_terms ); ?></td>
								<td>
									<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-types&type_id=' . (int) $row->id ) ); ?>">Upravit</a>
									<a
										class="button button-small button-link-delete"
										href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=hlavas-terms-types&action=delete_type&type_id=' . (int) $row->id ), 'hlavas_delete_type_' . (int) $row->id ) ); ?>"
										onclick="return confirm('Opravdu smazat tento typ kvalifikace? U navázaných termínů se jen zruší vazba, samotné termíny zůstanou.');"
									>Smazat</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="hlavas-admin-side">
			<form method="post" class="hlavas-term-form">
				<?php wp_nonce_field( 'hlavas_type_save', '_hlavas_type_nonce' ); ?>
				<input type="hidden" name="type_id" value="<?php echo esc_attr( $type->id ); ?>">

				<h2><?php echo $type->id ? 'Upravit typ kvalifikace' : 'Nový typ kvalifikace'; ?></h2>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th><label for="name">Název typu *</label></th>
							<td><input type="text" class="regular-text" name="name" id="name" value="<?php echo esc_attr( $type->name ); ?>" required></td>
						</tr>
						<tr>
							<th><label for="accreditation_number">Číslo akreditace</label></th>
							<td><input type="text" class="regular-text" name="accreditation_number" id="accreditation_number" value="<?php echo esc_attr( $type->accreditation_number ); ?>"></td>
						</tr>
						<tr>
							<th>Akreditované</th>
							<td>
								<label>
									<input type="checkbox" name="is_accredited" value="1" <?php checked( $type->is_accredited, 1 ); ?>>
									Ano, jde o akreditovaný typ
								</label>
							</td>
						</tr>
						<tr>
							<th>Obsahuje</th>
							<td>
								<label><input type="checkbox" name="has_courses" value="1" <?php checked( $type->has_courses, 1 ); ?>> Kurzy</label><br>
								<label><input type="checkbox" name="has_exams" value="1" <?php checked( $type->has_exams, 1 ); ?>> Zkoušky</label>
							</td>
						</tr>
						<tr>
							<th><label for="course_form_id">Fluent Form ID pro kurz</label></th>
							<td><input type="number" min="0" step="1" class="small-text" name="course_form_id" id="course_form_id" value="<?php echo esc_attr( (string) $type->course_form_id ); ?>"></td>
						</tr>
						<tr>
							<th><label for="exam_form_id">Fluent Form ID pro zkoušku</label></th>
							<td><input type="number" min="0" step="1" class="small-text" name="exam_form_id" id="exam_form_id" value="<?php echo esc_attr( (string) $type->exam_form_id ); ?>"></td>
						</tr>
						<tr>
							<th><label for="description">Popis kurzu</label></th>
							<td><textarea name="description" id="description" rows="4" class="large-text"><?php echo esc_textarea( $type->description ?? '' ); ?></textarea></td>
						</tr>
						<tr>
							<th><label for="notes">Poznámky</label></th>
							<td><textarea name="notes" id="notes" rows="4" class="large-text"><?php echo esc_textarea( $type->notes ?? '' ); ?></textarea></td>
						</tr>
						<tr>
							<th><label for="sort_order">Pořadí</label></th>
							<td><input type="number" name="sort_order" id="sort_order" value="<?php echo esc_attr( (string) $type->sort_order ); ?>"></td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" name="hlavas_type_save" value="1" class="button button-primary"><?php echo $type->id ? 'Uložit změny' : 'Vytvořit typ'; ?></button>
				</p>
			</form>
		</div>
	</div>
</div>
