<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Component\Content\Import\WebspaceInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export a webspace in a specific format.
 */
class WebspaceImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:webspaces:import')
            ->addArgument('file', InputArgument::REQUIRED, 'test.xliff')
            ->addOption('webspace', 'w', InputOption::VALUE_REQUIRED)
            ->addOption('locale', 'l', InputOption::VALUE_REQUIRED)
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, '', '1.2.xliff')
            ->addOption('uuid', 'u', InputOption::VALUE_REQUIRED)
            ->setDescription('Import webspace');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webspaceKey = $input->getOption('webspace');
        $filePath = $input->getArgument('file');
        if (!strpos($filePath, '/') === 0) {
            $filePath = getcwd() . '/' . $filePath;
        }
        $locale = $input->getOption('locale');
        $format = $input->getOption('format');
        $uuid = $input->getOption('uuid');

        /** @var WebspaceInterface $webspaceImporter */
        $webspaceImporter = $this->getContainer()->get('sulu_content.import.webspace');

        list($count, $fails, $successes, $failed) = $webspaceImporter->import(
            $webspaceKey,
            $locale,
            $filePath,
            $format,
            $uuid
        );

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(sprintf('<info>Imported %s/%s</info>', $successes, $count));
        }

        return $fails;
    }
}
