<?php

namespace Sulu\Bundle\ContactBundle\Command;

use Sulu\Bundle\ContactBundle\Import\Import;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command to execute an import on the console
 * @package Sulu\Bundle\AuditBundle\Command
 */
class CompleteDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:contacts:data:complete')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'complete a csv file'
            )
            ->addOption(
                'database',
                'd',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'complete database entries. currently supported: state',
                array()
            )
            ->addOption(
                'language',
                'lo',
                InputOption::VALUE_REQUIRED,
                'the language locale',
                'de'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'number of data to process'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getOption('file');
        $limit = $input->getOption('limit');
        $database = $input->getOption('database');
        $locale = $input->getOption('language');

        /** @var \Sulu\Bundle\ContactBundle\Import\DataCompleter $completer */
        $completer = $this->getContainer()->get('sulu_contact.data_completer');
        // set locale
        $completer->setLocale($locale);
        
        // set limit number of columns to import
        if ($limit) {
            $completer->setLimit($limit);
        }
        
        if ($file) {
            $completer->setFile($file);
            $completer->executeCsvCompletion();
        } elseif($database) {
            $completer->executeDbCompletion($database);
        } else {
            $output->writeln('<comment>No file of database option given. See --help for more information<comment>');
            return;
        }

        $output->writeln("\nData Completion finished");
    }
}
