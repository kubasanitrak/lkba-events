<?php
/**
 * Booking form (guest, bank-first).
 *
 * @var int    $event_id
 * @var array  $draft
 * @var array  $events_open
 * @var string $error_msg
 * @var string $support
 * @var int    $privacy
 * @var int    $terms_p
 * @var int    $storno
 */

if (!defined('ABSPATH')) {
    exit;
}

$d_name  = $draft['name'] ?? '';
$d_email = $draft['email'] ?? '';
$d_phone = $draft['phone'] ?? '';
$d_notes = $draft['notes'] ?? '';
$d_inv   = isset($draft['invoice']) && is_array($draft['invoice']) ? $draft['invoice'] : array();
?>
<div class="lkba-events lkba-booking-form">
    <h2><?php echo esc_html__('Rezervace', 'lkba-events'); ?></h2>

    <?php if ($error_msg !== '') : ?>
        <div class="lkba-booking-form__error" role="alert"><?php echo esc_html($error_msg); ?></div>
    <?php endif; ?>

    <?php if (!$events_open) : ?>
        <p><?php echo esc_html__('Momentálně nelze nic rezervovat.', 'lkba-events'); ?></p>
    <?php else : ?>
        <form method="post" action="" class="lkba-booking-form__form">
            <?php wp_nonce_field('lkba_events_book', 'lkba_events_book_nonce'); ?>

            <p>
                <label for="lkba_event_id"><?php echo esc_html__('Termín', 'lkba-events'); ?></label><br>
                <select name="lkba_event_id" id="lkba_event_id" required>
                    <option value=""><?php echo esc_html__('— Vyberte —', 'lkba-events'); ?></option>
                    <?php foreach ($events_open as $post) : ?>
                        <option value="<?php echo esc_attr((string) $post->ID); ?>" <?php selected($event_id, $post->ID); ?>>
                            <?php
                            echo esc_html(get_the_title($post) . ' — ' . LKBA_Events_Event::format_when($post->ID)
                                . ' (' . number_format_i18n(LKBA_Events_Event::get_price($post->ID), 0) . ' Kč)');
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label for="lkba_name"><?php echo esc_html__('Jméno a příjmení', 'lkba-events'); ?></label><br>
                <input type="text" name="lkba_name" id="lkba_name" required value="<?php echo esc_attr($d_name); ?>">
            </p>
            <p>
                <label for="lkba_email"><?php echo esc_html__('E-mail', 'lkba-events'); ?></label><br>
                <input type="email" name="lkba_email" id="lkba_email" required value="<?php echo esc_attr($d_email); ?>">
            </p>
            <p>
                <label for="lkba_phone"><?php echo esc_html__('Telefon', 'lkba-events'); ?></label><br>
                <input type="text" name="lkba_phone" id="lkba_phone" value="<?php echo esc_attr($d_phone); ?>">
            </p>
            <p>
                <label for="lkba_notes"><?php echo esc_html__('Poznámka', 'lkba-events'); ?></label><br>
                <textarea name="lkba_notes" id="lkba_notes" rows="3"><?php echo esc_textarea($d_notes); ?></textarea>
            </p>

            <fieldset class="lkba-booking-form__invoice">
                <legend><?php echo esc_html__('Fakturační údaje (volitelné)', 'lkba-events'); ?></legend>
                <p><input type="text" name="lkba_inv_company" placeholder="<?php echo esc_attr__('Firma / jméno', 'lkba-events'); ?>" value="<?php echo esc_attr($d_inv['company_name'] ?? ''); ?>"></p>
                <p><input type="text" name="lkba_inv_street" placeholder="<?php echo esc_attr__('Ulice', 'lkba-events'); ?>" value="<?php echo esc_attr($d_inv['street'] ?? ''); ?>"></p>
                <p>
                    <input type="text" name="lkba_inv_zip" placeholder="PSČ" value="<?php echo esc_attr($d_inv['zip'] ?? ''); ?>">
                    <input type="text" name="lkba_inv_city" placeholder="<?php echo esc_attr__('Město', 'lkba-events'); ?>" value="<?php echo esc_attr($d_inv['city'] ?? ''); ?>">
                </p>
                <p>
                    <input type="text" name="lkba_inv_ic" placeholder="IČO" value="<?php echo esc_attr($d_inv['ic'] ?? ''); ?>">
                    <input type="text" name="lkba_inv_dic" placeholder="DIČ" value="<?php echo esc_attr($d_inv['dic'] ?? ''); ?>">
                </p>
            </fieldset>

            <p class="lkba-booking-form__legal">
                <label>
                    <input type="checkbox" name="lkba_legal" value="1" required>
                    <?php echo esc_html__('Souhlasím s podmínkami a zpracováním osobních údajů', 'lkba-events'); ?>
                    <?php if ($terms_p) : ?>
                        (<a href="<?php echo esc_url(get_permalink($terms_p)); ?>" target="_blank" rel="noopener"><?php echo esc_html__('podmínky', 'lkba-events'); ?></a>
                    <?php endif; ?>
                    <?php if ($privacy) : ?>
                        · <a href="<?php echo esc_url(get_permalink($privacy)); ?>" target="_blank" rel="noopener"><?php echo esc_html__('soukromí', 'lkba-events'); ?></a>
                    <?php endif; ?>
                    <?php if ($storno) : ?>
                        · <a href="<?php echo esc_url(get_permalink($storno)); ?>" target="_blank" rel="noopener"><?php echo esc_html__('storno', 'lkba-events'); ?></a>
                    <?php endif; ?>
                </label>
            </p>

            <p class="lkba-booking-form__paynote">
                <?php echo esc_html__('Platba: bankovní převod (QR a údaje v e-mailu a na rekapitulaci).', 'lkba-events'); ?>
            </p>

            <p>
                <button type="submit" class="lkba-booking-form__submit"><?php echo esc_html__('Rezervovat', 'lkba-events'); ?></button>
            </p>
        </form>
    <?php endif; ?>

    <p class="lkba-booking-form__support">
        <?php echo esc_html__('Podpora:', 'lkba-events'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a>
    </p>
</div>
