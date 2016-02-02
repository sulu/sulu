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

use Sulu\Bundle\TranslateBundle\Translate\Export;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command to execute an export on the console.
 */
class ExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:translate:export')
            ->setDescription('Export all catalogues with a locale')
            ->addArgument(
                'locale',
                InputArgument::REQUIRED,
                'The locale of the catalogue to export'
            )
            ->addArgument(
                'format',
                InputArgument::OPTIONAL,
                'The format of the export',
                'json'
            )
            ->addOption(
                'package',
                'p',
                InputOption::VALUE_OPTIONAL,
                'The id of the package of which the translations should be exported. If not given all packages will be exported'
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
                null,
                InputOption::VALUE_OPTIONAL,
                'Sets the path to which the file should be exported',
                'web/admin/translations'
            )
            ->addOption(
                'filename',
                null,
                InputOption::VALUE_OPTIONAL,
                'sets the filename of the exported file',
                'sulu'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $input->getArgument('locale');
        $format = $input->getArgument('format');
        $backend = $input->getOption('backend');
        $frontend = $input->getOption('frontend');
        $location = $input->getOption('location');
        $path = $input->getOption('path');
        $filename = $input->getOption('filename');
        $packageId = $input->getOption('package');

        $export = $this->getContainer()->get('sulu_translate.export');

        $export->setLocale($locale);

        // Parse format
        switch ($format) {
            case 'xliff':
            case 'xlf':
                $export->setFormat(Export::XLIFF);
                break;
            default:
                $export->setFormat(Export::JSON);
        }
        $export->setFilename($filename);
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
        if ($packageId) {
            $export->setPackageId($packageId);
        }
        $export->execute();

        $output->writeln('Successfully exported translations to file!');
    }
}
