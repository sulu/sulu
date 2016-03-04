<?php

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Component\DocumentManager\DocumentHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Content\Document\SyncronizationManager;
use Symfony\Component\Console\Input\InputOption;
use Sulu\Component\Content\Document\Behavior\SyncronizeBehavior;

class SyncronizeCommand extends Command
{
    public function __construct(
        DocumentManagerInterface $defaultManager,
        SyncronizationManager $syncManager
    )
    {
        parent::__construct();
        $this->defaultManager = $defaultManager;
        $this->syncManager = $syncManager;
    }

    public function configure()
    {
        $this->setName('sulu:document:syncronize');
        $this->addOption('id', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Document UUID or path to syncronize');
        $this->addOption('locale', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Locale');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Force sync (ignore flags)');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $ids = $input->getOption('id');
        $locales = $input->getOption('locale');
        $force = $input->getOption('force');

        if (!empty($ids)) {
            $documents = [];
            foreach ($ids as $id) {
                $documents[] = $this->defaultManager->find($id);
            }
            $this->syncDocuments($output, $documents, $locales, $force);
            return;
        }

        $query = $this->defaultManager->createQuery('SELECT * FROM [nt:unstructured]');
        $documents = $query->execute();

        $this->syncDocuments($output, $documents, $locales, $force);
    }

    private function syncDocuments(OutputInterface $output, $documents, array $locales, $force)
    {
        if (empty($documents)) {
            return;
        }

        $output->writeln('Syncronizing documents');
        $inspector = $this->defaultManager->getInspector();

        foreach ($documents as $document) {
            if (empty($locales)) {
                $locales = $this->defaultManager->getInspector()->getLocales($document);
            }

            if (!$document instanceof SyncronizeBehavior) {
                continue;
            }

            foreach ($locales as $locale) {
                // translate document
                $this->defaultManager->find($inspector->getUuid($document), $locale);
                $synced = $document->getSyncronizedManagers() ?: [];
                $output->write(sprintf(
                    '<info>=></> %s [<comment>synced</>:%s <comment>locale</>:%s]', 
                    $inspector->getPath($document),
                    implode(', ', $synced),
                    $locale
                ));
                $this->syncManager->syncronizeFull($document, $force);
                $output->writeln(' [<info>OK</>]');
            }
        }
    }

}
