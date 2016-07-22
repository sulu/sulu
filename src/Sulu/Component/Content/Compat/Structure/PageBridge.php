<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\Compat\PageInterface;

class PageBridge extends StructureBridge implements PageInterface
{
    /**
     * {@inheritdoc}
     */
    public function getView()
    {
        return $this->structure->view;
    }

    /**
     * {@inheritdoc}
     */
    public function getController()
    {
        return $this->structure->controller;
    }

    public function getUrls()
    {
        return $this->inspector->getLocalizedUrlsForPage($this->getDocument());
    }

    public function getLanguageCode()
    {
        if (!$this->document) {
            return $this->locale;
        }

        // return original locale for shadow or ghost pages
        if ($this->getIsShadow() || ($this->getType() && $this->getType()->getName() === 'ghost')) {
            return $this->inspector->getOriginalLocale($this->getDocument());
        }

        return parent::getLanguageCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheLifeTime()
    {
        return $this->structure->cacheLifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginTemplate()
    {
        return $this->structure->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginTemplate($originTemplate)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getNavContexts()
    {
        return $this->document->getNavigationContexts();
    }

    /**
     * {@inheritdoc}
     */
    public function setNavContexts($navContexts)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getExt()
    {
        return $this->document->getExtensionsData();
    }

    /**
     * {@inheritdoc}
     */
    public function setExt($data)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setInternalLinkContent($internalLinkContent)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function setInternal($internal)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function setNodeState($state)
    {
        $this->readOnlyException(__METHOD__);
    }
}
