<?php

declare(strict_types=1);

/*
 * This file is part of the "AWS Tools" extension.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\AwsTools\Command;

use Aws\CloudFront\Exception\CloudFrontException;
use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\AwsTools\Exception\InvalidInvalidationPathException;
use Leuchtfeuer\AwsTools\Service\InvalidationPathNormalizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InvalidationCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly CloudFrontRepository $cloudFrontRepository,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly InvalidationPathNormalizer $pathNormalizer,
    ) {
        parent::__construct();
    }

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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $paths = array_map($this->pathNormalizer->normalize(...), (array)$input->getArgument('paths'));
        } catch (InvalidInvalidationPathException $exception) {
            $this->io->error($exception->getMessage());

            return Command::FAILURE;
        }

        foreach ($this->extensionConfiguration->getCloudFrontDistributions() as $distribution) {
            try {
                $result = $this->cloudFrontRepository->createInvalidation($distribution, $paths);
                $paths = implode(', ', $result['Invalidation']['InvalidationBatch']['Paths']['Items'] ?? []);
                $this->io->success(sprintf('Marked path "%s" as invalid for distribution "%s".', urldecode($paths), $distribution));
            } catch (CloudFrontException $exception) {
                // @extensionScannerIgnoreLine
                $this->io->error(sprintf('%s:%s', $exception->getAwsErrorCode(), $exception->getAwsErrorMessage()));
            }
        }

        return 0;
    }
}
