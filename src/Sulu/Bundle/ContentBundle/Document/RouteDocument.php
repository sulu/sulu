<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Document;

use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface;

/**
 * The route document represents a route with in a webspace.
 *
 * Route Documents are children of the designated route-containing
 * node (which is a child of the webspace node).
 *
 * Routes contain a reference to the content which should be dispayed
 * when the route is resolved by the RouteProvider.
 */
class RouteDocument implements
    NodeNameBehavior,
    PathBehavior,
    UuidBehavior,
    RouteBehavior,
    AutoRouteInterface
{
    /**
     * @var string
     */
    private $nodeName;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var object
     */
    private $targetDocument;

    /**
     * @Var string
     */
    private $locale;

    /**
     * @var boolean
     */
    private $redirect = false;

    /**
     * @var DateTime
     */
    private $created;

    public function __construct()
    {
        $this->created = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetDocument()
    {
        return $this->targetDocument;
    }

    /**
     * {@inheritdoc}
     */
    public function setTargetDocument($targetDocument)
    {
        $this->targetDocument = $targetDocument;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->targetDocument;
    }

    /**
     * TODO: We currently do not support routes by name.
     *
     * {@inheritdoc}
     */
    public function getRouteKey()
    {
        return null;
    }

    /**
     * Set a tag which can be used by a database implementation
     * to distinguish a route from other routes as required
     *
     * @param string $tag
     */
    public function setAutoRouteTag($tag)
    {
        $this->locale = $tag;
    }

    /**
     * {@inheritdoc}
     */
    public function getAutoRouteTag()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        if ($type === AutoRouteInterface::TYPE_REDIRECT) {
            $this->redirect = true;
            return;
        }

        if ($type === AutoRouteInterface::TYPE_PRIMARY) {
            $this->redirect = false;
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            'Unknown auto route type "%s"', $type
        ));
    }

    public function isRedirect()
    {
        return $this->redirect;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectTarget($autoTarget)
    {
        throw new \BadMethodCallException(
            'Not implemented: We infer the redirect route from the content.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectTarget()
    {
        throw new \BadMethodCallException(
            'Not implemented: We infer the redirect route from the content.'
        );
    }
}
