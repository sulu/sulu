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
use Sulu\Bundle\TranslateBundle\Translate\Export;

/**
 * The command to execute an export on the console
 * @package Sulu\Bundle\TranslateBundle\Command
 */
class ExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:translate:export')
            ->setDescription('Export a catalogue from Sulu')
            ->addArgument(
                'packageId',
                InputArgument::REQUIRED,
                'The id of the package to export'
            )
            ->addArgument(
                'locale',
                InputArgument::REQUIRED,
                'The locale of the catalogue to export'
            )
            ->addArgument(
                'format',
                InputArgument::OPTIONAL,
                'The format of the export',
                'xliff'
            )
            ->addOption(
                'backend',
                'b',
                InputOption::VALUE_NONE,
                'Defines if only the backend translations should be exported'
            )
            ->addOption(
                'frontend',
                'f',
                InputOption::VALUE_NONE,
                'Defines if only the frontend translations should be exported'
            )
            ->addOption(
                'location',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Sets the name of the location that should be exported'
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Sets the path to which the file should be exported'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageId = $input->getArgument('packageId');
        $locale = $input->getArgument('locale');
        $format = $input->getArgument('format');
        $backend = $input->getOption('backend');
        $frontend = $input->getOption('frontend');
        $location = $input->getOption('location');
        $path = $input->getOption('path');

        $export = $this->getContainer()->get('sulu_translate.export');

        $export->setPackageId($packageId);
        $export->setLocale($locale);

        // Parse format
        switch ($format) {
            case 'xliff':
            case 'xlf':
                $export->setFormat(Export::XLIFF);
                break;
            case 'json':
                $export->setFormat(Export::JSON);
                break;
            default:
                $export->setFormat(Export::XLIFF);
        }
        if ($backend) {
            $export->setBackend($backend);
        }
        if ($frontend) {
            $export->setFrontend($frontend);
        }
        if ($location) {
            $export->setLocation($location);
        }
        if ($path) {
            $export->setPath($path);
        }
        $export->execute();

        $output->writeln('Successfully exported translation to file!');
    }
}
