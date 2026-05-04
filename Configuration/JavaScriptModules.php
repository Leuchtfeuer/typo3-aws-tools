<?php

declare(strict_types=1);

return [
    'dependencies' => ['backend'],
    'imports' => [
        '@leuchtfeuer/aws-tools/cloud-front-invalidation-module' => 'EXT:aws_tools/Resources/Public/JavaScript/CloudFrontInvalidationModule.js',
    ],
];
