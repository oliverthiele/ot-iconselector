<?php

declare(strict_types=1);

use OliverThiele\OtIconselector\Controller\IconSelectorController;

return [
    'ot_iconselector_search' => [
        'path' => '/ot-iconselector/search',
        'methods' => ['GET'],
        'target' => IconSelectorController::class . '::searchAction',
    ],
    'ot_iconselector_toggle_favorite' => [
        'path' => '/ot-iconselector/toggle-favorite',
        'methods' => ['POST'],
        'target' => IconSelectorController::class . '::toggleFavoriteAction',
    ],
];
