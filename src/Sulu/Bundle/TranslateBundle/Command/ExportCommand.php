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
                InputArgument::OPTIONAL,
                'The locale of the catalogue to export'
            )
            ->addArgument(
                'format',
                InputArgument::OPTIONAL,
                'The format of the export',
                'json'
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

        $this->setDescription(
            <<< 'EOD'
Loads translation messages as defined by the parameters
from the bundles and writes them to a file, which also can be configured
via the parameters. If a locale got specified, only the translation
corresponding to that locale get exported. Otherwise the translation
files get exported for all locales defined in the system
EOD
        );
    }

    /**
     * Loads translation messages as defined by the parameters from the bundles
     * and writes them to a file, which also can be configured via the parameters.
     * If a locale got specified, only the translation corresponding to that locale
     * get exported. Otherwise the translation files get exported for all locales
     * defined in the system.
     *
     * @param InputInterface $input The input of the command
     * @param OutputInterface $output The output of the command
     *
     * @return int 0 iff everything went fine
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $input->getArgument('locale');
        $locales = null;
        if ($locale) {
            $locales = [$locale];
        } else {
            $locales = $this->getContainer()->getParameter('sulu_core.translations');
        }
        foreach ($locales as $l) {
            $this->exportForLocale($input, $output, $l);
        }

        return 0;
    }

    /**
     * Executes the command for a given locale.
     *
     * @param InputInterface $input The input of the command
     * @param OutputInterface $output The output of the command
     * @param $locale string The locale
     */
    private function exportForLocale(InputInterface $input, OutputInterface $output, $locale)
    {
        $formatInput = $input->getArgument('format');
        $backend = $input->getOption('backend');
        $frontend = $input->getOption('frontend');
        $path = $input->getOption('path');
        $filename = $input->getOption('filename');

        $export = new Export($this->getContainer()->get('translator.default'), $output);
        $export->setLocale($locale);
        $export->setPath($path);
        $export->setFilename($filename);
        $export->setFormat($this->getFormatFromInput($formatInput));
        if ($backend) {
            $export->setBackend(true);
        }
        if ($frontend) {
            $export->setFrontend(true);
        }

        $export->execute();
    }

    /**
     * Converts the users input to the integer description of the
     * format the export class expects.
     *
     * @param $input string The users input to the command
     *
     * @return int The export format code
     */
    private function getFormatFromInput($input)
    {
        switch ($input) {
            case 'xliff':
            case 'xlf':
                return Export::XLIFF;
                break;
            default:
                return Export::JSON;
        }

        throw new \InvalidArgumentException('Unknown format: ' . $input);
    }
}
