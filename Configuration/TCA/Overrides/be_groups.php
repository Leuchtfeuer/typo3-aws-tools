<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        \Leuchtfeuer\AwsTools\TCA\FilePermissions::extendFilePermissions('be_groups');
    }
);

