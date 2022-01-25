<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Sfdbutf8',
    'tools',
    'utf8convert',
    '',
    [
        \StefanFroemken\Sfdbutf8\Controller\Utf8Controller::class => 'show, dbCheck, convert'
    ],
    [
        'access' => 'user,group',
        'iconIdentifier' => 'sfdbutf8-backend-module',
        'labels' => 'LLL:EXT:sfdbutf8/Resources/Private/Language/locallang_mod.xlf',
    ]
);
