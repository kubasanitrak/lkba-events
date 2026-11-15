<?php
/**
 * Booking form — legacy teacher-training-form chrome (radio tiles + signup rows).
 *
 * @var int    $event_id
 * @var array  $draft
 * @var array  $events_open
 * @var string $error_msg
 * @var string $support
 * @var int    $privacy
 * @var int    $terms_p
 * @var int    $storno
 * @var array  $payment_methods
 * @var string $draft_method
 * @var array  $methods_by_event
 */

if (!defined('ABSPATH')) {
    exit;
}

$d_name  = $draft['name'] ?? '';
$d_email = $draft['email'] ?? '';
$d_phone = $draft['phone'] ?? '';
$d_notes = $draft['notes'] ?? '';
$d_inv   = isset($draft['invoice']) && is_array($draft['invoice']) ? $draft['invoice'] : array();
$has_inv = trim(($d_inv['company_name'] ?? '') . ($d_inv['street'] ?? '') . ($d_inv['ic'] ?? '')) !== '';
$selected_method = $draft_method !== '' && in_array($draft_method, $payment_methods, true)
    ? $draft_method
    : ($payment_methods[0] ?? 'bank_transfer');
?>
<div class="lkba-events lkba-booking-form signup-form--wrapper padded-content mar-T pad-B">
    <div class="signup-form--submit_message">
        <h2 class="message"><?php echo esc_html__('Rezervace', 'lkba-events'); ?></h2>
    </div>

    <?php if ($error_msg !== '') : ?>
        <div class="signup-form--submit_message pad-B lkba-booking-form__error" role="alert">
            <h2 class="color-red message" style="color:var(--LKBA-red,#c00)"><?php echo esc_html($error_msg); ?></h2>
        </div>
    <?php endif; ?>

    <?php if (!$events_open) : ?>
        <p class="plain"><?php echo esc_html__('Momentálně nelze nic rezervovat.', 'lkba-events'); ?></p>
    <?php else : ?>
        <div class="signup-form-container">
            <form method="post" action="" id="signup-formID" class="signup-form lkba-booking-form__form" accept-charset="UTF-8">
                <?php wp_nonce_field('lkba_events_book', 'lkba_events_book_nonce'); ?>
                <div class="signup-form--inner">

                    <div class="signup-form--inner_row mar-B-0">
                        <label><?php echo esc_html__('Vyberte termín', 'lkba-events'); ?> <span style="color: red;">*</span></label>
                    </div>

                    <div class="signup-form--inner_row flex-row workshop-terms full-W" id="lkba_event_tiles">
                        <?php foreach ($events_open as $post) :
                            $meta     = LKBA_Events_Event::get_meta($post->ID);
                            $date     = LKBA_Events_Event::format_date($post->ID);
                            $hours    = LKBA_Events_Event::format_hours($post->ID);
                            $caption  = LKBA_Events_Event::format_caption_place($post->ID);
                            $place    = $meta['place_name'];
                            $studio   = $meta['place_address'];
                            $show_loc = $place !== '' && stripos($place, 'Praha') === false;
                            $price    = LKBA_Events_Event::get_price($post->ID);
                            ?>
                            <div class="workshop-terms--item">
                                <label class="nice-input radio-input workshop-terms--item_label">
                                    <input class="workshop-id-input" type="radio"
                                           name="lkba_event_id"
                                           id="lkba_event_id_<?php echo esc_attr((string) $post->ID); ?>"
                                           value="<?php echo esc_attr((string) $post->ID); ?>"
                                           <?php checked($event_id, $post->ID); ?>
                                           required>
                                    <span class="checkmark"></span>
                                    <?php if ($show_loc) : ?>
                                        <div class="location">
                                            <p class="minor">
                                                <span class="location_place strong"><?php echo esc_html($place); ?></span><?php
                                                echo $studio !== '' ? ', ' . esc_html($studio) : '';
                                                ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="workshop-terms--item_caption">
                                        <?php if ($date !== '') : ?>
                                            <h2 class="workshop-terms--item_date"><?php echo esc_html($date); ?></h2>
                                        <?php endif; ?>
                                        <?php if ($hours !== '') : ?>
                                            <p class="workshop-terms--item_hour"><?php echo esc_html($hours); ?></p>
                                        <?php endif; ?>
                                        <?php if ($caption !== '') : ?>
                                            <p class="workshop-terms--item_place"><?php echo esc_html($caption); ?></p>
                                        <?php endif; ?>
                                        <p class="workshop-terms--item_hour"><?php echo esc_html(number_format_i18n($price, 0)); ?> Kč</p>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php /* Keep #lkba_event_id for payment-method sync script. */ ?>
                    <input type="hidden" id="lkba_event_id" value="<?php echo esc_attr((string) $event_id); ?>">

                    <div class="signup-form--inner_row flex-col">
                        <label for="lkba_name"><?php echo esc_html__('Jméno a příjmení', 'lkba-events'); ?> <span style="color: red;">*</span></label>
                        <input type="text" name="lkba_name" id="lkba_name" class="required" required value="<?php echo esc_attr($d_name); ?>" placeholder="<?php echo esc_attr__('Jméno Příjmení', 'lkba-events'); ?>">
                    </div>
                    <div class="signup-form--inner_row flex-col">
                        <label for="lkba_email"><?php echo esc_html__('E-mail', 'lkba-events'); ?> <span style="color: red;">*</span></label>
                        <input type="email" name="lkba_email" id="lkba_email" class="required email" required value="<?php echo esc_attr($d_email); ?>" placeholder="email@email.cz">
                    </div>
                    <div class="signup-form--inner_row flex-col">
                        <label for="lkba_phone"><?php echo esc_html__('Telefon', 'lkba-events'); ?></label>
                        <input type="text" name="lkba_phone" id="lkba_phone" value="<?php echo esc_attr($d_phone); ?>" placeholder="+420…">
                    </div>
                    <div class="signup-form--inner_row flex-col">
                        <label for="lkba_notes"><?php echo esc_html__('Poznámka', 'lkba-events'); ?></label>
                        <textarea name="lkba_notes" id="lkba_notes" rows="3"><?php echo esc_textarea($d_notes); ?></textarea>
                    </div>

                    <div class="signup-form--inner_row flex-col">
                        <div class="form-group">
                            <input type="checkbox" class="custom-checkbox" id="lkba_want_invoice" data-group="invoice" value="1" <?php checked($has_inv); ?>>
                            <label for="lkba_want_invoice"><?php echo esc_html__('Chci fakturační údaje', 'lkba-events'); ?></label>
                        </div>
                    </div>
                    <div class="signup-form--inner_row flex-col optional lkba-booking-form__invoice <?php echo $has_inv ? '' : 'rolled-up'; ?>">
                        <label for="lkba_inv_company"><?php echo esc_html__('Firma / jméno', 'lkba-events'); ?></label>
                        <input type="text" name="lkba_inv_company" id="lkba_inv_company" value="<?php echo esc_attr($d_inv['company_name'] ?? ''); ?>">
                    </div>
                    <div class="signup-form--inner_row flex-col optional <?php echo $has_inv ? '' : 'rolled-up'; ?>">
                        <label for="lkba_inv_street"><?php echo esc_html__('Ulice', 'lkba-events'); ?></label>
                        <input type="text" name="lkba_inv_street" id="lkba_inv_street" value="<?php echo esc_attr($d_inv['street'] ?? ''); ?>">
                    </div>
                    <div class="signup-form--inner_row flex-col optional <?php echo $has_inv ? '' : 'rolled-up'; ?>">
                        <label for="lkba_inv_zip"><?php echo esc_html__('PSČ / město', 'lkba-events'); ?></label>
                        <input type="text" name="lkba_inv_zip" id="lkba_inv_zip" placeholder="PSČ" value="<?php echo esc_attr($d_inv['zip'] ?? ''); ?>">
                        <input type="text" name="lkba_inv_city" id="lkba_inv_city" placeholder="<?php echo esc_attr__('Město', 'lkba-events'); ?>" value="<?php echo esc_attr($d_inv['city'] ?? ''); ?>">
                    </div>
                    <div class="signup-form--inner_row flex-col optional <?php echo $has_inv ? '' : 'rolled-up'; ?>">
                        <label for="lkba_inv_ic">IČO / DIČ</label>
                        <input type="text" name="lkba_inv_ic" id="lkba_inv_ic" placeholder="IČO" value="<?php echo esc_attr($d_inv['ic'] ?? ''); ?>">
                        <input type="text" name="lkba_inv_dic" id="lkba_inv_dic" placeholder="DIČ" value="<?php echo esc_attr($d_inv['dic'] ?? ''); ?>">
                    </div>

                    <?php
                    $has_bank = in_array('bank_transfer', $payment_methods, true);
                    $has_card = in_array('comgate', $payment_methods, true);
                    $both_pay = $has_bank && $has_card;
                    $pay_assets = LKBA_EVENTS_PLUGIN_URL . 'public/assets/payment/';
                    ?>
                    <div class="signup-form--inner_row flex-col lkba-booking-form__payment" id="lkba_payment_methods">
                        <div class="">
                            <h2 class="section-title"><?php echo esc_html__('Zvolte způsob platby', 'lkba-events'); ?></h2>
                        </div>
                        <div class="lkba-pay-tiles" id="lkba_events_pay_tiles"
                             <?php echo $both_pay ? '' : 'hidden'; ?>>
                            <label class="lkba-pay-tile<?php echo $selected_method === 'bank_transfer' ? ' is-selected' : ''; ?>" for="lkba_events_pay_bank">
                                <input class="lkba-pay-tile__input" type="radio"
                                       name="lkba_payment_method"
                                       id="lkba_events_pay_bank"
                                       value="bank_transfer"
                                       <?php checked($selected_method, 'bank_transfer'); ?>
                                       <?php echo $both_pay ? '' : 'disabled'; ?>>
                                <span class="lkba-pay-tile__face">
                                    <span class="lkba-pay-tile__icon lkba-pay-tile__icon--bank" aria-hidden="true"
                                          style="--lkba-pay-icon:url('<?php echo esc_url($pay_assets . 'icon-bank.png'); ?>')"></span>
                                    <span class="lkba-pay-tile__label"><?php echo esc_html__('Bankovní převod', 'lkba-events'); ?></span>
                                </span>
                            </label>
                            <label class="lkba-pay-tile<?php echo $selected_method === 'comgate' ? ' is-selected' : ''; ?>" for="lkba_events_pay_card">
                                <input class="lkba-pay-tile__input" type="radio"
                                       name="lkba_payment_method"
                                       id="lkba_events_pay_card"
                                       value="comgate"
                                       <?php checked($selected_method, 'comgate'); ?>
                                       <?php echo $both_pay ? '' : 'disabled'; ?>>
                                <span class="lkba-pay-tile__face">
                                    <span class="lkba-pay-tile__icon lkba-pay-tile__icon--card" aria-hidden="true">
                                        <img src="<?php echo esc_url($pay_assets . 'icon-card.png'); ?>" alt="" width="48" height="38" decoding="async">
                                    </span>
                                    <span class="lkba-pay-tile__label"><?php echo esc_html__('Platební karta', 'lkba-events'); ?></span>
                                </span>
                            </label>
                        </div>
                        <p class="lkba-booking-form__pay-bank plain" id="lkba_events_pay_bank_only" <?php echo (!$both_pay && $has_bank) ? '' : 'hidden'; ?>>
                            <?php echo esc_html__('Platba převodem (QR / proforma)', 'lkba-events'); ?>
                        </p>
                        <p class="lkba-booking-form__pay-card plain" id="lkba_events_pay_card_only" <?php echo (!$both_pay && $has_card) ? '' : 'hidden'; ?>>
                            <?php echo esc_html__('Platba kartou (Comgate)', 'lkba-events'); ?>
                        </p>
                        <?php if (!$both_pay) : ?>
                            <input type="hidden" name="lkba_payment_method" id="lkba_events_payment_method_fallback"
                                   value="<?php echo esc_attr($has_card && !$has_bank ? 'comgate' : 'bank_transfer'); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="signup-form--inner_row flex-col lkba-booking-form__legal">
                        <div class="form-group">
                            <input type="checkbox" name="lkba_legal" id="lkba_legal" class="custom-checkbox" value="1" required>
                            <label for="lkba_legal">
                                <?php echo esc_html__('Souhlasím s podmínkami a zpracováním osobních údajů', 'lkba-events'); ?>
                                <?php if ($terms_p) : ?>
                                    · <a class="strong" href="<?php echo esc_url(get_permalink($terms_p)); ?>" target="_blank" rel="noopener"><?php echo esc_html__('podmínky', 'lkba-events'); ?></a>
                                <?php endif; ?>
                                <?php if ($privacy) : ?>
                                    · <a class="strong" href="<?php echo esc_url(get_permalink($privacy)); ?>" target="_blank" rel="noopener"><?php echo esc_html__('soukromí', 'lkba-events'); ?></a>
                                <?php endif; ?>
                                <?php if ($storno) : ?>
                                    · <a class="strong" href="<?php echo esc_url(get_permalink($storno)); ?>" target="_blank" rel="noopener"><?php echo esc_html__('storno', 'lkba-events'); ?></a>
                                <?php endif; ?>
                                <span style="color: red;">*</span>
                            </label>
                        </div>
                    </div>

                    <div class="signup-form--inner_row flex-col">
                        <button type="submit" id="submit-btnID" class="btn submit-btn form-submit btn-major lkba-booking-form__submit">
                            <?php echo esc_html__('Rezervovat', 'lkba-events'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <script>
        (function () {
            var map = <?php echo wp_json_encode($methods_by_event); ?>;
            var eventHidden = document.getElementById('lkba_event_id');
            var radios = document.querySelectorAll('input[name="lkba_event_id"]');
            var tiles = document.getElementById('lkba_events_pay_tiles');
            var bankRadio = document.getElementById('lkba_events_pay_bank');
            var cardRadio = document.getElementById('lkba_events_pay_card');
            var bankOnly = document.getElementById('lkba_events_pay_bank_only');
            var cardOnly = document.getElementById('lkba_events_pay_card_only');
            var fallback = document.getElementById('lkba_events_payment_method_fallback');
            var invToggle = document.getElementById('lkba_want_invoice');
            if (!tiles || !bankRadio || !cardRadio) return;

            function selectedId() {
                for (var i = 0; i < radios.length; i++) {
                    if (radios[i].checked) return radios[i].value;
                }
                return eventHidden ? eventHidden.value : '';
            }

            function ensureFallback(method) {
                if (!fallback) {
                    fallback = document.createElement('input');
                    fallback.type = 'hidden';
                    fallback.name = 'lkba_payment_method';
                    fallback.id = 'lkba_events_payment_method_fallback';
                    tiles.parentNode.appendChild(fallback);
                }
                fallback.disabled = false;
                fallback.value = method;
            }

            function clearFallback() {
                if (fallback) {
                    fallback.disabled = true;
                    if (fallback.parentNode) fallback.parentNode.removeChild(fallback);
                    fallback = null;
                }
            }

            function paintSelected() {
                var payRadios = tiles.querySelectorAll('.lkba-pay-tile__input');
                for (var i = 0; i < payRadios.length; i++) {
                    var tile = payRadios[i].closest('.lkba-pay-tile');
                    if (tile) tile.classList.toggle('is-selected', !!payRadios[i].checked && !payRadios[i].disabled);
                }
            }

            function selectPay(method) {
                if (tiles.hidden) return;
                if (method === 'comgate' && !cardRadio.disabled) {
                    cardRadio.checked = true;
                    bankRadio.checked = false;
                } else if (!bankRadio.disabled) {
                    bankRadio.checked = true;
                    cardRadio.checked = false;
                }
                paintSelected();
            }

            function sync() {
                var id = selectedId();
                if (eventHidden) eventHidden.value = id;
                var methods = map[String(id)] || ['bank_transfer'];
                var hasBank = methods.indexOf('bank_transfer') !== -1;
                var hasCard = methods.indexOf('comgate') !== -1;
                var both = hasBank && hasCard;
                tiles.hidden = !both;
                if (bankOnly) bankOnly.hidden = !(!both && hasBank);
                if (cardOnly) cardOnly.hidden = !(!both && hasCard);
                bankRadio.disabled = !both;
                cardRadio.disabled = !both;
                if (both) {
                    clearFallback();
                    if (!bankRadio.checked && !cardRadio.checked) {
                        bankRadio.checked = true;
                    }
                } else if (hasCard && !hasBank) {
                    bankRadio.checked = false;
                    cardRadio.checked = false;
                    ensureFallback('comgate');
                } else {
                    bankRadio.checked = false;
                    cardRadio.checked = false;
                    ensureFallback('bank_transfer');
                }
                paintSelected();
            }

            tiles.addEventListener('click', function (e) {
                var tile = e.target.closest('.lkba-pay-tile');
                if (!tile || tiles.hidden) return;
                var input = tile.querySelector('.lkba-pay-tile__input');
                if (!input || input.disabled) return;
                e.preventDefault();
                selectPay(input.value);
            });
            tiles.addEventListener('change', paintSelected);
            bankRadio.addEventListener('change', paintSelected);
            cardRadio.addEventListener('change', paintSelected);

            for (var i = 0; i < radios.length; i++) {
                radios[i].addEventListener('change', sync);
            }
            sync();
            paintSelected();

            if (invToggle) {
                invToggle.addEventListener('change', function () {
                    document.querySelectorAll('.lkba-booking-form .optional').forEach(function (el) {
                        el.classList.toggle('rolled-up', !invToggle.checked);
                    });
                });
            }
        })();
        </script>
    <?php endif; ?>

    <p class="lkba-booking-form__support plain">
        <?php echo esc_html__('Podpora:', 'lkba-events'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a>
    </p>
</div>
