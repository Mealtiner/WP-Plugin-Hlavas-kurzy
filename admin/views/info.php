<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array<string, string> $plugin_info */
/** @var array<int, array<string, mixed>> $form_registry */

$grouped_forms = [];

foreach ( $form_registry as $form_item ) {
	$form_id = (int) ( $form_item['form_id'] ?? 0 );

	if ( $form_id <= 0 ) {
		continue;
	}

	if ( ! isset( $grouped_forms[ $form_id ] ) ) {
		$grouped_forms[ $form_id ] = [
			'form_id'    => $form_id,
			'form_title' => (string) ( $form_item['form_title'] ?? '' ),
			'exists'     => ! empty( $form_item['exists'] ),
			'status'     => (string) ( $form_item['status'] ?? '' ),
			'usages'     => [],
		];
	}

	$grouped_forms[ $form_id ]['usages'][] = (string) ( $form_item['usage'] ?? '' );
}
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
				<td><a href="<?php echo esc_url( $plugin_info['plugin_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $plugin_info['plugin_url'] ); ?></a> <span class="description">nasazený web</span></td>
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
				<th>Tabulka typů kvalifikací</th>
				<td><code><?php echo esc_html( $plugin_info['types_table'] ); ?></code></td>
			</tr>
			<tr>
				<th>Propojené Fluent Forms formuláře</th>
				<td>
					<?php if ( empty( $grouped_forms ) ) : ?>
						<span class="hlavas-subline">Žádný formulář zatím není propojen.</span>
					<?php else : ?>
						<ul style="margin: 0; padding-left: 18px;">
							<?php foreach ( $grouped_forms as $form_id => $grouped_form ) : ?>
								<?php
								$form_url = add_query_arg(
									[
										'page'      => 'fluent_forms',
										'form_id'   => $form_id,
										'route'     => 'settings',
										'sub_route' => 'form_settings',
									],
									admin_url( 'admin.php' )
								) . '#basic_settings';
								?>
								<li>
									<a href="<?php echo esc_url( $form_url ); ?>">
										ID <?php echo esc_html( (string) $form_id ); ?>
									</a>
									<?php if ( ! empty( $grouped_form['form_title'] ) ) : ?>
										<strong><?php echo esc_html( ' - ' . (string) $grouped_form['form_title'] ); ?></strong>
									<?php endif; ?>
									<?php if ( ! empty( $grouped_form['status'] ) ) : ?>
										<span class="hlavas-subline">(stav: <?php echo esc_html( (string) $grouped_form['status'] ); ?>)</span>
									<?php endif; ?>
									<div class="hlavas-subline">
										Využití: <?php echo esc_html( implode( ', ', array_unique( array_filter( $grouped_form['usages'] ) ) ) ); ?>
									</div>
									<?php if ( empty( $grouped_form['exists'] ) ) : ?>
										<div class="hlavas-subline hlavas-status-no">Formulář v databázi Fluent Forms nebyl nalezen.</div>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</td>
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
	<p>Slouží jako rychlý technický rozcestník pro správce webu nebo vývojáře. Když je potřeba dohledat verzi, kompatibilitu, propojené formuláře nebo zapnutý debug, vše je na jednom místě.</p>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>" class="button button-primary">Otevřít nastavení pluginu</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>" class="button">Přejít na synchronizaci</a>
	</p>
</div>
