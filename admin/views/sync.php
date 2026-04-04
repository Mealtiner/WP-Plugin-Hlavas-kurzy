<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $message */
/** @var array<int, array<string, mixed>> $form_sections */
/** @var array<string, mixed>|null $sync_result */
/** @var int $selected_form_id */
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Synchronizace s Fluent Forms</h1>
	<p class="hlavas-page-intro">
		Pracovní stránka pro propojení HLAVAS a Fluent Forms. Tady nastavíš směr synchronizace,
		zjistíš, zda plugin ve formuláři našel správná pole, uvidíš náhled termínů, které se budou zapisovat,
		a spustíš synchronizaci konkrétního nebo všech napojených formulářů.
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

	<?php if ( 'imported_from_ff' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				Import z Fluent Forms do pluginu byl dokončen.
				Vytvořeno: <?php echo esc_html( (string) absint( $_GET['created'] ?? 0 ) ); ?>,
				aktualizováno: <?php echo esc_html( (string) absint( $_GET['updated'] ?? 0 ) ); ?>,
				přeskočeno: <?php echo esc_html( (string) absint( $_GET['skipped'] ?? 0 ) ); ?>.
			</p>
		</div>
	<?php elseif ( 'import_from_ff_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Import z Fluent Forms do pluginu nenašel žádné použitelné termíny nebo selhal.</p></div>
	<?php elseif ( 'exported_to_ff' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Export z pluginu HLAVAS do Fluent Forms proběhl úspěšně.</p></div>
	<?php elseif ( 'export_to_ff_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Export z pluginu HLAVAS do Fluent Forms selhal.</p></div>
	<?php endif; ?>

	<div class="hlavas-settings-section hlavas-sync-intro-card">
		<h2>Jak synchronizace funguje</h2>
		<div class="hlavas-docs-grid hlavas-docs-grid-tight">
			<div class="hlavas-doc-card">
				<h3>1. Vyber směr práce</h3>
				<p>
					Pokud plugin nasazuješ na web, kde už formulář existuje, začni obvykle načtením z Fluent Forms do HLAVAS.
					Pokud jsou termíny spravované už v pluginu, použij export z HLAVAS do Fluent Forms.
				</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>2. Zkontroluj pole a náhled</h3>
				<p>
					Každý formulář níže ukazuje, která pole byla nalezena, kam jsou napojená a jaké termíny se do nich budou propisovat.
				</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>3. Zvol režim hodnot</h3>
				<p>
					<strong>Nový režim (term_key)</strong> zapisuje do value interní klíč termínu.
					<strong>Legacy režim</strong> zapisuje text labelu a je vhodný tam, kde jsou na hodnoty navázané starší podmínky nebo e-maily.
				</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>4. Servisní zásahy</h3>
				<p>
					Rebuild účastníků a oprava legacy entries jsou níže na této stránce, protože souvisí přímo s FF daty.
					Backupy, log a raw debug zůstávají na stránce <strong>Servis / Diagnostika</strong>.
				</p>
			</div>
		</div>
		<div class="hlavas-sync-mode-note">
			<p><strong>Nový režim (term_key)</strong> ukládá do hodnoty volby interní klíč termínu, například <code>kurz_2026_05_15_17</code>. Je stabilní i při pozdější změně textu labelu.</p>
			<p><strong>Legacy režim (label jako value)</strong> ukládá do hodnoty volby přímo text termínu. Hodí se hlavně pro starší formuláře a historická data.</p>
			<p><strong>Co dělá synchronizace do FF:</strong> přepíše volby polí <code>termin_kurz</code> a <code>termin_zkouska</code> podle aktuálních termínů v pluginu a současně nastaví jejich kapacity. Osobní údaje, entries a ostatní pole formuláře tím neměníš.</p>
		</div>
	</div>

	<div class="hlavas-settings-grid hlavas-sync-top-grid">
		<div class="hlavas-settings-main">
			<div class="hlavas-settings-section">
				<h2>Směr synchronizace</h2>
				<div class="hlavas-settings-sync-tools">
					<div class="hlavas-settings-sync-card">
						<h3>Načíst z Fluent Forms do HLAVAS</h3>
						<p>
							Stáhne aktuální volby z polí <code>termin_kurz</code> a <code>termin_zkouska</code> z napojených formulářů
							a vytvoří nebo aktualizuje termíny v pluginu.
						</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>">
							<?php wp_nonce_field( 'hlavas_terms_import_from_ff', '_hlavas_ff_import_nonce' ); ?>
							<p>
								<label>
									<input type="checkbox" name="hlavas_terms_import_replace_existing" value="1">
									Při importu nahradit stávající termíny v pluginu
								</label>
							</p>
							<p class="description">Tento krok nemění samotné formuláře ve Fluent Forms.</p>
							<p>
								<button
									type="submit"
									name="hlavas_terms_import_from_ff"
									value="1"
									class="button button-primary"
									onclick="return confirm('Načíst termíny z Fluent Forms do pluginu HLAVAS?');"
								>
									Načíst z Fluent Forms do HLAVAS
								</button>
							</p>
						</form>
					</div>

					<div class="hlavas-settings-sync-card">
						<h3>Odeslat z HLAVAS do Fluent Forms</h3>
						<p>
							Přepíše ve formulářích volby termínů a jejich kapacity podle aktuálních dat v pluginu.
							Ostatní nastavení formuláře zůstávají beze změny.
						</p>
						<form method="post" class="hlavas-sync-inline-form" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>">
							<?php wp_nonce_field( 'hlavas_sync', '_hlavas_sync_nonce' ); ?>
							<input type="hidden" name="sync_form_id" value="0">

							<label for="hlavas-sync-all-value-mode">Režim hodnot:</label>
							<select name="value_mode" id="hlavas-sync-all-value-mode">
								<option value="term_key" <?php selected( hlavas_terms_get_sync_value_mode(), 'term_key' ); ?>>Nový režim (term_key)</option>
								<option value="label" <?php selected( hlavas_terms_get_sync_value_mode(), 'label' ); ?>>Legacy režim (label jako value)</option>
							</select>

							<button
								type="submit"
								name="hlavas_sync_execute"
								value="1"
								class="button button-primary"
								onclick="return confirm('Opravdu synchronizovat všechny nakonfigurované formuláře?');"
							>
								Synchronizovat všechny formuláře
							</button>
						</form>
						<p class="description">
							Tohle je hlavní exportní akce pro běžný provoz. Pro konkrétní formulář můžeš použít tlačítko přímo v jeho sekci níže.
						</p>
					</div>
				</div>
			</div>

			<div class="hlavas-settings-section">
				<h2>Servisní zásahy pro FF data</h2>
				<p class="description">
					Tyto dva nástroje řeší přímo entries a párování dat mezi HLAVAS a Fluent Forms, proto jsou tady na stránce synchronizace.
				</p>
				<div class="hlavas-settings-sync-tools">
					<div class="hlavas-settings-sync-card">
						<h3>Rebuild účastníků a kapacit</h3>
						<p>
							Projede znovu všechny dostupné entries z napojených Fluent Forms formulářů a přepočítá výstupy pro stránky
							<strong>Účastníci</strong> a <strong>Obsazenost a kapacita</strong>. Nemaže data, jen provede nový průchod a kontrolu párování.
						</p>
						<form method="post" class="hlavas-sync-inline-form" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>">
							<?php wp_nonce_field( 'hlavas_sync', '_hlavas_sync_nonce' ); ?>
							<button
								type="submit"
								name="hlavas_sync_rebuild"
								value="1"
								class="button"
								onclick="return confirm('Spustit rebuild účastníků a kapacit z Fluent Forms entries?');"
							>
								Rebuild účastníků a kapacit
							</button>
						</form>
					</div>

					<div class="hlavas-settings-sync-card">
						<h3>Opravit legacy entries na nový formát</h3>
						<p>
							Doplní do starších entries nové HLAVAS klíče jako <code>typ_prihlasky</code>, <code>termin_kurz</code>
							a <code>termin_zkouska</code>. Původní legacy pole zůstávají zachována, takže je migrace ne-destruktivní.
						</p>
						<form method="post" class="hlavas-sync-inline-form" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>">
							<?php wp_nonce_field( 'hlavas_sync', '_hlavas_sync_nonce' ); ?>
							<button
								type="submit"
								name="hlavas_sync_migrate_legacy"
								value="1"
								class="button"
								onclick="return confirm('Opravdu doplnit staré legacy entries o nový HLAVAS formát? Původní data zůstanou zachována.');"
							>
								Opravit legacy na nový formát
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div class="hlavas-settings-side">
			<div class="hlavas-settings-section">
				<h2>Kontext napojení</h2>
				<div class="hlavas-sync-info hlavas-sync-info-compact">
					<p>
						Výchozí fallback formulář: <strong><?php echo esc_html( (string) hlavas_terms_get_form_id() ); ?></strong>
						<br>
						Debug režim: <strong><?php echo hlavas_terms_is_debug_enabled() ? 'Zapnuto' : 'Vypnuto'; ?></strong>
					</p>
					<p class="description">
						Form ID pro konkrétní kurz nebo zkoušku nastavíš v
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-types' ) ); ?>">Typech kurzů</a>.
						Fallback, výchozí režim synchronizace a reportovací e-mail najdeš v
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">Nastavení</a>.
					</p>
					<p class="description">
						Log, backupy a raw debug jsou na stránce
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics' ) ); ?>">Servis / Diagnostika</a>.
					</p>
				</div>
			</div>
		</div>
	</div>

	<?php if ( empty( $form_sections ) ) : ?>
		<div class="notice notice-warning">
			<p>Není nastaven žádný formulář. Nejprve doplň Form ID v typech kurzů nebo ve fallback nastavení pluginu.</p>
		</div>
	<?php else : ?>
		<div class="hlavas-sync-sections">
			<?php foreach ( $form_sections as $index => $section ) : ?>
				<?php
				$form_id        = (int) $section['form_id'];
				$form_found     = ! empty( $section['form_found'] );
				$form_title     = (string) $section['form_title'];
				$last_synced_at = hlavas_terms_get_form_last_synced_at( $form_id );
				$config         = is_array( $section['config'] ?? null ) ? $section['config'] : [];
				$assignments    = is_array( $config['assignments'] ?? null ) ? $config['assignments'] : [];
				$sync_fields    = is_array( $section['sync_fields'] ?? null ) ? $section['sync_fields'] : [];
				$field_matches  = is_array( $section['field_matches'] ?? null ) ? $section['field_matches'] : [];
				$field_catalog  = is_array( $section['field_catalog'] ?? null ) ? $section['field_catalog'] : [];
				$term_previews  = is_array( $section['term_previews'] ?? null ) ? $section['term_previews'] : [];
				$is_open        = ( $selected_form_id > 0 && $selected_form_id === $form_id ) || ( 0 === $selected_form_id && 0 === $index );
				?>
				<details class="hlavas-sync-section" <?php echo $is_open ? 'open' : ''; ?>>
					<summary>
						<span class="hlavas-sync-summary-main">
							<strong>Formulář #<?php echo esc_html( (string) $form_id ); ?></strong>
							<?php if ( $form_title ) : ?>
								<span class="hlavas-sync-summary-title"><?php echo esc_html( $form_title ); ?></span>
							<?php endif; ?>
							<span class="hlavas-sync-summary-title">
								Poslední synchronizace:
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
								<h3>Napojení</h3>
								<p class="description">
									Tady vidíš, pro které typy kvalifikací se tento formulář používá a zda obsluhuje kurzy, zkoušky nebo obojí.
								</p>
								<?php if ( empty( $assignments ) ) : ?>
									<p><em>Zatím bez navázaných typů kvalifikací.</em></p>
								<?php else : ?>
									<ul class="hlavas-sync-assignment-list">
										<?php foreach ( $assignments as $assignment ) : ?>
											<?php
											$assignment_term_type = (string) ( $assignment['term_type'] ?? '' );
											$assignment_suffix    = 'mixed' === $assignment_term_type ? 'kurzy i zkoušky' : $assignment_term_type;
											?>
											<li>
												<strong><?php echo esc_html( (string) ( $assignment['label'] ?? 'Bez názvu' ) ); ?></strong>
												<span class="hlavas-subline"><?php echo esc_html( $assignment_suffix ); ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>

							<div>
								<h3>Synchronizovaná pole</h3>
								<p class="description">
									Tahle tabulka ukazuje, zda plugin opravdu našel cílová pole termínů ve formuláři a pod jakým názvem s nimi komunikuje.
								</p>
								<table class="widefat striped hlavas-sync-field-table">
									<thead>
										<tr>
											<th>Pole</th>
											<th>Stav</th>
											<th>Napojeno na</th>
											<th>Termínů</th>
										</tr>
									</thead>
									<tbody>
										<?php if ( empty( $sync_fields ) ) : ?>
											<tr><td colspan="4">Tento formulář nemá v aktuální konfiguraci žádné synchronizované pole.</td></tr>
										<?php else : ?>
											<?php foreach ( $sync_fields as $sync_field ) : ?>
												<tr>
													<td><code><?php echo esc_html( (string) $sync_field['identifier'] ); ?></code></td>
													<td><?php echo ! empty( $sync_field['found'] ) ? '<span class="hlavas-status-yes">Nalezeno</span>' : '<span class="hlavas-status-no">Nenalezeno</span>'; ?></td>
													<td>
														<?php if ( ! empty( $sync_field['field'] ) && is_array( $sync_field['field'] ) ) : ?>
															<div><strong>Name:</strong> <code><?php echo esc_html( (string) $sync_field['field']['name'] ); ?></code></div>
															<div class="hlavas-subline">
																<?php echo esc_html( (string) ( $sync_field['field']['label'] ?: $sync_field['field']['admin_field_label'] ) ); ?>
															</div>
														<?php else : ?>
															<span class="hlavas-subline">Pole ve formuláři chybí.</span>
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
							<?php foreach ( [ 'kurz' => 'Termíny kurzů', 'zkouska' => 'Termíny zkoušek' ] as $term_type => $term_type_label ) : ?>
								<?php $preview_items = is_array( $term_previews[ $term_type ]['terms'] ?? null ) ? $term_previews[ $term_type ]['terms'] : []; ?>
								<div class="hlavas-sync-preview-card">
									<h3><?php echo esc_html( $term_type_label ); ?> (<?php echo esc_html( (string) count( $preview_items ) ); ?>)</h3>
									<p class="description">
										Náhled přesně toho, co plugin připraví jako options/value a kapacity při exportu do tohoto formuláře.
									</p>
									<?php if ( empty( $preview_items ) ) : ?>
										<p><em>Pro tento formulář tady nejsou žádné synchronizovatelné termíny.</em></p>
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
															echo esc_html( $type_label ?: 'Bez návaznosti' );
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
							<summary class="hlavas-sync-toggle-summary">Mapovat formulář: očekávaná pole a jejich shoda</summary>
							<div class="hlavas-sync-mapping-body">
								<p class="description">
									Tady porovnáš, která pole plugin očekává a co skutečně našel ve formuláři. Pokud něco nesedí, doplň ruční mapování v Nastavení.
								</p>
								<table class="widefat striped hlavas-sync-field-table">
									<thead>
										<tr>
											<th>Očekávané pole</th>
											<th>Popis</th>
											<th>Stav</th>
											<th>Nalezená vazba</th>
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
														<span class="hlavas-subline">Očekávané pole ve formuláři chybí nebo je pojmenované jinak.</span>
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</details>

						<details class="hlavas-sync-mapping">
							<summary class="hlavas-sync-toggle-summary hlavas-sync-toggle-summary-secondary">Všechna pole ve formuláři</summary>
							<div class="hlavas-sync-mapping-body">
								<p class="description">
									Kompletní katalog polí pomůže při ručním mapování nebo při hledání rozdílu mezi interním <code>name</code>, admin labelem a viditelným labelem.
								</p>
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
											<tr><td colspan="4">U tohoto formuláře se nepodařilo načíst žádná pole.</td></tr>
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
							</div>
						</details>

						<div class="hlavas-sync-form-actions">
							<form method="post" class="hlavas-sync-inline-form">
								<?php wp_nonce_field( 'hlavas_sync', '_hlavas_sync_nonce' ); ?>
								<input type="hidden" name="sync_form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">

								<label for="hlavas-sync-value-mode-<?php echo esc_attr( (string) $form_id ); ?>">Režim hodnot:</label>
								<select name="value_mode" id="hlavas-sync-value-mode-<?php echo esc_attr( (string) $form_id ); ?>">
									<option value="term_key" <?php selected( hlavas_terms_get_sync_value_mode(), 'term_key' ); ?>>Nový režim (term_key)</option>
									<option value="label" <?php selected( hlavas_terms_get_sync_value_mode(), 'label' ); ?>>Legacy režim (label jako value)</option>
								</select>

								<button
									type="submit"
									name="hlavas_sync_execute"
									value="1"
									class="button button-primary"
									onclick="return confirm('Opravdu synchronizovat formulář #<?php echo esc_js( (string) $form_id ); ?>?');"
								>
									Synchronizovat tento formulář
								</button>

								<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics&form_id=' . $form_id . '&debug=1#hlavas-debug' ) ); ?>" class="button">
									Debug formuláře
								</a>
							</form>
						</div>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
