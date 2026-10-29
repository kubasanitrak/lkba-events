<?php
/**
 * Legacy TT / workshop migrate UI.
 *
 * @var array      $probe
 * @var array|null $result
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('Migrace Teacher Training / workshopů', 'lkba-events'); ?></h1>

    <?php if (is_array($result) && !empty($result['message'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($result['message']); ?></p></div>
    <?php endif; ?>

    <p><?php echo esc_html__('Legacy přihlášky žijí ve stejné DB tabulce jako poukazy (lkba_goods) plus katalog termínů v includes/config.php. Samostatné PHP formuláře zatím nemažte (Phase 5).', 'lkba-events'); ?></p>

    <h2><?php echo esc_html__('Detekce', 'lkba-events'); ?></h2>
    <ul>
        <li>
            <strong>includes/config.php:</strong>
            <?php echo !empty($probe['config'])
                ? esc_html(sprintf(__('ano — %d workshopů v katalogu', 'lkba-events'), (int) $probe['workshops_count']))
                : esc_html__('nenalezeno (katalog termínů nelze načíst)', 'lkba-events'); ?>
        </li>
        <li>
            <strong>lkba_goods:</strong>
            <?php
            echo $probe['goods_table']
                ? esc_html($probe['goods_table'] . ' — ' . sprintf(__('%d eventových řádků (bez voucherů)', 'lkba-events'), (int) $probe['goods_event_rows']))
                : esc_html__('nenalezeno', 'lkba-events');
            ?>
        </li>
        <li>
            <strong>lk_barre:</strong>
            <?php
            echo $probe['barre_table']
                ? esc_html($probe['barre_table'] . ' — ' . sprintf(__('%d řádků (Pilates studio)', 'lkba-events'), (int) $probe['barre_rows']))
                : esc_html__('nenalezeno', 'lkba-events');
            ?>
        </li>
    </ul>

    <?php if (!empty($probe['goods_types'])) : ?>
        <h3><?php echo esc_html__('Typy v lkba_goods', 'lkba-events'); ?></h3>
        <table class="widefat striped" style="max-width:40rem">
            <thead><tr><th>type</th><th>count</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($probe['goods_types'] as $t) : ?>
                    <tr>
                        <td><?php echo esc_html($t['type']); ?></td>
                        <td><?php echo esc_html((string) $t['count']); ?></td>
                        <td><?php echo !empty($t['voucher']) ? esc_html__('→ vouchers plugin', 'lkba-events') : esc_html__('→ events', 'lkba-events'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2><?php echo esc_html__('Co import dělá', 'lkba-events'); ?></h2>
    <ol>
        <li><?php echo esc_html__('Kategorie lkba_event_cat (+ výchozí ceny): Teacher Training, Level 2, Barre & Strength, Choreo Inspiration, Jak si otevřít Pilates Studio.', 'lkba-events'); ?></li>
        <li><?php echo esc_html__('Termíny z $workshops → draft/publish lkba_event s meta (datum, místo, instruktor, _lkba_legacy_workshop_id).', 'lkba-events'); ?></li>
        <li><?php echo esc_html__('Registrace z lkba_goods (bez voucher typů) + lk_barre → wp_lkba_bookings. Legacy řádky nemají ID termínu — jdou na draft „Legacy registrace — {kategorie}“. Invoice number slouží k dedupe.', 'lkba-events'); ?></li>
    </ol>

    <h2><?php echo esc_html__('Spustit', 'lkba-events'); ?></h2>
    <form method="post" style="margin-bottom:1rem;">
        <?php wp_nonce_field('lkba_events_migrate', 'lkba_events_migrate_nonce'); ?>
        <input type="hidden" name="lkba_migrate_action" value="all">
        <?php submit_button(__('Importovat vše (kategorie + workshopy + registrace)', 'lkba-events'), 'primary', 'submit', false); ?>
    </form>
    <form method="post" style="display:inline-block;margin-right:0.5rem;">
        <?php wp_nonce_field('lkba_events_migrate', 'lkba_events_migrate_nonce'); ?>
        <input type="hidden" name="lkba_migrate_action" value="categories">
        <?php submit_button(__('Jen kategorie', 'lkba-events'), 'secondary', 'submit', false); ?>
    </form>
    <form method="post" style="display:inline-block;margin-right:0.5rem;">
        <?php wp_nonce_field('lkba_events_migrate', 'lkba_events_migrate_nonce'); ?>
        <input type="hidden" name="lkba_migrate_action" value="workshops">
        <?php submit_button(__('Jen workshopy', 'lkba-events'), 'secondary', 'submit', false, empty($probe['workshops_count']) ? array('disabled' => 'disabled') : array()); ?>
    </form>
    <form method="post" style="display:inline-block;">
        <?php wp_nonce_field('lkba_events_migrate', 'lkba_events_migrate_nonce'); ?>
        <input type="hidden" name="lkba_migrate_action" value="registrations">
        <?php
        $can_reg = !empty($probe['goods_table']) || !empty($probe['barre_table']);
        submit_button(__('Jen registrace', 'lkba-events'), 'secondary', 'submit', false, $can_reg ? array() : array('disabled' => 'disabled'));
        ?>
    </form>

    <h2><?php echo esc_html__('Omezení', 'lkba-events'); ?></h2>
    <ul>
        <li><?php echo esc_html__('lkba_goods.type je jen název produktu (např. „Teacher Training“), ne konkrétní occurrence id — proto holding eventy.', 'lkba-events'); ?></li>
        <li><?php echo esc_html__('Voucher řádky (Dárkový poukaz / Voucher / gift voucher) se sem neimportují — použijte LKBA Poukazy → Migrace.', 'lkba-events'); ?></li>
        <li><?php echo esc_html__('Po importu přiřaďte legacy rezervace na správné termíny ručně, pokud je to potřeba.', 'lkba-events'); ?></li>
    </ul>
</div>
