# Changelog

## [0.3.6] — 2026-10-30

### Added

- `[lkba_event_book]` optional `category` / `cat` (shortcode or `?category=` / `?cat=`) filters term radios when no `event_id`.

## [0.3.5] — 2026-10-30

### Fixed

- `[lkba_events_list book_page]` resolves via `home_url()` / page slug (leading `/` is not domain-root). Works on subdirectory installs.

## [0.3.4] — 2026-10-30

### Changed

- `[lkba_events_list]` + booking form chrome aligned with legacy `teacher-training-form` (`workshop-terms` tiles, `signup-form` rows). Payment/recap logic unchanged.

## [0.3.3] — 2026-10-30

### Added

- `[lkba_events_list]` attributes `category` / `cat` (taxonomy slug, e.g. `teacher-training`).

## [0.3.2] — 2026-10-29

### Fixed

- Restore **Kategorie** / **Místa** under LKBA Události via explicit `edit-tags.php` submenus (taxonomies do not attach to a custom CPT parent slug). CPT still listed once.

## [0.3.1] — 2026-10-29

### Fixed

- Duplicate admin submenu “Události” (manual `add_submenu_page` + CPT `show_in_menu`). CPT registers once under LKBA Události.

### Added

- Admin **Migrace**: import workshop catalog from `includes/config.php`, non-voucher `lkba_goods` + `lk_barre` registrations into events/bookings (holding events when occurrence id missing).

## [0.3.0] — 2026-10-29

### Added

- Phase 3 card + account: Comgate when event∩company allow card; payment success/fail pages + shortcodes; invoice download for owners; hardened `[lkba_my_bookings]` with cancel/reschedule mailto `rezervace@barreacademy.cz`.
- Admin: email attendees of a picked event; stats filters by instructor & place; booking list filters by event.
- Settings: payment success / failed page IDs for Comgate return URLs.
- Hook `lkba_payments_fakturoid_invoice_after_card` → store Fakturoid meta (invoice only after Comgate paid).

## [0.2.0] — 2026-10-29

### Added

- Phase 2 bank-first booking MVP: event meta, capacity/12h close, bookings CRUD, shortcodes (list/book/recap/my bookings), E- payments registry, Fakturoid proforma → webhook → confirmed + emails, guest checkout with WP user on paid, admin meta boxes and bookings list.
- Default support/From email: `rezervace@barreacademy.cz`.

## [0.1.0] — 2026-10-29

### Added

- Phase 0 scaffold: bootstrap with `lkba-payments` dependency, CPT/taxonomy stubs, bookings table, shortcode and admin menu stubs, release pipeline.
