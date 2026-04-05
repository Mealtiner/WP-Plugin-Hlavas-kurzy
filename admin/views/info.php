<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array<string, string> $plugin_info */
/** @var array<string, mixed> $settings_status */

$release_log         = [];
$release_log_path    = __DIR__ . '/partials/release-log.php';
$manual_partial_path = __DIR__ . '/partials/help-manual.php';
$faq_partial_path    = __DIR__ . '/partials/help-faq.php';

if ( file_exists( $release_log_path ) ) {
	$loaded_release_log = require $release_log_path;
	if ( is_array( $loaded_release_log ) ) {
		$release_log = $loaded_release_log;
	}
}
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Nápověda / O pluginu</h1>
	<p class="hlavas-page-intro">
		Centralizovaná dokumentace pluginu pro správce a programátora. Níže najdeš rychlý start, podrobný manuál,
		nejčastější otázky, technické informace i průběžně vedený changelog verzí.
	</p>

	<nav class="hlavas-section-nav">
		<a href="#hlavas-info-start">Rychlý start</a>
		<a href="#hlavas-info-tech">Technické informace</a>
		<a href="#hlavas-info-manual">Dokumentace</a>
		<a href="#hlavas-info-faq">FAQ</a>
		<a href="#hlavas-info-changelog">Changelog</a>
	</nav>

	<div class="hlavas-docs-section" id="hlavas-info-start">
		<h2>Rychlý start</h2>
		<div class="hlavas-docs-grid">
			<div class="hlavas-doc-card">
				<h3>1. Nastav typy a formuláře</h3>
				<p>
					V menu <strong>Typy kurzů</strong> přiřaď ke každé kvalifikaci správné Form ID pro kurz a zkoušku.
					V <strong>Nastavení</strong> nech vyplněný jen fallback formulář pro starší nebo smíšené scénáře.
				</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>2. Založ nebo importuj termíny</h3>
				<p>
					Termíny můžeš vytvořit ručně přes <strong>Přidat termín</strong>, nebo je při prvním nasazení načíst
					z existujícího FF formuláře přes stránku <strong>Synchronizace s Fluent Forms</strong>.
				</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>3. Proveď synchronizaci do formulářů</h3>
				<p>
					Na stránce <strong>Synchronizace</strong> zkontroluj nalezená pole, náhled termínů a spusť export
					z HLAVAS do Fluent Forms.
				</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>4. Sleduj kapacity a účastníky</h3>
				<p>
					Stránky <strong>Obsazenost a kapacita</strong> a <strong>Účastníci</strong> vycházejí z Fluent Forms entries,
					ručního párování a kapacit vedených v pluginu.
				</p>
			</div>
		</div>
	</div>

	<div class="hlavas-docs-section" id="hlavas-info-tech">
		<h2>Technické informace</h2>
		<div class="hlavas-doc-card hlavas-doc-card-wide">
			<table class="widefat striped hlavas-info-tech-table">
				<tbody>
					<tr>
						<th style="width: 240px;">Název pluginu</th>
						<td><?php echo esc_html( $plugin_info['name'] ); ?></td>
					</tr>
					<tr>
						<th>Verze</th>
						<td><?php echo esc_html( $plugin_info['version'] ); ?></td>
					</tr>
					<tr>
						<th>Slug / text domain</th>
						<td><code><?php echo esc_html( $plugin_info['slug'] ); ?></code> / <code><?php echo esc_html( $plugin_info['text_domain'] ); ?></code></td>
					</tr>
					<tr>
						<th>Autor</th>
						<td><a href="<?php echo esc_url( $plugin_info['author_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $plugin_info['author'] ); ?></a></td>
					</tr>
					<tr>
						<th>E-mail autora</th>
						<td><a href="mailto:<?php echo esc_attr( $plugin_info['author_email'] ); ?>"><?php echo esc_html( $plugin_info['author_email'] ); ?></a></td>
					</tr>
					<tr>
						<th>Zadavatel / organizace</th>
						<td><?php echo esc_html( $plugin_info['organization'] ); ?></td>
					</tr>
					<tr>
						<th>E-mail organizace</th>
						<td><a href="mailto:<?php echo esc_attr( $plugin_info['organization_email'] ); ?>"><?php echo esc_html( $plugin_info['organization_email'] ); ?></a></td>
					</tr>
					<tr>
						<th>Projekt / URL</th>
						<td><a href="<?php echo esc_url( $plugin_info['plugin_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $plugin_info['plugin_url'] ); ?></a></td>
					</tr>
					<tr>
						<th>Licence</th>
						<td><?php echo esc_html( $plugin_info['license'] ); ?></td>
					</tr>
					<tr>
						<th>Aktuální prostředí</th>
						<td>WordPress <?php echo esc_html( $plugin_info['current_wp'] ); ?>, PHP <?php echo esc_html( $plugin_info['current_php'] ); ?></td>
					</tr>
					<tr>
						<th>Minimální kompatibilita</th>
						<td>WordPress <?php echo esc_html( $plugin_info['min_wp'] ); ?>+, PHP <?php echo esc_html( $plugin_info['min_php'] ); ?>+</td>
					</tr>
					<tr>
						<th>Databázové tabulky</th>
						<td><code><?php echo esc_html( $plugin_info['table'] ); ?></code><br><code><?php echo esc_html( $plugin_info['types_table'] ); ?></code></td>
					</tr>
					<tr>
						<th>Stav tabulek a FF</th>
						<td>
							Termíny: <?php echo ! empty( $settings_status['terms_table_exists'] ) ? 'Ano' : 'Ne'; ?>,
							Typy: <?php echo ! empty( $settings_status['types_table_exists'] ) ? 'Ano' : 'Ne'; ?>,
							FF formuláře: <?php echo ! empty( $settings_status['fluent_forms_table_exists'] ) ? 'Ano' : 'Ne'; ?>,
							FF entries: <?php echo ! empty( $settings_status['fluent_subs_table_exists'] ) ? 'Ano' : 'Ne'; ?>
						</td>
					</tr>
					<tr>
						<th>Plugin file</th>
						<td><code><?php echo esc_html( $plugin_info['plugin_file'] ); ?></code></td>
					</tr>
					<tr>
						<th>Plugin directory</th>
						<td><code><?php echo esc_html( $plugin_info['plugin_directory'] ); ?></code></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<?php if ( file_exists( $manual_partial_path ) ) : ?>
		<?php require $manual_partial_path; ?>
	<?php endif; ?>

	<?php if ( file_exists( $faq_partial_path ) ) : ?>
		<?php require $faq_partial_path; ?>
	<?php endif; ?>

	<div class="hlavas-docs-section" id="hlavas-info-changelog">
		<h2>Changelog a release log</h2>
		<p class="hlavas-page-intro">
			Přehled níže shrnuje hlavní změny v jednotlivých verzích pluginu a pomáhá rychle dohledat,
			kdy se která úprava, oprava nebo rozšíření do pluginu dostaly.
		</p>

		<div class="hlavas-docs-grid">
			<?php foreach ( $release_log as $release ) : ?>
				<div class="hlavas-doc-card">
					<h3>
						<?php echo esc_html( (string) ( $release['version'] ?? '' ) ); ?>
						<?php if ( ! empty( $release['date'] ) ) : ?>
							<span class="hlavas-subline"><?php echo esc_html( (string) $release['date'] ); ?></span>
						<?php endif; ?>
					</h3>
					<?php if ( ! empty( $release['summary'] ) ) : ?>
						<p style="margin-bottom:12px;"><?php echo esc_html( (string) $release['summary'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $release['items'] ) && is_array( $release['items'] ) ) : ?>
						<ul class="hlavas-flat-list">
							<?php foreach ( $release['items'] as $release_item ) : ?>
								<li><?php echo esc_html( (string) $release_item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
