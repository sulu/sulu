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

use Sulu\Component\Content\Export\WebspaceInterface;
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
        $this->setName('sulu:webspaces:export')
            ->addArgument('file', InputArgument::REQUIRED, 'test.xliff')
            ->addOption('webspace', 'w', InputOption::VALUE_REQUIRED)
            ->addOption('locale', 'l', InputOption::VALUE_REQUIRED)
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, '', '1.2.xliff')
            ->setDescription('Export webspace');
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

        /** @var WebspaceInterface $webspaceExporter */
        $webspaceExporter = $this->getContainer()->get('sulu_content.import.webspace');

        $webspaceExporter->export(
            $webspaceKey,
            $locale,
            $filePath,
            $format
        );

        return 0;
    }
}
