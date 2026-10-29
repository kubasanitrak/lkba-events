<?php
/**
 * Account overview of bookings.
 *
 * @var object[] $mine
 * @var string   $support
 */

if (!defined('ABSPATH')) {
    exit;
}

$status_labels = array(
    'pending'     => __('Čeká na platbu', 'lkba-events'),
    'confirmed'   => __('Potvrzeno', 'lkba-events'),
    'cancelled'   => __('Zrušeno', 'lkba-events'),
    'rescheduled' => __('Přeřazeno', 'lkba-events'),
);
$pay_labels = array(
    'awaiting' => __('Čeká', 'lkba-events'),
    'paid'     => __('Zaplaceno', 'lkba-events'),
    'failed'   => __('Selhalo', 'lkba-events'),
    'expired'  => __('Vypršelo', 'lkba-events'),
);
?>
<div class="lkba-events lkba-my-bookings">
    <h2><?php echo esc_html__('Moje rezervace', 'lkba-events'); ?></h2>

    <?php if (!$mine) : ?>
        <p><?php echo esc_html__('Zatím nemáte žádné rezervace.', 'lkba-events'); ?></p>
    <?php else : ?>
        <div class="lkba-my-bookings__list">
            <?php foreach ($mine as $b) : ?>
                <?php
                $event = get_post((int) $b->event_id);
                $meta  = LKBA_Events_Event::get_meta((int) $b->event_id);
                $title = $event ? $event->post_title : $b->booking_number;
                $cancel_mail = LKBA_Events_Settings::support_mailto($b, 'cancel');
                $resched_mail = LKBA_Events_Settings::support_mailto($b, 'reschedule');
                ?>
                <article class="lkba-my-bookings__item">
                    <h3><?php echo esc_html($title); ?></h3>
                    <p class="lkba-my-bookings__ref">
                        <code><?php echo esc_html($b->booking_number); ?></code>
                        · <?php echo esc_html($status_labels[$b->status] ?? $b->status); ?>
                        · <?php echo esc_html($pay_labels[$b->payment_status] ?? $b->payment_status); ?>
                    </p>
                    <?php if ($meta['datetime_start'] !== '') : ?>
                        <p><?php echo esc_html(LKBA_Events_Event::format_when((int) $b->event_id)); ?></p>
                    <?php endif; ?>
                    <?php if ($meta['place_name'] !== '') : ?>
                        <p><?php echo esc_html($meta['place_name']); ?></p>
                    <?php endif; ?>
                    <p><?php echo esc_html(number_format_i18n((float) $b->total, 0)); ?> Kč
                        · <?php echo esc_html($b->payment_method === 'comgate' ? __('karta', 'lkba-events') : __('převod', 'lkba-events')); ?>
                    </p>
                    <p class="lkba-my-bookings__actions">
                        <?php if (!empty($b->fakturoid_pdf) && $b->payment_status === 'paid') : ?>
                            <a href="<?php echo esc_url(LKBA_Events_Bookings::invoice_download_url((int) $b->id)); ?>">
                                <?php echo esc_html__('Stáhnout fakturu', 'lkba-events'); ?>
                            </a>
                            ·
                        <?php endif; ?>
                        <a href="<?php echo esc_url($cancel_mail); ?>"><?php echo esc_html__('Zrušit', 'lkba-events'); ?></a>
                        ·
                        <a href="<?php echo esc_url($resched_mail); ?>"><?php echo esc_html__('Změnit termín', 'lkba-events'); ?></a>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p class="lkba-my-bookings__support">
        <?php echo esc_html__('Zrušení nebo změna termínu:', 'lkba-events'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a>
    </p>
</div>
