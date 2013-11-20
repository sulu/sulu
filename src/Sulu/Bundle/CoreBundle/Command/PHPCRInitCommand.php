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
use PHPCR\WorkspaceInterface;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Sulu\Component\PHPCR\NodeTypes\PathHistory\PathHistoryNodeType;
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
        $output->writeln('Register node types');
        $workspace->getNodeTypeManager()->registerNodeType(new SuluNodeType(), true);
        $workspace->getNodeTypeManager()->registerNodeType(new PathNodeType(), true);
        $workspace->getNodeTypeManager()->registerNodeType(new PathHistoryNodeType(), true);
        $workspace->getNodeTypeManager()->registerNodeType(new ContentNodeType(), true);

        // get basic node paths
        $contents = $this->getContainer()->getParameter('sulu.content.base_path.content');
        $routes = $this->getContainer()->getParameter('sulu.content.base_path.route');

        /** @var SessionInterface $session */
        $session = $this->getContainer()->get('sulu.phpcr.session')->getSession();
        $root = $session->getRootNode();

        $output->writeln('Create basic nodes: "' . $contents . '", "' . $routes . '"');

        // create basic nodes
        $this->createRecursive($contents, $root);
        $this->createRecursive($routes, $root);

        $session->save();
    }

    /**
     * create a node recursivly
     * @param string $path path to node
     * @param NodeInterface $rootNode base node to begin
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
                $curNode->addMixin('mix:referenceable');
            }
        }
    }
}
