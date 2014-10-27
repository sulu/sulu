<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use PHPCR\NodeInterface;

/**
 * Utility class for extracting Sulu-centric properties from nodes.
 * Note this should be removed when we have domain objects.
 */
class SuluNodeHelper
{
    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @param string $languageNamespace
     */
    public function __construct($languageNamespace)
    {
        $this->languageNamespace = $languageNamespace;
    }

    /**
     * Return the languages that are currently registered on the
     * given PHPCR node.
     *
     * @param NodeInterface $node
     * @return array
     */
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
