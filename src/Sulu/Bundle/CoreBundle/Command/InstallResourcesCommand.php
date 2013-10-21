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
 * The command to execute an export on the console
 * @package Sulu\Bundle\TranslateBundle\Command
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

        $this->copyr($resourceDir, $webDir);
    }

    protected function copyr($source, $dest)
    {
        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest);
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            if ($dest !== $source.'/'.$entry) {
                $this->copyr($source.'/'.$entry, $dest.'/'.$entry);
            }
        }

        // Clean up
        $dir->close();
        return true;
    }
}
