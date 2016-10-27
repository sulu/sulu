<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Build;

use Massive\Bundle\BuildBundle\Build\BuilderContext;
use Massive\Bundle\BuildBundle\Build\BuilderInterface;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Subscriber\OrderSubscriber;
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
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $defaultSession;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var BuilderContext
     */
    private $context;

    public function __construct(
        SessionManagerInterface $sessionManager,
        SessionInterface $defaultSession,
        SessionInterface $liveSession,
        WebspaceManagerInterface $webspaceManager,
        PropertyEncoder $propertyEncoder
    ) {
        $this->sessionManager = $sessionManager;
        $this->defaultSession = $defaultSession;
        $this->liveSession = $liveSession;
        $this->webspaceManager = $webspaceManager;

        $this->propertyName = $propertyEncoder->systemName(OrderSubscriber::FIELD);
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
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $contentPath = $this->sessionManager->getContentPath($webspace->getKey());

            $this->context->getOutput()->writeln('Default workspace');
            $this->traverse($this->defaultSession->getNode($contentPath));

            $this->context->getOutput()->writeln('');

            $this->context->getOutput()->writeln('Live workspace');
            $this->traverse($this->liveSession->getNode($contentPath));
        }

        $this->defaultSession->save();
        $this->liveSession->save();
    }

    private function traverse(NodeInterface $node)
    {
        $i = 10;
        foreach ($node->getNodes() as $childNode) {
            $childNode->setProperty($this->propertyName, $i);
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
