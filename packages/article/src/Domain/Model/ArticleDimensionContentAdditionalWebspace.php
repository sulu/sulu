<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Domain\Model;

class ArticleDimensionContentAdditionalWebspace
{
    protected int $id;

    public function __construct(
        protected string $additionalWebspace,
        protected ArticleDimensionContent $articleDimensionContent
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAdditionalWebspace(): string
    {
        return $this->additionalWebspace;
    }

    public function getArticleDimensionContent(): ArticleDimensionContent
    {
        return $this->articleDimensionContent;
    }
}
