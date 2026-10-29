<?php
/**
 * @var WP_Post $post
 * @var array   $meta
 * @var array   $companies
 * @var string  $status
 */

if (!defined('ABSPATH')) {
    exit;
}

$start_val = $meta['datetime_start'] !== '' ? str_replace(' ', 'T', substr($meta['datetime_start'], 0, 16)) : '';
$end_val   = $meta['datetime_end'] !== '' ? str_replace(' ', 'T', substr($meta['datetime_end'], 0, 16)) : '';
?>
<p><strong><?php echo esc_html__('Stav rezervací (výpočet):', 'lkba-events'); ?></strong> <code><?php echo esc_html($status); ?></code></p>
<table class="form-table">
    <tr>
        <th><label for="lkba_datetime_start"><?php echo esc_html__('Začátek', 'lkba-events'); ?></label></th>
        <td><input type="datetime-local" name="lkba_datetime_start" id="lkba_datetime_start" value="<?php echo esc_attr($start_val); ?>"></td>
    </tr>
    <tr>
        <th><label for="lkba_datetime_end"><?php echo esc_html__('Konec', 'lkba-events'); ?></label></th>
        <td><input type="datetime-local" name="lkba_datetime_end" id="lkba_datetime_end" value="<?php echo esc_attr($end_val); ?>"></td>
    </tr>
    <tr>
        <th><label for="lkba_place_name"><?php echo esc_html__('Místo (název)', 'lkba-events'); ?></label></th>
        <td><input type="text" class="regular-text" name="lkba_place_name" id="lkba_place_name" value="<?php echo esc_attr($meta['place_name']); ?>"></td>
    </tr>
    <tr>
        <th><label for="lkba_place_address"><?php echo esc_html__('Adresa', 'lkba-events'); ?></label></th>
        <td><input type="text" class="regular-text" name="lkba_place_address" id="lkba_place_address" value="<?php echo esc_attr($meta['place_address']); ?>"></td>
    </tr>
    <tr>
        <th><label for="lkba_instructor"><?php echo esc_html__('Lektor', 'lkba-events'); ?></label></th>
        <td><input type="text" class="regular-text" name="lkba_instructor" id="lkba_instructor" value="<?php echo esc_attr($meta['instructor']); ?>"></td>
    </tr>
    <tr>
        <th><label for="lkba_capacity"><?php echo esc_html__('Kapacita', 'lkba-events'); ?></label></th>
        <td><input type="number" min="0" name="lkba_capacity" id="lkba_capacity" value="<?php echo esc_attr((string) $meta['capacity']); ?>">
            <p class="description"><?php echo esc_html(sprintf(__('Obsazeno: %d', 'lkba-events'), LKBA_Events_Bookings::count_active_for_event($post->ID))); ?></p>
        </td>
    </tr>
    <tr>
        <th><label for="lkba_price"><?php echo esc_html__('Cena (Kč, vč. DPH)', 'lkba-events'); ?></label></th>
        <td>
            <input type="number" step="0.01" min="0" name="lkba_price" id="lkba_price" value="<?php echo esc_attr((string) $meta['price']); ?>" placeholder="<?php echo esc_attr__('prázdné = cena kategorie', 'lkba-events'); ?>">
            <p class="description"><?php echo esc_html(sprintf(__('Efektivní cena: %s Kč', 'lkba-events'), number_format_i18n(LKBA_Events_Event::get_price($post->ID), 2))); ?></p>
        </td>
    </tr>
    <tr>
        <th><label for="lkba_company_id"><?php echo esc_html__('Fakturace na firmu', 'lkba-events'); ?></label></th>
        <td>
            <select name="lkba_company_id" id="lkba_company_id">
                <?php if (!$companies) : ?>
                    <option value="company-1" <?php selected($meta['company_id'], 'company-1'); ?>>company-1</option>
                    <option value="company-2" <?php selected($meta['company_id'], 'company-2'); ?>>company-2</option>
                <?php else : ?>
                    <?php foreach ($companies as $c) : ?>
                        <option value="<?php echo esc_attr($c['id']); ?>" <?php selected($meta['company_id'], $c['id']); ?>><?php echo esc_html($c['label']); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th><label for="lkba_payment_methods"><?php echo esc_html__('Platební metody', 'lkba-events'); ?></label></th>
        <td>
            <select name="lkba_payment_methods" id="lkba_payment_methods">
                <option value="bank_transfer" <?php selected($meta['payment_methods'], 'bank_transfer'); ?>><?php echo esc_html__('Jen převod (Phase 2)', 'lkba-events'); ?></option>
                <option value="both" <?php selected($meta['payment_methods'], 'both'); ?>><?php echo esc_html__('Obě (karta v Phase 3)', 'lkba-events'); ?></option>
                <option value="card" <?php selected($meta['payment_methods'], 'card'); ?>><?php echo esc_html__('Jen karta (Phase 3)', 'lkba-events'); ?></option>
            </select>
            <p class="description"><?php echo esc_html__('Phase 2 front-end vždy nabízí bankovní převod.', 'lkba-events'); ?></p>
        </td>
    </tr>
    <tr>
        <th><?php echo esc_html__('Otevřeno pro rezervace', 'lkba-events'); ?></th>
        <td><label><input type="checkbox" name="lkba_booking_open" value="1" <?php checked(!empty($meta['booking_open'])); ?>> <?php echo esc_html__('Ano', 'lkba-events'); ?></label></td>
    </tr>
</table>
