<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $report */
?>
<div class="wrap hlavas-terms-wrap">
    <h1>Obsazenost termínů</h1>
    <p>Přehled aktuálního stavu přihlášek a zbývající kapacity pro aktivní termíny.</p>

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
            <?php
            $kurzy = array_filter( $report, fn( $r ) => $r['type'] === 'kurz' );
            if ( empty( $kurzy ) ) :
            ?>
                <tr><td colspan="6"><em>Žádné aktivní kurzy.</em></td></tr>
            <?php else :
                foreach ( $kurzy as $r ) :
                    $pct = $r['capacity'] > 0 ? round( $r['enrolled'] / $r['capacity'] * 100 ) : 0;
            ?>
            <tr>
                <td class="column-qualification">
                    <span class="hlavas-qualification-main"><?php echo esc_html( $r['qualification'] ); ?></span>
                </td>
                <td class="column-term">
                    <strong><?php echo esc_html( $r['title'] ); ?></strong>
                    <div class="hlavas-subline"><?php echo esc_html( $r['label'] ); ?></div>
                    <code class="hlavas-subline-code"><?php echo esc_html( $r['term_key'] ); ?></code>
                </td>
                <td class="column-capacity"><?php echo esc_html( $r['capacity'] ); ?></td>
                <td class="column-capacity"><?php echo esc_html( $r['enrolled'] ); ?></td>
                <td>
                    <strong class="<?php echo $r['remaining'] <= 0 ? 'hlavas-status-no' : ''; ?>">
                        <?php echo esc_html( $r['remaining'] ); ?>
                    </strong>
                </td>
                <td class="column-status">
                    <div class="hlavas-capacity-bar hlavas-capacity-bar-wide">
                        <div class="hlavas-capacity-fill <?php
                            echo $pct >= 100 ? 'full' : ( $pct >= 75 ? 'high' : '' );
                        ?>" style="width: <?php echo min( 100, $pct ); ?>%;"></div>
                    </div>
                    <small class="hlavas-capacity-percent"><?php echo $pct; ?>%</small>
                </td>
            </tr>
            <?php endforeach; endif; ?>
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
            <?php
            $zkousky = array_filter( $report, fn( $r ) => $r['type'] === 'zkouska' );
            if ( empty( $zkousky ) ) :
            ?>
                <tr><td colspan="6"><em>Žádné aktivní zkoušky.</em></td></tr>
            <?php else :
                foreach ( $zkousky as $r ) :
                    $pct = $r['capacity'] > 0 ? round( $r['enrolled'] / $r['capacity'] * 100 ) : 0;
            ?>
            <tr>
                <td class="column-qualification">
                    <span class="hlavas-qualification-main"><?php echo esc_html( $r['qualification'] ); ?></span>
                </td>
                <td class="column-term">
                    <strong><?php echo esc_html( $r['title'] ); ?></strong>
                    <div class="hlavas-subline"><?php echo esc_html( $r['label'] ); ?></div>
                    <code class="hlavas-subline-code"><?php echo esc_html( $r['term_key'] ); ?></code>
                </td>
                <td class="column-capacity"><?php echo esc_html( $r['capacity'] ); ?></td>
                <td class="column-capacity"><?php echo esc_html( $r['enrolled'] ); ?></td>
                <td>
                    <strong class="<?php echo $r['remaining'] <= 0 ? 'hlavas-status-no' : ''; ?>">
                        <?php echo esc_html( $r['remaining'] ); ?>
                    </strong>
                </td>
                <td class="column-status">
                    <div class="hlavas-capacity-bar hlavas-capacity-bar-wide">
                        <div class="hlavas-capacity-fill <?php
                            echo $pct >= 100 ? 'full' : ( $pct >= 75 ? 'high' : '' );
                        ?>" style="width: <?php echo min( 100, $pct ); ?>%;"></div>
                    </div>
                    <small class="hlavas-capacity-percent"><?php echo $pct; ?>%</small>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <?php endif; ?>

    <hr>
    <p class="description">
        Počet přihlášených se počítá z odeslaných formulářů (Fluent Forms entries) pro formulář
        ID <?php echo esc_html( hlavas_terms_get_form_id() ); ?>. Smazané (trashed) záznamy se nepočítají.
    </p>
</div>
