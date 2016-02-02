<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

use Massive\Bundle\BuildBundle\Build\BuilderContext;
use Massive\Bundle\BuildBundle\Build\BuilderInterface;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Mapper\Subscriber\NodeOrderSubscriber;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Builder for initializing PHPCR.
 */
class NodeOrderBuilder implements BuilderInterface
{
    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var BuilderContext
     */
    protected $context;

    public function __construct(SessionManagerInterface $sessionManager, WebspaceManagerInterface $webspaceManager)
    {
        $this->sessionManager = $sessionManager;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(BuilderContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'node_order';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $webspaceCollection = $this->webspaceManager->getWebspaceCollection();

        foreach ($webspaceCollection as $webspace) {
            $contentNode = $this->sessionManager->getContentNode($webspace->getKey());
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
