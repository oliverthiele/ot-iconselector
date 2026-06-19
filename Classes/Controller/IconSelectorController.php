<?php

declare(strict_types=1);

namespace OliverThiele\OtIconselector\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class IconSelectorController
{
    private const MAX_RESULTS_LIMIT = 100;
    private const MIN_SEARCH_LENGTH = 2;

    private ?FrontendInterface $cache = null;

    private function getCache(): FrontendInterface
    {
        if ($this->cache === null) {
            $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('ot_iconselector');
        }
        return $this->cache;
    }

    public function searchAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $search = trim((string)($queryParams['search'] ?? ''));
        $iconDirectory = (string)($queryParams['iconDirectory'] ?? '');
        $iconStyle = (string)($queryParams['iconStyle'] ?? 'solid');
        $maxResults = min((int)($queryParams['maxResults'] ?? 36), self::MAX_RESULTS_LIMIT);

        if (mb_strlen($search) < self::MIN_SEARCH_LENGTH) {
            return new JsonResponse([]);
        }

        if (!$this->isValidIconDirectory($iconDirectory)) {
            return new JsonResponse([], 400);
        }

        $basePath = GeneralUtility::getFileAbsFileName($iconDirectory);
        if ($basePath === '' || !is_dir($basePath)) {
            return new JsonResponse([], 400);
        }

        $directoryPath = rtrim($basePath, '/') . '/' . $iconStyle;
        if (!is_dir($directoryPath)) {
            return new JsonResponse([], 400);
        }

        $iconIndex = $this->getIconIndex($iconDirectory, $iconStyle, $directoryPath);

        $searchTerms = array_filter(
            array_map('trim', explode(' ', mb_strtolower($search)))
        );

        $matches = [];
        foreach ($iconIndex as $identifier) {
            $identifierLower = mb_strtolower($identifier);
            $matchesAll = true;
            foreach ($searchTerms as $term) {
                if (!str_contains($identifierLower, $term)) {
                    $matchesAll = false;
                    break;
                }
            }
            if ($matchesAll) {
                $matches[] = $identifier;
            }
            if (count($matches) >= $maxResults) {
                break;
            }
        }

        $results = [];
        foreach ($matches as $identifier) {
            $filePath = $directoryPath . '/' . $identifier . '.svg';
            $svgContent = file_get_contents($filePath);
            if ($svgContent === false) {
                continue;
            }

            $results[] = [
                'identifier' => $identifier,
                'svg' => $this->prepareSvgForPreview($svgContent),
            ];
        }

        return new JsonResponse($results);
    }

    /** @return list<string> */
    private function getIconIndex(string $iconDirectory, string $iconStyle, string $directoryPath): array
    {
        $cacheKey = 'index_' . md5($iconDirectory . '/' . $iconStyle);

        $cached = $this->getCache()->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $identifiers = [];
        $files = scandir($directoryPath);
        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            if (!str_ends_with($file, '.svg')) {
                continue;
            }
            $identifiers[] = substr($file, 0, -4);
        }

        sort($identifiers, SORT_STRING);
        $this->getCache()->set($cacheKey, $identifiers);

        return $identifiers;
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

    public function toggleFavoriteAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $identifier = trim((string)($body['identifier'] ?? ''));
        $group = trim((string)($body['group'] ?? 'default'));

        if ($identifier === '') {
            return new JsonResponse(['success' => false], 400);
        }

        $backendUser = $GLOBALS['BE_USER'];
        $favorites = $this->getUserFavorites($backendUser, $group);

        if (in_array($identifier, $favorites, true)) {
            $favorites = array_values(array_filter($favorites, static fn(string $item): bool => $item !== $identifier));
        } else {
            $favorites[] = $identifier;
        }

        $backendUser->uc['ot_iconselector']['favorites'][$group] = $favorites;
        $backendUser->writeUC();

        return new JsonResponse(['success' => true, 'favorites' => $favorites]);
    }

    /** @return list<string> */
    public function getUserFavorites(object $backendUser, string $group): array
    {
        $stored = $backendUser->uc['ot_iconselector']['favorites'][$group] ?? [];
        if (!is_array($stored)) {
            return [];
        }
        return array_values(array_filter($stored, static fn(mixed $value): bool => is_string($value) && $value !== ''));
    }

    private function isValidIconDirectory(string $iconDirectory): bool
    {
        if ($iconDirectory === '') {
            return false;
        }

        if (str_starts_with($iconDirectory, 'EXT:')) {
            return true;
        }

        return false;
    }
}
