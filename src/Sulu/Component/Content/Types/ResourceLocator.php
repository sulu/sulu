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
use Sulu\Component\PHPCR\SessionFactory\SessionManagerInterface;

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
     * @param string $webspace
     * @return mixed
     */
    public function get(NodeInterface $node, PropertyInterface $property, $webspace)
    {
        $value = $this->getResourceLocator($node ,$webspace);
        $property->setValue($value);
    }

    /**
     * reads the value for given property out of the database + sets the value of the property
     * @param NodeInterface $node
     * @param string $webspaceKey
     * @return mixed
     */
    public function getResourceLocator(NodeInterface $node, $webspaceKey)
    {
        try {
            $value = $this->getStrategy()->loadByContent($node, $webspaceKey);
        } catch (ResourceLocatorNotFoundException $ex) {
            $value = null;
        }

        return $value;
    }

    /**
     * reads the value for given property out of the database + sets the value of the property
     * @param string $uuid
     * @param string $webspaceKey
     * @return string
     */
    public function getResourceLocatorByUuid($uuid, $webspaceKey)
    {
        try {
            $value = $this->getStrategy()->loadByContentUuid($uuid, $webspaceKey);
        } catch (ResourceLocatorNotFoundException $ex) {
            $value = null;
        }

        return $value;
    }

    /**
     * save the value from given property
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @param string $webspaceKey
     * @return mixed
     */
    public function set(NodeInterface $node, PropertyInterface $property, $webspaceKey)
    {
        $value = $property->getValue();
        if ($value != null && $value != '') {
            $old = $this->getResourceLocator($node, $webspaceKey);
            if ($old !== '/') {
                if ($old != null) {
                    $this->getStrategy()->move($old, $value, $webspaceKey);
                } else {
                    $this->getStrategy()->save($node, $value, $webspaceKey);
                }
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
     * @param string $webspaceKey
     * @return string
     */
    public function loadContentNodeUuid($resourceLocator, $webspaceKey)
    {
        return $this->getStrategy()->loadByResourceLocator($resourceLocator, $webspaceKey);
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
