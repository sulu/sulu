<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Command;

use Jackalope\Query\Row;
use PHPCR\SessionInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps pages without valid templates
 * @package Sulu\Bundle\CoreBundle\Command
 */
class DumpInvalidPagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:content:dump:invalid-pages')
            ->addArgument('webspaceKey', InputArgument::REQUIRED, 'Which webspace to search')
            ->setDescription('Dumps pages without valid templates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webspaceKey = $input->getArgument('webspaceKey');

        /** @var SessionInterface $session */
        $session = $this->getContainer()->get('sulu.phpcr.session')->getSession();

        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');

        /** @var StructureManagerInterface $structureManager */
        $structureManager = $this->getContainer()->get('sulu.content.structure_manager');

        /** @var Webspace $webspace */
        $webspace = $webspaceManager->findWebspaceByKey($webspaceKey);

        $select = '';
        $header = array('valid', 'path');
        foreach ($webspace->getAllLocalizations() as $localization) {
            $select .= '[i18n:' . $localization->getLocalization() . '-template] as ' . $localization->getLocalization(
                ) . ',';
            $header[] = $localization->getLocalization();
        }
        $select = rtrim($select, ',');

        $sql2 = sprintf(
            "SELECT %s FROM [nt:unstructured] as page WHERE page.[jcr:mixinTypes] = 'sulu:content' AND (isdescendantnode(page, '/cmf/%s/contents') OR issamenode(page, '/cmf/%s/contents'))",
            $select,
            $webspaceKey,
            $webspaceKey
        );

        $structures = array();
        foreach ($structureManager->getStructures() as $structure) {
            $structures[] = $structure->getKey();
        }

        $queryManager = $session->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        $queryResult = $query->execute();

        /** @var TableHelper $table */
        $table = $this->getHelper('table');
        $table->setHeaders($header);

        /** @var Row $row */
        foreach ($queryResult as $row) {
            $tableRow = array(' ');
            foreach ($header as $h) {
                if ($h === 'path') {
                    $tableRow[] = $row->getPath();
                } elseif ($h !== 'valid') {
                    $template = $row->getValue($h);
                    $tableRow[] = $template;
                    if ($template !== "" && !in_array($template, $structures)) {
                        $tableRow[0] = 'X';
                    }
                }
            }

            $table->addRow($tableRow);
        }
        $table->render($output);

    }
}
