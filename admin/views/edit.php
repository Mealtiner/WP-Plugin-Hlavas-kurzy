<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var object|null $term */
/** @var int $term_id */
/** @var string $error */
/** @var string $message */
/** @var array<int, object> $qualification_types */

$is_edit = $term !== null;
$page_title = $is_edit ? 'Upravit termín' : 'Přidat nový termín';

// Defaults for new term
$defaults = (object) [
    'id'                    => 0,
    'term_type'             => 'kurz',
    'term_key'              => '',
    'qualification_type_id' => 0,
    'title'                 => '',
    'label'                 => '',
    'date_start'            => '',
    'date_end'              => '',
    'enrollment_deadline'   => '',
    'capacity'              => 16,
    'is_visible'            => 1,
    'is_active'             => 1,
    'is_archived'           => 0,
    'sort_order'            => 0,
    'notes'                 => '',
];
$t = $term ?? $defaults;
?>
<div class="wrap hlavas-terms-wrap">
    <h1><?php echo esc_html( $page_title ); ?></h1>

    <?php if ( $message === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible"><p>Termín byl uložen.</p></div>
    <?php endif; ?>
    <?php if ( $error === 'missing_fields' ) : ?>
        <div class="notice notice-error"><p>Vyplňte prosím všechna povinná pole (datum od).</p></div>
    <?php elseif ( $error === 'duplicate_key' ) : ?>
        <div class="notice notice-error"><p>Termín s tímto klíčem již existuje. Zvolte jiný klíč.</p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="hlavas-term-form">
        <?php wp_nonce_field( 'hlavas_term_save', '_hlavas_nonce' ); ?>
        <input type="hidden" name="term_id" value="<?php echo esc_attr( $t->id ); ?>">

        <table class="form-table">
            <tr>
                <th><label for="term_type">Typ *</label></th>
                <td>
                    <select name="term_type" id="term_type" required>
                        <option value="kurz" <?php selected( $t->term_type, 'kurz' ); ?>>Kurz</option>
                        <option value="zkouska" <?php selected( $t->term_type, 'zkouska' ); ?>>Zkouška</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="title">Administrativní název</label></th>
                <td>
                    <input type="text" name="title" id="title" class="regular-text"
                           value="<?php echo esc_attr( $t->title ); ?>"
                           placeholder="Interní popis pro administraci">
                </td>
            </tr>
            <tr>
                <th><label for="qualification_type_id">Typ kvalifikace</label></th>
                <td>
                    <select name="qualification_type_id" id="qualification_type_id">
                        <option value="0">Bez návaznosti na typ kvalifikace</option>
                        <?php foreach ( $qualification_types as $qualification_type ) : ?>
                            <option
                                value="<?php echo esc_attr( $qualification_type->id ); ?>"
                                data-has-kurz="<?php echo esc_attr( (int) $qualification_type->has_courses ); ?>"
                                data-has-zkouska="<?php echo esc_attr( (int) $qualification_type->has_exams ); ?>"
                                <?php selected( (int) $t->qualification_type_id, (int) $qualification_type->id ); ?>
                            >
                                <?php
                                $prefix = ! empty( $qualification_type->accreditation_number ) ? $qualification_type->accreditation_number . ' – ' : '';
                                echo esc_html( $prefix . $qualification_type->name );
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Vyberte typ kurzu nebo zkoušky, pokud má termín návaznost na jednu z definovaných kvalifikací.</p>
                </td>
            </tr>
            <tr>
                <th><label for="date_start">Datum od *</label></th>
                <td>
                    <input type="date" name="date_start" id="date_start" required
                           value="<?php echo esc_attr( $t->date_start ); ?>">
                </td>
            </tr>
            <tr id="row_date_end">
                <th><label for="date_end">Datum do</label></th>
                <td>
                    <input type="date" name="date_end" id="date_end"
                           value="<?php echo esc_attr( $t->date_end ); ?>">
                    <p class="description">Pro zkoušky se vyplní automaticky. Pro kurzy je povinné.</p>
                </td>
            </tr>
            <tr>
                <th><label for="enrollment_deadline">Přihlášky do</label></th>
                <td>
                    <input type="date" name="enrollment_deadline" id="enrollment_deadline"
                           value="<?php echo esc_attr( $t->enrollment_deadline ); ?>">
                    <p class="description">Po tomto datu se termín automaticky přestane nabízet pro přihlášení a skryje se ze synchronizace do formulářů.</p>
                </td>
            </tr>
            <tr>
                <th><label for="term_key">Interní klíč *</label></th>
                <td>
                    <input type="text" name="term_key" id="term_key" class="regular-text"
                           value="<?php echo esc_attr( $t->term_key ); ?>"
                           placeholder="např. kurz_2026_04_17_19">
                    <button type="button" id="btn_generate_key" class="button button-small">Vygenerovat z datumů</button>
                    <p class="description">Stabilní identifikátor. Po vytvoření záznamů neměňte.</p>
                </td>
            </tr>
            <tr>
                <th><label for="label">Label pro uživatele *</label></th>
                <td>
                    <input type="text" name="label" id="label" class="regular-text"
                           value="<?php echo esc_attr( $t->label ); ?>"
                           placeholder="např. kurz: 17. - 19. dubna 2026">
                    <button type="button" id="btn_generate_label" class="button button-small">Vygenerovat z datumů</button>
                    <p class="description">Tento text se zobrazí v dropdownu formuláře.</p>
                </td>
            </tr>
            <tr>
                <th><label for="capacity">Kapacita</label></th>
                <td>
                    <input type="number" name="capacity" id="capacity" min="0" step="1"
                           value="<?php echo esc_attr( $t->capacity ); ?>">
                </td>
            </tr>
            <tr>
                <th>Stav</th>
                <td>
                    <label>
                        <input type="checkbox" name="is_visible" value="1" <?php checked( $t->is_visible, 1 ); ?>>
                        Zobrazené na webu
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php checked( $t->is_active, 1 ); ?>>
                        Aktivní
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="is_archived" value="1" <?php checked( $t->is_archived, 1 ); ?>>
                        Archivováno
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="sort_order">Pořadí</label></th>
                <td>
                    <input type="number" name="sort_order" id="sort_order"
                           value="<?php echo esc_attr( $t->sort_order ); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="notes">Poznámky</label></th>
                <td>
                    <textarea name="notes" id="notes" rows="4" class="large-text"><?php
                        echo esc_textarea( $t->notes ?? '' );
                    ?></textarea>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="hlavas_term_save" value="1" class="button button-primary">
                <?php echo $is_edit ? 'Uložit změny' : 'Vytvořit termín'; ?>
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms' ) ); ?>" class="button">Zpět na seznam</a>
        </p>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Czech month names (genitive)
    const months = {
        1:'ledna',2:'února',3:'března',4:'dubna',5:'května',6:'června',
        7:'července',8:'srpna',9:'září',10:'října',11:'listopadu',12:'prosince'
    };

    function getValues() {
        return {
            type: document.getElementById('term_type').value,
            start: document.getElementById('date_start').value,
            end: document.getElementById('date_end').value,
        };
    }

    function syncQualificationOptions() {
        const termType = document.getElementById('term_type').value;
        const select = document.getElementById('qualification_type_id');
        const options = Array.from(select.options);

        options.forEach(function(option, index) {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const hasKurz = option.dataset.hasKurz === '1';
            const hasZkouska = option.dataset.hasZkouska === '1';
            const allowed = termType === 'kurz' ? hasKurz : hasZkouska;

            option.hidden = !allowed;
            option.disabled = !allowed;

            if (!allowed && option.selected) {
                select.value = '0';
            }
        });
    }

    // Generate key
    document.getElementById('btn_generate_key').addEventListener('click', function() {
        const v = getValues();
        if (!v.start) { alert('Vyplňte datum od.'); return; }
        const s = new Date(v.start);
        const prefix = v.type === 'kurz' ? 'kurz' : 'zkouska';
        let key = prefix + '_' + v.start.replace(/-/g, '_');
        if (v.type === 'kurz' && v.end && v.end !== v.start) {
            const e = new Date(v.end);
            key += '_' + String(e.getDate()).padStart(2, '0');
        }
        document.getElementById('term_key').value = key;
    });

    // Generate label
    document.getElementById('btn_generate_label').addEventListener('click', function() {
        const v = getValues();
        if (!v.start) { alert('Vyplňte datum od.'); return; }
        const s = new Date(v.start);
        const prefix = v.type === 'kurz' ? 'kurz' : 'zkouška';
        const dayS = s.getDate();
        const monthS = s.getMonth() + 1;
        const yearS = s.getFullYear();

        if (v.type === 'kurz' && v.end && v.end !== v.start) {
            const e = new Date(v.end);
            const dayE = e.getDate();
            const monthE = e.getMonth() + 1;
            if (monthS === monthE && yearS === e.getFullYear()) {
                document.getElementById('label').value =
                    prefix + ': ' + dayS + '. - ' + dayE + '. ' + months[monthE] + ' ' + yearS;
            } else {
                document.getElementById('label').value =
                    prefix + ': ' + dayS + '. ' + months[monthS] + ' - ' + dayE + '. ' + months[monthE] + ' ' + yearS;
            }
        } else {
            document.getElementById('label').value =
                prefix + ': ' + dayS + '. ' + months[monthS] + ' ' + yearS;
        }
    });

    document.getElementById('term_type').addEventListener('change', syncQualificationOptions);
    syncQualificationOptions();
});
</script>
