<?php
/**
 * Comgate success / pending return.
 *
 * @var object|null $booking
 * @var bool        $verified
 * @var string      $support
 * @var string      $ref
 */

if (!defined('ABSPATH')) {
    exit;
}

$paid = $booking && $booking->payment_status === 'paid';
$event = $booking ? get_post((int) $booking->event_id) : null;
?>
<div class="lkba-events lkba-payment-return lkba-payment-return--success">
    <?php if ($paid) : ?>
        <h2><?php echo esc_html__('Platba úspěšná', 'lkba-events'); ?></h2>
        <p><?php echo esc_html__('Děkujeme. Rezervace je potvrzená. Potvrzení jsme poslali e-mailem.', 'lkba-events'); ?></p>
    <?php else : ?>
        <h2><?php echo esc_html__('Platba se zpracovává', 'lkba-events'); ?></h2>
        <p><?php echo esc_html__('Pokud jste platbu dokončili, potvrzení přijde e-mailem během chvíle. Stav můžete zkontrolovat v přehledu rezervací po přihlášení.', 'lkba-events'); ?></p>
    <?php endif; ?>

    <?php if ($booking) : ?>
        <ul class="lkba-payment-return__meta">
            <li><strong><?php echo esc_html__('Rezervace:', 'lkba-events'); ?></strong> <?php echo esc_html($booking->booking_number); ?></li>
            <?php if ($event) : ?>
                <li><strong><?php echo esc_html__('Událost:', 'lkba-events'); ?></strong> <?php echo esc_html($event->post_title); ?></li>
                <li><?php echo esc_html(LKBA_Events_Event::format_when((int) $booking->event_id)); ?></li>
            <?php endif; ?>
            <li><strong><?php echo esc_html__('Stav platby:', 'lkba-events'); ?></strong> <?php echo esc_html($booking->payment_status); ?></li>
        </ul>
        <?php if ($paid && !empty($booking->fakturoid_pdf) && is_user_logged_in()) : ?>
            <p>
                <a class="lkba-btn" href="<?php echo esc_url(LKBA_Events_Bookings::invoice_download_url((int) $booking->id)); ?>">
                    <?php echo esc_html__('Stáhnout fakturu (PDF)', 'lkba-events'); ?>
                </a>
            </p>
        <?php endif; ?>
    <?php elseif ($ref !== '') : ?>
        <p><?php echo esc_html(sprintf(__('Reference: %s', 'lkba-events'), $ref)); ?></p>
    <?php endif; ?>

    <p>
        <?php echo esc_html__('Podpora / zrušení:', 'lkba-events'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a>
    </p>
</div>
