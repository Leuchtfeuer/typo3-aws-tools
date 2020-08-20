<?php

$GLOBALS['SiteConfiguration']['site_language']['columns']['awstools_cdn_enabled'] = [
    'label' => sprintf(
        'LLL:EXT:%s/Resources/Private/Language/Database.xlf:site_configuration.awstools_cdn_enabled',
        \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
    ),
    'onChange' => 'reload',
    'config' => [
        'type' => 'check',
        'renderType' => 'checkboxToggle',
        'items' => [
            [
                0 => '',
                1 => '',
            ],
        ],
    ],
];

$GLOBALS['SiteConfiguration']['site_language']['columns']['awstools_cdn_host'] = [
    'label' => sprintf(
        'LLL:EXT:%s/Resources/Private/Language/Database.xlf:site_configuration.awstools_cdn_host',
        \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
    ),
    'displayCond' => 'FIELD:awstools_cdn_enabled:=:1',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site_language']['palettes']['awstools-cdn'] = [
    'label' => sprintf(
        'LLL:EXT:%s/Resources/Private/Language/Database.xlf:site_configuration.label',
        \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
    ),
    'showitem' => 'awstools_cdn_enabled, --linebreak--, awstools_cdn_host'
];

$GLOBALS['SiteConfiguration']['site_language']['types']['1']['showitem'] = str_replace(
    'flag,',
    'flag, --palette--;;awstools-cdn,',
    $GLOBALS['SiteConfiguration']['site_language']['types']['1']['showitem']
);
