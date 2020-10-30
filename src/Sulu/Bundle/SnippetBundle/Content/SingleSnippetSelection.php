<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleSnippetSelection extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var SnippetResolverInterface
     */
    private $snippetResolver;

    /**
     * @var ReferenceStoreInterface
     */
    private $snippetReferenceStore;

    public function __construct(
        SnippetResolverInterface $snippetResolver,
        ReferenceStoreInterface $snippetReferenceStore
    ) {
        $this->snippetResolver = $snippetResolver;
        $this->snippetReferenceStore = $snippetReferenceStore;

        parent::__construct('SingleSnippetSelection', null);
    }

    public function getContentData(PropertyInterface $property)
    {
        $resolvedSnippet = $this->resolveSnippet($property);

        if (null === $resolvedSnippet) {
            return $this->defaultValue;
        }

        return $resolvedSnippet['content'];
    }

    public function getViewData(PropertyInterface $property)
    {
        $resolvedSnippet = $this->resolveSnippet($property);

        if (null === $resolvedSnippet) {
            return [];
        }

        return $resolvedSnippet['view'];
    }

    public function preResolve(PropertyInterface $property)
    {
        $snippetUuid = $property->getValue();

        if (empty($snippetUuid)) {
            return;
        }

        $this->snippetReferenceStore->add($snippetUuid);
    }

    private function resolveSnippet(PropertyInterface $property)
    {
        $snippetUuid = $property->getValue();

        if (empty($snippetUuid)) {
            return null;
        }

        /** @var PageBridge $page */
        $page = $property->getStructure();
        $webspaceKey = $page->getWebspaceKey();
        $locale = $page->getLanguageCode();
        $shadowLocale = null;
        if ($page->getIsShadow()) {
            $shadowLocale = $page->getShadowBaseLanguage();
        }

        $params = $property->getParams();
        $loadExcerpt = isset($params['loadExcerpt']) ? $params['loadExcerpt']->getValue() : false;

        /** @var array[] $resolvedSnippets */
        $resolvedSnippets = $this->snippetResolver->resolve(
            [$snippetUuid],
            $webspaceKey,
            $locale,
            $shadowLocale,
            $loadExcerpt
        );

        return \reset($resolvedSnippets) ?: null;
    }
}
