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

use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
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
     * @var DefaultSnippetManagerInterface
     */
    private $defaultSnippetManager;

    /**
     * @var ReferenceStoreInterface
     */
    private $snippetReferenceStore;

    /**
     * @var ReferenceStoreInterface
     */
    private $snippetAreaReferenceStore;

    public function __construct(
        SnippetResolverInterface $snippetResolver,
        DefaultSnippetManagerInterface $defaultSnippetManager,
        ReferenceStoreInterface $snippetReferenceStore,
        ReferenceStoreInterface $snippetAreaReferenceStore
    ) {
        $this->snippetResolver = $snippetResolver;
        $this->defaultSnippetManager = $defaultSnippetManager;
        $this->snippetReferenceStore = $snippetReferenceStore;
        $this->snippetAreaReferenceStore = $snippetAreaReferenceStore;

        parent::__construct('SingleSnippetSelection', null);
    }

    public function getContentData(PropertyInterface $property)
    {
        $resolvedSnippet = $this->resolveSnippet($property);

        if (null === $resolvedSnippet) {
            return null;
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
        $defaultSnippetArea = isset($params['default']) ? $params['default']->getValue() : null;

        if (empty($snippetUuid) && $defaultSnippetArea) {
            $snippetUuid = $this->getDefaultSnippetId($webspaceKey, $defaultSnippetArea, $locale);
        }

        if (empty($snippetUuid)) {
            return null;
        }

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

    private function getDefaultSnippetId(string $webspaceKey, string $snippetArea, string $locale): ?string
    {
        try {
            $snippet = $this->defaultSnippetManager->load($webspaceKey, $snippetArea, $locale);
            $this->snippetAreaReferenceStore->add($snippetArea);
        } catch (WrongSnippetTypeException $exception) {
            return null;
        }

        return $snippet ? $snippet->getUuid() : null;
    }
}
