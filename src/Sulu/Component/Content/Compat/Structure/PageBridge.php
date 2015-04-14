<?php

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\Compat\PageInterface;

class PageBridge extends StructureBridge implements PageInterface
{
    /**
     * {@inheritDoc}
     */
    public function getView()
    {
        return $this->structure->getParameter('template');
    }

    /**
     * {@inheritDoc}
     */
    public function getController()
    {
        return $this->structure->getParameter('controller');
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheLifeTime()
    {
        return $this->structure->getParameter('cache_lifetime');
    }

    /**
     * {@inheritDoc}
     */
    public function getOriginTemplate()
    {
        return $this->structure->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setOriginTemplate($originTemplate)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getNavContexts()
    {
        return $this->document->getNavContexts();
    }

    /**
     * {@inheritDoc}
     */
    public function setNavContexts($navContexts)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getExt()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function setExt($data)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getInternalLinkContent()
    {
        throw new \Exception('TODO');
    }

    /**
     * {@inheritDoc}
     */
    public function setInternalLinkContent($internalLinkContent)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getInternal()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function setInternal($internal)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function setNodeState($state)
    {
        $this->readOnlyException(__METHOD__);
    }
}
