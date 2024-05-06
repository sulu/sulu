<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Snippet\Export;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\Export\Export;
use Sulu\Component\Export\Manager\ExportManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

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
        Environment $templating,
        SnippetRepository $snippetManager,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        ExportManagerInterface $exportManager,
        $formatFilePaths
    ) {
        parent::__construct($templating, $documentManager, $documentInspector, $exportManager, $formatFilePaths);

        $this->snippetManager = $snippetManager;
        $this->output = new NullOutput();
    }

    public function export($locale, $output = null, $format = '1.2.xliff')
    {
        if (!$locale) {
            throw new \Exception(\sprintf('Invalid parameters for export "%s"', $locale));
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

        $this->output->writeln('<info>Loading Data…</info>');

        $progress = new ProgressBar($this->output, \count($snippets));
        $progress->start();

        /*
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
        $where = [];

        // only snippets
        $where[] = '[jcr:mixinTypes] = "sulu:snippet"';

        // filter by webspace key
        $where[] = 'ISDESCENDANTNODE("/cmf/snippets")';

        // filter by locale
        $where[] = \sprintf(
            '[i18n:%s-title] IS NOT NULL',
            $this->exportLocale
        );

        $query = $this->documentManager->createQuery(
            'SELECT * FROM [nt:unstructured] AS a WHERE ' . \implode(' AND ', $where) . ' ORDER BY [jcr:path] ASC',
            $this->exportLocale
        );

        return $query->execute();
    }
}
