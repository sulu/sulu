<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Snippet\Export;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\Export\Export;
use Sulu\Component\Export\Manager\ExportManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Templating\EngineInterface;

/**
 * Export Snippet by given locale to xliff file.
 */
class SnippetExport extends Export implements SnippetExportInterface
{
    /**
     * @var SnippetRepository
     */
    private $snippetManager;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(
        EngineInterface $templating,
        SnippetRepository $snippetManager,
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        ExportManagerInterface $exportManager,
        $formatFilePaths
    ) {
        parent::__construct($templating, $documentManager, $documentInspector, $exportManager, $formatFilePaths);

        $this->snippetManager = $snippetManager;
        $this->output = new NullOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function export($locale, $output = null, $format = '1.2.xliff')
    {
        if (!$locale) {
            throw new \Exception(sprintf('Invalid parameters for export "%s"', $locale));
        }

        $this->exportLocale = $locale;
        $this->output = $output;
        $this->format = $format;

        if (null === $this->output) {
            $this->output = new NullOutput();
        }

        return $this->templating->render(
            $this->getTemplate($format),
            $this->getExportData()
        );
    }

    /**
     * Returns all data that we need to create a xliff-File.
     *
     * @return array
     *
     * @throws DocumentManagerException
     */
    public function getExportData()
    {
        $snippets = $this->getSnippets();
        $snippetsData = [];

        $progress = new ProgressBar($this->output, count($snippets));
        $progress->start();

        /**
         * @var SnippetDocument
         */
        foreach ($snippets as $snippet) {
            $contentData = $this->getContentData($snippet, $this->exportLocale);

            $snippetsData[] = [
                'uuid' => $snippet->getUuid(),
                'locale' => $snippet->getLocale(),
                'content' => $contentData,
            ];

            $progress->advance();
        }

        $progress->finish();

        return [
            'locale' => $this->exportLocale,
            'format' => $this->format,
            'snippetData' => $snippetsData,
        ];
    }

    /**
     * Returns all Snippets.
     *
     * @return SnippetDocument[]
     */
    protected function getSnippets()
    {
        $this->output->writeln('<info>Loading Data…</info>');

        return $this->snippetManager->getSnippets($this->exportLocale);
    }
}
