<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $message */
/** @var int $form_id */
/** @var bool $debug_mode */
/** @var string $sync_value_mode */
/** @var string $report_email */
/** @var array<string, string> $plugin_info */
/** @var array<int, object> $types */
/** @var array<string, mixed> $settings_status */
/** @var array<int, array<string, mixed>> $form_registry */
/** @var array<string, string> $sync_log */
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Nastavení pluginu</h1>
	<p>
		Centrální správa provozních voleb pluginu, kontroly napojení na Fluent Forms a servisních nástrojů.
		Na jednom místě je tu fallback formulář, režim synchronizace, kontrola všech přiřazených Form ID i záloha dat pluginu.
	</p>

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Nastavení bylo uloženo.</p></div>
	<?php elseif ( 'imported' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Záloha pluginu byla úspěšně importována.</p></div>
	<?php elseif ( 'import_missing_file' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Nebyl vybrán žádný soubor k importu.</p></div>
	<?php elseif ( 'import_invalid_file' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Importovaný soubor není platná záloha pluginu.</p></div>
	<?php elseif ( 'sync_log_reset' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Datumy synchronizace byly vymazány.</p></div>
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
		</div>

		<div class="hlavas-settings-side">
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
						Smaže interní log posledních synchronizací termínů do Fluent Forms. Nemění termíny ani formuláře samotné.
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
