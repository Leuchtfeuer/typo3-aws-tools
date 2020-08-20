<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\Domain\Repository;

use Aws\CloudFront\CloudFrontClient;
use Leuchtfeuer\AwsTools\Constants;
use TYPO3\CMS\Core\Http\Uri;

class CloudFrontRepository
{
    protected $cloudFrontClient;

    public function __construct(CloudFrontClient $cloudFrontClient)
    {
        $this->cloudFrontClient = $cloudFrontClient;
    }

    public function findInvalidationsByDistribution(string $distribution, int $maxItems = 10): array
    {
        return $this->cloudFrontClient->listInvalidations([
            'DistributionId' => htmlentities($distribution),
            'MaxItems' => $maxItems
        ])->toArray();
    }

    public function createInvalidation(string $distribution, $items): array
    {
        if (!is_array($items)) {
            $items = [$items];
        }

        array_walk($items, function (&$item) {
            $item = '/' . ltrim((new Uri($item))->getPath(), '/');
            $item = trim($item);
        });

        return $this->cloudFrontClient->createInvalidation([
            'DistributionId' => htmlentities($distribution),
            'InvalidationBatch' => [
                'CallerReference' => uniqid(Constants::UNIQUE_ID_PREFIX),
                'Paths' => [
                    'Quantity' => count($items),
                    'Items' => $items,
                ],
            ],
        ])->toArray();
    }
}
