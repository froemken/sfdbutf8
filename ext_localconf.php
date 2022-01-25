<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(static function() {
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Imaging\IconRegistry::class
    );
    $iconRegistry->registerIcon(
        'sfdbutf8-backend-module',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:sfdbutf8/Resources/Public/Icons/Extension.svg']
    );
});
