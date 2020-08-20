<?php

return [
    'awstools_invalidate' => [
        'path' => '/awstools/invalidate',
        'target' => \Leuchtfeuer\AwsTools\Controller\BackendController::class . '::invalidateAction'
    ]
];
