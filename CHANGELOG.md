# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.0.0] — 2026-07-31

### Added

- Backend labels of the form element are translatable. The new
  `Resources/Private/Language/locallang_be.xlf` (plus German translation)
  provides the favourites button, the search placeholder and the remove
  button. The JavaScript module receives its two labels as data attributes,
  since it cannot read XLF files itself

### Changed

- **Breaking:** Drop TYPO3 v13 support, require TYPO3 `^14.3`
- **Breaking:** Raise the PHP minimum to `>=8.4`
- Migrate the site set labels from XLIFF 1.2 to XLIFF 2.0. Unit identifiers and
  all translations are unchanged
- Drop the hardcoded `version` field from `composer.json`. The version now
  comes from the git tag, as Packagist expects

### Fixed

- The form element rendered German strings regardless of the backend
  language ("Favoriten anzeigen", "Icon suchen...", "Entfernen")

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

[Unreleased]: https://github.com/oliverthiele/ot-iconselector/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/oliverthiele/ot-iconselector/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/oliverthiele/ot-iconselector/releases/tag/v1.0.0