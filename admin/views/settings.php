<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $message */
/** @var int $form_id */
/** @var bool $debug_mode */
/** @var string $sync_value_mode */
/** @var string $report_email */
/** @var array<int, object> $types */
/** @var array<string, mixed> $settings_status */
/** @var array<int, array<string, mixed>> $form_registry */
/** @var array<int, array<string, mixed>> $form_mapping_sections */
/** @var array<int, array<string, string>> $field_map */
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Nastavení pluginu</h1>
	<p class="hlavas-page-intro">
		Tady patří jen to, co plugin potřebuje mít správně nastavené: fallback formulář, výchozí režim synchronizace,
		debug režim, reportovací e-mail a případné ruční mapování polí. Synchronizaci formulářů řeš na stránce
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>">Synchronizace</a>,
		servisní nástroje a backupy na stránce
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics' ) ); ?>">Servis / Diagnostika</a>.
	</p>

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Nastavení bylo uloženo.</p></div>
	<?php endif; ?>

	<div class="hlavas-settings-grid">
		<div class="hlavas-settings-main">
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
										Tento režim se předvyplní na stránce synchronizace a používá se i při rychlé synchronizaci z detailu termínu.
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
										Podrobný raw debug formulářů je pak dostupný na stránce Servis / Diagnostika.
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
										Na tuto adresu se odesílají CSV/XLS reporty z přehledů termínů, obsazenosti a účastníků.
									</p>
								</td>
							</tr>
						</tbody>
					</table>

					<details class="hlavas-settings-advanced">
						<summary>Rozšířené nastavení: ruční mapování polí mezi HLAVAS a Fluent Forms</summary>
						<div class="hlavas-settings-advanced-body">
							<p class="description">
								Použij jen tehdy, když má formulář ve Fluent Forms jiná interní jména polí, než plugin očekává automaticky.
								Tahle vazba se potom použije při synchronizaci, diagnostice i načítání účastníků.
							</p>

							<?php if ( empty( $form_mapping_sections ) ) : ?>
								<p class="description">Zatím není k dispozici žádný formulář pro mapování polí.</p>
							<?php else : ?>
								<?php foreach ( $form_mapping_sections as $section ) : ?>
									<?php
									$section_form_id = (int) ( $section['form_id'] ?? 0 );
									$field_matches   = is_array( $section['field_matches'] ?? null ) ? $section['field_matches'] : [];
									$field_catalog   = is_array( $section['field_catalog'] ?? null ) ? $section['field_catalog'] : [];
									$datalist_id     = 'hlavas-map-suggestions-' . $section_form_id;
									$manual_form_map = $field_map[ $section_form_id ] ?? [];
									?>
									<div class="hlavas-settings-map-card">
										<h3>
											Formulář #<?php echo esc_html( (string) $section_form_id ); ?>
											<?php if ( ! empty( $section['form_title'] ) ) : ?>
												<span class="hlavas-subline"><?php echo esc_html( (string) $section['form_title'] ); ?></span>
											<?php endif; ?>
										</h3>

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
		</div>

		<div class="hlavas-settings-side">
			<div class="hlavas-settings-section">
				<h2>Stručná kontrola stavu</h2>
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
							<th>Fluent Forms formuláře</th>
							<td><?php echo ! empty( $settings_status['fluent_forms_table_exists'] ) ? '<span class="hlavas-status-yes">Ano</span>' : '<span class="hlavas-status-no">Ne</span>'; ?></td>
						</tr>
						<tr>
							<th>Fluent Forms entries</th>
							<td><?php echo ! empty( $settings_status['fluent_subs_table_exists'] ) ? '<span class="hlavas-status-yes">Ano</span>' : '<span class="hlavas-status-no">Ne</span>'; ?></td>
						</tr>
						<tr>
							<th>Počet typů / termínů</th>
							<td><?php echo esc_html( (string) $settings_status['types_count'] ); ?> / <?php echo esc_html( (string) $settings_status['terms_count'] ); ?></td>
						</tr>
					</tbody>
				</table>
				<p class="description">
					Podrobnější servisní kontrolu, rebuildy, backupy a log pluginu najdeš na stránce
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics' ) ); ?>">Servis / Diagnostika</a>.
				</p>
			</div>

			<div class="hlavas-settings-section">
				<h2>Napojené Fluent Forms</h2>
				<p class="description">
					Každý typ kvalifikace může mít vlastní Form ID pro kurz i zkoušku. Výchozí formulář níže slouží jen jako fallback.
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
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-types' ) ); ?>">Typech kurzů</a>.
				</p>
			</div>
		</div>
	</div>
</div>
