<?php

declare(strict_types=1);

return [
    'frontend' => [
        'awstools/content-replace' => [
            'target' => \Leuchtfeuer\AwsTools\Middleware\ContentReplaceMiddleware::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers',
            ],
            'before' => [
                'typo3/cms-frontend/output-compression',
            ],
        ],
    ],
];
