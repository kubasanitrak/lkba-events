<?php
/**
 * Post-submit bank recap.
 *
 * @var array       $recap
 * @var object|null $booking
 */

if (!defined('ABSPATH')) {
    exit;
}

$support = LKBA_Events_Settings::get_support_email();
$event   = $booking ? get_post((int) $booking->event_id) : null;
?>
<div class="lkba-events lkba-booking-recap">
    <h2><?php echo esc_html__('Rezervace přijata', 'lkba-events'); ?></h2>
    <?php if ($booking) : ?>
        <p>
            <?php echo esc_html__('Číslo rezervace:', 'lkba-events'); ?>
            <strong><?php echo esc_html($booking->booking_number); ?></strong>
        </p>
        <?php if ($event) : ?>
            <p><?php echo esc_html($event->post_title); ?> — <?php echo esc_html(LKBA_Events_Event::format_when((int) $booking->event_id)); ?></p>
        <?php endif; ?>
        <p><?php echo esc_html__('Platební údaje jsme poslali e-mailem. Můžete zaplatit i podle rekapitulace níže.', 'lkba-events'); ?></p>

        <?php if (!empty($recap['instructions'])) : ?>
            <div class="lkba-booking-recap__instructions">
                <?php echo $recap['instructions']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php elseif (!empty($recap['qr_url'])) : ?>
            <p><img src="<?php echo esc_url($recap['qr_url']); ?>" alt="<?php echo esc_attr__('QR platba', 'lkba-events'); ?>" width="200" height="200"></p>
        <?php endif; ?>
    <?php endif; ?>

    <p>
        <?php echo esc_html__('Zrušení / změna:', 'lkba-events'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a>
    </p>
</div>
