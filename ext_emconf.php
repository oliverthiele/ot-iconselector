<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Icon Selector',
    'description' => 'TYPO3 backend form element for selecting FontAwesome icons with AJAX search and SVG preview',
    'category' => 'be',
    'author' => 'Oliver Thiele',
    'author_email' => 'mail@oliver-thiele.de',
    'state' => 'alpha',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
