# lkba-events

Teacher trainings and event bookings for Lenka Krejčová Barre Academy.

Requires [`lkba-payments`](https://github.com/kubasanitrak/lkba-payments) ≥ 0.2.0.

Repository: https://github.com/kubasanitrak/lkba-events

## Status

Phase 3 — bank + Comgate card on events; my bookings + invoice download. Vouchers = Phase 4.

## Shortcodes

| Shortcode | Purpose |
|-----------|---------|
| `[lkba_event_categories]` | Category list |
| `[lkba_events_list book_page="/cesta-k-formi/"]` | Open + past events |
| `[lkba_event_book]` | Booking form + bank recap / Comgate redirect |
| `[lkba_payment_success]` | Comgate return (paid / pending) |
| `[lkba_payment_failed]` | Comgate return (cancel / fail) |
| `[lkba_my_bookings]` | Account overview + invoice PDF + mailto cancel/reschedule |

Support / cancel mailto: `rezervace@barreacademy.cz` (settings).

## Releasing

1. Bump `Version` and `LKBA_EVENTS_VERSION`.
2. Update `CHANGELOG.md`.
3. Tag `v0.3.0`.
