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

use Sulu\Component\Content\Import\WebspaceInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Export a webspace in a specific format.
 */
class WebspaceImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:webspaces:import')
            ->addArgument('file', InputArgument::REQUIRED, 'test.xliff')
            ->addArgument('webspace', InputOption::VALUE_REQUIRED)
            ->addArgument('locale', InputOption::VALUE_REQUIRED)
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, '', '1.2.xliff')
            ->addOption('uuid', 'u', InputOption::VALUE_REQUIRED)
            ->addOption('exportSuluVersion', '', InputOption::VALUE_OPTIONAL, '1.2 or 1.3', '1.3')
            ->addOption('overrideSettings', 'o', InputOption::VALUE_OPTIONAL, 'Override Settings-Tab', 'false')
            ->setDescription('Import webspace');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webspaceKey = $input->getArgument('webspace');
        $filePath = $input->getArgument('file');
        if (!strpos($filePath, '/') === 0) {
            $filePath = getcwd() . '/' . $filePath;
        }
        $locale = $input->getArgument('locale');
        $format = $input->getOption('format');
        $uuid = $input->getOption('uuid');
        $overrideSettings = $input->getOption('overrideSettings');
        $exportSuluVersion = $input->getOption('exportSuluVersion');

        $output->writeln([
            '<info>Language Import</info>',
            '<info>===============</info>',
            '',
            '<info>Options</info>',
            'Webspace: ' . $webspaceKey,
            'Locale: ' . $locale,
            'Format: ' . $format,
            'UUID: ' . $uuid,
            'Override Setting: ' . $overrideSettings,
            'Sulu Version by export: ' . $exportSuluVersion . '.x',
            '---------------',
            '',
        ]);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Continue with this options? Be careful! (y/n)</question> ', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Abort!</error>');

            return;
        }

        $output->writeln('<info>Continue!</info>');

        /** @var WebspaceInterface $webspaceImporter */
        $webspaceImporter = $this->getContainer()->get('sulu_content.import.webspace');

        $import = $webspaceImporter->import(
            $webspaceKey,
            $locale,
            $filePath,
            $output,
            $format,
            $uuid,
            $overrideSettings,
            $exportSuluVersion
        );

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(sprintf('<info>Imported %s/%s</info>', $import->successes, $import->count));
        }

        $this->printExceptions($output, $import);

        return $import->fails;
    }

    /**
     * Print the completion message after import is done.
     *
     * @param OutputInterface $output
     * @param stdClass $import
     */
    protected function printExceptions($output, $import)
    {
        /** @var $logger LoggerInterface */
        $logger = $this->getContainer()->get('logger');

        if (null === $output) {
            $output = new NullOutput();
        }

        $output->writeln([
            '',
            '',
            '<info>Import Result</info>',
            '<info>===============</info>',
            '<info>' . $import->successes . ' Documents imported.</info>',
            '<comment>' . count($import->failed) . ' Documents ignored.</comment>',
        ]);

        if (!isset($import->exceptionStore['ignore'])) {
            return;
        }

        // If more than 20 exceptions write only into log.
        if (count($import->exceptionStore['ignore']) > 20) {
            foreach ($import->exceptionStore['ignore'] as $msg) {
                $logger->info($msg);
            }

            return;
        }

        foreach ($import->exceptionStore['ignore'] as $msg) {
            $output->writeln('<comment>' . $msg . '</comment>');
            $logger->info($msg);
        }
    }
}
