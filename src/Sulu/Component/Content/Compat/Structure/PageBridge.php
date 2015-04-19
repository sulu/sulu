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
        $target = $this->getDocument()->getRedirectTarget();
        if (!$target) {
            throw new \RuntimeException(sprintf(
                'No redirect target set on document at path "%s" with redirect type "%s"',
                $this->inspector->getPath($this->document),
                $this->document->getRedirectType()
            ));
        }

        return $this->documentToStructure($target);
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
