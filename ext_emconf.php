<?php

$EM_CONF['ot_iconselector'] = [
    'title' => 'Icon Selector',
    'description' => 'TYPO3 backend form element for selecting SVG icons with AJAX search, live preview and favorites.',
    'category' => 'be',
    'author' => 'Oliver Thiele',
    'author_email' => 'mail@oliver-thiele.de',
    'author_company' => 'Web Development Oliver Thiele',
    'state' => 'stable',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.99.99',
            'php' => '8.4.0-8.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
