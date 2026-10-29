<?php
/**
 * Events list partial.
 *
 * @var array  $split
 * @var string $book_page
 * @var int    $privacy
 * @var int    $terms
 * @var int    $storno
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="lkba-events lkba-events-list">
    <section class="lkba-events-list__open">
        <h2><?php echo esc_html__('Otevřené termíny', 'lkba-events'); ?></h2>
        <?php if (empty($split['open'])) : ?>
            <p><?php echo esc_html__('Momentálně nejsou otevřené žádné termíny.', 'lkba-events'); ?></p>
        <?php else : ?>
            <div class="lkba-events-grid">
                <?php foreach ($split['open'] as $post) : ?>
                    <?php
                    $meta   = LKBA_Events_Event::get_meta($post->ID);
                    $status = LKBA_Events_Event::get_booking_status($post->ID);
                    $price  = LKBA_Events_Event::get_price($post->ID);
                    $when   = LKBA_Events_Event::format_when($post->ID);
                    $book_url = $book_page !== ''
                        ? add_query_arg('event_id', $post->ID, $book_page)
                        : add_query_arg('event_id', $post->ID);
                    ?>
                    <article class="lkba-event-card status-<?php echo esc_attr($status); ?>">
                        <h3><?php echo esc_html(get_the_title($post)); ?></h3>
                        <?php if ($when !== '') : ?>
                            <p class="lkba-event-card__when"><?php echo esc_html($when); ?></p>
                        <?php endif; ?>
                        <?php if ($meta['place_name'] !== '') : ?>
                            <p class="lkba-event-card__place"><?php echo esc_html($meta['place_name']); ?></p>
                        <?php endif; ?>
                        <?php if ($meta['instructor'] !== '') : ?>
                            <p class="lkba-event-card__instructor"><?php echo esc_html($meta['instructor']); ?></p>
                        <?php endif; ?>
                        <p class="lkba-event-card__meta">
                            <?php echo esc_html(sprintf(
                                /* translators: 1: used, 2: capacity */
                                __('Kapacita: %1$d / %2$d', 'lkba-events'),
                                LKBA_Events_Bookings::count_active_for_event($post->ID),
                                (int) $meta['capacity']
                            )); ?>
                            · <?php echo esc_html(number_format_i18n($price, 0)); ?> Kč
                            · <span class="lkba-event-card__status"><?php echo esc_html($status); ?></span>
                        </p>
                        <?php if ($status === 'open') : ?>
                            <p><a class="lkba-event-card__cta" href="<?php echo esc_url($book_url); ?>"><?php echo esc_html__('Chci se přihlásit', 'lkba-events'); ?></a></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="lkba-events-list__past">
        <h2><?php echo esc_html__('Proběhlé termíny', 'lkba-events'); ?></h2>
        <?php if (empty($split['past'])) : ?>
            <p><?php echo esc_html__('Zatím žádné proběhlé termíny.', 'lkba-events'); ?></p>
        <?php else : ?>
            <ul>
                <?php foreach ($split['past'] as $post) : ?>
                    <li>
                        <strong><?php echo esc_html(get_the_title($post)); ?></strong>
                        — <?php echo esc_html(LKBA_Events_Event::format_when($post->ID)); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <p class="lkba-events-list__legal">
        <?php if ($terms) : ?>
            <a href="<?php echo esc_url(get_permalink($terms)); ?>"><?php echo esc_html__('Obchodní podmínky', 'lkba-events'); ?></a>
        <?php endif; ?>
        <?php if ($privacy) : ?>
            · <a href="<?php echo esc_url(get_permalink($privacy)); ?>"><?php echo esc_html__('Ochrana soukromí', 'lkba-events'); ?></a>
        <?php endif; ?>
        <?php if ($storno) : ?>
            · <a href="<?php echo esc_url(get_permalink($storno)); ?>"><?php echo esc_html__('Storno podmínky', 'lkba-events'); ?></a>
        <?php endif; ?>
    </p>
</div>
