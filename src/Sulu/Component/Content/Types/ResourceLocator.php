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
        $value = $this->getStrategy()->read($node, $this->getPortal());
        $property->setValue($value);
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
        $this->getStrategy()->save($node, $property->getValue(), $this->getPortal());
    }

    /**
     * returns the node of referenced content node
     * @param string $resourceLocator
     * @return string
     */
    public function loadContentNode($resourceLocator)
    {
        return $this->getStrategy()->load($resourceLocator, $this->getPortal());
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

    public function getPortal()
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
