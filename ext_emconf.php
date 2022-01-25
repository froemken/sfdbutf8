<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Alter DB to UTF-8',
    'description' => 'With this extension you can set all tables and columns to UTF-8 collation (no converting)',
    'category' => 'be',
    'version' => '2.0.0',
    'module' => 'mod1',
    'state' => 'stable',
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-8.1.0',
            'typo3' => '9.5.29-11.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
