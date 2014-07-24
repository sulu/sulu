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
class ImportBundlesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:translate:import:bundles')
            ->setDescription('Scan all bundles for sulu translations and import them')
            ->addArgument(
                'locale',
                InputArgument::REQUIRED,
                'The locale to use for the import'
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Sets the path relative to a bundle. In each bundles translations are searched in this folder',
                'Resources/public/translations/sulu'
            )
            ->addOption(
                'backendDomain',
                'bd',
                InputOption::VALUE_OPTIONAL,
                'Specifies the domain of the backend translation files',
                'backend'
            )
            ->addOption(
                'frontendDomain',
                'fd',
                InputOption::VALUE_OPTIONAL,
                'Specifies the domain of the frontend translation files',
                'frontend'
            )
            ->addOption(
                'defaultLocale',
                'dl',
                InputOption::VALUE_OPTIONAL,
                'Specifies what catalogues get set as default catalogues if they need to be created',
                'en'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getOption('path');
        $locale = $input->getArgument('locale');
        $backendDomain = $input->getOption('backendDomain');
        $frontendDomain = $input->getOption('frontendDomain');
        $defaultLocale = $input->getOption('defaultLocale');

        $import = $this->getContainer()->get('sulu_translate.import');

        $import->setFormat(Import::XLIFF);
        $import->setOutput($output);
        $import->setLocale($locale);
        $import->setDefaultLocale($defaultLocale);
        $import->setFrontendDomain($frontendDomain);
        $import->setBackendDomain($backendDomain);
        $import->setPath($path);

        $import->executeFromBundles();
        $output->writeln('Successfully imported files from bundles to Sulu Database!');
    }
}
