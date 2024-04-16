<?php

declare(strict_types=1);

return [
    'awstools' => [
        'parent' => 'tools',
        'position' => 'bottom',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/page/awstools',
        'labels' => 'LLL:EXT:aws_tools/Resources/Private/Language/locallang_mod.xlf',
        'iconIdentifier' => 'awstoolsModuleIcon',
        'extensionName' => 'AwsTools',
        'controllerActions' => [
            \Leuchtfeuer\AwsTools\Controller\InvalidationController::class => [
                'index',
                'invalidate'
            ],
        ],
    ],
];
