<?php
/**
 * @var object[] $bookings
 * @var string   $status
 * @var int      $filter_event
 * @var string   $filter_instructor
 * @var string   $filter_place
 * @var array    $choices
 * @var array    $stats
 * @var array|null $email_result
 */

if (!defined('ABSPATH')) {
    exit;
}

$support = LKBA_Events_Settings::get_support_email();
$base    = admin_url('admin.php?page=lkba-events-bookings');
$events  = LKBA_Events_Event::query_events(array('posts_per_page' => 100, 'post_status' => array('publish', 'draft', 'private')));
?>
<div class="wrap">
    <h1><?php echo esc_html__('Rezervace', 'lkba-events'); ?></h1>

    <?php if (!empty($email_result) && is_array($email_result)) : ?>
        <div class="notice notice-success is-dismissible"><p>
            <?php
            echo esc_html(sprintf(
                /* translators: 1: sent 2: skipped 3: errors */
                __('E-mail odeslán: %1$d, přeskočeno: %2$d, chyby: %3$d', 'lkba-events'),
                (int) ($email_result['sent'] ?? 0),
                (int) ($email_result['skipped'] ?? 0),
                (int) ($email_result['errors'] ?? 0)
            ));
            ?>
        </p></div>
    <?php endif; ?>

    <h2><?php echo esc_html__('Statistiky', 'lkba-events'); ?></h2>
    <form method="get" action="">
        <input type="hidden" name="page" value="lkba-events-bookings">
        <?php if ($status !== '') : ?>
            <input type="hidden" name="status" value="<?php echo esc_attr($status); ?>">
        <?php endif; ?>
        <label>
            <?php echo esc_html__('Instruktor', 'lkba-events'); ?>
            <select name="instructor">
                <option value=""><?php echo esc_html__('— všichni —', 'lkba-events'); ?></option>
                <?php foreach ($choices['instructors'] as $name) : ?>
                    <option value="<?php echo esc_attr($name); ?>" <?php selected($filter_instructor, $name); ?>><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?php echo esc_html__('Místo', 'lkba-events'); ?>
            <select name="place">
                <option value=""><?php echo esc_html__('— všechna —', 'lkba-events'); ?></option>
                <?php foreach ($choices['places'] as $name) : ?>
                    <option value="<?php echo esc_attr($name); ?>" <?php selected($filter_place, $name); ?>><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php submit_button(__('Filtrovat', 'lkba-events'), 'secondary', '', false); ?>
    </form>
    <p>
        <strong><?php echo esc_html(sprintf(__('Celkem rezervací: %d', 'lkba-events'), (int) $stats['total'])); ?></strong>
        <?php
        if (!empty($stats['by_status'])) {
            $bits = array();
            foreach ($stats['by_status'] as $st => $cnt) {
                $bits[] = $st . '=' . $cnt;
            }
            echo ' — ' . esc_html(implode(', ', $bits));
        }
        ?>
    </p>

    <h2><?php echo esc_html__('E-mail účastníkům', 'lkba-events'); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field('lkba_events_attendee_email', 'lkba_attendee_email_nonce'); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="lkba_email_event_id"><?php echo esc_html__('Událost', 'lkba-events'); ?></label></th>
                <td>
                    <select name="lkba_email_event_id" id="lkba_email_event_id" required>
                        <option value=""><?php echo esc_html__('— Vyberte —', 'lkba-events'); ?></option>
                        <?php foreach ($events as $ev) : ?>
                            <option value="<?php echo esc_attr((string) $ev->ID); ?>" <?php selected($filter_event, $ev->ID); ?>>
                                <?php echo esc_html($ev->post_title . ' — ' . LKBA_Events_Event::format_when($ev->ID)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="lkba_email_status"><?php echo esc_html__('Komu', 'lkba-events'); ?></label></th>
                <td>
                    <select name="lkba_email_status" id="lkba_email_status">
                        <option value=""><?php echo esc_html__('Aktivní (pending + confirmed + rescheduled)', 'lkba-events'); ?></option>
                        <option value="confirmed"><?php echo esc_html__('Jen confirmed', 'lkba-events'); ?></option>
                        <option value="pending"><?php echo esc_html__('Jen pending', 'lkba-events'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="lkba_email_subject"><?php echo esc_html__('Předmět', 'lkba-events'); ?></label></th>
                <td><input type="text" class="regular-text" name="lkba_email_subject" id="lkba_email_subject" required></td>
            </tr>
            <tr>
                <th><label for="lkba_email_body"><?php echo esc_html__('Zpráva', 'lkba-events'); ?></label></th>
                <td><textarea class="large-text" rows="6" name="lkba_email_body" id="lkba_email_body" required></textarea></td>
            </tr>
        </table>
        <?php submit_button(__('Odeslat účastníkům', 'lkba-events')); ?>
    </form>

    <h2><?php echo esc_html__('Seznam', 'lkba-events'); ?></h2>
    <p>
        <a href="<?php echo esc_url($base); ?>"><?php echo esc_html__('Vše', 'lkba-events'); ?></a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'pending', $base)); ?>">pending</a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'confirmed', $base)); ?>">confirmed</a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'cancelled', $base)); ?>">cancelled</a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'rescheduled', $base)); ?>">rescheduled</a>
    </p>
    <form method="get" action="" style="margin-bottom:1em;">
        <input type="hidden" name="page" value="lkba-events-bookings">
        <?php if ($status !== '') : ?>
            <input type="hidden" name="status" value="<?php echo esc_attr($status); ?>">
        <?php endif; ?>
        <label>
            <?php echo esc_html__('Událost', 'lkba-events'); ?>
            <select name="event_id">
                <option value="0"><?php echo esc_html__('— všechny —', 'lkba-events'); ?></option>
                <?php foreach ($events as $ev) : ?>
                    <option value="<?php echo esc_attr((string) $ev->ID); ?>" <?php selected($filter_event, $ev->ID); ?>>
                        <?php echo esc_html($ev->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php submit_button(__('Filtrovat', 'lkba-events'), 'secondary', '', false); ?>
    </form>
    <p><?php echo esc_html__('Podpora / mailto:', 'lkba-events'); ?> <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a></p>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Číslo', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Událost', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Zákazník', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Stav', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Platba', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Metoda', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Částka', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Firma', 'lkba-events'); ?></th>
                <th><?php echo esc_html__('Akce', 'lkba-events'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$bookings) : ?>
                <tr><td colspan="9"><?php echo esc_html__('Žádné rezervace.', 'lkba-events'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($bookings as $b) : ?>
                    <?php
                    $event = get_post((int) $b->event_id);
                    $nonce = wp_create_nonce('lkba_booking_' . $b->id);
                    $emeta = LKBA_Events_Event::get_meta((int) $b->event_id);
                    if ($filter_instructor !== '' && $emeta['instructor'] !== $filter_instructor) {
                        continue;
                    }
                    if ($filter_place !== '' && $emeta['place_name'] !== $filter_place) {
                        continue;
                    }
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($b->booking_number); ?></code></td>
                        <td>
                            <?php echo esc_html($event ? $event->post_title : ('#' . $b->event_id)); ?>
                            <?php if ($emeta['instructor'] !== '') : ?>
                                <br><small><?php echo esc_html($emeta['instructor']); ?></small>
                            <?php endif; ?>
                            <?php if ($emeta['place_name'] !== '') : ?>
                                <br><small><?php echo esc_html($emeta['place_name']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html($b->customer_data['name'] ?? ''); ?><br>
                            <a href="mailto:<?php echo esc_attr($b->customer_data['email'] ?? ''); ?>"><?php echo esc_html($b->customer_data['email'] ?? ''); ?></a>
                        </td>
                        <td><?php echo esc_html($b->status); ?></td>
                        <td><?php echo esc_html($b->payment_status); ?></td>
                        <td><?php echo esc_html((string) $b->payment_method); ?></td>
                        <td><?php echo esc_html(number_format_i18n((float) $b->total, 2)); ?> Kč</td>
                        <td><?php echo esc_html((string) $b->company_id); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(array('lkba_booking_action' => 'confirm', 'booking_id' => $b->id, '_wpnonce' => $nonce), $base)); ?>"><?php echo esc_html__('Potvrdit', 'lkba-events'); ?></a>
                            |
                            <a href="<?php echo esc_url(add_query_arg(array('lkba_booking_action' => 'cancel', 'booking_id' => $b->id, '_wpnonce' => $nonce), $base)); ?>"><?php echo esc_html__('Zrušit', 'lkba-events'); ?></a>
                            <?php if (!empty($b->fakturoid_pdf)) : ?>
                                | <a href="<?php echo esc_url(LKBA_Events_Bookings::invoice_download_url((int) $b->id)); ?>"><?php echo esc_html__('Faktura', 'lkba-events'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
