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

use Sulu\Component\Snippet\Export\SnippetExportInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export snippet translation by given language.
 */
class SnippetExportCommand extends Command
{
    /**
     * @var SnippetExportInterface
     */
    private $snippetExporter;

    /**
     * SnippetExportCommand constructor.
     *
     * @param SnippetExportInterface $snippetExporter
     */
    public function __construct(SnippetExportInterface $snippetExporter)
    {
        $this->snippetExporter = $snippetExporter;
        parent::__construct('sulu:snippet:export');
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
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
        $file = $this->snippetExporter->export($locale, $output, '1.2.xliff');

        file_put_contents($target, $file);
    }
}
