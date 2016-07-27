<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Search;

use Massive\Bundle\SearchBundle\Search\Converter\ConverterInterface;
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

    /**
     * {@inheritdoc}
     */
    public function convert($value)
    {
        return $this->tagManager->resolveTagNames($value);
    }
}
