<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Command;

use Sulu\Component\Util\DirectoryUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Bundle\TranslateBundle\Translate\Export;

/**
 * Copy <app-dir>/Resources/public to <web-dir>
 *
 * @package Sulu\Bundle\CoreBundle\Command
 */
class InstallResourcesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:install:resources')
            ->setDescription('Install Sulu App Resources')
            ->addArgument(
                'app-dir',
                InputArgument::REQUIRED
            )
            ->addArgument(
                'web-dir',
                InputArgument::REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $appDir = $input->getArgument('app-dir');
        $webDir = $input->getArgument('web-dir');

        $resourceDir = $appDir . '/Resources/public';

        $output->writeln('Copy ' . $resourceDir . ' to ' . $webDir);

        DirectoryUtils::copyRecursive($resourceDir, $webDir);
    }
}
