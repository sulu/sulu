<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Command;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

/**
 * @internal no backwards compatibility promise is given for this class it can be removed, moved or changed at any time
 *           it still save to add `sulu:admin:validate-build` to your CI and sciprts. The command and its arguments
 *           are under the backards compatibility promise.
 */
#[AsCommand(
    name: 'sulu:admin:validate-build',
    description: 'Validate that the admin build version is the same version as the installed sulu package.',
)]
final class ValidateBuildCommand extends Command
{
    public function __construct(
        private Packages $assets,
        private string $projectDir,
        private string $suluVersion,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jsAdminBuildVersion = $this->getJavascriptBuildVersion($io);

        if (null === $jsAdminBuildVersion) {
            $io->error('Invalid JS Admin build version.');

            return self::FAILURE;
        }

        if ($jsAdminBuildVersion !== $this->suluVersion) {
            $io->error(<<<TEXT
                Version missmatch:
                Admin bundle version: {$jsAdminBuildVersion}
                Sulu Backend version: {$this->suluVersion}
                TEXT);

            return self::FAILURE;
        }

        $io->success('JS Build is up to date.');

        return self::SUCCESS;
    }

    private function getJavascriptBuildVersion(SymfonyStyle $io): ?string
    {
        $filePath = $this->projectDir . '/public' . $this->assets->getUrl('main.js', 'sulu_admin');

        if (!\file_exists($filePath)) {
            $io->error('Couldn\'t find JS Admin build path: ' . $filePath);
        } else {
            $io->info('JS Admin build path: ' . $filePath);
        }

        $content = \file_get_contents($filePath);
        Assert::string($content, 'Unable to read JS Admin build file');

        $matches = [];
        Assert::integer(
            \preg_match(
                pattern: '#JavaScript build version: ([_a-zA-Z0-9.\/@-]+)\\\\n\\\\n#',
                subject: $content,
                matches: $matches,
            ),
            'Invalid regex for finding the version'
        );

        $version = $matches[1] ?? null;
        if (!\is_string($version)) {
            return null;
        }

        return $version;
    }
}
