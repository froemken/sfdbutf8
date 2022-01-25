<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'DB_UTF8',
    'description' => 'With this extension you can set all tables and columns to UTF-8 collation (no converting)',
    'category' => 'be',
    'version' => '1.2.4',
    'module' => 'mod1',
    'state' => 'stable',
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.29-10.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
