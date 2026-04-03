<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $title */
/** @var array<string, string> $columns */
/** @var array<int, array<string, mixed>> $rows */
?>
<!DOCTYPE html>
<html lang="cs">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?></title>
	<style>
		body {
			font-family: Arial, sans-serif;
			font-size: 12px;
			line-height: 1.45;
			color: #111;
			margin: 18px;
		}
		h1 {
			margin: 0 0 8px;
			font-size: 22px;
		}
		.hlavas-print-meta {
			margin: 0 0 18px;
			color: #555;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			table-layout: auto;
		}
		th,
		td {
			border: 1px solid #bfbfbf;
			padding: 6px 8px;
			vertical-align: top;
			text-align: left;
			word-break: break-word;
		}
		th {
			background: #f1f1f1;
			font-weight: 700;
		}
		.hlavas-print-empty {
			padding: 14px 0;
			color: #666;
		}
		@media print {
			body {
				margin: 10mm;
			}
			thead {
				display: table-header-group;
			}
			tr,
			td,
			th {
				page-break-inside: avoid;
			}
		}
	</style>
</head>
<body>
	<h1><?php echo esc_html( $title ); ?></h1>
	<p class="hlavas-print-meta">
		Generováno: <?php echo esc_html( current_time( 'mysql' ) ); ?>
	</p>

	<?php if ( empty( $rows ) ) : ?>
		<p class="hlavas-print-empty">V tomto výpisu momentálně nejsou žádná data.</p>
	<?php else : ?>
		<table>
			<thead>
				<tr>
					<?php foreach ( $columns as $label ) : ?>
						<th><?php echo esc_html( $label ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<?php foreach ( array_keys( $columns ) as $key ) : ?>
							<td><?php echo esc_html( is_scalar( $row[ $key ] ?? '' ) ? (string) $row[ $key ] : '' ); ?></td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<script>
		window.addEventListener('load', function () {
			window.print();
		});
	</script>
</body>
</html>
