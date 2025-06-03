<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\XmlParserTrait;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 */
class TagXmlParser
{
    use XmlParserTrait;

    /**
     * @param \DOMNode[] $tagNodes
     *
     * @return array<TagMetadata>
     */
    public function load(\DOMXPath $xpath, iterable $tagNodes): array
    {
        $result = [];

        foreach ($tagNodes as $node) {
            $tag = [
                'name' => null,
                'priority' => null,
                'attributes' => [],
            ];

            /** @var string $key */
            /** @var \DOMAttr $attr */
            foreach ($node->attributes ?? [] as $key => $attr) {
                if (\in_array($key, ['name', 'priority'], true)) {
                    $tag[$key] = $attr->value;
                } else {
                    $tag['attributes'][$key] = $attr->value;
                }
            }

            if (!isset($tag['name'])) {
                // this should not happen because of the XSD validation
                throw new \InvalidArgumentException('Tag does not have a name in template definition');
            }

            $tagMetadata = new TagMetadata();
            $tagMetadata->setName($tag['name']);
            $tagMetadata->setPriority(\is_numeric($tag['priority']) ? \intval($tag['priority']) : null);
            $tagMetadata->setAttributes($tag['attributes']);

            $result[] = $tagMetadata;
        }

        return $result;
    }
}
