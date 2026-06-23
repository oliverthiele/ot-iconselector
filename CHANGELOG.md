# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2026-06-23

### Added

- Custom TCA render type `otIconSelector` for `type: input` fields
- AJAX search endpoint with cached icon index and multi-term AND filtering
- Inline SVG preview in search results and selected state
- Keyboard navigation (arrow keys, Enter, Escape) in search grid
- Favorites system with integrator-defined (SiteSet) and personal (be_users.uc) favorites
- Favorites modal accessible via star button
- SiteSet configuration for default and button-context favorites (`otIconselector.favorites.*`)
- Brands directory fallback in search and SVG preview
- Marquee-scroll label on hover for truncated icon names
- Dynamic favorites button visibility (appears after first favorite is added)
- Observe `icon_style` field changes to update search directory and preview live
- Read `icon_style` from record for initial preview rendering

### Fixed

- Reset to SiteSet default icon style when "Default" is selected in `icon_style` dropdown

[Unreleased]: https://github.com/oliverthiele/ot-iconselector/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/oliverthiele/ot-iconselector/releases/tag/v1.0.0