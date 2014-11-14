<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

use Massive\Bundle\BuildBundle\Build\BuilderInterface;
use PHPCR\NodeInterface;
use Massive\Bundle\BuildBundle\Build\BuilderContext;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Content\Mapper\Subscriber\NodeOrderSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builder for initializing PHPCR
 */
class NodeOrderBuilder implements BuilderInterface
{
    /**
     * @var BuilderContext
     */
    protected $context;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function setContext(BuilderContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'node_order';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function build()
    {
        $sessionManager = $this->container->get('sulu.phpcr.session');
        $webspaceManager = $this->container->get('sulu_core.webspace.webspace_manager');

        $webspaceCollection = $webspaceManager->getWebspaceCollection();

        foreach ($webspaceCollection as $webspace) {
            $contentNode = $sessionManager->getContentNode($webspace->getKey());
            $this->traverse($contentNode);
            $this->sessionManager->getSession()->save();
        }
    }

    private function traverse(NodeInterface $node)
    {
        $i = 10;
        foreach ($node->getNodes() as $childNode) {
            $childNode->setProperty(NodeOrderSubscriber::SULU_ORDER, $i);
            $this->context->getOutput()->writeln(sprintf(
                '<info>[+]</info> Setting order "<comment>%s</comment>" on <comment>%s</comment>',
                $i,
                $childNode->getPath()
            ));

            $this->traverse($childNode);
            $i += 10;
        }
    }
}
