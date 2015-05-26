<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use DateTime;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates default routes in PHPCR for webspaces.
 */
class WebspacesInitCommand extends ContainerAwareCommand
{
    /**
     * @var MultipleTranslatedProperties
     */
    private $properties;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    protected function configure()
    {
        $this->setName('sulu:webspaces:init')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, '', 1)
            ->setDescription('Creates default nodes in PHPCR for webspaces');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get basic node paths
        $base = $this->getContainer()->getParameter('sulu.content.node_names.base');
        $contents = $this->getContainer()->getParameter('sulu.content.node_names.content');
        $routes = $this->getContainer()->getParameter('sulu.content.node_names.route');
        $snippets = $this->getContainer()->getParameter('sulu.content.node_names.snippet');
        $template = $this->getContainer()->getParameter('sulu.content.structure.default_type.homepage');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');

        // properties
        $this->properties = new MultipleTranslatedProperties(
            array(
                'changer',
                'changed',
                'created',
                'creator',
                'state',
                'template',
                'navigation',
                'published',
                'nodeType',
                'title',
            ),
            $this->getContainer()->getParameter('sulu.content.language.namespace')
        );

        /** @var WebspaceManagerInterface $webspaceManager */
        $consoleLogger = new ConsoleLogger($output);
        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $webspaceManager->setLogger($consoleLogger);

        /** @var SessionInterface $session */
        $session = $this->getContainer()->get('sulu.phpcr.session')->getSession();
        $root = $session->getRootNode();

        $userId = $input->getOption('user-id');

        $output->writeln('<comment>Create basic nodes</comment>');

        /** @var Webspace $webspace */
        foreach ($webspaceManager->getWebspaceCollection() as $webspace) {
            $contentsPath = $base . '/' . $webspace->getKey() . '/' . $contents;
            $routesPath = $base . '/' . $webspace->getKey() . '/' . $routes;

            $output->writeln("  {$webspace->getName()}");

            // create content node
            $output->writeln("    content: /{$contentsPath}");
            $content = $this->createRecursive($contentsPath, $root);
            $content->addMixin('sulu:page');
            $this->setBasicProperties($webspace, $content, $template, $userId);
            $session->save();

            // create routes node
            $output->writeln('    routes:');
            $route = $this->createRecursive($routesPath, $root);
            $this->createLanguageRoutes($webspace, $route, $content, $output);
            $session->save();
        }

        $snippetsPath = $base . '/' . $snippets;

        // create snippet nodes
        $this->createRecursive($snippetsPath, $root);
        $output->writeln("    snippets: /{$snippetsPath}");

        $snippetStructures = $this->structureManager->getStructures(Structure::TYPE_SNIPPET);
        foreach ($snippetStructures as $snippetStructure) {
            $snippetPath = $snippetsPath . '/' . $snippetStructure->getKey();
            $output->writeln("    snippets: /{$snippetPath}");
            $this->createRecursive($snippetPath, $root);
        }
        $session->save();

        $output->writeln('');
    }

    private function setBasicProperties(Webspace $webspace, NodeInterface $node, $template, $userId)
    {
        foreach ($webspace->getAllLocalizations() as $local) {
            $this->setBasicLocalizationProperties($local, $node, $template, $userId, $webspace->getKey());
        }
    }

    private function setBasicLocalizationProperties(
        Localization $localization,
        NodeInterface $node,
        $template,
        $userId
    ) {
        $this->properties->setLanguage($localization->getLocalization());

        if (!$node->hasProperty($this->properties->getName('template'))) {
            $node->setProperty($this->properties->getName('template'), $template);
            $node->setProperty($this->properties->getName('changer'), $userId);
            $node->setProperty($this->properties->getName('changed'), new DateTime());
            $node->setProperty($this->properties->getName('creator'), $userId);
            $node->setProperty($this->properties->getName('created'), new DateTime());
            $node->setProperty($this->properties->getName('navigation'), true);
            $node->setProperty($this->properties->getName('state'), StructureInterface::STATE_PUBLISHED);
            $node->setProperty($this->properties->getName('published'), new DateTime());
        }
        if (!$node->hasProperty($this->properties->getName('nodeType'))) {
            $node->setProperty($this->properties->getName('nodeType'), Structure::NODE_TYPE_CONTENT);
        }

        if (!$node->hasProperty($this->properties->getName('title'))) {
            // set title
            $node->setProperty($this->properties->getName('title'), 'Homepage');
        }

        if (is_array($localization->getChildren()) && sizeof($localization->getChildren()) > 0) {
            foreach ($localization->getChildren() as $local) {
                $this->setBasicLocalizationProperties($local, $node, $template, $userId);
            }
        }

        // set resource locator to node

        /** @var StructureManagerInterface $structureManager */
        $structure = $this->structureManager->getStructure($template);

        $property = $structure->getPropertyByTagName('sulu.rlp');
        $translatedProperty = new TranslatedProperty(
            $property,
            $localization->getLocalization(),
            $this->getContainer()->getParameter('sulu.content.language.namespace')
        );
        $translatedProperty->setValue('/');
        $node->setProperty($translatedProperty->getName(), $translatedProperty->getValue());
    }

    /**
     * create a node recursivly.
     *
     * @param string $path path to node
     * @param NodeInterface $rootNode base node to begin
     *
     * @return \PHPCR\NodeInterface
     */
    private function createRecursive($path, $rootNode)
    {
        $pathParts = explode('/', ltrim($path, '/'));
        $curNode = $rootNode;
        for ($i = 0; $i < sizeof($pathParts); $i++) {
            if ($curNode->hasNode($pathParts[$i])) {
                $curNode = $curNode->getNode($pathParts[$i]);
            } else {
                $curNode = $curNode->addNode($pathParts[$i]);
            }
        }

        return $curNode;
    }

    /**
     * @param Webspace $webspace
     * @param NodeInterface $route
     * @param NodeInterface $content
     * @param OutputInterface $output
     */
    private function createLanguageRoutes(
        Webspace $webspace,
        NodeInterface $route,
        NodeInterface $content,
        OutputInterface $output
    ) {
        foreach ($webspace->getAllLocalizations() as $local) {
            if (!$route->hasNode($local->getLocalization())) {
                $node = $route->addNode($local->getLocalization());
            } else {
                $node = $route->getNode($local->getLocalization());
            }
            $node->addMixin('sulu:path');
            $node->setProperty('sulu:content', $content);
            $node->setProperty('sulu:history', false);

            $output->writeln('      - ' . $node->getPath());
        }
    }
}
