<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Search;

use Massive\Bundle\SearchBundle\Search\Converter\ConverterInterface;
use Massive\Bundle\SearchBundle\Search\Field;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;

/**
 * Converts tag names into id array.
 */
class TagsConverter implements ConverterInterface
{
    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    public function __construct(TagManagerInterface $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    public function convert($value/*, Document $document = null*/)
    {
        if (\count(\func_get_args()) < 2 || false === \func_get_arg(1) || null === \func_get_arg(1)) {
            // Preserve backward compatibility
            return $this->tagManager->resolveTagNames($value);
        }

        $resultValue = null;
        $fields = [];

        if (\is_string($value)) {
            $tag = $this->tagManager->findByName($value);
            $resultValue = $tag->getId();

            $fields = [
                new Field('id', $tag->getId()),
                new Field('name', $tag->getName()),
            ];
        }

        if (\is_array($value)) {
            $ids = $this->tagManager->resolveTagNames($value);
            $resultValue = $ids;
            $tags = \array_combine($ids, $value);

            if (false !== $tags) {
                $index = 0;
                foreach ($tags as $id => $tagName) {
                    $fields[] = new Field($index . '#id', $id);
                    $fields[] = new Field($index . '#name', $tagName);
                    ++$index;
                }
            }
        }

        return [
            'value' => $resultValue,
            'fields' => $fields,
        ];
    }
}
