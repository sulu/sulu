<?php

namespace Sulu\Component\PHPCR\Wrapper\Traits;

use PHPCR\ItemInterface;
use PHPCR\ItemVisitorInterface;

/**
 * This trait fulfils the PHPCR\ItemInterface
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
trait ItemTrait
{
    use WrappedObjectTrait;
    use WrapperAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->getWrappedObject()->getPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getWrappedObject()->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getAncestor($depth)
    {
        return $this->getWrappedObject()->getAncestor();
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getParent(), 'PHPCR\NodeInterface');
    }

    /**
     * {@inheritDoc}
     */
    public function getDepth()
    {
        return $this->getWrappedObject()->getDepth();
    }

    /**
     * {@inheritDoc}
     */
    public function isNode()
    {
        return $this->getWrappedObject()->isNode();
    }

    /**
     * {@inheritDoc}
     */
    public function isNew()
    {
        return $this->getWrappedObject()->isNew();
    }

    /**
     * {@inheritDoc}
     */
    public function isModified()
    {
        return $this->getWrappedObject()->isModified();
    }

    /**
     * {@inheritDoc}
     */
    public function isSame(ItemInterface $otherItem)
    {
        return $this->getWrappedObject()->isSame($otherItem);
    }

    /**
     * {@inheritDoc}
     */
    public function accept(ItemVisitorInterface $visitor)
    {
        return $this->getWrappedObject()->accept();
    }

    /**
     * {@inheritDoc}
     */
    public function revert()
    {
        return $this->getWrappedObject()->revert();
    }

    /**
     * {@inheritDoc}
     */
    public function remove()
    {
        return $this->getWrappedObject()->remove();
    }

    /**
     * {@inheritDoc}
     */
    public function getSession()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getSession(), 'PHPCR\SessionInterface');
    }
}
