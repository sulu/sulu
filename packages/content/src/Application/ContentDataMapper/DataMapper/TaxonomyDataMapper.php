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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Content\Domain\Factory\CategoryFactoryInterface;
use Sulu\Content\Domain\Factory\TargetGroupFactoryInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TaxonomyInterface;
use Webmozart\Assert\Assert;

class TaxonomyDataMapper implements DataMapperInterface
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        private CategoryFactoryInterface $categoryFactory,
        private ?TargetGroupFactoryInterface $targetGroupFactory,
    ) {
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data,
    ): void {
        if (!$localizedDimensionContent instanceof TaxonomyInterface) {
            return;
        }

        $this->setTaxonomyData($localizedDimensionContent, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setTaxonomyData(TaxonomyInterface $dimensionContent, array $data): void
    {
        if (\array_key_exists('excerptSegment', $data)) {
            $segment = $data['excerptSegment'];
            if (\is_array($data['excerptSegment'])) {
                $segment = \array_values($data['excerptSegment'])[0] ?? null;
            }
            Assert::nullOrString($segment);
            $dimensionContent->setExcerptSegment($segment);
        }
        if (\array_key_exists('excerptTags', $data)) {
            Assert::isArray($data['excerptTags']);
            Assert::allInteger($data['excerptTags']);
            $dimensionContent->setExcerptTags(
                $this->tagRepository->findTagsByIds($data['excerptTags'])
            );
        }
        if (\array_key_exists('excerptCategories', $data)) {
            Assert::isArray($data['excerptCategories']);
            Assert::allInteger($data['excerptCategories']);
            $dimensionContent->setExcerptCategories(
                $this->categoryFactory->create($data['excerptCategories']),
            );
        }
        if (\array_key_exists('excerptAudienceTargetGroups', $data) && $this->targetGroupFactory) {
            Assert::isArray($data['excerptAudienceTargetGroups']);
            Assert::allInteger($data['excerptAudienceTargetGroups']);
            $dimensionContent->setExcerptAudienceTargetGroups(
                $this->targetGroupFactory->create($data['excerptAudienceTargetGroups']),
            );
        }
    }
}
