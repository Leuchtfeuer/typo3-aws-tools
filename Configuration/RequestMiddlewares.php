<?php

declare(strict_types=1);

use Leuchtfeuer\AwsTools\Middleware\ContentReplaceMiddleware;
use Leuchtfeuer\AwsTools\Middleware\RequestContextMiddleware;

return [
    'frontend' => [
        'awstools/request-context' => [
            'target' => RequestContextMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/eid',
            ],
        ],
        'awstools/content-replace' => [
            'target' => ContentReplaceMiddleware::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers',
            ],
        ],
    ],
];
