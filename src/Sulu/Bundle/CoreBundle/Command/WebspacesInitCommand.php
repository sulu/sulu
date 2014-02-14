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
use Sulu\Component\Workspace\Manager\WorkspaceManagerInterface;
use Sulu\Component\Workspace\Workspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates default routes in PHPCR for webspaces
 *
 * @package Sulu\Bundle\CoreBundle\Command
 */
class WebspacesInitCommand extends ContainerAwareCommand
{
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
            $content->setProperty('sulu:template', $template);
            $content->setProperty('sulu:creator', $userId);
            $content->setProperty('sulu:created', new \DateTime());
            $content->setProperty('sulu:changer', $userId);
            $content->setProperty('sulu:changed', new \DateTime());
            $content->addMixin('sulu:content');
            $session->save();

            $route = $this->createRecursive($routesPath, $root);
            $route->setProperty('sulu:content', $content);
            $route->setProperty('sulu:history', false);

            $session->save();
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
