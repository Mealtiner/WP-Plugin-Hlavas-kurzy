<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $message */
/** @var int $selected_form_id */
/** @var array<int, array<string, mixed>> $form_sections */
/** @var array<string, mixed>|null $debug */
/** @var array<string, mixed>|null $sync_result */
/** @var string $activity_log_path */
/** @var array<int, string> $activity_log_lines */
/** @var array<string, mixed> $settings_status */
/** @var array<string, string> $plugin_info */
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Servis / Diagnostika</h1>
	<p class="hlavas-page-intro">
		Technická a servisní stránka pro správce pluginu. Tady jsou backupy, aktivitní log, technická kontrola stavu
		a raw debug výstupy. Rebuild účastníků a oprava legacy entries jsou nově zpět na stránce
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>">Synchronizace s Fluent Forms</a>,
		protože přímo pracují s FF daty.
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

	<?php if ( 'imported' === $message ) : ?>
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
				<h2>Záloha a obnova</h2>
				<div class="hlavas-settings-sync-tools">
					<div class="hlavas-settings-sync-card">
						<h3>Export zálohy</h3>
						<p>Vytvoří jeden JSON soubor se všemi provozními volbami pluginu a obsahem pluginových databázových tabulek.</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics' ) ); ?>">
							<?php wp_nonce_field( 'hlavas_terms_export_backup', '_hlavas_export_nonce' ); ?>
							<p><button type="submit" name="hlavas_terms_export_backup" value="1" class="button button-primary">Stáhnout backup nastavení a dat</button></p>
						</form>
					</div>

					<div class="hlavas-settings-sync-card">
						<h3>Import zálohy</h3>
						<p>Načte dříve exportovaný backup pluginu. Lze jím obnovit nastavení, typy kvalifikací i termíny.</p>
						<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics' ) ); ?>">
							<?php wp_nonce_field( 'hlavas_terms_import_backup', '_hlavas_import_nonce' ); ?>
							<p><input type="file" name="hlavas_terms_backup_file" accept=".json,application/json"></p>
							<p>
								<label>
									<input type="checkbox" name="hlavas_terms_replace_existing" value="1" checked>
									Při importu nahradit stávající data pluginu
								</label>
							</p>
							<p class="description">Pokud je tato volba zapnutá, import smaže aktuální termíny a typy kvalifikací a nahradí je obsahem backupu.</p>
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
				</div>
			</div>

			<div class="hlavas-settings-section">
				<h2>Aktivitní log pluginu</h2>
				<p class="description">
					Log zachycuje důležité akce v pluginu včetně toho, kdo je provedl a kdy.
					Ukládá se jako textový soubor do pluginu: <code><?php echo esc_html( $activity_log_path ); ?></code>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics' ) ); ?>" class="hlavas-report-inline-form">
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

			<div class="hlavas-settings-section" id="hlavas-debug">
				<h2>Raw debug výstup</h2>
				<p class="description">
					Debug je určený hlavně pro programátora nebo správce při řešení problémů s mapováním a strukturou formulářů.
				</p>

				<?php if ( null === $debug ) : ?>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics&debug=1' ) ); ?>" class="button">Zobrazit debug prvního formuláře</a>
					</p>
				<?php else : ?>
					<div class="hlavas-debug-output">
						<pre style="background:#f1f1f1;padding:15px;overflow:auto;max-height:600px;font-size:12px;"><?php echo esc_html( wp_json_encode( $debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $form_sections ) ) : ?>
					<p class="description">
						Rychlý výběr formuláře:
						<?php foreach ( $form_sections as $index => $section ) : ?>
							<?php
							$form_id = (int) ( $section['form_id'] ?? 0 );
							if ( $form_id <= 0 ) {
								continue;
							}
							?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics&debug=1&form_id=' . $form_id . '#hlavas-debug' ) ); ?>">
								#<?php echo esc_html( (string) $form_id ); ?>
							</a><?php echo $index + 1 < count( $form_sections ) ? ', ' : ''; ?>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="hlavas-settings-side">
			<div class="hlavas-settings-section">
				<h2>Technická kontrola stavu</h2>
				<table class="widefat striped hlavas-settings-status-table">
					<tbody>
						<tr>
							<th>Plugin / DB verze</th>
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
						<tr>
							<th>Záznamů v sync logu</th>
							<td><?php echo esc_html( (string) $settings_status['sync_log_count'] ); ?></td>
						</tr>
						<tr>
							<th>WordPress / PHP</th>
							<td><?php echo esc_html( (string) $plugin_info['current_wp'] ); ?> / <?php echo esc_html( (string) $plugin_info['current_php'] ); ?></td>
						</tr>
					</tbody>
				</table>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-diagnostics' ) ); ?>" style="margin-top:16px;">
					<?php wp_nonce_field( 'hlavas_terms_reset_sync_log', '_hlavas_reset_sync_nonce' ); ?>
					<p>
						<button type="submit" name="hlavas_terms_reset_sync_log" value="1" class="button">Vymazat datumy synchronizace</button>
					</p>
					<p class="description">
						Smaže interní log posledních synchronizací termínů a formulářů do Fluent Forms. Nemění termíny ani formuláře samotné.
					</p>
				</form>
			</div>
		</div>
	</div>
</div>
