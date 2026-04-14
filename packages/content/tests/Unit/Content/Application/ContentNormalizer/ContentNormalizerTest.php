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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentNormalizer;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizer;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Content\Application\ContentNormalizer\Normalizer\DimensionContentNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\ExcerptNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\RoutableNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\SeoNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\TaxonomyNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\TemplateNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\WorkflowNormalizer;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\DimensionContentTrait;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\ExcerptTrait;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\RoutableTrait;
use Sulu\Content\Domain\Model\SeoInterface;
use Sulu\Content\Domain\Model\SeoTrait;
use Sulu\Content\Domain\Model\TaxonomyInterface;
use Sulu\Content\Domain\Model\TaxonomyTrait;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\TemplateTrait;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTrait;

class ContentNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createContentNormalizerInstance(): ContentNormalizerInterface
    {
        return new ContentNormalizer([
            new DimensionContentNormalizer(),
            new TaxonomyNormalizer(),
            new ExcerptNormalizer(),
            new SeoNormalizer(),
            new TemplateNormalizer(),
            new WorkflowNormalizer(),
            new RoutableNormalizer(),
        ]);
    }

    public function testResolveSimple(): void
    {
        $contentRichEntityMock = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntityMock->getId()->willReturn(5);

        $object = new class($contentRichEntityMock->reveal()) implements DimensionContentInterface {
            use DimensionContentTrait;

            /**
             * @var ContentRichEntityInterface<self>
             */
            protected $resource;

            /**
             * @param ContentRichEntityInterface<self> $resource
             */
            public function __construct(ContentRichEntityInterface $resource)
            {
                $this->resource = $resource;
                $this->locale = 'de';
                $this->stage = 'live';
                $this->ghostLocale = 'de';
                $this->availableLocales = ['de'];
            }

            public static function getResourceKey(): string
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }

            /**
             * @return ContentRichEntityInterface<self>
             */
            public function getResource(): ContentRichEntityInterface
            {
                return $this->resource;
            }
        };

        $contentNormalizer = $this->createContentNormalizerInstance();
        $this->assertSame([
            'availableLocales' => ['de'],
            'ghostLocale' => 'de',
            'id' => 5,
            'locale' => 'de',
            'stage' => 'live',
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ], $contentNormalizer->normalize($object));
    }

    public function testResolveFull(): void
    {
        $contentRichEntityMock = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntityMock->getId()->willReturn(5);

        $object = new class($contentRichEntityMock->reveal()) implements DimensionContentInterface, ExcerptInterface, TaxonomyInterface, SeoInterface, TemplateInterface, WorkflowInterface, RoutableInterface {
            use DimensionContentTrait;
            use ExcerptTrait;
            use TaxonomyTrait;
            use RoutableTrait;
            use SeoTrait;
            use TemplateTrait;
            use WorkflowTrait;

            /**
             * @var ContentRichEntityInterface<self>
             */
            protected $resource;

            /**
             * @param ContentRichEntityInterface<self> $resource
             */
            public function __construct(ContentRichEntityInterface $resource)
            {
                $this->resource = $resource;
                $this->locale = 'de';
                $this->stage = 'live';
            }

            public static function getResourceKey(): string
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }

            public static function getTemplateType(): string
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }

            /**
             * @return ContentRichEntityInterface<self>
             */
            public function getResource(): ContentRichEntityInterface
            {
                return $this->resource;
            }
        };

        $object->setGhostLocale('en');
        $object->addAvailableLocale('en');
        $object->addAvailableLocale('de');

        $tag1 = $this->prophesize(TagInterface::class);
        $tag1->getId()->willReturn(1);
        $tag1->getName()->willReturn('Tag 1');
        $tag2 = $this->prophesize(TagInterface::class);
        $tag2->getId()->willReturn(2);
        $tag2->getName()->willReturn('Tag 2');

        $category1 = $this->prophesize(CategoryInterface::class);
        $category1->getId()->willReturn(3);
        $category2 = $this->prophesize(CategoryInterface::class);
        $category2->getId()->willReturn(4);

        $object->setSeoData([
            'title' => 'Seo Title',
            'description' => 'Seo Description',
            'keywords' => 'Seo Keyword 1, Seo Keyword 2',
            'canonicalUrl' => 'https://caninical.localhost/',
        ]);

        $object->setSeoNoIndex(true);
        $object->setSeoNoFollow(true);
        $object->setSeoHideInSitemap(true);

        $object->setExcerptData([
            'title' => 'Excerpt Title',
            'description' => 'Excerpt Description',
            'more' => 'Excerpt More',
            'image' => ['id' => 8],
            'icon' => ['id' => 9],
        ]);
        $object->setExcerptTags([$tag1->reveal(), $tag2->reveal()]);
        $object->setExcerptCategories([$category1->reveal(), $category2->reveal()]);

        $object->setTemplateKey('template-key');
        $object->setTemplateData(['someTemplate' => 'data']);

        $published = new \DateTimeImmutable('2020-02-02T12:30:00+00:00');
        $object->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_DRAFT);
        $object->setWorkflowPublished($published);

        $contentNormalizer = $this->createContentNormalizerInstance();

        $normalizedData = $contentNormalizer->normalize($object);

        $this->assertSame([
            'availableLocales' => ['en', 'de'],
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'more' => 'Excerpt More',
                'image' => ['id' => 8],
                'icon' => ['id' => 9],
            ],
            'excerptAudienceTargetGroups' => [],
            'excerptCategories' => [
                3,
                4,
            ],
            'excerptSegment' => null,
            'excerptTags' => [
                1,
                2,
            ],
            'ghostLocale' => 'en',
            'id' => 5,
            'locale' => 'de',
            'published' => '2020-02-02T12:30:00+00:00',
            'publishedState' => false,
            'seo' => [
                'title' => 'Seo Title',
                'description' => 'Seo Description',
                'keywords' => 'Seo Keyword 1, Seo Keyword 2',
                'canonicalUrl' => 'https://caninical.localhost/',
            ],
            'seoHideInSitemap' => true,
            'seoNoFollow' => true,
            'seoNoIndex' => true,
            'someTemplate' => 'data',
            'stage' => 'live',
            'template' => 'template-key',
            'version' => DimensionContentInterface::CURRENT_VERSION,
            'workflowPlace' => 'draft',
        ], $normalizedData);
    }
}
