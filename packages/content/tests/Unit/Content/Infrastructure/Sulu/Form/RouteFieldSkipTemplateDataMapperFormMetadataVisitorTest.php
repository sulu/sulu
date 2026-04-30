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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Form;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Content\Application\ContentDataMapper\DataMapper\TemplateDataMapper;
use Sulu\Content\Infrastructure\Sulu\Form\RouteFieldSkipTemplateDataMapperFormMetadataVisitor;

class RouteFieldSkipTemplateDataMapperFormMetadataVisitorTest extends TestCase
{
    public function testTagsRouteAndPageTreeRouteFields(): void
    {
        $formMetadata = new FormMetadata();

        $title = new FieldMetadata('title');
        $title->setType('text_line');
        $formMetadata->addItem($title);

        $url = new FieldMetadata('url');
        $url->setType('route');
        $formMetadata->addItem($url);

        $pageTreeUrl = new FieldMetadata('pageTreeUrl');
        $pageTreeUrl->setType('page_tree_route');
        $formMetadata->addItem($pageTreeUrl);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        (new RouteFieldSkipTemplateDataMapperFormMetadataVisitor())
            ->visitTypedFormMetadata($typedFormMetadata, 'examples', 'en');

        $this->assertFalse($title->hasTag(TemplateDataMapper::SKIP_TAG));
        $this->assertTrue($url->hasTag(TemplateDataMapper::SKIP_TAG));
        $this->assertTrue($pageTreeUrl->hasTag(TemplateDataMapper::SKIP_TAG));
    }

    public function testDoesNotAddTagTwice(): void
    {
        $formMetadata = new FormMetadata();

        $url = new FieldMetadata('url');
        $url->setType('route');
        $formMetadata->addItem($url);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $visitor = new RouteFieldSkipTemplateDataMapperFormMetadataVisitor();
        $visitor->visitTypedFormMetadata($typedFormMetadata, 'examples', 'en');
        $visitor->visitTypedFormMetadata($typedFormMetadata, 'examples', 'en');

        $matchingTags = \array_filter(
            $url->getTags(),
            static fn ($tag) => TemplateDataMapper::SKIP_TAG === $tag->getName(),
        );
        $this->assertCount(1, $matchingTags);
    }

    public function testTagsRouteFieldsInTypedFormMetadata(): void
    {
        $formMetadata = new FormMetadata();

        $title = new FieldMetadata('title');
        $title->setType('text_line');
        $formMetadata->addItem($title);

        $url = new FieldMetadata('url');
        $url->setType('route');
        $formMetadata->addItem($url);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        (new RouteFieldSkipTemplateDataMapperFormMetadataVisitor())
            ->visitTypedFormMetadata($typedFormMetadata, 'examples', 'en');

        $this->assertFalse($title->hasTag(TemplateDataMapper::SKIP_TAG));
        $this->assertTrue($url->hasTag(TemplateDataMapper::SKIP_TAG));
    }
}
