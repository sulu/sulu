<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Serializer;

use PHPCR\NodeInterface;

/**
 * Serialize content data into a series of properties in a single node.
 */
class FlatSerializer implements SerializerInterface
{
    const ARRAY_DELIM = '.';
    const NS = 'cont';

    /**
     * {@inheritDoc}
     */
    public function serialize($data, NodeInterface $node)
    {
        $res = $this->flatten($data);

        foreach ($res as $propName => $propValue) {
            $node->setProperty($propName, $propValue);
        }

        return $node;
    }

    /**
     * {@inheritDoc}
     */
    public function deserialize(NodeInterface $node)
    {
        $flatData = array();
        foreach ($node->getProperties(self::NS . ':*') as $propName => $prop) {
            $propName = substr($propName, strlen(self::NS) + 1);
            $flatData[$propName] = $prop->getValue();
        }

        $res = array();
        foreach ($flatData as $key => $value) {
            $keys = explode(self::ARRAY_DELIM, $key);
            $res = array_merge_recursive(
                $res,
                $this->blowUp($keys, $value, $res)
            );
        }

        return $res;
    }

    /**
     * Convert the given multidimensional array into a flat array
     */
    private function flatten($value, $ancestors = array(), $res = array())
    {
        foreach ($value as $key => $value) {
            $currentAncestors = $ancestors;
            array_push($currentAncestors, $key);

            if (is_array($value)) {
                $res = $this->flatten($value, $currentAncestors, $res);
                continue;
            }

            $key = self::NS . ':' . implode(self::ARRAY_DELIM, $currentAncestors);
            $res[$key] = $value;
        }

        return $res;
    }

    /**
     * Hydate a multidimensional array using the given keys and value
     *
     * @param array $keys
     * @param mixed $value
     *
     * @return array
     */
    private function blowUp($keys, $value)
    {
        if (count($keys) == 0) {
            return $value;
        }

        $res = array();

        $key = array_shift($keys);
        $res[$key] = $this->blowUp($keys, $value);

        return $res;
    }
}
