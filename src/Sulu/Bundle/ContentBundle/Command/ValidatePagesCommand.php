<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Jackalope\Query\Row;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Validates pages.
 */
class ValidatePagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:content:validate')
            ->addArgument('webspaceKey', InputArgument::REQUIRED, 'Which webspace to search')
            ->setDescription('Dumps pages without valid templates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webspaceKey = $input->getArgument('webspaceKey');

        $session = $this->getContainer()->get('sulu.phpcr.session')->getSession();
        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $structureProvider = $this->getContainer()->get('sulu.content.webspace_structure_provider');

        /** @var Webspace $webspace */
        $webspace = $webspaceManager->findWebspaceByKey($webspaceKey);

        $select = '';
        $headers = [];
        foreach ($webspace->getAllLocalizations() as $localization) {
            $select .= '[i18n:' . $localization->getLocalization() . '-template] as ' . $localization->getLocalization(
                ) . ',';
            $headers[] = $localization->getLocalization();
        }
        $select = rtrim($select, ',');

        $sql2 = sprintf(
            "SELECT %s FROM [nt:unstructured] as page WHERE page.[jcr:mixinTypes] = 'sulu:page' AND (isdescendantnode(page, '/cmf/%s/contents') OR issamenode(page, '/cmf/%s/contents'))",
            $select,
            $webspaceKey,
            $webspaceKey
        );

        $structures = [];
        foreach ($structureManager->getStructures() as $structure) {
            $structures[] = $structure->getKey();
        }

        $availableStructureKeys = [];
        foreach ($structureProvider->getStructures($webspaceKey) as $structure) {
            $availableStructureKeys[] = $structure->getKey();
        }

        $queryManager = $session->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        $queryResult = $query->execute();

        $completeHeader = array_merge(['invalid', 'path'], $headers, ['description']);

        /** @var TableHelper $table */
        $table = $this->getHelper('table');
        $table->setHeaders($completeHeader);
        $result = 0;
        $messages = [];

        /** @var Row $row */
        foreach ($queryResult as $row) {
            $tableRow = [' '];

            $tableRow[] = $row->getPath();
            $descriptions = [];

            foreach ($headers as $header) {
                $template = $row->getValue($header);
                $tableRow[] = $template;
                if ($template !== '' && !in_array($template, $structures)) {
                    $tableRow[0] = 'X';
                    $descriptions[] = sprintf('Language "%s" contains a not existing xml-template', $header);
                    ++$result;
                }
                if ($template !== '' && !in_array($template, $availableStructureKeys)) {
                    $tableRow[0] = 'X';
                    $descriptions[] = sprintf(
                        'Language "%s" contains a not implemented xml-template in webspace "%s"',
                        $header,
                        $webspaceKey
                    );
                    ++$result;
                }
            }

            $messages = array_merge($messages, $descriptions);
            $tableRow[] = implode(', ', $descriptions);

            $table->addRow($tableRow);
        }
        $table->render($output);

        $style = new OutputFormatterStyle('red', null, ['bold', 'blink']);
        $output->getFormatter()->setStyle('error', $style);

        $style = new OutputFormatterStyle('green', null, ['bold', 'blink']);
        $output->getFormatter()->setStyle('ok', $style);

        $output->writeln('');

        if ($result > 0) {
            $output->writeln(sprintf("<error>%s Errors found: \r\n  - %s</error>", $result, implode("\r\n  - ", $messages)));
        } else {
            $output->writeln(sprintf('<ok>%s Errors found</ok>', $result));
        }
    }
}
