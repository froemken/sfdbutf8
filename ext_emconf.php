<?php

/**
 * Extension Manager/Repository config file for ext "sfdbutf8".
 *
 * Auto generated 14-01-2016 14:26
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 */

$EM_CONF[$_EXTKEY] = array(
    'title' => 'DB_UTF8',
    'description' => 'With this extension you can set all tables and fields to UTF-8 collation (no converting)',
    'category' => 'be',
    'shy' => 1,
    'version' => '1.1.0',
    'priority' => '',
    'loadOrder' => '',
    'module' => 'mod1',
    'state' => 'stable',
    'uploadfolder' => 1,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 1,
    'lockType' => '',
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'author_company' => '',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => array(
        'depends' => array(
            'typo3' => '6.2.4-8.99.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
);
