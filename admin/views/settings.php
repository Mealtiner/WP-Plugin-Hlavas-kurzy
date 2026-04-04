<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $message */
/** @var int $form_id */
/** @var bool $debug_mode */
/** @var string $sync_value_mode */
/** @var string $report_email */
/** @var string $activity_log_path */
/** @var array<string, string> $plugin_info */
/** @var array<int, object> $types */
/** @var array<string, mixed> $settings_status */
/** @var array<int, array<string, mixed>> $form_registry */
/** @var array<int, array<string, mixed>> $form_mapping_sections */
/** @var array<int, array<string, string>> $field_map */
/** @var array<string, string> $sync_log */
/** @var array<int, string> $activity_log_lines */
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Nastavení pluginu</h1>
	<p>
		Centrální správa provozních voleb pluginu, kontroly napojení na Fluent Forms a servisních nástrojů.
		Na jednom místě je tu fallback formulář, režim synchronizace, kontrola všech přiřazených Form ID i záloha dat pluginu.
	</p>

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Nastavení bylo uloženo.</p></div>
	<?php elseif ( 'imported_from_ff' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Import z Fluent Forms do pluginu byl dokončen. Vytvořeno: <?php echo esc_html( (string) absint( $_GET['created'] ?? 0 ) ); ?>, aktualizováno: <?php echo esc_html( (string) absint( $_GET['updated'] ?? 0 ) ); ?>, přeskočeno: <?php echo esc_html( (string) absint( $_GET['skipped'] ?? 0 ) ); ?>.</p></div>
	<?php elseif ( 'import_from_ff_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Import z Fluent Forms do pluginu nenašel žádné použitelné termíny nebo selhal.</p></div>
	<?php elseif ( 'exported_to_ff' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Export z pluginu HLAVAS do Fluent Forms proběhl úspěšně.</p></div>
	<?php elseif ( 'export_to_ff_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Export z pluginu HLAVAS do Fluent Forms selhal.</p></div>
	<?php elseif ( 'imported' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Záloha pluginu byla úspěšně importována.</p></div>
	<?php elseif ( 'import_missing_file' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Nebyl vybrán žádný soubor k importu.</p></div>
	<?php elseif ( 'import_invalid_file' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Importovaný soubor není platná záloha pluginu.</p></div>
	<?php elseif ( 'sync_log_reset' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Datumy synchronizace byly vymazány.</p></div>
	<?php elseif ( 'log_cleared' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Textový log pluginu byl vymazán.</p></div>
	<?php elseif ( 'log_missing' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Log soubor zatím neexistuje nebo není čitelný.</p></div>
	<?php elseif ( 'log_clear_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Log soubor se nepodařilo vymazat.</p></div>
	<?php endif; ?>

	<div class="hlavas-settings-grid">
		<div class="hlavas-settings-main">
			<div class="hlavas-settings-section">
				<h2>Synchronizace mezi HLAVAS a Fluent Forms</h2>
				<p class="description">
					Synchronizace je rozdělená na dva samostatné směry. Nejdřív můžeš načíst stávající data z Fluent Forms do pluginu HLAVAS a teprve potom odeslat upravené termíny zpět do formulářů.
				</p>

				<div class="hlavas-settings-sync-tools">
					<div class="hlavas-settings-sync-card">
						<h3>Do pluginu HLAVAS</h3>
						<p>
							Načte aktuální volby z polí <code>termin_kurz</code> a <code>termin_zkouska</code> z nakonfigurovaných formulářů a vytvoří nebo aktualizuje termíny v pluginu.
						</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">
							<?php wp_nonce_field( 'hlavas_terms_import_from_ff', '_hlavas_ff_import_nonce' ); ?>
							<p>
								<label>
									<input type="checkbox" name="hlavas_terms_import_replace_existing" value="1">
									Při importu nahradit stávající termíny v pluginu
								</label>
							</p>
							<p class="description">
								Tento krok nepřepisuje samotné formuláře ve Fluent Forms.
							</p>
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
						<h3>Do pluginu FF</h3>
						<p>
							Odešle aktuální termíny z pluginu HLAVAS do Fluent Forms a aktualizuje volby termínů i jejich kapacity. Ostatní nastavení pole, včetně podmínek, ponechá beze změny.
						</p>
						<p class="description">
							Pokud máš další logiky navázané na konkrétní hodnoty voleb, zkontroluj před exportem zvolený synchronizační režim níže.
						</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">
							<?php wp_nonce_field( 'hlavas_terms_export_to_ff', '_hlavas_ff_export_nonce' ); ?>
							<p>
								<button
									type="submit"
									name="hlavas_terms_export_to_ff"
									value="1"
									class="button"
									onclick="return confirm('Odeslat aktuální termíny z pluginu HLAVAS do Fluent Forms? Tímto se přepíšou volby termínů ve formulářích.');"
								>
									Odeslat z HLAVAS do Fluent Forms
								</button>
							</p>
						</form>
					</div>
				</div>
			</div>

			<div class="hlavas-settings-section">
				<h2>Provozní nastavení</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">
					<?php wp_nonce_field( 'hlavas_terms_settings', '_hlavas_settings_nonce' ); ?>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><label for="hlavas_terms_form_id">Výchozí Fluent Form ID</label></th>
								<td>
									<input
										type="number"
										min="1"
										step="1"
										class="regular-text"
										name="hlavas_terms_form_id"
										id="hlavas_terms_form_id"
										value="<?php echo esc_attr( (string) $form_id ); ?>"
									>
									<p class="description">
										Fallback formulář pro starší napojení, debug a případy, kdy termín nebo typ kvalifikace nemá vlastní Form ID.
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="hlavas_terms_sync_value_mode">Výchozí režim synchronizace</label></th>
								<td>
									<select name="hlavas_terms_sync_value_mode" id="hlavas_terms_sync_value_mode">
										<option value="term_key" <?php selected( $sync_value_mode, 'term_key' ); ?>>Nový režim (term_key)</option>
										<option value="label" <?php selected( $sync_value_mode, 'label' ); ?>>Legacy režim (label jako value)</option>
									</select>
									<p class="description">
										Tento režim se předvyplní na stránce synchronizace a používá se i při rychlé synchronizaci z detailu termínu. Pokud je na hodnoty voleb navázaná další logika ve Fluent Forms, bývá bezpečnější legacy režim.
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">Debug režim</th>
								<td>
									<label for="hlavas_terms_debug_mode">
										<input
											type="checkbox"
											name="hlavas_terms_debug_mode"
											id="hlavas_terms_debug_mode"
											value="1"
											<?php checked( $debug_mode ); ?>
										>
										Zapnout rozšířený debug výstup v administrační části pluginu
									</label>
									<p class="description">
										Po zapnutí se automaticky nabízí detailní debug výstup struktury formulářů na stránce synchronizace.
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="hlavas_terms_report_email">E-mail pro reporty</label></th>
								<td>
									<input
										type="email"
										class="regular-text"
										name="hlavas_terms_report_email"
										id="hlavas_terms_report_email"
										value="<?php echo esc_attr( $report_email ); ?>"
									>
									<p class="description">
										Na tuto adresu se odesílají CSV reporty ze stránek termínů, obsazenosti a účastníků.
									</p>
								</td>
							</tr>
						</tbody>
					</table>

					<details class="hlavas-settings-advanced">
						<summary>Rozšíření nastavení: ruční mapování polí mezi HLAVAS a Fluent Forms</summary>
						<div class="hlavas-settings-advanced-body">
							<p class="description">
								Tato část je určená pro případy, kdy jsou ve Fluent Forms pole přejmenovaná nebo používají jiné <code>name</code>, <code>admin_field_label</code> či viditelný label než plugin HLAVAS očekává automaticky.
								Můžeš tu ručně nastavit mapování 1:1 pro konkrétní formulář. Plugin pak tuto vazbu použije při synchronizaci termínů, diagnostice i načítání účastníků.
							</p>

							<?php if ( empty( $form_mapping_sections ) ) : ?>
								<p class="description">Zatím není k dispozici žádný formulář pro mapování polí.</p>
							<?php else : ?>
								<?php foreach ( $form_mapping_sections as $section ) : ?>
									<?php
									$section_form_id  = (int) ( $section['form_id'] ?? 0 );
									$field_matches    = is_array( $section['field_matches'] ?? null ) ? $section['field_matches'] : [];
									$field_catalog    = is_array( $section['field_catalog'] ?? null ) ? $section['field_catalog'] : [];
									$datalist_id      = 'hlavas-map-suggestions-' . $section_form_id;
									$manual_form_map  = $field_map[ $section_form_id ] ?? [];
									?>
									<div class="hlavas-settings-map-card">
										<h3>
											Formulář #<?php echo esc_html( (string) $section_form_id ); ?>
											<?php if ( ! empty( $section['form_title'] ) ) : ?>
												<span class="hlavas-subline"><?php echo esc_html( (string) $section['form_title'] ); ?></span>
											<?php endif; ?>
										</h3>

										<?php if ( empty( $section['form_found'] ) ) : ?>
											<p class="description">Formulář teď není dostupný, takže nejde zkontrolovat jeho pole. Ruční mapování se ale uloží a použije, jakmile bude formulář znovu k dispozici.</p>
										<?php endif; ?>

										<datalist id="<?php echo esc_attr( $datalist_id ); ?>">
											<?php foreach ( $field_catalog as $catalog_item ) : ?>
												<?php foreach ( [ 'name', 'admin_field_label', 'label' ] as $catalog_key ) : ?>
													<?php $catalog_value = trim( (string) ( $catalog_item[ $catalog_key ] ?? '' ) ); ?>
													<?php if ( '' !== $catalog_value ) : ?>
														<option value="<?php echo esc_attr( $catalog_value ); ?>"></option>
													<?php endif; ?>
												<?php endforeach; ?>
											<?php endforeach; ?>
										</datalist>

										<table class="widefat striped hlavas-settings-map-table">
											<thead>
												<tr>
													<th>HLAVAS pole</th>
													<th>Význam</th>
													<th>Automaticky nalezeno</th>
													<th>Ruční mapování 1:1</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $field_matches as $identifier => $match ) : ?>
													<?php
													$manual_value = (string) ( $manual_form_map[ sanitize_key( (string) $identifier ) ] ?? '' );
													$found_field  = is_array( $match['field'] ?? null ) ? $match['field'] : [];
													?>
													<tr>
														<td>
															<strong><?php echo esc_html( (string) ( $match['label'] ?? $identifier ) ); ?></strong>
															<div class="hlavas-subline"><code><?php echo esc_html( (string) $identifier ); ?></code></div>
														</td>
														<td><?php echo esc_html( (string) ( $match['description'] ?? '' ) ); ?></td>
														<td>
															<?php if ( ! empty( $match['found'] ) && ! empty( $found_field ) ) : ?>
																<span class="hlavas-status-yes">Ano</span>
																<div class="hlavas-subline">
																	<?php if ( ! empty( $found_field['name'] ) ) : ?>
																		<code><?php echo esc_html( (string) $found_field['name'] ); ?></code>
																	<?php elseif ( ! empty( $found_field['admin_field_label'] ) ) : ?>
																		<?php echo esc_html( (string) $found_field['admin_field_label'] ); ?>
																	<?php else : ?>
																		<?php echo esc_html( (string) ( $found_field['label'] ?? '' ) ); ?>
																	<?php endif; ?>
																</div>
															<?php else : ?>
																<span class="hlavas-status-no">Nenalezeno</span>
															<?php endif; ?>
														</td>
														<td>
															<input
																type="text"
																class="regular-text"
																name="hlavas_terms_field_map[<?php echo esc_attr( (string) $section_form_id ); ?>][<?php echo esc_attr( (string) sanitize_key( (string) $identifier ) ); ?>]"
																value="<?php echo esc_attr( $manual_value ); ?>"
																list="<?php echo esc_attr( $datalist_id ); ?>"
																placeholder="např. termin_kurz nebo Vyber termín kurzu"
															>
															<p class="description">
																Zadej přesné <code>name</code>, <code>admin label</code> nebo viditelný label pole z tohoto formuláře.
															</p>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>

										<details class="hlavas-settings-map-catalog">
											<summary>Všechna nalezená pole formuláře</summary>
											<?php if ( empty( $field_catalog ) ) : ?>
												<p class="description">Katalog polí není k dispozici.</p>
											<?php else : ?>
												<table class="widefat striped hlavas-settings-map-catalog-table">
													<thead>
														<tr>
															<th>Element</th>
															<th><code>name</code></th>
															<th><code>admin_field_label</code></th>
															<th>Label</th>
														</tr>
													</thead>
													<tbody>
														<?php foreach ( $field_catalog as $catalog_item ) : ?>
															<tr>
																<td><?php echo esc_html( (string) ( $catalog_item['element'] ?? '' ) ); ?></td>
																<td><code><?php echo esc_html( (string) ( $catalog_item['name'] ?? '' ) ); ?></code></td>
																<td><?php echo esc_html( (string) ( $catalog_item['admin_field_label'] ?? '' ) ); ?></td>
																<td><?php echo esc_html( (string) ( $catalog_item['label'] ?? '' ) ); ?></td>
															</tr>
														<?php endforeach; ?>
													</tbody>
												</table>
											<?php endif; ?>
										</details>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</details>

					<p class="submit">
						<button type="submit" name="hlavas_terms_save_settings" value="1" class="button button-primary">Uložit nastavení</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>" class="button">Přejít na synchronizaci</a>
					</p>
				</form>
			</div>

			<div class="hlavas-settings-section">
				<h2>Kontrola stavu</h2>
				<table class="widefat striped hlavas-settings-status-table">
					<tbody>
						<tr>
							<th>Verze pluginu / DB</th>
							<td><?php echo esc_html( (string) $settings_status['plugin_version'] ); ?> / <?php echo esc_html( (string) $settings_status['db_version'] ); ?></td>
						</tr>
						<tr>
							<th>Tabulka termínů</th>
							<td><?php echo ! empty( $settings_status['terms_table_exists'] ) ? '<span class="hlavas-status-yes">Ano</span>' : '<span class="hlavas-status-no">Ne</span>'; ?></td>
						</tr>
						<tr>
							<th>Tabulka typů kvalifikací</th>
							<td><?php echo ! empty( $settings_status['types_table_exists'] ) ? '<span class="hlavas-status-yes">Ano</span>' : '<span class="hlavas-status-no">Ne</span>'; ?></td>
						</tr>
						<tr>
							<th>Fluent Forms tabulka formulářů</th>
							<td><?php echo ! empty( $settings_status['fluent_forms_table_exists'] ) ? '<span class="hlavas-status-yes">Ano</span>' : '<span class="hlavas-status-no">Ne</span>'; ?></td>
						</tr>
						<tr>
							<th>Fluent Forms tabulka přihlášek</th>
							<td><?php echo ! empty( $settings_status['fluent_subs_table_exists'] ) ? '<span class="hlavas-status-yes">Ano</span>' : '<span class="hlavas-status-no">Ne</span>'; ?></td>
						</tr>
						<tr>
							<th>Počet typů kvalifikací</th>
							<td><?php echo esc_html( (string) $settings_status['types_count'] ); ?></td>
						</tr>
						<tr>
							<th>Počet termínů</th>
							<td><?php echo esc_html( (string) $settings_status['terms_count'] ); ?></td>
						</tr>
						<tr>
							<th>Záznamů v logu synchronizace</th>
							<td><?php echo esc_html( (string) $settings_status['sync_log_count'] ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="hlavas-settings-section">
				<h2>Napojení Fluent Forms</h2>
				<p class="description">
					Plugin už pracuje s více formuláři. Každý typ kvalifikace může mít vlastní Form ID pro kurz a pro zkoušku.
					Výchozí formulář níže slouží jen jako fallback.
				</p>

				<table class="wp-list-table widefat striped hlavas-settings-forms-table">
					<thead>
						<tr>
							<th>Využití</th>
							<th style="width: 90px;">Form ID</th>
							<th>Formulář nalezen</th>
							<th>Stav</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $form_registry ) ) : ?>
							<tr><td colspan="4">Zatím není nakonfigurovaný žádný formulář.</td></tr>
						<?php else : ?>
							<?php foreach ( $form_registry as $form_item ) : ?>
								<tr>
									<td><?php echo esc_html( (string) $form_item['usage'] ); ?></td>
									<td><code><?php echo esc_html( (string) $form_item['form_id'] ); ?></code></td>
									<td>
										<?php if ( ! empty( $form_item['exists'] ) ) : ?>
											<span class="hlavas-status-yes">Ano</span>
											<div class="hlavas-subline"><?php echo esc_html( (string) $form_item['form_title'] ); ?></div>
										<?php else : ?>
											<span class="hlavas-status-no">Nenalezeno</span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( (string) ( $form_item['status'] ?: '—' ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<p class="description">
					Přímé přiřazení Form ID ke kurzům a zkouškám upravíš v
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-types' ) ); ?>">typech kurzů</a>.
				</p>
			</div>

			<div class="hlavas-settings-section">
				<h2>Aktivitní log pluginu</h2>
				<p class="description">
					Log zachycuje úpravy a důležité akce v pluginu, včetně toho kdo je provedl a kdy.
					Ukládá se jako textový soubor do pluginu: <code><?php echo esc_html( $activity_log_path ); ?></code>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>" class="hlavas-report-inline-form">
					<?php wp_nonce_field( 'hlavas_terms_log_actions', '_hlavas_log_nonce' ); ?>
					<button type="submit" name="hlavas_terms_download_log" value="1" class="button button-primary">Stáhnout TXT log</button>
					<button
						type="submit"
						name="hlavas_terms_clear_log"
						value="1"
						class="button"
						onclick="return confirm('Opravdu vymazat textový log pluginu?');"
					>
						Vymazat log
					</button>
				</form>

				<?php if ( empty( $activity_log_lines ) ) : ?>
					<p class="description">Log je zatím prázdný.</p>
				<?php else : ?>
					<pre class="hlavas-log-preview"><?php echo esc_html( implode( "\n", $activity_log_lines ) ); ?></pre>
					<p class="description">Zobrazeno je posledních <?php echo esc_html( (string) count( $activity_log_lines ) ); ?> záznamů od nejnovějšího.</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="hlavas-settings-side">

			<div class="hlavas-settings-section hlavas-shortcodes-reference">
				<h2>Shortcodes pro frontend</h2>
				<p class="description">
					Vkládej shortcodes do stránek, článků nebo page builderů (Elementor, Divi, Bricks, Gutenberg). Zobrazují pouze veřejné informace — žádná osobní data účastníků.
				</p>

				<div class="hlavas-shortcode-card">
					<div class="hlavas-shortcode-tag"><code>[hlavas_terms]</code></div>
					<p class="hlavas-shortcode-desc">Tabulka nadcházejících termínů s kapacitami.</p>
					<table class="hlavas-shortcode-attrs">
						<thead><tr><th>Atribut</th><th>Hodnoty</th><th>Výchozí</th></tr></thead>
						<tbody>
							<tr><td><code>type</code></td><td><code>kurz</code> | <code>zkouska</code> | <code>all</code></td><td><code>all</code></td></tr>
							<tr><td><code>show</code></td><td><code>upcoming</code> | <code>all</code></td><td><code>upcoming</code></td></tr>
							<tr><td><code>limit</code></td><td>číslo</td><td><code>0</code> (vše)</td></tr>
							<tr><td><code>show_capacity</code></td><td><code>yes</code> | <code>no</code></td><td><code>yes</code></td></tr>
							<tr><td><code>qualification</code></td><td>type_key</td><td>— (vše)</td></tr>
							<tr><td><code>class</code></td><td>CSS třída</td><td>—</td></tr>
						</tbody>
					</table>
					<p class="hlavas-shortcode-example"><strong>Příklady:</strong></p>
					<code class="hlavas-shortcode-snippet">[hlavas_terms type="kurz" limit="5"]</code>
					<code class="hlavas-shortcode-snippet">[hlavas_terms type="zkouska" show_capacity="no"]</code>
				</div>

				<div class="hlavas-shortcode-card">
					<div class="hlavas-shortcode-tag"><code>[hlavas_term_capacity]</code></div>
					<p class="hlavas-shortcode-desc">Odznak volných míst pro konkrétní termín.</p>
					<table class="hlavas-shortcode-attrs">
						<thead><tr><th>Atribut</th><th>Hodnoty</th><th>Výchozí</th></tr></thead>
						<tbody>
							<tr><td><code>term_key</code></td><td>klíč termínu</td><td>— (povinné)</td></tr>
							<tr><td><code>format</code></td><td><code>badge</code> | <code>text</code> | <code>number</code></td><td><code>badge</code></td></tr>
						</tbody>
					</table>
					<code class="hlavas-shortcode-snippet">[hlavas_term_capacity term_key="kurz_2026_05_15_17"]</code>
					<code class="hlavas-shortcode-snippet">[hlavas_term_capacity term_key="kurz_2026_05_15_17" format="text"]</code>
				</div>

				<div class="hlavas-shortcode-card">
					<div class="hlavas-shortcode-tag"><code>[hlavas_waitlist]</code></div>
					<p class="hlavas-shortcode-desc">Formulář čekací listiny — zobrazí se automaticky, když je termín plný. Ukládá jméno a e-mail se souhlasem GDPR.</p>
					<table class="hlavas-shortcode-attrs">
						<thead><tr><th>Atribut</th><th>Hodnoty</th><th>Výchozí</th></tr></thead>
						<tbody>
							<tr><td><code>term_key</code></td><td>klíč termínu</td><td>— (povinné)</td></tr>
							<tr><td><code>show_always</code></td><td><code>yes</code> | <code>no</code></td><td><code>no</code></td></tr>
						</tbody>
					</table>
					<code class="hlavas-shortcode-snippet">[hlavas_waitlist term_key="kurz_2026_05_15_17"]</code>
					<p class="description" style="margin-top:6px;">Záznamy čekací listiny jsou uloženy v databázi a přístupné pouze v adminu.</p>
				</div>
			</div>

			<div class="hlavas-settings-section">
				<h2>Servis a údržba</h2>
				<p>
					Tady jsou rychlé zásahy, které se hodí při migraci, testování nebo když chceš vyčistit provozní metadata pluginu.
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">
					<?php wp_nonce_field( 'hlavas_terms_reset_sync_log', '_hlavas_reset_sync_nonce' ); ?>
					<p>
						<button type="submit" name="hlavas_terms_reset_sync_log" value="1" class="button">Vymazat datumy synchronizace</button>
					</p>
					<p class="description">
						Smaže interní log posledních synchronizací termínů a formulářů do Fluent Forms. Nemění termíny ani formuláře samotné.
					</p>
				</form>
			</div>

			<div class="hlavas-settings-section">
				<h2>Export zálohy</h2>
				<p>
					Export vytvoří jeden JSON soubor se všemi provozními volbami pluginu a obsahem pluginových databázových tabulek.
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">
					<?php wp_nonce_field( 'hlavas_terms_export_backup', '_hlavas_export_nonce' ); ?>
					<p>
						<button type="submit" name="hlavas_terms_export_backup" value="1" class="button button-primary">Stáhnout backup nastavení a dat</button>
					</p>
				</form>
			</div>

			<div class="hlavas-settings-section">
				<h2>Import zálohy</h2>
				<p>
					Import načte dříve exportovaný backup pluginu. Lze jím obnovit nastavení, typy kvalifikací i termíny.
				</p>

				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">
					<?php wp_nonce_field( 'hlavas_terms_import_backup', '_hlavas_import_nonce' ); ?>
					<p>
						<input type="file" name="hlavas_terms_backup_file" accept=".json,application/json">
					</p>
					<p>
						<label>
							<input type="checkbox" name="hlavas_terms_replace_existing" value="1" checked>
							Při importu nahradit stávající data pluginu
						</label>
					</p>
					<p class="description">
						Pokud je tato volba zapnutá, import smaže aktuální termíny a typy kvalifikací a nahradí je obsahem backupu.
					</p>
					<p>
						<button
							type="submit"
							name="hlavas_terms_import_backup"
							value="1"
							class="button"
							onclick="return confirm('Opravdu importovat backup pluginu? Při nahrazení stávajících dat dojde k přepisu pluginových tabulek.');"
						>
							Importovat backup
						</button>
					</p>
				</form>
			</div>

			<div class="hlavas-settings-section">
				<h2>Rychlé informace</h2>
				<table class="widefat striped hlavas-settings-status-table">
					<tbody>
						<tr>
							<th>Plugin</th>
							<td><?php echo esc_html( $plugin_info['name'] ); ?></td>
						</tr>
						<tr>
							<th>Verze</th>
							<td><?php echo esc_html( $plugin_info['version'] ); ?></td>
						</tr>
						<tr>
							<th>WordPress / PHP</th>
							<td><?php echo esc_html( $plugin_info['current_wp'] ); ?> / <?php echo esc_html( $plugin_info['current_php'] ); ?></td>
						</tr>
						<tr>
							<th>Text domain</th>
							<td><code><?php echo esc_html( $plugin_info['text_domain'] ); ?></code></td>
						</tr>
						<tr>
							<th>E-mail reportů</th>
							<td><?php echo esc_html( $report_email ); ?></td>
						</tr>
						<tr>
							<th>Log soubor</th>
							<td><code><?php echo esc_html( $activity_log_path ); ?></code></td>
						</tr>
						<tr>
							<th>Tabulka termínů</th>
							<td><code><?php echo esc_html( $plugin_info['table'] ); ?></code></td>
						</tr>
						<tr>
							<th>Tabulka typů</th>
							<td><code><?php echo esc_html( $plugin_info['types_table'] ); ?></code></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
