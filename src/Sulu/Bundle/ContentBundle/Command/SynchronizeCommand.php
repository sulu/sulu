<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Component\Content\Document\SynchronizationManager;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;

/**
 * Manually synchronize all or a set of nodes.
 */
class SynchronizeCommand extends Command
{
    public function __construct(
        DocumentManagerInterface $defaultManager,
        SynchronizationManager $syncManager
    ) {
        parent::__construct();
        $this->defaultManager = $defaultManager;
        $this->syncManager = $syncManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('sulu:document:synchronize');
        $this->addOption('id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Document UUID or path to synchronize');
        $this->addOption('locale', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Locale');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Force sync (ignore flags)');
        $this->addOption('stop-on-exception', 'S', InputOption::VALUE_NONE, 'Stop on exception');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $ids = $input->getOption('id');
        $locales = $input->getOption('locale');
        $force = $input->getOption('force');
        $stopOnException = $input->getOption('stop-on-exception');

        if (!empty($ids)) {
            $documents = [];
            foreach ($ids as $id) {
                $documents[] = $this->defaultManager->find($id);
            }
            $this->syncDocuments($output, $documents, $locales, $force, $stopOnException);

            return;
        }

        $query = $this->defaultManager->createQuery('SELECT * FROM [nt:unstructured]');
        $documents = $query->execute();

        $this->syncDocuments($output, $documents, $locales, $force, $stopOnException);
    }

    private function syncDocuments(OutputInterface $output, $documents, array $locales, $force, $stopOnException)
    {
        if (empty($documents)) {
            return;
        }

        $output->writeln('Synchronizing documents ...');

        $inspector = $this->defaultManager->getInspector();
        $errors = [];
        $documentCount = 0;
        $syncedCount = 0;

        foreach ($documents as $document) {
            if (false === $document instanceof LocalizedStructureBehavior) {
                $syncLocales = [null];
            } elseif (empty($locales)) {
                $syncLocales = $this->defaultManager->getInspector()->getLocales($document);
            } else {
                $syncLocales = $locales;
            }

            if (!$document instanceof SynchronizeBehavior) {
                continue;
            }

            foreach ($syncLocales as $locale) {
                ++$documentCount;
                $start = microtime(true);
                // translate document
                $output->write(sprintf(
                    '[%s] </> %-50s',
                    $locale === null ? '--' : $locale,
                    $inspector->getPath($document)
                ));
                $this->defaultManager->find($inspector->getUuid($document), $locale);

                try {
                    $this->syncManager->synchronizeSingle($document, [
                        'force' => $force,
                        'repair' => true,
                    ]);
                    $synced = $document->getSynchronizedManagers() ?: [];
                    $output->writeln(sprintf(
                        '<comment>=></> %s %ss <info>OK</>',
                        implode(', ', $synced),
                        number_format(microtime(true) - $start, 2)
                    ));
                    ++$syncedCount;
                } catch (\Exception $e) {
                    if ($stopOnException) {
                        throw $e;
                    }
                    $errors[] = [$inspector->getPath($document), $locale, get_class($e), $e->getMessage()];
                    $output->writeln(' [<error>ERROR</>]');
                }
            }
        }

        $output->write('Flushing publish document manager:');
        $this->syncManager->getPublishDocumentManager()->flush();
        $output->writeln(' [<info>OK</>]');
        $output->write('Flushing default document manager:');
        $this->defaultManager->flush();
        $output->writeln(' [<info>OK</>]');
        $output->writeln(sprintf('%d/%d documents syncronized (inc. localizations)', $syncedCount, $documentCount));

        if (count($errors)) {
            $output->writeln(sprintf('%d errors encountered: ', count($errors)));
            $output->write(PHP_EOL);
            foreach ($errors as $error) {
                list($path, $locale, $class, $message) = $error;
                $output->writeln(sprintf('<error>%s</> %s [%s]', $class, $path, $locale));
                $output->writeln($message);
                $output->write(PHP_EOL);
            }
        }
    }
}
