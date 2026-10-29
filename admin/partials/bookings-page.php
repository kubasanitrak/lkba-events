<?php
/**
 * @var object[] $bookings
 * @var string   $status
 */

if (!defined('ABSPATH')) {
    exit;
}

$support = LKBA_Events_Settings::get_support_email();
$base    = admin_url('admin.php?page=lkba-events-bookings');
?>
<div class="wrap">
    <h1><?php echo esc_html__('Rezervace', 'lkba-events'); ?></h1>
    <p>
        <a href="<?php echo esc_url($base); ?>"><?php echo esc_html__('Vše', 'lkba-events'); ?></a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'pending', $base)); ?>">pending</a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'confirmed', $base)); ?>">confirmed</a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'cancelled', $base)); ?>">cancelled</a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'rescheduled', $base)); ?>">rescheduled</a>
    </p>
    <p><?php echo esc_html__('Podpora / mailto:', 'lkba-events'); ?> <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a></p>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Číslo', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Událost', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Zákazník', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Stav', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Platba', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Částka', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Firma', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Akce', 'lkba-events'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$bookings) : ?>
                <tr><td colspan="8"><?php echo esc_html__('Žádné rezervace.', 'lkba-events'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($bookings as $b) : ?>
                    <?php
                    $event = get_post((int) $b->event_id);
                    $nonce = wp_create_nonce('lkba_booking_' . $b->id);
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($b->booking_number); ?></code></td>
                        <td><?php echo esc_html($event ? $event->post_title : ('#' . $b->event_id)); ?></td>
                        <td>
                            <?php echo esc_html($b->customer_data['name'] ?? ''); ?><br>
                            <a href="mailto:<?php echo esc_attr($b->customer_data['email'] ?? ''); ?>"><?php echo esc_html($b->customer_data['email'] ?? ''); ?></a>
                        </td>
                        <td><?php echo esc_html($b->status); ?></td>
                        <td><?php echo esc_html($b->payment_status); ?></td>
                        <td><?php echo esc_html(number_format_i18n((float) $b->total, 2)); ?> Kč</td>
                        <td><?php echo esc_html((string) $b->company_id); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(array('lkba_booking_action' => 'confirm', 'booking_id' => $b->id, '_wpnonce' => $nonce), $base)); ?>"><?php echo esc_html__('Potvrdit', 'lkba-events'); ?></a>
                            |
                            <a href="<?php echo esc_url(add_query_arg(array('lkba_booking_action' => 'cancel', 'booking_id' => $b->id, '_wpnonce' => $nonce), $base)); ?>"><?php echo esc_html__('Zrušit', 'lkba-events'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
