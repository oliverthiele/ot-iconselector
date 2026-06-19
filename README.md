# Icon Selector — TYPO3 Backend Icon Picker

Custom backend form element for selecting SVG icons with AJAX search, live preview and a favorites system.

[![TYPO3](https://img.shields.io/badge/TYPO3-13.4-orange.svg)](https://typo3.org/)
[![Packagist Version](https://img.shields.io/packagist/v/oliverthiele/ot-iconselector.svg)](https://packagist.org/packages/oliverthiele/ot-iconselector)
[![PHP](https://img.shields.io/packagist/dependency-v/oliverthiele/ot-iconselector/php.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/oliverthiele/ot-iconselector.svg)](LICENSE)
[![Changelog](https://img.shields.io/badge/Changelog-CHANGELOG.md-blue.svg)](CHANGELOG.md)

---

## Features

- **AJAX icon search** — type-ahead search through thousands of SVG icons with debounced requests
- **SVG preview** — inline SVG rendering in search results and selected state
- **Multi-term search** — space-separated words are combined with AND logic (e.g. "chevron right")
- **Keyboard navigation** — arrow keys, Enter to select, Escape to close
- **Favorites modal** — integrator-defined and personal editor favorites, accessible via a heart button
- **SiteSet configuration** — icon directory, style and favorites configurable per site
- **No vendor lock-in** — works with any SVG icon set (FontAwesome, Bootstrap Icons, custom icons)
- **Non-breaking integration** — stores plain string identifiers, existing values remain compatible

---

## Requirements

| Requirement | Version        |
|-------------|----------------|
| TYPO3       | ^13.4 \| ^14.3 |
| PHP         | >=8.3          |

---

## Installation

```bash
composer require oliverthiele/ot-iconselector
```

Then run the TYPO3 setup:

```bash
vendor/bin/typo3 extension:setup -e ot_iconselector
# or via DDEV:
ddev typo3 extension:setup -e ot_iconselector
```

---

## Configuration

### 1. Include the SiteSet

Add `oliverthiele/ot-iconselector` to your site configuration's dependencies.

### 2. Configure the icon directory

The extension reads the icon directory from the `otIcons.iconDirectory` and `otIcons.defaultIconStyle` site settings (provided by `oliverthiele/ot-icons`). Alternatively, set them directly in TCA:

```php
'icon_identifier' => [
    'config' => [
        'type' => 'input',
        'renderType' => 'otIconSelector',
        'iconDirectory' => 'EXT:my_sitepackage/Resources/Public/Icons/SVG/',
        'iconStyle' => 'solid',
    ],
],
```

### 3. Configure favorites (optional)

Set integrator favorites in your site settings:

```yaml
otIconselector:
  favorites:
    default: 'chevron-right,chevron-left,phone,envelope,download'
    buttons: 'arrow-right,phone,envelope,download'
```

Editors can add personal favorites via the star toggle in search results.

---

## Usage

Register the `otIconSelector` render type on any `type: input` field:

```php
'icon_identifier' => [
    'exclude' => true,
    'label' => 'Icon',
    'config' => [
        'type' => 'input',
        'renderType' => 'otIconSelector',
        // Optional overrides:
        // 'iconDirectory' => 'EXT:my_ext/Resources/Public/Icons/',
        // 'iconStyle' => 'solid',
        // 'maxResults' => 36,
        // 'favoriteGroup' => 'buttons',
    ],
],
```

The field stores a plain string identifier (e.g. `chevron-right`). The database column remains a standard `varchar` — no schema changes required.

For conditional usage (when the extension may not be installed):

```php
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

'icon_identifier' => [
    'config' => [
        'type' => 'input',
        'renderType' => ExtensionManagementUtility::isLoaded('ot_iconselector')
            ? 'otIconSelector'
            : null,
        'size' => 30,
        'max' => 40,
    ],
],
```

---

## License

GPL-2.0-or-later — see [LICENSE](LICENSE)

---

## Author

Oliver Thiele — [oliver-thiele.de](https://www.oliver-thiele.de)
