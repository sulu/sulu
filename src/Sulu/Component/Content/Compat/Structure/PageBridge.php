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
        return $this->structure->view;
    }

    /**
     * {@inheritDoc}
     */
    public function getController()
    {
        return $this->structure->controller;
    }

    public function getUrls()
    {
        return $this->inspector->getLocalizedUrlsForPage($this->getDocument());
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheLifeTime()
    {
        return $this->structure->cacheLifetime;
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
        return $this->document->getNavigationContexts();
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
        return $this->document->getExtensionsData();
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
