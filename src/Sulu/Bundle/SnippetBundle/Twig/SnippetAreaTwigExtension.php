<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Twig;

use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Provides snippets by area.
 */
class SnippetAreaTwigExtension extends \Twig_Extension
{
    /**
     * @var DefaultSnippetManagerInterface
     */
    private $defaultSnippetManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var SnippetResolverInterface
     */
    private $snippetResolver;

    public function __construct(
        DefaultSnippetManagerInterface $defaultSnippetManager,
        RequestAnalyzerInterface $requestAnalyzer,
        SnippetResolverInterface $snippetResolver
    ) {
        $this->defaultSnippetManager = $defaultSnippetManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->snippetResolver = $snippetResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_snippet_load_by_area', [$this, 'loadByArea']),
        ];
    }

    /**
     * Load snippet for webspace by area.
     *
     * @param string $area
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return array
     */
    public function loadByArea($area, $webspaceKey = null, $locale = null)
    {
        if (!$webspaceKey) {
            $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        }
        if (!$locale) {
            $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        }

        try {
            $snippet = $this->defaultSnippetManager->load($webspaceKey, $area, $locale);
        } catch (WrongSnippetTypeException $exception) {
            return null;
        } catch (DocumentNotFoundException $exception) {
            return null;
        }

        if (!$snippet) {
            return null;
        }

        $snippets = $this->snippetResolver->resolve([$snippet->getUuid()], $webspaceKey, $locale);

        if (!array_key_exists(0, $snippets)) {
            return null;
        }

        return $snippets[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_snippet.area';
    }
}
