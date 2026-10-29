<?php
/**
 * Events settings.
 */

if (!defined('ABSPATH')) {
    exit;
}

settings_errors('lkba_events');

$close_hours   = LKBA_Events_Settings::get_booking_close_hours();
$support_email = LKBA_Events_Settings::get_support_email();
$sender_name   = get_option(LKBA_Events_Settings::OPTION_SENDER_NAME, '');
$sender_email  = LKBA_Events_Settings::get_sender_email();
$terms_page    = (int) get_option(LKBA_Events_Settings::OPTION_TERMS_PAGE, 0);
$privacy_page  = (int) get_option(LKBA_Events_Settings::OPTION_PRIVACY_PAGE, 0);
$storno_page   = (int) get_option(LKBA_Events_Settings::OPTION_STORNO_PAGE, 0);
?>
<div class="wrap">
    <h1><?php echo esc_html__('Nastavení událostí', 'lkba-events'); ?></h1>
    <p><?php echo esc_html__('Phase 2 — bank-first booking. Guest checkout; WP účet po zaplacení.', 'lkba-events'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('lkba_events_save_settings', 'lkba_events_settings_nonce'); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="lkba_booking_close_hours"><?php echo esc_html__('Uzavření rezervací (hodiny před začátkem)', 'lkba-events'); ?></label></th>
                <td>
                    <input type="number" min="1" class="small-text" id="lkba_booking_close_hours" name="lkba_booking_close_hours" value="<?php echo esc_attr((string) $close_hours); ?>">
                    <p class="description"><?php echo esc_html__('Výchozí 12 hodin.', 'lkba-events'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lkba_support_email"><?php echo esc_html__('Support e-mail', 'lkba-events'); ?></label></th>
                <td>
                    <input type="email" class="regular-text" id="lkba_support_email" name="lkba_support_email" value="<?php echo esc_attr($support_email); ?>">
                    <p class="description"><?php echo esc_html__('From + cancel/reschedule mailto. Default: rezervace@barreacademy.cz', 'lkba-events'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lkba_sender_name"><?php echo esc_html__('Sender name', 'lkba-events'); ?></label></th>
                <td><input type="text" class="regular-text" id="lkba_sender_name" name="lkba_sender_name" value="<?php echo esc_attr($sender_name); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="lkba_sender_email"><?php echo esc_html__('Sender e-mail', 'lkba-events'); ?></label></th>
                <td><input type="email" class="regular-text" id="lkba_sender_email" name="lkba_sender_email" value="<?php echo esc_attr($sender_email); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Terms page', 'lkba-events'); ?></th>
                <td><?php wp_dropdown_pages(array(
                    'name'              => 'lkba_terms_page',
                    'selected'          => $terms_page,
                    'show_option_none'  => __('— Select —', 'lkba-events'),
                    'option_none_value' => '0',
                )); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Privacy page', 'lkba-events'); ?></th>
                <td><?php wp_dropdown_pages(array(
                    'name'              => 'lkba_privacy_page',
                    'selected'          => $privacy_page,
                    'show_option_none'  => __('— Select —', 'lkba-events'),
                    'option_none_value' => '0',
                )); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Storno page', 'lkba-events'); ?></th>
                <td>
                    <?php wp_dropdown_pages(array(
                        'name'              => 'lkba_storno_page',
                        'selected'          => $storno_page,
                        'show_option_none'  => __('— Select —', 'lkba-events'),
                        'option_none_value' => '0',
                    )); ?>
                    <p class="description">https://barreacademy.cz/podminky-storna-teacher-training/</p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Uložit', 'lkba-events')); ?>
    </form>
</div>
