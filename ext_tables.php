<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey): void {

        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@leuchtfeuer/aws-tools/cloud-front-invalidation-module');

    }, \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
);
