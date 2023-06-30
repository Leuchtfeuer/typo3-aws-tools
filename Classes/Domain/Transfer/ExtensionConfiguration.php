<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\Domain\Transfer;

use Leuchtfeuer\AwsTools\Constants;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration as CoreExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionConfiguration implements SingletonInterface
{
    private $cloudFrontDistributions = '';
    private $accessKeyId = '';
    private $secretAccessKey = '';
    private $region = '';

    public function __construct()
    {
        try {
            $configuration = GeneralUtility::makeInstance(CoreExtensionConfiguration::class)->get(Constants::EXTENSION_KEY);
        } catch (ExtensionConfigurationExtensionNotConfiguredException $exception) {
            $configuration = [];
        }

        if ($configuration) {
            $this->setPropertiesFromConfiguration($configuration);
        }
    }

    protected function setPropertiesFromConfiguration(array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function getCloudFrontDistributions(): array
    {
        return GeneralUtility::trimExplode(',', $this->cloudFrontDistributions, true);
    }

    public function getAccessKeyId(): string
    {
        return $this->accessKeyId;
    }

    public function getSecretAccessKey(): string
    {
        return $this->secretAccessKey;
    }

    public function getRegion(): string
    {
        return $this->region;
    }
}
