<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Twig;

use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Provides global snippets by type.
 */
class SnippetTypeTwigExtension extends \Twig_Extension
{
    /**
     * @var SnippetRepository
     */
    private $snippetRepository;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @param SnippetRepository $snippetRepository
     * @param RequestAnalyzerInterface $requestAnalyzer
     */
    public function __construct(SnippetRepository $snippetRepository, RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->snippetRepository = $snippetRepository;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_snippet_load_by_type', [$this, 'loadByType']),
        ];
    }

    /**
     * Load global snippets by type.
     *
     * @param string type
     * @param string $locale
     *
     * @return array
     */
    public function loadByType($type, $locale = null)
    {
        // ensure locale
        if (null === $locale) {
            $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        }

        // practical helper
        $helper = [];

        // get all snippets by their type
        if ($snippets = $this->snippetRepository->getSnippets($locale, $type)) {
            // convert snippet documents to array
            foreach ($snippets as $snippet) {
                $helper[] = $snippet->getStructure()->toArray();
            }
        }

        return $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_snippet.type';
    }
}
