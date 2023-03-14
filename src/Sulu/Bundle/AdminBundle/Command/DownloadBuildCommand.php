<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

@trigger_deprecation(
    'sulu/sulu',
    '2.0',
    'The "%s" class is deprecated, use "%s" instead.',
    DownloadBuildCommand::class,
    UpdateBuildCommand::class
);

/**
 * @deprecated use the "UpdateBuildCommand" class instead
 */
class DownloadBuildCommand extends Command
{
    protected static $defaultName = 'sulu:admin:download-build';

    protected function configure()
    {
        $this->setDescription('Downloads the current admin application build from the sulu/skeleton repository.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = $this->getApplication()->find('sulu:admin:update-build');

        return $command->run($input, $output);
    }
}
