<?php

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Component\DocumentManager\DocumentHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Content\Document\SynchronizationManager;
use Symfony\Component\Console\Input\InputOption;
use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;

class SynchronizeCommand extends Command
{
    public function __construct(
        DocumentManagerInterface $defaultManager,
        SynchronizationManager $syncManager
    )
    {
        parent::__construct();
        $this->defaultManager = $defaultManager;
        $this->syncManager = $syncManager;
    }

    public function configure()
    {
        $this->setName('sulu:document:synchronize');
        $this->addOption('id', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Document UUID or path to synchronize');
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

        $output->writeln('Synchronizing documents');
        $inspector = $this->defaultManager->getInspector();

        foreach ($documents as $document) {
            if (empty($locales)) {
                $locales = $this->defaultManager->getInspector()->getLocales($document);
            }

            if (!$document instanceof SynchronizeBehavior) {
                continue;
            }

            foreach ($locales as $locale) {
                $start = microtime(true);
                $synced = $document->getSynchronizedManagers() ?: [];
                // translate document
                $output->write(sprintf(
                    '<info>=></> %s [<comment>synced</>:%s <comment>locale</>:%s]', 
                    $inspector->getPath($document),
                    implode(', ', $synced),
                    $locale
                ));
                $this->defaultManager->find($inspector->getUuid($document), $locale);
                $this->syncManager->synchronizeSingle($document, $force);
                $output->writeln(sprintf(
                    ' [<info>OK</> %ss]',
                    number_format(microtime(true) - $start, 2)
                ));
            }
        }

        $output->write('Flushing publish document manager:');
        $this->syncManager->getPublishDocumentManager()->flush();
        $output->writeln(' [<info>OK</>]');
        $output->write('Flushing default document manager:');
        $this->defaultManager->flush();
        $output->writeln(' [<info>OK</>]');
    }

}
