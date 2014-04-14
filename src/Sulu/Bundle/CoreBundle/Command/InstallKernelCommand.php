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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Bundle\TranslateBundle\Translate\Export;

/**
 * Create <app-dir>/<kernel>/cache and <app-dir>/<kernel>/logs
 *
 * @package Sulu\Bundle\CoreBundle\Command
 */
class InstallKernelCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:install:kernel')
            ->setDescription('Install Sulu App Kernel')
            ->addArgument(
                'app-dir',
                InputArgument::REQUIRED
            )
            ->addArgument(
                'kernel',
                InputArgument::REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $appDir = $input->getArgument('app-dir');
        $kernel = $input->getArgument('kernel');

        $kernelDir = $appDir . '/' . $kernel;

        $cacheDir = $kernelDir . '/cache';
        $logDir = $kernelDir . '/logs';

        $output->writeln('Create Logs and Cache dir in ' . $kernelDir);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir);
        } else {
            $output->writeLn('Directory ' . $cacheDir . ' already exists');
        }
        if (!is_dir($logDir)) {
            mkdir($logDir);
        } else {
            $output->writeLn('Directory ' . $logDir . ' already exists');
        }
    }
}
