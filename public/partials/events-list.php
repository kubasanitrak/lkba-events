<?php
/**
 * Events list — legacy teacher-training-form workshop-terms grid.
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

/**
 * @param WP_Post $post
 * @param string  $status open|full|closed|…
 * @param string  $book_url
 * @param bool    $interactive
 */
$render_tile = static function ($post, $status, $book_url, $interactive) {
    $meta     = LKBA_Events_Event::get_meta($post->ID);
    $date     = LKBA_Events_Event::format_date($post->ID);
    $hours    = LKBA_Events_Event::format_hours($post->ID);
    $title    = get_the_title($post);
    $place    = $meta['place_name'];
    $address  = $meta['place_address'];
    $show_loc = $place !== '' && stripos($place, 'Praha') === false;
    $capacity_label = '';
    if ($status === 'full') {
        $capacity_label = __('plně obsazen', 'lkba-events');
    } elseif ($status !== 'open' && !$interactive) {
        $capacity_label = __('proběhlý termín', 'lkba-events');
    }

    $item_class = 'workshop-terms--item lkba-event-card status-' . esc_attr($status);
    if (!$interactive) {
        $item_class .= ' pointer-none';
        $style = 'opacity:.75;';
    } else {
        $style = '';
    }
    ?>
    <div class="<?php echo esc_attr($item_class); ?>"<?php echo $style !== '' ? ' style="' . esc_attr($style) . '"' : ''; ?>>
        <?php if ($interactive && $status === 'open') : ?>
            <a class="nice-input radio-input workshop-terms--item_label lkba-event-card__cta" href="<?php echo esc_url($book_url); ?>">
        <?php else : ?>
            <div class="nice-input radio-input workshop-terms--item_label">
        <?php endif; ?>

            <?php if ($capacity_label !== '') : ?>
                <p class="workshop-terms--item_capacity caps"><?php echo esc_html($capacity_label); ?></p>
            <?php endif; ?>

            <?php if ($show_loc) : ?>
                <div class="location">
                    <p class="minor">
                        <span class="location_place strong"><?php echo esc_html($place); ?></span><?php
                        echo $address !== '' ? ', ' . esc_html($address) : '';
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="workshop-terms--item_caption">
                <?php if ($date !== '') : ?>
                    <h2 class="workshop-terms--item_date lkba-event-card__when"><?php echo esc_html($date); ?></h2>
                <?php endif; ?>
                <?php if ($hours !== '') : ?>
                    <p class="workshop-terms--item_hour"><?php echo esc_html($hours); ?></p>
                <?php endif; ?>
                <p class="workshop-terms--item_place lkba-event-card__place"><?php echo esc_html($title); ?></p>
                <?php if ($meta['instructor'] !== '') : ?>
                    <p class="lkba-event-card__instructor"><?php echo esc_html($meta['instructor']); ?></p>
                <?php endif; ?>
            </div>

        <?php if ($interactive && $status === 'open') : ?>
            </a>
        <?php else : ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
};
?>
<div class="lkba-events lkba-events-list signup-form--wrapper padded-content mar-T pad-B">
    <div class="signup-form--submit_message">
        <h2 class="message"><?php echo esc_html__('Vyberte si termín', 'lkba-events'); ?></h2>
    </div>

    <section class="lkba-events-list__open signup-form-container">
        <div class="signup-form--inner">
            <div class="signup-form--inner_row mar-B-0">
                <label><?php echo esc_html__('Otevřené termíny', 'lkba-events'); ?></label>
            </div>
            <?php if (empty($split['open'])) : ?>
                <p class="plain"><?php echo esc_html__('Momentálně nejsou otevřené žádné termíny.', 'lkba-events'); ?></p>
            <?php else : ?>
                <div class="signup-form--inner_row flex-row workshop-terms full-W lkba-events-grid">
                    <?php foreach ($split['open'] as $post) :
                        $status   = LKBA_Events_Event::get_booking_status($post->ID);
                        $book_url = $book_page !== ''
                            ? add_query_arg('event_id', $post->ID, $book_page)
                            : add_query_arg('event_id', $post->ID);
                        $render_tile($post, $status, $book_url, $status === 'open');
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="lkba-events-list__past signup-form-container mar-T">
        <div class="signup-form--inner">
            <div class="signup-form--inner_row mar-B-0">
                <label><?php echo esc_html__('Proběhlé termíny', 'lkba-events'); ?></label>
            </div>
            <?php if (empty($split['past'])) : ?>
                <p class="plain"><?php echo esc_html__('Zatím žádné proběhlé termíny.', 'lkba-events'); ?></p>
            <?php else : ?>
                <div class="signup-form--inner_row flex-row workshop-terms full-W">
                    <?php foreach ($split['past'] as $post) :
                        $render_tile($post, 'closed', '', false);
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <p class="lkba-events-list__legal plain">
        <?php if ($terms) : ?>
            <a class="strong" href="<?php echo esc_url(get_permalink($terms)); ?>"><?php echo esc_html__('Obchodní podmínky', 'lkba-events'); ?></a>
        <?php endif; ?>
        <?php if ($privacy) : ?>
            · <a class="strong" href="<?php echo esc_url(get_permalink($privacy)); ?>"><?php echo esc_html__('Ochrana soukromí', 'lkba-events'); ?></a>
        <?php endif; ?>
        <?php if ($storno) : ?>
            · <a class="strong" href="<?php echo esc_url(get_permalink($storno)); ?>"><?php echo esc_html__('Storno podmínky', 'lkba-events'); ?></a>
        <?php endif; ?>
    </p>
</div>
