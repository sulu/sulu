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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Bundle\TranslateBundle\Translate\Import;

/**
 * The command to execute an import on the console
 * @package Sulu\Bundle\TranslateBundle\Command
 */
class ImportFileCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:translate:import:file')
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
            )
            ->addOption(
                'packageId',
                'p',
                InputOption::VALUE_REQUIRED,
                'The id of the package to import the values from the file'
            )
            ->addOption(
                'defaultLocale',
                'dl',
                InputOption::VALUE_OPTIONAL,
                'Specifies if a catalogue gets set as default if it needs to be created',
                'en'
            )
            ->addOption(
                'frontend',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Specifies wheter the translations are available in frontend',
                false
            )
            ->addOption(
                'backend',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Specifies wheter the translations are available in backend',
                true
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');
        $name = $input->getArgument('name');
        $locale = $input->getArgument('locale');
        $packageId = $input->getOption('packageId');
        $defaultLocale = $input->getOption('defaultLocale');
        $backend = $input->getOption('backend');
        $frontend = $input->getOption('frontend');

        $import = $this->getContainer()->get('sulu_translate.import');

        $import->setFile($file);
        $import->setName($name);
        $import->setFormat(Import::XLIFF);
        $import->setLocale($locale);
        $import->setDefaultLocale($defaultLocale);
        $import->setOutput($output);

        if ($packageId) {
            $import->setPackageId($packageId);
        }

        $import->executeFromFile($backend, $frontend);
        $output->writeln('Successfully imported single file to Sulu Database!');
    }
}
