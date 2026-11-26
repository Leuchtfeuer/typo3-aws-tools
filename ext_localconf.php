<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

ExtensionManagementUtility::addTypoScriptSetup(
    '@import \'EXT:aws_tools/Configuration/TypoScript/setup.typoscript\''
);
