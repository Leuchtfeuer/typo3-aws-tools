<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\Command;

use Aws\CloudFront\Exception\CloudFrontException;
use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InvalidationCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var CloudFrontRepository
     */
    private $cloudFrontRepository;

    private $distributions = [];

    protected function configure(): void
    {
        $this
            ->setDescription('Invalidates entries in Amazon CloudFront')
            ->setHelp('This command invalidates assets of given paths in Amazon CloudFront.')
            ->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The paths to invalidate');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->cloudFrontRepository = GeneralUtility::makeInstance(CloudFrontRepository::class);
        $this->distributions = GeneralUtility::makeInstance(ExtensionConfiguration::class)->getCloudFrontDistributions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paths = $input->getArgument('paths');

        foreach ($this->distributions as $distribution) {
            try {
                $result = $this->cloudFrontRepository->createInvalidation($distribution, $paths);
                $paths = implode(', ', $result['Invalidation']['InvalidationBatch']['Paths']['Items'] ?? []);
                $this->io->success(sprintf('Marked path "%s" as invalid for distribution "%s".', urldecode($paths), $distribution));
            } catch (CloudFrontException $exception) {
                $this->io->error(sprintf('%s:%s', $exception->getAwsErrorCode(), $exception->getAwsErrorMessage()));
            }
        }

        return 0;
    }
}
