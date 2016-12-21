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
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\Export\Export;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\Templating\EngineInterface;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Export\Manager\ExportManagerInterface;

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
     * @var ExportManagerInterface
     */
    protected $exportManager;

    /**
     * @var String
     */
    protected $exportLocale;

    /**
     * @var String
     */
    protected $format;

    /**
     * @var string[]
     */
    protected $formatFilePaths;

    /**
     * Snippet constructor.
     *
     * @param EngineInterface $templating
     * @param SnippetRepository $snippetManager
     * @param DocumentManager $documentManager
     * @param DocumentInspector $documentInspector
     * @param array $formatFilePaths
     */
    public function __construct(
        EngineInterface $templating,
        SnippetRepository $snippetManager,
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        ExportManagerInterface $exportManager,
        $formatFilePaths
    ) {
        $this->templating = $templating;
        $this->snippetManager = $snippetManager;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->exportManager = $exportManager;
        $this->formatFilePaths = $formatFilePaths;
    }

    /**
     * {@inheritdoc}
     */
    public function export(
        $locale,
        $output = null,
        $format = '1.2.xliff'
    ) {
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
     */
    protected function getExportData()
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
     * @return SnippetBridge[]
     */
    protected function getSnippets()
    {
        $this->output->writeln('<info>Loading Data…</info>');

        return $this->snippetManager->getSnippets($this->exportLocale);
    }
}