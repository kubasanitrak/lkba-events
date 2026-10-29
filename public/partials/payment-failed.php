<?php
/**
 * Comgate cancel / fail return.
 *
 * @var object|null $booking
 * @var string      $support
 * @var string      $ref
 */

if (!defined('ABSPATH')) {
    exit;
}

$event = $booking ? get_post((int) $booking->event_id) : null;
?>
<div class="lkba-events lkba-payment-return lkba-payment-return--failed">
    <h2><?php echo esc_html__('Platba se nepodařila', 'lkba-events'); ?></h2>
    <p><?php echo esc_html__('Platba kartou byla zrušena nebo selhala. Místo v termínu nemusí být rezervované — napište nám, pokud potřebujete pomoci.', 'lkba-events'); ?></p>

    <?php if ($booking) : ?>
        <ul class="lkba-payment-return__meta">
            <li><strong><?php echo esc_html__('Rezervace:', 'lkba-events'); ?></strong> <?php echo esc_html($booking->booking_number); ?></li>
            <?php if ($event) : ?>
                <li><strong><?php echo esc_html__('Událost:', 'lkba-events'); ?></strong> <?php echo esc_html($event->post_title); ?></li>
            <?php endif; ?>
            <li><strong><?php echo esc_html__('Stav:', 'lkba-events'); ?></strong> <?php echo esc_html($booking->status . ' / ' . $booking->payment_status); ?></li>
        </ul>
    <?php elseif ($ref !== '') : ?>
        <p><?php echo esc_html(sprintf(__('Reference: %s', 'lkba-events'), $ref)); ?></p>
    <?php endif; ?>

    <p>
        <?php echo esc_html__('Kontakt:', 'lkba-events'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a>
    </p>
</div>
