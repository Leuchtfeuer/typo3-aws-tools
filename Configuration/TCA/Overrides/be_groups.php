<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    function (): void {
        \Leuchtfeuer\AwsTools\TCA\FilePermissions::extendFilePermissions('be_groups');
    }
);

