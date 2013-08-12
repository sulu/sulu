<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Bundle\TranslateBundle\Translate\Import;

/**
 * The command to execute an import on the console
 * @package Sulu\Bundle\TranslateBundle\Command
 */
class ImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:translate:import')
            ->setDescription('Import a xliff catalogue in Sulu')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'The file to import'
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name of the new translate package in Sulu'
            )
            ->addArgument(
                'locale',
                InputArgument::REQUIRED,
                'The locale of the first catalogue in the translate package'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');
        $name = $input->getArgument('name');
        $locale = $input->getArgument('locale');

        $import = $this->getContainer()->get('sulu_translate.import');

        $import->setFile($file);
        $import->setName($name);
        $import->setFormat(Import::XLIFF); //FIXME design configurable, if there will be more supported formats
        $import->setLocale($locale);
        $import->execute();
        $output->writeln('Successfully imported file to Sulu Database!');
    }
}
