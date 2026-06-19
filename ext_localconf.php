<?php

declare(strict_types=1);

use OliverThiele\OtIconselector\Form\Element\IconSelectorElement;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1750300000] = [
    'nodeName' => 'otIconSelector',
    'priority' => 40,
    'class' => IconSelectorElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['ot_iconselector'] ??= [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
];
