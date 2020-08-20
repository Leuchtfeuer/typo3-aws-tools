<?php

$EM_CONF[\Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY] = [
    'title' => 'AWS Toolbox',
    'description' => 'AWS related stuff',
    'version' => '0.1.0',
    'category' => 'misc',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.6-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'state' => 'beta',
    'uploadfolder' => false,
    'clearCacheOnLoad' => false,
    'author' => 'Florian Wessels',
    'author_email' => 'dev@Leuchtfeuer.com',
    'author_company' => 'Leuchtfeuer Digital Marketing',
    'autoload' => [
        'psr-4' => [
            'Leuchtfeuer\\AwsTools\\' => 'Classes',
        ],
    ],
];
