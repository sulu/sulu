<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Command;

use Jackalope\Query\QueryManager;
use Jackalope\Session;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copy internationalized properties from one locale to another.
 */
class SnippetLocaleCopyCommand extends ContainerAwareCommand
{
    /**
     * The namespace for languages.
     *
     * @var string
     */
    private $languageNamespace;

    /**
     * @var SnippetRepository
     */
    private $snippetRepository;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var QueryManager
     */
    private $queryManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('sulu:snippet:locale-copy');
        $this->setDescription('Copy snippet nodes from one locale to another');
        $this->setHelp(
            <<<'EOT'
            The <info>%command.name%</info> command copies the internationalized properties matching <info>srcLocale</info>
to <info>destLocale</info> on all snippet nodes from a specific type.

    %command.full_name% de en --dry-run

You can overwrite existing values using the <info>overwrite</info> option:

    %command.full_name% de en --overwrite --dry-run

Remove the <info>dry-run</info> option to actually persist the changes.
EOT
        );
        $this->addArgument('srcLocale', InputArgument::REQUIRED, 'Locale to copy from (e.g. de)');
        $this->addArgument('destLocale', InputArgument::REQUIRED, 'Locale to copy to (e.g. en)');
        $this->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite existing locales');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persist changes');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $srcLocale = $input->getArgument('srcLocale');
        $destLocale = $input->getArgument('destLocale');
        $overwrite = $input->getOption('overwrite');
        $dryRun = $input->getOption('dry-run');

        $this->session = $this->getContainer()->get('doctrine_phpcr.session');
        $this->queryManager = $this->session->getWorkspace()->getQueryManager();
        $this->languageNamespace = $this->getContainer()->getParameter('sulu.content.language.namespace');
        $this->snippetRepository = $this->getContainer()->get('sulu_snippet.repository');
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $this->output = $output;

        $this->copyDocuments($srcLocale, $destLocale, $overwrite);
        $this->documentManager->flush();

        if (false === $dryRun) {
            $this->output->writeln('<info>Saving ...</info>');
            $this->session->save();
            $this->output->writeln('<info>Done</info>');
        } else {
            $this->output->writeln('<info>Dry run complete</info>');
        }
    }

    private function copyDocuments($srcLocale, $destLocale, $overwrite)
    {
        foreach ($this->snippetRepository->getSnippets($srcLocale) as $document) {
            $this->copyDocument($srcLocale, $destLocale, $document, $overwrite);
        }
    }

    private function copyDocument($srcLocale, $destLocale, SnippetDocument $document, $overwrite = false)
    {
        if (!$overwrite) {
            $destStructure = $this->contentMapper->load($document->getUuid(), null, $destLocale, true);

            if (!($destStructure->getType() && 'ghost' === $destStructure->getType()->getName())) {
                $this->output->writeln(
                    '<info>Processing aborted: </info>' .
                    $document->getNodeName() . ' <comment>(use overwrite option to force)</comment>'
                );

                return;
            }
        }

        $this->contentMapper->copyLanguage(
            $document->getUuid(),
            $document->getChanger(),
            null,
            $srcLocale,
            $destLocale,
            Structure::TYPE_SNIPPET
        );
        $destDocument = $this->documentManager->find($document->getUuid(), $destLocale);
        $this->documentManager->publish($destDocument, $destLocale);

        $this->output->writeln('<info>Processing: </info>' . $document->getNodeName());
    }
}
