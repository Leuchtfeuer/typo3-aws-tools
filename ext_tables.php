<?php
defined('TYPO3') or die('Access denied.');

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

        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/AwsTools/CloudFrontInvalidationModule');

    }, \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
);
