<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $extensionKey,
            'tools',
            'awstools',
            'bottom',
            [
                \Leuchtfeuer\AwsTools\Controller\InvalidationController::class => 'index, invalidate'
            ], [
                'access' => 'admin',
                'icon' => sprintf('EXT:%s/Resources/Public/Icons/Module.svg', $extensionKey),
                'labels' => sprintf('LLL:EXT:%s/Resources/Private/Language/locallang_mod.xlf', $extensionKey)
            ]
        );

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'][$extensionKey]
            = \Leuchtfeuer\AwsTools\Hook\EditIconsHook::class;

        if (TYPO3_MODE === 'BE') {
            $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/AwsTools/CloudFrontInvalidationModule');
        }

    }, \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
);
