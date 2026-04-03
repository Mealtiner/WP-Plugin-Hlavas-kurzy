<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array<int, array<string, mixed>> $report */
/** @var string $report_message */

$report_args = [
	'page' => 'hlavas-terms-availability',
];
$download_csv_url = wp_nonce_url(
	add_query_arg(
		array_merge(
			$report_args,
			[
				'hlavas_report_action' => 'download',
				'report_format'        => 'csv',
			]
		),
		admin_url( 'admin.php' )
	),
	'hlavas_report_action',
	'_hlavas_report_nonce'
);
$download_xls_url = wp_nonce_url(
	add_query_arg(
		array_merge(
			$report_args,
			[
				'hlavas_report_action' => 'download',
				'report_format'        => 'xls',
			]
		),
		admin_url( 'admin.php' )
	),
	'hlavas_report_action',
	'_hlavas_report_nonce'
);
$print_url = wp_nonce_url(
	add_query_arg(
		array_merge(
			$report_args,
			[
				'hlavas_report_action' => 'print',
			]
		),
		admin_url( 'admin.php' )
	),
	'hlavas_report_action',
	'_hlavas_report_nonce'
);
$kurzy   = array_values( array_filter( $report, static fn( $item ) => 'kurz' === ( $item['type'] ?? '' ) ) );
$zkousky = array_values( array_filter( $report, static fn( $item ) => 'zkouska' === ( $item['type'] ?? '' ) ) );
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Obsazenost termínů</h1>
	<p>Přehled aktuálního stavu přihlášek a zbývající kapacity pro aktivní termíny.</p>

	<?php if ( 'emailed' === $report_message ) : ?>
		<div class="notice notice-success is-dismissible"><p>Report byl odeslán na e-mail <?php echo esc_html( hlavas_terms_get_report_email() ); ?>.</p></div>
	<?php elseif ( 'email_failed' === $report_message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Report se nepodařilo odeslat. Zkontroluj e-mail pro reporty v nastavení pluginu.</p></div>
	<?php elseif ( 'report_failed' === $report_message ) : ?>
		<div class="notice notice-error is-dismissible"><p>Report se nepodařilo vygenerovat.</p></div>
	<?php endif; ?>

	<div class="hlavas-report-actions">
		<a href="<?php echo esc_url( $download_csv_url ); ?>" class="button">Download CSV</a>
		<a href="<?php echo esc_url( $download_xls_url ); ?>" class="button">Download XLS</a>
		<a href="<?php echo esc_url( $print_url ); ?>" class="button" target="_blank" rel="noopener noreferrer">Tisk</a>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="hlavas-report-inline-form">
			<?php wp_nonce_field( 'hlavas_report_action', '_hlavas_report_nonce' ); ?>
			<input type="hidden" name="page" value="hlavas-terms-availability">
			<input type="hidden" name="hlavas_report_action" value="email">
			<button type="submit" class="button">Odeslat data na e-mail</button>
		</form>
		<span class="hlavas-report-actions-note">Odeslání proběhne na adresu <?php echo esc_html( hlavas_terms_get_report_email() ); ?>.</span>
	</div>

	<?php if ( empty( $report ) ) : ?>
		<div class="notice notice-info"><p>Žádné aktivní termíny k zobrazení.</p></div>
	<?php else : ?>
		<h2>Kurzy</h2>
		<table class="widefat striped hlavas-availability-table">
			<thead>
				<tr>
					<th class="column-qualification">Kvalifikace</th>
					<th class="column-term">Termín</th>
					<th style="width:90px;">Kapacita</th>
					<th style="width:90px;">Přihlášeno</th>
					<th style="width:90px;">Zbývá</th>
					<th class="column-status">Obsazení</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $kurzy ) ) : ?>
					<tr><td colspan="6"><em>Žádné aktivní kurzy.</em></td></tr>
				<?php else : ?>
					<?php foreach ( $kurzy as $item ) : ?>
						<?php $pct = (int) ( ( ! empty( $item['capacity'] ) && (int) $item['capacity'] > 0 ) ? round( (int) $item['enrolled'] / (int) $item['capacity'] * 100 ) : 0 ); ?>
						<tr>
							<td class="column-qualification">
								<span class="hlavas-qualification-main"><?php echo esc_html( (string) $item['qualification'] ); ?></span>
							</td>
							<td class="column-term">
								<strong><?php echo esc_html( (string) $item['title'] ); ?></strong>
								<div class="hlavas-subline"><?php echo esc_html( (string) $item['label'] ); ?></div>
								<code class="hlavas-subline-code"><?php echo esc_html( (string) $item['term_key'] ); ?></code>
							</td>
							<td class="column-capacity"><?php echo esc_html( (string) $item['capacity'] ); ?></td>
							<td class="column-capacity"><?php echo esc_html( (string) $item['enrolled'] ); ?></td>
							<td>
								<strong class="<?php echo (int) $item['remaining'] <= 0 ? 'hlavas-status-no' : ''; ?>">
									<?php echo esc_html( (string) $item['remaining'] ); ?>
								</strong>
							</td>
							<td class="column-status">
								<div class="hlavas-capacity-bar hlavas-capacity-bar-wide">
									<div class="hlavas-capacity-fill <?php echo $pct >= 100 ? 'full' : ( $pct >= 75 ? 'high' : '' ); ?>" style="width: <?php echo esc_attr( (string) min( 100, $pct ) ); ?>%;"></div>
								</div>
								<small class="hlavas-capacity-percent"><?php echo esc_html( (string) $pct ); ?>%</small>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<h2>Zkoušky</h2>
		<table class="widefat striped hlavas-availability-table">
			<thead>
				<tr>
					<th class="column-qualification">Kvalifikace</th>
					<th class="column-term">Termín</th>
					<th style="width:90px;">Kapacita</th>
					<th style="width:90px;">Přihlášeno</th>
					<th style="width:90px;">Zbývá</th>
					<th class="column-status">Obsazení</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $zkousky ) ) : ?>
					<tr><td colspan="6"><em>Žádné aktivní zkoušky.</em></td></tr>
				<?php else : ?>
					<?php foreach ( $zkousky as $item ) : ?>
						<?php $pct = (int) ( ( ! empty( $item['capacity'] ) && (int) $item['capacity'] > 0 ) ? round( (int) $item['enrolled'] / (int) $item['capacity'] * 100 ) : 0 ); ?>
						<tr>
							<td class="column-qualification">
								<span class="hlavas-qualification-main"><?php echo esc_html( (string) $item['qualification'] ); ?></span>
							</td>
							<td class="column-term">
								<strong><?php echo esc_html( (string) $item['title'] ); ?></strong>
								<div class="hlavas-subline"><?php echo esc_html( (string) $item['label'] ); ?></div>
								<code class="hlavas-subline-code"><?php echo esc_html( (string) $item['term_key'] ); ?></code>
							</td>
							<td class="column-capacity"><?php echo esc_html( (string) $item['capacity'] ); ?></td>
							<td class="column-capacity"><?php echo esc_html( (string) $item['enrolled'] ); ?></td>
							<td>
								<strong class="<?php echo (int) $item['remaining'] <= 0 ? 'hlavas-status-no' : ''; ?>">
									<?php echo esc_html( (string) $item['remaining'] ); ?>
								</strong>
							</td>
							<td class="column-status">
								<div class="hlavas-capacity-bar hlavas-capacity-bar-wide">
									<div class="hlavas-capacity-fill <?php echo $pct >= 100 ? 'full' : ( $pct >= 75 ? 'high' : '' ); ?>" style="width: <?php echo esc_attr( (string) min( 100, $pct ) ); ?>%;"></div>
								</div>
								<small class="hlavas-capacity-percent"><?php echo esc_html( (string) $pct ); ?>%</small>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<hr>
	<p class="description">
		Počet přihlášených se počítá z odeslaných formulářů (Fluent Forms entries) pro navázané formuláře.
		Smazané záznamy se do obsazenosti nezapočítávají.
	</p>
</div>
