<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;


use PHPCR\NodeInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryInterface;

class ResourceLocator extends ComplexContentType implements ResourceLocatorInterface
{
    /**
     * @var RlpStrategyInterface
     */
    private $strategy;
    /**
     * template for form generation
     * @var string
     */
    private $template;

    function __construct(RlpStrategyInterface $strategy, $template)
    {
        $this->strategy = $strategy;
        $this->template = $template;
    }

    /**
     * reads the value for given property out of the database + sets the value of the property
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @return mixed
     */
    public function get(NodeInterface $node, PropertyInterface $property)
    {
        $value = $this->getResourceLocator($node);
        $property->setValue($value);
    }

    /**
     * reads the value for given property out of the database + sets the value of the property
     * @param NodeInterface $node
     * @return mixed
     */
    public function getResourceLocator(NodeInterface $node)
    {
        try {
            $value = $this->getStrategy()->loadByContent($node, $this->getPortalKey());
        } catch (ResourceLocatorNotFoundException $ex) {
            $value = null;
        }

        return $value;
    }

    /**
     * reads the value for given property out of the database + sets the value of the property
     * @param string $uuid
     * @return string
     */
    public function getResourceLocatorByUuid($uuid)
    {
        try {
            $value = $this->getStrategy()->loadByContentUuid($uuid, $this->getPortalKey());
        } catch (ResourceLocatorNotFoundException $ex) {
            $value = null;
        }

        return $value;
    }

    /**
     * save the value from given property
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException
     * @return mixed
     */
    public function set(NodeInterface $node, PropertyInterface $property)
    {
        $value = $property->getValue();
        if ($value != null && $value != '') {
            $old = $this->getResourceLocator($node);
            if ($old != null) {
                $this->getStrategy()->move($old, $value, $this->getPortalKey());
            } else {
                $this->getStrategy()->save($node, $value, $this->getPortalKey());
            }
        } else {
            $this->remove($node, $property);
        }
    }

    /**
     * remove property from given node
     * @param NodeInterface $node
     * @param PropertyInterface $property
     */
    public function remove(NodeInterface $node, PropertyInterface $property)
    {
        // TODO: Implement remove() method.
    }

    /**
     * returns the node uuid of referenced content node
     * @param string $resourceLocator
     * @return string
     */
    public function loadContentNodeUuid($resourceLocator)
    {
        return $this->getStrategy()->loadByResourceLocator($resourceLocator, $this->getPortalKey());
    }

    /**
     * returns strategy of current portal
     * @return RLPStrategyInterface
     */
    public function getStrategy()
    {
        // TODO get strategy from ???
        return $this->strategy;
    }

    /**
     * @return string
     */
    public function getPortalKey()
    {
        // TODO get real portal from portalmanager + request
        return 'default';
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE
     * @return int
     */
    public function getType()
    {
        return ContentTypeInterface::POST_SAVE;
    }

    /**
     * returns a template to render a form
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
