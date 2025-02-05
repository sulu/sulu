<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Domain\Model;

use Sulu\Content\Domain\Model\AuthorTrait;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentTrait;
use Sulu\Content\Domain\Model\ExcerptTrait;
use Sulu\Content\Domain\Model\RoutableTrait;
use Sulu\Content\Domain\Model\SeoTrait;
use Sulu\Content\Domain\Model\ShadowTrait;
use Sulu\Content\Domain\Model\TemplateTrait;
//use Sulu\Content\Domain\Model\WebspaceTrait;
use Sulu\Content\Domain\Model\WebspaceTrait;
use Sulu\Content\Domain\Model\WorkflowTrait;

/**
 * @experimental
 */
class PageDimensionContent implements PageDimensionContentInterface
{
    use AuthorTrait;
    use DimensionContentTrait;
    use ExcerptTrait;
    use RoutableTrait;
    use SeoTrait;
    use ShadowTrait;
    use TemplateTrait {
        setTemplateData as parentSetTemplateData;
    }
    use WebspaceTrait;
    use WorkflowTrait;

    protected int $id;

    protected PageInterface $page;

    protected ?string $title;

    public function __construct(PageInterface $page)
    {
        $this->page = $page;
    }

    /**
     * @return PageInterface
     */
    public function getResource(): ContentRichEntityInterface
    {
        return $this->page;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTemplateData(array $templateData): void
    {
        if (\array_key_exists('title', $templateData)
            && \is_string($templateData['title'])
        ) {
            $this->title = $templateData['title'];
        }

        $this->parentSetTemplateData($templateData);
    }

    public static function getTemplateType(): string
    {
        return PageInterface::TEMPLATE_TYPE;
    }

    public static function getResourceKey(): string
    {
        return PageInterface::RESOURCE_KEY;
    }
}
