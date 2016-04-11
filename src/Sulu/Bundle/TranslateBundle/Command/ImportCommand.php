<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Command;

use Sulu\Bundle\TranslateBundle\Translate\Import;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command to execute an import on the console.
 */
class ImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:translate:import')
            ->setDescription('Scan all bundles for sulu translations and import them')
            ->addArgument(
                'locale',
                InputArgument::REQUIRED,
                'The locale to use for the import'
            )
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_OPTIONAL,
                'The file to import. If no file given files from all bundles get imported',
                null
            )
            ->addOption(
                'reset',
                'r',
                InputOption::VALUE_NONE,
                'If set the all translations get deleted from the DB before importing'
            )
            ->addOption(
                'packageId',
                'p',
                InputOption::VALUE_REQUIRED,
                'The id of the package to import the values from the file. If a single gile gets imported',
                null
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Sets the path relative to a bundle. In each bundles translations are searched in this folder',
                'Resources/translations/sulu'
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                'The name of the new translate package in Sulu. If a single file gets imported',
                'sulu'
            )
            ->addOption(
                'frontend',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specifies wheter the translations are available in frontend. Or if all bundles get imported, if the frontend files should be included in the import.',
                false
            )
            ->addOption(
                'backend',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specifies wheter the translations are available in backend. Or if all bundles get imported, if the backend files should be included in the import.',
                true
            )
            ->addOption(
                'backendDomain',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specifies the domain of the backend translation files',
                'backend'
            )
            ->addOption(
                'frontendDomain',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specifies the domain of the frontend translation files',
                'frontend'
            )
            ->addOption(
                'defaultLocale',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specifies what catalogues get set as default catalogues if they need to be created',
                'en'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $input->getArgument('locale');
        $defaultLocale = $input->getOption('defaultLocale');
        $frontend = $input->getOption('frontend');
        $backend = $input->getOption('backend');
        $reset = $input->getOption('reset');

        // options only for single-file import
        $file = $input->getOption('file');
        $name = $input->getOption('name');
        $packageId = $input->getOption('packageId');

        // options only for bundle import
        $path = $input->getOption('path');
        $backendDomain = $input->getOption('backendDomain');
        $frontendDomain = $input->getOption('frontendDomain');

        $import = $this->getContainer()->get('sulu_translate.import');

        $import->setFormat(Import::XLIFF);
        $import->setOutput($output);
        $import->setLocale($locale);
        $import->setDefaultLocale($defaultLocale);

        if ($reset) {
            $import->resetPackages();
        }

        if ($file === null) {
            $import->setFrontendDomain($frontendDomain);
            $import->setBackendDomain($backendDomain);
            $import->setPath($path);

            $import->executeFromBundles($backend, $frontend);
            $output->writeln('Successfully imported files from bundles to Sulu Database!');
        } else {
            $import->setFile($file);
            $import->setName($name);

            if ($packageId) {
                $import->setPackageId($packageId);
            }

            $import->executeFromFile($backend, $frontend);
            $output->writeln('Successfully imported single file to Sulu Database!');
        }
    }
}
