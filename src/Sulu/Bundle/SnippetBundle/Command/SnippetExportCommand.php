<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export snippet translation by given language.
 */
class SnippetExportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('sulu:snippet:export');
        $this->setDescription('Export snippet translations from given language.');
        $this->addArgument('target', InputArgument::REQUIRED, 'Target for export (e.g. export_de.xliff)');
        $this->addArgument('locale', InputArgument::REQUIRED, 'Locale to export (e.g. de, en)');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');
        $locale = $input->getArgument('locale');
        $exporter = $this->getContainer()->get('sulu_snippet.export.snippet');
        $file = $exporter->export($locale, $output, '1.2.xliff');

        file_put_contents($target, $file);
    }
}
