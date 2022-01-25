<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'StefanFroemken.sfdbutf8',
    'tools',
    'utf8convert',
    '',
    [
        'Utf8' => 'show, dbCheck, convert'
    ],
    [
        'access' => 'user,group',
        'iconIdentifier' => 'sfdbutf8-backend-module',
        'labels' => 'LLL:EXT:sfdbutf8/Resources/Private/Language/locallang_mod.xlf',
    ]
);
