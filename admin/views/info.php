<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array<string, string> $plugin_info */
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Info o HLAVASovi</h1>
	<p>Interní informační stránka pro správce a programátora. Najdete zde základní technické údaje o pluginu, kompatibilitě a provozním nastavení.</p>

	<table class="widefat striped" style="max-width: 980px;">
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
				<th>Projekt / URL</th>
				<td><a href="<?php echo esc_url( $plugin_info['plugin_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $plugin_info['plugin_url'] ); ?></a></td>
			</tr>
			<tr>
				<th>Licence</th>
				<td><?php echo esc_html( $plugin_info['license'] ); ?></td>
			</tr>
			<tr>
				<th>Minimální kompatibilita</th>
				<td>WordPress <?php echo esc_html( $plugin_info['min_wp'] ); ?>+, PHP <?php echo esc_html( $plugin_info['min_php'] ); ?>+</td>
			</tr>
			<tr>
				<th>Aktuální prostředí</th>
				<td>WordPress <?php echo esc_html( $plugin_info['current_wp'] ); ?>, PHP <?php echo esc_html( $plugin_info['current_php'] ); ?></td>
			</tr>
			<tr>
				<th>Databázová tabulka</th>
				<td><code><?php echo esc_html( $plugin_info['table'] ); ?></code></td>
			</tr>
			<tr>
				<th>Napojený Fluent Forms formulář</th>
				<td>ID <strong><?php echo esc_html( $plugin_info['configured_form'] ); ?></strong></td>
			</tr>
			<tr>
				<th>Debug režim</th>
				<td><?php echo esc_html( $plugin_info['debug_mode'] ); ?></td>
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

	<h2>Co plugin dělá</h2>
	<p>Plugin spravuje termíny kurzů a zkoušek, synchronizuje je do Fluent Forms, hlídá kapacity při odeslání formuláře a v administraci zobrazuje přehled obsazenosti.</p>

	<h2>Proč tato stránka existuje</h2>
	<p>Slouží jako rychlý technický rozcestník pro správce webu nebo vývojáře. Když je potřeba dohledat verzi, kompatibilitu, cílový formulář nebo zapnutý debug, vše je na jednom místě.</p>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>" class="button button-primary">Otevřít nastavení pluginu</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>" class="button">Přejít na synchronizaci</a>
	</p>
</div>
