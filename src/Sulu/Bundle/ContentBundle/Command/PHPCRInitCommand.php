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

use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;
use PHPCR\WorkspaceInterface;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\PageNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\SnippetNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * initiate phpcr repository (namespaces, nodetypes).
 */
class PHPCRInitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:phpcr:init')
            ->addOption('clear', 'c', InputOption::VALUE_OPTIONAL, '', false)
            ->setDescription('initiate phpcr repository (namespaces, nodetypes)');
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
        $workspace->getNamespaceRegistry()->registerNamespace(
            $this->getContainer()->getParameter('sulu.content.language.namespace'),
            'http://sulu.io/phpcr/locale'
        );

        $output->writeln('Register node types');
        foreach (array(
            new SuluNodeType(),
            new PathNodeType(),
            new ContentNodeType(),
            new SnippetNodeType(),
            new PageNodeType(),
        ) as $nodeType) {
            $output->writeln('  - ' . $nodeType->getName());
            $workspace->getNodeTypeManager()->registerNodeType($nodeType, true);
        }

        /** @var SessionInterface $session */
        $session = $this->getContainer()->get('sulu.phpcr.session')->getSession();
        if ($input->getOption('clear')) {
            NodeHelper::purgeWorkspace($session);
            $session->save();
            $output->writeln('Clear repository');
        }
    }
}
