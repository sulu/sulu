<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Reference\Command;

use Jackalope\Query\Row;
use Jackalope\Query\RowIterator;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\DocumentReferenceProviderInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DocumentReferencesCommand extends Command
{
    protected static $defaultName = 'sulu:document:update-references';

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var array<string, DocumentReferenceProviderInterface>
     */
    private array $documentReferenceProviders;

    /**
     * @param iterable<DocumentReferenceProviderInterface> $documentReferenceProviders
     */
    public function __construct(
        SessionInterface $session,
        WebspaceManagerInterface $webspaceManager,
        DocumentManagerInterface $documentManager,
        iterable $documentReferenceProviders
    ) {
        parent::__construct();

        $this->session = $session;
        $this->webspaceManager = $webspaceManager;
        $this->documentManager = $documentManager;
        $this->documentReferenceProviders = $documentReferenceProviders instanceof \Traversable ? \iterator_to_array($documentReferenceProviders) : $documentReferenceProviders;
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::REQUIRED, 'Which type of the document to search')
            ->setDescription('Type of the documents, e.g. "article", "page" or "snippet"');
        $this->addArgument('webspaceKey', InputArgument::REQUIRED, 'Which webspace to search')
            ->setDescription('Webspace is used for the localizations and pages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');

        $provider = $this->documentReferenceProviders[$type] ?? null;

        if (!$provider) {
            throw new \Exception(\sprintf('No provider found for type "%s"', $type));
        }

        $sql2 = \sprintf("SELECT jcr:uuid FROM [nt:unstructured] as document WHERE document.[jcr:mixinTypes] = 'sulu:%s'", $type);

        $webspaceKey = $input->getArgument('webspaceKey');
        /** @var Webspace $webspace */
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $sql2 .= \sprintf(" AND (isdescendantnode(document, '/cmf/%s/contents') OR issamenode(document, '/cmf/%s/contents'))", $webspaceKey, $webspaceKey);

        $queryManager = $this->session->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        $queryResult = $query->execute();

        $ui = new SymfonyStyle($input, $output);
        $ui->info('Updating references for documents');
        /** @var RowIterator $rows */
        $rows = $queryResult->getRows();
        $ui->progressStart(\count($webspace->getAllLocalizations()) * $rows->count());
        foreach ($webspace->getAllLocalizations() as $localization) {
            $locale = $localization->getLocale();
            /** @var Row $row */
            foreach ($rows as $row) {
                /** @var string $uuid */
                $uuid = $row->getValue('jcr:uuid');
                /** @var StructureBehavior|null $document */
                $document = $this->documentManager->find($uuid, $locale);

                if (!$document) {
                    continue;
                }

                $provider->updateReferences($document, $locale);
                $ui->progressAdvance();
            }
        }

        $ui->success('Finished');

        return Command::SUCCESS;
    }
}
