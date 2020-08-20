<?php

$GLOBALS['SiteConfiguration']['site_language']['columns']['awstools_cdn_enabled'] = [
    'label' => 'Enable CDN for this domain',
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

$GLOBALS['SiteConfiguration']['site_language']['columns']['awstools_cdn_hostname'] = [
    'label' => 'CDN Hostname (e.g. cdn.site.org)',
    'displayCond' => 'FIELD:awstools_cdn_enabled:=:1',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site_language']['palettes']['awstools-cdn'] = [
    'label' => 'Content Delivery Network',
    'showitem' => 'awstools_cdn_enabled, --linebreak--, awstools_cdn_hostname'
];

$GLOBALS['SiteConfiguration']['site_language']['types']['1']['showitem'] = str_replace(
    'flag,',
    'flag, --palette--;;awstools-cdn,',
    $GLOBALS['SiteConfiguration']['site_language']['types']['1']['showitem']
);
