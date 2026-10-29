# lkba-events

Teacher trainings and event bookings for Lenka Krejčová Barre Academy.

Requires [`lkba-payments`](https://github.com/kubasanitrak/lkba-payments).

Repository: https://github.com/kubasanitrak/lkba-events

## Status

Phase 0 scaffold — CPTs, schema, shortcodes and admin shells only.

## Shortcodes (stubs)

| Shortcode | Purpose |
|-----------|---------|
| `[lkba_event_categories]` | Category overview |
| `[lkba_events_list]` | Open + past events listing |
| `[lkba_event_book]` | Booking form / recap |
| `[lkba_my_bookings]` | Account overview |

## Releasing

1. Bump `Version` and `LKBA_EVENTS_VERSION` in `lkba-events.php`.
2. Add a `## [x.y.z]` section to `CHANGELOG.md`.
3. Push tag `v0.1.0` matching the header.
