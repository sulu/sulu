<?php

namespace Sulu\Component\Util;

use PHPCR\NodeInterface;

/**
 * Utility class for extracting Sulu-centric properties from nodes.
 */
class SuluNodeHelper
{
    private $languageNamespace;

    public function __construct($languageNamespace)
    {
        $this->languageNamespace = $languageNamespace;
    }

    public function getLanguagesForNode(NodeInterface $node)
    {
        $languages = array();
        foreach ($node->getProperties() as $property) {
            preg_match('/^' . $this->languageNamespace . ':(.*?)-template/', $property->getName(), $matches);

            if ($matches) {
                $languages[$matches[1]] = $matches[1];
            }
        }

        return array_values($languages);
    }
}
