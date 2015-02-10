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
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'file to complete'
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
        $file = $input->getArgument('file');
        $limit = $input->getOption('limit');

        /** @var Import $import */
        $completer = $this->getContainer()->get('sulu_contact.data_completer');
        $completer->setFile($file);

        // set limit number of columns to import
        if ($limit) {
            $completer->setLimit($limit);
        }

        $completer->execute();

        $output->writeln('Data Completion complete');
    }
}
