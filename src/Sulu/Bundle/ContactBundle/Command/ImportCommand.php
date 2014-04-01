<?php

namespace Sulu\Bundle\ContactBundle\Command;

use Sulu\Bundle\ContactBundle\Import\Import;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command to execute an import on the console
 * @package Sulu\Bundle\AuditBundle\Command
 */
class ImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:contacts:import')
            ->addArgument(
                'fileName',
                InputArgument::REQUIRED,
                'fileName of file to import'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getArgument('fileName');

        /** @var Import $import */
        $import = $this->getContainer()->get('sulu_contact.import');

        $import->setContactFile($fileName);

        $import->execute($fileName);

        $output->writeln('Successfully imported file to Sulu Database!');
    }
}
