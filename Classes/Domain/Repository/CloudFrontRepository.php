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

    /**
     * This will list the past X ($maxItems) invalidation items for the given distribution ($distributionId).
     *
     * @param string $distribution The ID of the distributions to fetch invalidations from
     * @param int $maxItems Maximum amount of entries to fetch
     *
     * @return array The response of the webservice
     */
    public function findInvalidationsByDistribution(string $distribution, int $maxItems = 10): array
    {
        return $this->cloudFrontClient->listInvalidations([
            'DistributionId' => htmlentities($distribution),
            'MaxItems' => $maxItems
        ])->toArray();
    }

    /**
     * This will create invalidations of an array of file paths (or a single path) in the given distribution ID.
     *
     * @param string $distribution The ID of the distribution in which the specified item(s) should be invalidated
     * @param string|string[] $items Array of file paths to be invalidated (or a single path)
     *
     * @return array The response of the webservice
     */
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

    /**
     * This will create invalidations of an array of file paths (or a single path) in the given distributions.
     *
     * @param array $distributions The IDs of the distributions in which the specified item(s) should be invalidated
     * @param string|string[] $items Array of file paths to be invalidated (or a single path)
     *
     * @return array An array of responses of the webservice
     */
    public function createBatchInvalidation(array $distributions, $items): array
    {
        $responses = [];

        foreach ($distributions as $distribution) {
            $responses[$distribution] = $this->createInvalidation($distribution, $items);
        }

        return $responses;
    }
}
