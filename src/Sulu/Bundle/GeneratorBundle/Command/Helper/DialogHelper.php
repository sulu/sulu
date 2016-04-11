<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\GeneratorBundle\Command\Helper;

use Symfony\Component\Console\Helper\DialogHelper as BaseDialogHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates sulu bundles.
 */
class DialogHelper extends BaseDialogHelper
{
    public function writeGeneratorSummary(OutputInterface $output, $errors)
    {
        if (!$errors) {
            $this->writeSection($output, 'You can now start using the generated code!');
        } else {
            $this->writeSection($output, [
                'The command was not able to configure everything automatically.',
                'You must do the following changes manually.',
            ], 'error');

            $output->writeln($errors);
        }
    }

    public function getRunner(OutputInterface $output, &$errors)
    {
        $runner = function ($err) use ($output, &$errors) {
            if ($err) {
                $output->writeln('<fg=red>FAILED</>');
                $errors = array_merge($errors, $err);
            } else {
                $output->writeln('<info>OK</info>');
            }
        };

        return $runner;
    }

    public function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $output->writeln([
            '',
            $this->getHelperSet()->get('formatter')->formatBlock($text, $style, true),
            '',
        ]);
    }

    public function getQuestion($question, $default, $sep = ':')
    {
        return $default ? sprintf('<info>%s</info> [<comment>%s</comment>]%s ', $question, $default, $sep) : sprintf('<info>%s</info>%s ', $question, $sep);
    }
}
