<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $preview */
/** @var array|null $debug */
/** @var array|null $sync_result */
?>
<div class="wrap hlavas-terms-wrap">
    <h1>Synchronizace do Fluent Forms</h1>

    <?php if ( $sync_result ) : ?>
        <div class="notice notice-<?php echo $sync_result['success'] ? 'success' : 'error'; ?> is-dismissible">
            <p><strong><?php echo esc_html( $sync_result['message'] ); ?></strong></p>
            <?php if ( ! empty( $sync_result['details'] ) ) : ?>
                <ul>
                    <?php foreach ( $sync_result['details'] as $d ) : ?>
                        <li><?php echo esc_html( $d ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="hlavas-sync-info">
        <p>
            Formulář ID: <strong><?php echo esc_html( hlavas_terms_get_form_id() ); ?></strong>
            &nbsp;|&nbsp;
            Formulář nalezen: <strong><?php echo $preview['form_found'] ? '✓ Ano' : '✗ NE'; ?></strong>
            &nbsp;|&nbsp;
            Debug režim: <strong><?php echo hlavas_terms_is_debug_enabled() ? 'Zapnuto' : 'Vypnuto'; ?></strong>
        </p>
        <p class="description">
            Cílový formulář i debug režim můžete upravit v
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>">nastavení pluginu</a>.
        </p>

        <?php if ( ! $preview['form_found'] ) : ?>
            <div class="notice notice-error">
                <p>Formulář s ID <?php echo esc_html( hlavas_terms_get_form_id() ); ?> nebyl nalezen. Zkontrolujte, zda existuje ve Fluent Forms.</p>
            </div>
        <?php else : ?>

        <h2>Nalezená pole</h2>
        <table class="widefat fixed" style="max-width: 500px;">
            <tbody>
                <tr>
                    <td><code>termin_kurz</code></td>
                    <td>
                        <?php echo ! empty( $preview['fields_found']['termin_kurz'] )
                            ? '<span class="hlavas-status-yes">✓ Nalezeno</span>'
                            : '<span class="hlavas-status-no">✗ Nenalezeno</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>termin_zkouska</code></td>
                    <td>
                        <?php echo ! empty( $preview['fields_found']['termin_zkouska'] )
                            ? '<span class="hlavas-status-yes">✓ Nalezeno</span>'
                            : '<span class="hlavas-status-no">✗ Nenalezeno</span>'; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Preview: Kurzy -->
        <h2>Náhled: Termíny kurzů (<?php echo count( $preview['kurz'] ); ?>)</h2>
        <?php if ( empty( $preview['kurz'] ) ) : ?>
            <p><em>Žádné aktivní budoucí kurzy.</em></p>
        <?php else : ?>
            <table class="widefat fixed striped" style="max-width: 700px;">
                <thead>
                    <tr>
                        <th>Value (term_key)</th>
                        <th>Label</th>
                        <th>Kapacita</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $preview['kurz'] as $opt ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $opt['term_key'] ); ?></code></td>
                        <td><?php echo esc_html( $opt['label'] ); ?></td>
                        <td><?php echo esc_html( $opt['capacity'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Preview: Zkoušky -->
        <h2>Náhled: Termíny zkoušek (<?php echo count( $preview['zkouska'] ); ?>)</h2>
        <?php if ( empty( $preview['zkouska'] ) ) : ?>
            <p><em>Žádné aktivní budoucí zkoušky.</em></p>
        <?php else : ?>
            <table class="widefat fixed striped" style="max-width: 700px;">
                <thead>
                    <tr>
                        <th>Value (term_key)</th>
                        <th>Label</th>
                        <th>Kapacita</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $preview['zkouska'] as $opt ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $opt['term_key'] ); ?></code></td>
                        <td><?php echo esc_html( $opt['label'] ); ?></td>
                        <td><?php echo esc_html( $opt['capacity'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Execute Form -->
        <h2>Provést synchronizaci</h2>
        <form method="post">
            <?php wp_nonce_field( 'hlavas_sync', '_hlavas_sync_nonce' ); ?>

            <table class="form-table" style="max-width: 500px;">
                <tr>
                    <th><label for="value_mode">Režim hodnot (value)</label></th>
                    <td>
                        <select name="value_mode" id="value_mode">
                            <option value="term_key">Nový režim (term_key)</option>
                            <option value="label">Legacy režim (label = value)</option>
                        </select>
                        <p class="description">
                            <strong>Nový režim:</strong> value = interní klíč (doporučeno pro nové instalace).<br>
                            <strong>Legacy režim:</strong> value = text labelu (kompatibilní se stávajícími záznamy).
                        </p>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="hlavas_sync_execute" value="1"
                        class="button button-primary button-hero"
                        onclick="return confirm('Opravdu provést synchronizaci? Přepíše options ve Fluent Forms.');">
                    🔄 Provést synchronizaci
                </button>
            </p>
        </form>

        <?php endif; // form_found ?>
    </div>

    <!-- Debug section -->
    <hr>
    <h2>Debug: Interní struktura formuláře</h2>
    <?php if ( $debug === null ) : ?>
        <p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync&debug=1' ) ); ?>"
               class="button">Zobrazit debug výstup</a>
        </p>
    <?php else : ?>
        <div class="hlavas-debug-output">
            <pre style="background: #f1f1f1; padding: 15px; overflow: auto; max-height: 600px; font-size: 12px;"><?php
                echo esc_html( json_encode( $debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
            ?></pre>
        </div>
    <?php endif; ?>
</div>
