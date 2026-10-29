# lkba-events

Teacher trainings and event bookings for Lenka Krejčová Barre Academy.

Requires [`lkba-payments`](https://github.com/kubasanitrak/lkba-payments) ≥ 0.2.0.

Repository: https://github.com/kubasanitrak/lkba-events

## Status

Phase 2 — bank-first booking MVP (Comgate on events = Phase 3).

## Shortcodes

| Shortcode | Purpose |
|-----------|---------|
| `[lkba_event_categories]` | Category list |
| `[lkba_events_list book_page="/cesta-k-formi/"]` | Open + past events |
| `[lkba_event_book]` | Booking form + bank recap (`?event_id=` / `?lkba_book=ok&recap=`) |
| `[lkba_my_bookings]` | Account overview (after paid user exists) |

Support / cancel mailto: `rezervace@barreacademy.cz` (settings).

## Releasing

1. Bump `Version` and `LKBA_EVENTS_VERSION`.
2. Update `CHANGELOG.md`.
3. Tag `v0.2.0`.
