<?php

namespace Sulu\Bundle\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableHelper;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;

class ReindexCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('sulu:search:reindex-content');
        $this->setDescription('Reindex the content in the search index');
        $this->setHelp(<<<EOT
The %command.name_full% command will retindex all the sulu Structures in search index.
EOT
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $session = $container->get('sulu.phpcr.session')->getSession();
        $webspacePrefix = $container->getParameter('sulu.content.node_names.base');
        $contentMapper = $container->get('sulu.content.mapper');
        $searchManager = $container->get('massive_search.search_manager');

        $sql2 = 'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "sulu:content"';

        $queryManager = $session->getWorkspace()->getQueryManager();

        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');

        $res = $query->execute();

        $multiTranslatedProperties = new MultipleTranslatedProperties(array(), 'i18n', '');

        foreach ($res->getRows() as $row) {
            $node = $row->getNode('a');

            // Evil 1: This should be encapsulated in a domain object Structure
            //         which we havn't implemented yet.
            $locales = $multiTranslatedProperties->getLanguagesForNode($node);
            foreach ($locales as $locale) {

                // Evil 2: Also should be encapsulated.
                if (!preg_match('{/' . $webspacePrefix . '/(.*?)/.*$}', $row->getNode()->getPath(), $matches)) {
                    $output->writeln(sprintf('<error>Could not determine webspace for </error>: %s', $node->getPath()));
                    continue;
                }
                $webspaceKey = $matches[1];

                $output->writeln(' - <comment>Indexing structure (locale: ' . $locale . ')</comment>: ' . $node->getPath());
                $structure = $contentMapper->loadByNode($row->getNode('a'), $locale, $webspaceKey);

                $searchManager->index($structure);
            }
        }
    }
}
