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
use PHPCR\Util\NodeHelper;
use PHPCR\WorkspaceInterface;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Bundle\TranslateBundle\Translate\Export;

/**
 * Creates default routes in PHPCR
 *
 * @package Sulu\Bundle\CoreBundle\Command
 */
class PHPCRInitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:phpcr:init')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, '', 1)
            ->addOption('template', 't', InputOption::VALUE_OPTIONAL, '', 'overview')
            ->addOption('clear', 'c', InputOption::VALUE_OPTIONAL, '', false)
            ->setDescription('Creates default nodes in PHPCR');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SessionInterface $session */
        $session = $this->getContainer()->get('sulu.phpcr.session')->getSession();
        /** @var WorkspaceInterface $workspace */
        $workspace = $session->getWorkspace();

        // init node namespace and types
        $output->writeln('Register namespace');
        $workspace->getNamespaceRegistry()->registerNamespace('sulu', 'http://sulu.io/phpcr');
        $workspace->getNamespaceRegistry()->registerNamespace($this->getContainer()->getParameter('sulu.content.language.namespace'), 'http://sulu.io/phpcr/locale');

        $output->writeln('Register node types');
        $workspace->getNodeTypeManager()->registerNodeType(new SuluNodeType(), true);
        $workspace->getNodeTypeManager()->registerNodeType(new PathNodeType(), true);
        $workspace->getNodeTypeManager()->registerNodeType(new ContentNodeType(), true);

        // get basic node paths
        $contents = $this->getContainer()->getParameter('sulu.content.base_path.content');
        $routes = $this->getContainer()->getParameter('sulu.content.base_path.route');

        /** @var SessionInterface $session */
        $session = $this->getContainer()->get('sulu.phpcr.session')->getSession();
        $root = $session->getRootNode();

        if ($input->getOption('clear')) {
            NodeHelper::purgeWorkspace($session);
            $session->save();
            $output->writeln('Clear repository');
        }

        $output->writeln('Create basic nodes: "' . $contents . '", "' . $routes . '"');

        $userId = $input->getOption('user-id');
        $template = $input->getOption('template');

        // create basic nodes
        $content = $this->createRecursive($contents, $root);
        $content->setProperty('sulu:template', $template);
        $content->setProperty('sulu:creator', $userId);
        $content->setProperty('sulu:created', new \DateTime());
        $content->setProperty('sulu:changer', $userId);
        $content->setProperty('sulu:changed', new \DateTime());
        $content->addMixin('sulu:content');
        $session->save();

        $route = $this->createRecursive($routes, $root);
        $route->setProperty('sulu:content', $content);
        $route->setProperty('sulu:history', false);

        $session->save();
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
