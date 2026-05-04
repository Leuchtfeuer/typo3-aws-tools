<?php

declare(strict_types=1);

return [
    'awstools_invalidate' => [
        'path' => '/awstools/invalidate',
        'target' => \Leuchtfeuer\AwsTools\Controller\BackendController::class . '::invalidateAction'
    ]
];
