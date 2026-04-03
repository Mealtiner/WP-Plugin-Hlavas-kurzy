<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var string $message */
/** @var int $form_id */
/** @var bool $debug_mode */
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Nastavení pluginu</h1>
	<p>Správa propojení pluginu s Fluent Forms a základních provozních voleb.</p>

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>Nastavení bylo uloženo.</p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">
		<?php wp_nonce_field( 'hlavas_terms_settings', '_hlavas_settings_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="hlavas_terms_form_id">Fluent Forms formulář ID</label></th>
					<td>
						<input
							type="number"
							min="1"
							step="1"
							class="regular-text"
							name="hlavas_terms_form_id"
							id="hlavas_terms_form_id"
							value="<?php echo esc_attr( $form_id ); ?>"
						>
						<p class="description">
							Zadejte ID konkrétního Fluent Forms formuláře, ke kterému se má plugin připojit.
							Používá se pro synchronizaci termínů, validaci kapacity i výpočet obsazenosti.
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
							Po zapnutí se bude automaticky zobrazovat interní debug výstup na stránce synchronizace.
							Hodí se při vývoji nebo při ladění struktury formuláře ve Fluent Forms.
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
