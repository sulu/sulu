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

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Workspace\Localization;
use Sulu\Component\Workspace\Manager\WorkspaceManagerInterface;
use Sulu\Component\Workspace\Workspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

/**
 * Creates default routes in PHPCR for webspaces
 *
 * @package Sulu\Bundle\CoreBundle\Command
 */
class WebspacesInitCommand extends ContainerAwareCommand
{
    /**
     * @var MultipleTranslatedProperties
     */
    private $properties;

    protected function configure()
    {
        $this->setName('sulu:webspaces:init')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, '', 1)
            ->addOption('template', 't', InputOption::VALUE_OPTIONAL, '', 'overview')
            ->setDescription('Creates default nodes in PHPCR for webspaces');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get basic node paths
        $base = $this->getContainer()->getParameter('sulu.content.node_names.base');
        $contents = $this->getContainer()->getParameter('sulu.content.node_names.content');
        $routes = $this->getContainer()->getParameter('sulu.content.node_names.route');

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
                'publishedDate'
            ),
            $this->getContainer()->getParameter('sulu.content.language.namespace')
        );

        /** @var WorkspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->getContainer()->get('sulu_core.workspace.workspace_manager');

        /** @var SessionInterface $session */
        $session = $this->getContainer()->get('sulu.phpcr.session')->getSession();
        $root = $session->getRootNode();

        $userId = $input->getOption('user-id');
        $template = $input->getOption('template');

        $output->writeln('Create basic nodes');

        /** @var Workspace $webspace */
        foreach ($webspaceManager->getWorkspaceCollection() as $webspace) {
            $contentsPath = $base . '/' . $webspace->getKey() . '/' . $contents;
            $routesPath = $base . '/' . $webspace->getKey() . '/' . $routes;

            $output->writeln("  {$webspace->getName()} = content: '{$contentsPath}'', routes: '{$routesPath}'");

            // create basic nodes
            $content = $this->createRecursive($contentsPath, $root);
            $this->setBasicProperties($webspace, $content, $template, $userId);
            $content->addMixin('sulu:content');
            $session->save();

            $route = $this->createRecursive($routesPath, $root);
            $route->setProperty('sulu:content', $content);
            $route->setProperty('sulu:history', false);

            $session->save();
        }
    }

    private function setBasicProperties(Workspace $webspace, NodeInterface $node, $template, $userId)
    {
        foreach ($webspace->getLocalizations() as $local) {
            $this->setBasicLocalizationProperties($local, $node, $template, $userId);
        }
    }

    private function setBasicLocalizationProperties(Localization $Localization, NodeInterface $node, $template, $userId)
    {
        $this->properties->setLanguage($Localization->getLocalization());
        $node->setProperty($this->properties->getName('template'), $template);
        $node->setProperty($this->properties->getName('changer'), $userId);
        $node->setProperty($this->properties->getName('changed'), new DateTime());
        $node->setProperty($this->properties->getName('creator'), $userId);
        $node->setProperty($this->properties->getName('created'), new DateTime());

        $node->setProperty($this->properties->getName('navigation'), true);
        $node->setProperty($this->properties->getName('state'), StructureInterface::STATE_PUBLISHED);
        $node->setProperty($this->properties->getName('publishedDate'), new DateTime());

        foreach ($Localization->getChildren() as $local) {
            $this->setBasicLocalizationProperties($local, $node, $template, $userId);
        }
    }

    /**
     * create a node recursivly
     * @param string $path path to node
     * @param NodeInterface $rootNode base node to begin
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
}
