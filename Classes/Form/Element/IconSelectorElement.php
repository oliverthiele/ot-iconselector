<?php

declare(strict_types=1);

namespace OliverThiele\OtIconselector\Form\Element;

use OliverThiele\OtIconselector\Controller\IconSelectorController;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

final class IconSelectorElement extends AbstractFormElement
{
    /** @return array<string, mixed> */
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $fieldConfig = $parameterArray['fieldConf']['config'];
        $fieldName = $parameterArray['itemFormElName'];
        $fieldId = StringUtility::getUniqueId('formengine-ot-iconselector-');
        $currentValue = trim((string)($parameterArray['itemFormElValue'] ?? ''));

        $iconDirectory = (string)($fieldConfig['iconDirectory'] ?? '');
        $iconStyle = (string)($fieldConfig['iconStyle'] ?? '');

        if ($iconDirectory === '' || $iconStyle === '') {
            $siteSettings = $this->resolveSiteSettings();
            if ($iconDirectory === '') {
                $iconDirectory = $siteSettings['iconDirectory'];
            }
            if ($iconStyle === '') {
                $iconStyle = $siteSettings['iconStyle'];
            }
        }

        $maxResults = (int)($fieldConfig['maxResults'] ?? 36);
        $favoriteGroup = (string)($fieldConfig['favoriteGroup'] ?? 'default');

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $ajaxUrl = (string)$uriBuilder->buildUriFromRoute('ajax_ot_iconselector_search');
        $favoriteUrl = (string)$uriBuilder->buildUriFromRoute('ajax_ot_iconselector_toggle_favorite');

        $integratorFavorites = $this->getIntegratorFavorites($favoriteGroup);
        $userFavorites = $this->getUserFavorites($favoriteGroup);
        $allFavoriteIdentifiers = array_unique(array_merge($integratorFavorites, $userFavorites));
        $hasFavorites = $allFavoriteIdentifiers !== [];

        $renderedLabel = $this->renderLabel($fieldId);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $result = $this->mergeChildReturnIntoExistingResult($result, $fieldInformationResult, false);

        $selectedHtml = '';
        if ($currentValue !== '') {
            $selectedHtml = $this->buildSelectedPreview($currentValue, $iconDirectory, $iconStyle);
        }

        $html = [];
        $html[] = $renderedLabel;
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] = '<div class="form-control-wrap">';

        $html[] = '<typo3-ot-icon-selector';
        $html[] = ' data-ajax-url="' . htmlspecialchars($ajaxUrl) . '"';
        $html[] = ' data-favorite-url="' . htmlspecialchars($favoriteUrl) . '"';
        $html[] = ' data-icon-directory="' . htmlspecialchars($iconDirectory) . '"';
        $html[] = ' data-icon-style="' . htmlspecialchars($iconStyle) . '"';
        $html[] = ' data-max-results="' . $maxResults . '"';
        $html[] = ' data-field-id="' . htmlspecialchars($fieldId) . '"';
        $html[] = ' data-favorite-group="' . htmlspecialchars($favoriteGroup) . '"';
        $html[] = ' data-integrator-favorites="' . htmlspecialchars(implode(',', $integratorFavorites)) . '"';
        $html[] = ' data-user-favorites="' . htmlspecialchars(implode(',', $userFavorites)) . '"';
        $html[] = '>';

        $html[] = '<div class="ot-iconselector-selected" id="' . htmlspecialchars($fieldId) . '-selected">';
        $html[] = $selectedHtml;
        $html[] = '</div>';

        $inputGroupDisplay = $currentValue !== '' ? 'none' : 'flex';
        $html[] = '<div class="ot-iconselector-input-group" style="display:' . $inputGroupDisplay . ';gap:8px">';
        if ($hasFavorites) {
            $html[] = '<button type="button" class="btn btn-default ot-iconselector-favorites-btn" title="Favoriten anzeigen">';
            $html[] = '<typo3-backend-icon identifier="actions-heart" size="small"></typo3-backend-icon>';
            $html[] = '</button>';
        }
        $html[] = '<input type="text" class="form-control ot-iconselector-search" placeholder="Icon suchen..." autocomplete="off">';
        $html[] = '</div>';

        $html[] = '<div class="ot-iconselector-grid" style="display:none"></div>';

        $html[] = '<input type="hidden" class="ot-iconselector-value" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($currentValue) . '">';

        $html[] = '</typo3-ot-icon-selector>';

        $html[] = '</div>';
        $html[] = '</div>';

        $result['html'] = implode(LF, $html);
        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@oliverthiele/ot-iconselector/IconSelector.js'
        );

        return $result;
    }

    private function buildSelectedPreview(string $identifier, string $iconDirectory, string $iconStyle): string
    {
        $svgContent = $this->readSvgFile($identifier, $iconDirectory, $iconStyle);

        $html = '<div class="d-flex align-items-center gap-2 p-2 border rounded">';
        $html .= '<div class="ot-iconselector-preview-svg" style="width:32px;height:32px;flex-shrink:0">';
        $html .= $svgContent;
        $html .= '</div>';
        $html .= '<span class="ot-iconselector-identifier-label flex-grow-1">' . htmlspecialchars($identifier) . '</span>';
        $html .= '<button type="button" class="btn btn-default btn-sm ot-iconselector-remove" title="Entfernen">';
        $html .= '<typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>';
        $html .= '</button>';
        $html .= '</div>';

        return $html;
    }

    private function readSvgFile(string $identifier, string $iconDirectory, string $iconStyle): string
    {
        $basePath = GeneralUtility::getFileAbsFileName($iconDirectory);
        if ($basePath === '') {
            return '';
        }

        $filePath = rtrim($basePath, '/') . '/' . $iconStyle . '/' . $identifier . '.svg';
        if (!file_exists($filePath)) {
            return '';
        }

        $svgContent = file_get_contents($filePath);
        if ($svgContent === false) {
            return '';
        }

        return $this->prepareSvgForPreview($svgContent);
    }

    private function prepareSvgForPreview(string $svgContent): string
    {
        $svgContent = preg_replace('/<\?xml[^>]*\?>/', '', $svgContent) ?? $svgContent;
        $svgContent = preg_replace('/<!--.*?-->/s', '', $svgContent) ?? $svgContent;

        if (!str_contains($svgContent, 'width="') && !str_contains($svgContent, 'style="')) {
            $svgContent = str_replace('<svg ', '<svg style="width:100%;height:100%" ', $svgContent);
        } else {
            $svgContent = preg_replace('/\bwidth="[^"]*"/', 'width="100%"', $svgContent) ?? $svgContent;
            $svgContent = preg_replace('/\bheight="[^"]*"/', 'height="100%"', $svgContent) ?? $svgContent;
        }

        return trim($svgContent);
    }

    /** @return list<string> */
    private function getIntegratorFavorites(string $group): array
    {
        $site = $this->resolveSite();
        if (!$site instanceof Site) {
            return [];
        }

        $setting = (string)($site->getSettings()->get('otIconselector.favorites.' . $group) ?? '');
        if ($setting === '') {
            $setting = (string)($site->getSettings()->get('otIconselector.favorites.default') ?? '');
        }
        if ($setting === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $setting))
        ));
    }

    /** @return list<string> */
    private function getUserFavorites(string $group): array
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if ($backendUser === null) {
            return [];
        }

        $controller = GeneralUtility::makeInstance(IconSelectorController::class);
        return $controller->getUserFavorites($backendUser, $group);
    }

    /** @return array{iconDirectory: string, iconStyle: string} */
    private function resolveSiteSettings(): array
    {
        $site = $this->resolveSite();

        $iconDirectory = '';
        $iconStyle = 'solid';

        if ($site instanceof Site) {
            $settings = $site->getSettings();
            $iconDirectory = (string)($settings->get('otIcons.iconDirectory') ?? '');
            $resolvedStyle = (string)($settings->get('otIcons.defaultIconStyle') ?? '');
            if ($resolvedStyle !== '') {
                $iconStyle = $resolvedStyle;
            }
        }

        return [
            'iconDirectory' => $iconDirectory,
            'iconStyle' => $iconStyle,
        ];
    }

    private function resolveSite(): ?Site
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $site = $request?->getAttribute('site');
        if ($site instanceof Site) {
            return $site;
        }

        $effectivePid = (int)($this->data['effectivePid'] ?? 0);
        if ($effectivePid > 0) {
            try {
                return GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($effectivePid);
            } catch (\Exception) {
            }
        }

        return null;
    }
}
