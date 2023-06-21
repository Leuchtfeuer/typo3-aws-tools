<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\Factory;

use Aws\CloudFront\CloudFrontClient;
use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Leuchtfeuer\AwsTools\Constants;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CloudFrontFactory implements SingletonInterface
{
    private static ?CloudFrontClient $_client = null;

    public static function getClient(): CloudFrontClient
    {
        if (static::$_client instanceof CloudFrontClient) {
            return static::$_client;
        }

        /**
         * @var ExtensionConfiguration $extensionConfiguration
         */
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $credentials = new Credentials($extensionConfiguration->getAccessKeyId(), $extensionConfiguration->getSecretAccessKey());

        static::$_client = new CloudFrontClient([
            'credentials' => CredentialProvider::fromCredentials($credentials),
            'version' => Constants::VERSION,
            'region' => $extensionConfiguration->getRegion(),
            'validation' => false
        ]);

        return static::$_client;
    }
}
