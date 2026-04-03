<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array<int, array<string, mixed>> $form_sections */
/** @var array<string, mixed>|null $debug */
/** @var array<string, mixed>|null $sync_result */
/** @var int $selected_form_id */
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Synchronizace s Fluent Forms</h1>
	<p>
		Stranka pocita s tim, ze kazdy typ kvalifikace muze mit vlastni Fluent Form pro kurz i zkousku.
		Nize je proto synchronizace rozdelena po jednotlivych formularech, vcetne kontroly poli a mapovani.
	</p>

	<?php if ( $sync_result ) : ?>
		<div class="notice notice-<?php echo ! empty( $sync_result['success'] ) ? 'success' : 'error'; ?> is-dismissible">
			<p><strong><?php echo esc_html( (string) $sync_result['message'] ); ?></strong></p>
			<?php if ( ! empty( $sync_result['details'] ) ) : ?>
				<ul>
					<?php foreach ( (array) $sync_result['details'] as $detail ) : ?>
						<li><?php echo esc_html( (string) $detail ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="hlavas-sync-info">
		<p>
			Vychozi formular pluginu: <strong><?php echo esc_html( (string) hlavas_terms_get_form_id() ); ?></strong>
			&nbsp;|&nbsp;
			Debug rezim: <strong><?php echo hlavas_terms_is_debug_enabled() ? 'Zapnuto' : 'Vypnuto'; ?></strong>
		</p>
		<p class="description">
			Form ID pro konkretni kurz / zkousku nastavite v
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-types' ) ); ?>">typech kurzu</a>.
			Vychozi formular a debug rezim zustavaji v
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">nastaveni pluginu</a>.
		</p>
	</div>

	<div class="notice notice-info">
		<p><strong>Vysvetleni rezimu synchronizace</strong></p>
		<p>
			<strong>Novy rezim (term_key)</strong> uklada do hodnoty volby interni klic terminu, napr. <code>kurz_2026_05_15_17</code>.
			To je doporucena varianta pro nove formularove napojeni, protoze je stabilni i kdyz se pozdeji zmeni text labelu terminu.
		</p>
		<p>
			<strong>Legacy rezim (label jako value)</strong> uklada do hodnoty volby primo text labelu terminu.
			Hodi se hlavne pro starsi existujici formulare a zaznamy, ktere uz byly postavene na textove hodnote misto interniho klice.
		</p>
		<p>
			Po stisknuti <strong>Synchronizovat formular</strong> plugin prepise u vybraneho Fluent Forms formulare volby poli
			<code>termin_kurz</code> a/nebo <code>termin_zkouska</code> podle aktualnich terminu v pluginu. Soucasne se prepise i inventar
			kapacit pro tyto volby. Osobni udaje, odeslane prihlašky ani ostatni pole formulare se tim nemení.
		</p>
	</div>

	<?php if ( empty( $form_sections ) ) : ?>
		<div class="notice notice-warning">
			<p>Neni nastaven zadny formular. Nejdriv dopln Form ID v typech kurzu nebo ve vychozim nastaveni pluginu.</p>
		</div>
	<?php else : ?>
		<div class="hlavas-sync-actions">
			<form method="post" class="hlavas-sync-inline-form">
				<?php wp_nonce_field( 'hlavas_sync', '_hlavas_sync_nonce' ); ?>
				<input type="hidden" name="sync_form_id" value="0">

				<label for="hlavas-sync-all-value-mode">Rezim hodnot:</label>
				<select name="value_mode" id="hlavas-sync-all-value-mode">
					<option value="term_key" <?php selected( hlavas_terms_get_sync_value_mode(), 'term_key' ); ?>>Novy rezim (term_key)</option>
					<option value="label" <?php selected( hlavas_terms_get_sync_value_mode(), 'label' ); ?>>Legacy rezim (label jako value)</option>
				</select>

				<button
					type="submit"
					name="hlavas_sync_execute"
					value="1"
					class="button button-primary"
					onclick="return confirm('Opravdu synchronizovat vsechny nakonfigurovane formulare?');"
				>
					Synchronizovat vsechny formulare
				</button>
			</form>
		</div>

		<div class="hlavas-sync-sections">
			<?php foreach ( $form_sections as $index => $section ) : ?>
				<?php
				$form_id          = (int) $section['form_id'];
				$form_found       = ! empty( $section['form_found'] );
				$form_title       = (string) $section['form_title'];
				$last_synced_at   = hlavas_terms_get_form_last_synced_at( $form_id );
				$config           = is_array( $section['config'] ?? null ) ? $section['config'] : [];
				$assignments      = is_array( $config['assignments'] ?? null ) ? $config['assignments'] : [];
				$sync_fields      = is_array( $section['sync_fields'] ?? null ) ? $section['sync_fields'] : [];
				$field_matches    = is_array( $section['field_matches'] ?? null ) ? $section['field_matches'] : [];
				$field_catalog    = is_array( $section['field_catalog'] ?? null ) ? $section['field_catalog'] : [];
				$term_previews    = is_array( $section['term_previews'] ?? null ) ? $section['term_previews'] : [];
				$is_open          = ( $selected_form_id > 0 && $selected_form_id === $form_id ) || ( 0 === $selected_form_id && 0 === $index );
				?>
				<details class="hlavas-sync-section" <?php echo $is_open ? 'open' : ''; ?>>
					<summary>
						<span class="hlavas-sync-summary-main">
							<strong>Formular #<?php echo esc_html( (string) $form_id ); ?></strong>
							<?php if ( $form_title ) : ?>
								<span class="hlavas-sync-summary-title"><?php echo esc_html( $form_title ); ?></span>
							<?php endif; ?>
							<span class="hlavas-sync-summary-title">
								Posledni synchronizace:
								<?php echo '' !== $last_synced_at ? esc_html( $last_synced_at ) : 'Nikdy'; ?>
							</span>
						</span>
						<span class="hlavas-sync-summary-side">
							<?php echo $form_found ? '<span class="hlavas-status-yes">Ano</span>' : '<span class="hlavas-status-no">Nenalezeno</span>'; ?>
						</span>
					</summary>

					<div class="hlavas-sync-panel">
						<div class="hlavas-sync-meta">
							<div>
								<h3>Napojeni</h3>
								<?php if ( empty( $assignments ) ) : ?>
									<p><em>Zatim bez navazanych typu kvalifikaci.</em></p>
								<?php else : ?>
									<ul class="hlavas-sync-assignment-list">
										<?php foreach ( $assignments as $assignment ) : ?>
											<?php
											$assignment_term_type = (string) ( $assignment['term_type'] ?? '' );
											$assignment_suffix    = 'mixed' === $assignment_term_type ? 'kurzy i zkousky' : $assignment_term_type;
											?>
											<li>
												<strong><?php echo esc_html( (string) ( $assignment['label'] ?? 'Bez nazvu' ) ); ?></strong>
												<span class="hlavas-subline"><?php echo esc_html( $assignment_suffix ); ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>

							<div>
								<h3>Synchronizovana pole</h3>
								<table class="widefat striped hlavas-sync-field-table">
									<thead>
										<tr>
											<th>Pole</th>
											<th>Stav</th>
											<th>Napojeno na</th>
											<th>Terminu</th>
										</tr>
									</thead>
									<tbody>
										<?php if ( empty( $sync_fields ) ) : ?>
											<tr><td colspan="4">Tento formular nema v aktualni konfiguraci zadne synchronizovane pole.</td></tr>
										<?php else : ?>
											<?php foreach ( $sync_fields as $sync_field ) : ?>
												<tr>
													<td><code><?php echo esc_html( (string) $sync_field['identifier'] ); ?></code></td>
													<td>
														<?php echo ! empty( $sync_field['found'] ) ? '<span class="hlavas-status-yes">Nalezeno</span>' : '<span class="hlavas-status-no">Nenalezeno</span>'; ?>
													</td>
													<td>
														<?php if ( ! empty( $sync_field['field'] ) && is_array( $sync_field['field'] ) ) : ?>
															<div><strong>Name:</strong> <code><?php echo esc_html( (string) $sync_field['field']['name'] ); ?></code></div>
															<div class="hlavas-subline">
																<?php echo esc_html( (string) ( $sync_field['field']['label'] ?: $sync_field['field']['admin_field_label'] ) ); ?>
															</div>
														<?php else : ?>
															<span class="hlavas-subline">Pole ve formulari chybi.</span>
														<?php endif; ?>
													</td>
													<td><?php echo esc_html( (string) $sync_field['terms_count'] ); ?></td>
												</tr>
											<?php endforeach; ?>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>

						<div class="hlavas-sync-previews">
							<?php foreach ( [ 'kurz' => 'Terminy kurzu', 'zkouska' => 'Terminy zkousek' ] as $term_type => $term_type_label ) : ?>
								<?php $preview_items = is_array( $term_previews[ $term_type ]['terms'] ?? null ) ? $term_previews[ $term_type ]['terms'] : []; ?>
								<div class="hlavas-sync-preview-card">
									<h3><?php echo esc_html( $term_type_label ); ?> (<?php echo esc_html( (string) count( $preview_items ) ); ?>)</h3>
									<?php if ( empty( $preview_items ) ) : ?>
										<p><em>Pro tento formular zde nejsou zadne synchronizovatelne terminy.</em></p>
									<?php else : ?>
										<table class="widefat striped hlavas-sync-preview-table">
											<thead>
												<tr>
													<th>Value</th>
													<th>Label</th>
													<th>Typ kurzu</th>
													<th>Kapacita</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $preview_items as $item ) : ?>
													<tr>
														<td><code><?php echo esc_html( (string) $item['term_key'] ); ?></code></td>
														<td>
															<strong><?php echo esc_html( (string) $item['title'] ); ?></strong>
															<div class="hlavas-subline"><?php echo esc_html( (string) $item['label'] ); ?></div>
														</td>
														<td>
															<?php
															$type_label = ! empty( $item['qualification_code'] )
																? $item['qualification_code'] . ' - ' . $item['qualification_name']
																: $item['qualification_name'];
															echo esc_html( $type_label ?: 'Bez navaznosti' );
															?>
														</td>
														<td><?php echo esc_html( (string) $item['capacity'] ); ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>

						<details class="hlavas-sync-mapping">
							<summary>Mapovat formular: doporucena pole a jejich shoda</summary>
							<table class="widefat striped hlavas-sync-field-table">
								<thead>
									<tr>
										<th>Ocekavane pole</th>
										<th>Popis</th>
										<th>Stav</th>
										<th>Nalezena vazba</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $field_matches as $match ) : ?>
										<tr>
											<td><code><?php echo esc_html( (string) $match['identifier'] ); ?></code></td>
											<td><?php echo esc_html( (string) $match['description'] ); ?></td>
											<td><?php echo ! empty( $match['found'] ) ? '<span class="hlavas-status-yes">Nalezeno</span>' : '<span class="hlavas-status-no">Nenalezeno</span>'; ?></td>
											<td>
												<?php if ( ! empty( $match['field'] ) && is_array( $match['field'] ) ) : ?>
													<div><strong>Name:</strong> <code><?php echo esc_html( (string) $match['field']['name'] ); ?></code></div>
													<div class="hlavas-subline"><strong>Admin label:</strong> <?php echo esc_html( (string) $match['field']['admin_field_label'] ); ?></div>
													<div class="hlavas-subline"><strong>Label:</strong> <?php echo esc_html( (string) $match['field']['label'] ); ?></div>
												<?php else : ?>
													<span class="hlavas-subline">Ocekavane pole ve formulari chybi nebo je pojmenovane jinak.</span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</details>

						<details class="hlavas-sync-mapping">
							<summary>Vsechna pole ve formulari</summary>
							<table class="widefat striped hlavas-sync-field-table">
								<thead>
									<tr>
										<th>Element</th>
										<th>Name</th>
										<th>Admin field label</th>
										<th>Label</th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $field_catalog ) ) : ?>
										<tr><td colspan="4">U tohoto formulare se nepodarilo nacist zadna pole.</td></tr>
									<?php else : ?>
										<?php foreach ( $field_catalog as $field_item ) : ?>
											<tr>
												<td><?php echo esc_html( (string) $field_item['element'] ); ?></td>
												<td><code><?php echo esc_html( (string) $field_item['name'] ); ?></code></td>
												<td><?php echo esc_html( (string) $field_item['admin_field_label'] ); ?></td>
												<td><?php echo esc_html( (string) $field_item['label'] ); ?></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</details>

						<div class="hlavas-sync-form-actions">
							<form method="post" class="hlavas-sync-inline-form">
								<?php wp_nonce_field( 'hlavas_sync', '_hlavas_sync_nonce' ); ?>
								<input type="hidden" name="sync_form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">

								<label for="hlavas-sync-value-mode-<?php echo esc_attr( (string) $form_id ); ?>">Rezim hodnot:</label>
								<select name="value_mode" id="hlavas-sync-value-mode-<?php echo esc_attr( (string) $form_id ); ?>">
									<option value="term_key" <?php selected( hlavas_terms_get_sync_value_mode(), 'term_key' ); ?>>Novy rezim (term_key)</option>
									<option value="label" <?php selected( hlavas_terms_get_sync_value_mode(), 'label' ); ?>>Legacy rezim (label jako value)</option>
								</select>

								<button
									type="submit"
									name="hlavas_sync_execute"
									value="1"
									class="button button-primary"
									onclick="return confirm('Opravdu synchronizovat formular #<?php echo esc_js( (string) $form_id ); ?>?');"
								>
									Synchronizovat tento formular
								</button>
							</form>

							<a
								href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync&form_id=' . $form_id . '&debug=1' ) ); ?>"
								class="button"
							>
								Zobrazit raw debug
							</a>
						</div>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<hr>
	<h2>Raw debug vystup</h2>
	<?php if ( null === $debug ) : ?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync&debug=1' ) ); ?>" class="button">
				Zobrazit debug prvniho formulare
			</a>
		</p>
	<?php else : ?>
		<div class="hlavas-debug-output">
			<pre style="background: #f1f1f1; padding: 15px; overflow: auto; max-height: 600px; font-size: 12px;"><?php
				echo esc_html( json_encode( $debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
			?></pre>
		</div>
	<?php endif; ?>
</div>
