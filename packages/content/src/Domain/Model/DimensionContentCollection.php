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

namespace Sulu\Content\Domain\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * @template-covariant T of DimensionContentInterface
 *
 * @implements DimensionContentCollectionInterface<T>
 */
class DimensionContentCollection implements DimensionContentCollectionInterface
{
    /**
     * @var mixed[]
     */
    private array $dimensionAttributes;

    /**
     * @var mixed[]
     */
    private array $defaultDimensionAttributes;

    /**
     * DimensionContentCollection constructor.
     *
     * @param Collection<int, T> $dimensionContents
     * @param mixed[] $dimensionAttributes
     * @param class-string<T> $dimensionContentClass
     */
    public function __construct(
        private Collection $dimensionContents,
        array $dimensionAttributes,
        private string $dimensionContentClass
    ) {
        $this->defaultDimensionAttributes = $this->dimensionContentClass::getDefaultDimensionAttributes();
        $this->dimensionAttributes = $this->dimensionContentClass::getEffectiveDimensionAttributes($dimensionAttributes);
    }

    public function getDimensionContentClass(): string
    {
        return $this->dimensionContentClass;
    }

    public function getDimensionContent(array $dimensionAttributes): ?DimensionContentInterface
    {
        $dimensionAttributes = \array_merge($this->defaultDimensionAttributes, $dimensionAttributes);
        $criteria = $this->buildCriteriaFromAttributes($dimensionAttributes);

        return $this->dimensionContents->matching($criteria)->first() ?: null;
    }

    public function getDimensionAttributes(): array
    {
        return $this->dimensionAttributes;
    }

    /**
     * @return \Traversable<T>
     */
    public function getIterator(): \Traversable
    {
        $attributesWithoutLocale = $this->dimensionAttributes;
        $locale = $attributesWithoutLocale['locale'] ?? null;
        unset($attributesWithoutLocale['locale']);

        $criteria = $this->buildCriteriaFromAttributes($attributesWithoutLocale);

        if ($locale) {
            $criteria->andWhere(
                Criteria::expr()->orX(
                    Criteria::expr()->isNull('locale'),
                    Criteria::expr()->eq('locale', $locale)
                )
            );
        } else {
            $criteria->andWhere(Criteria::expr()->isNull('locale'));
        }

        return $this->dimensionContents->matching($criteria)->getIterator();
    }

    public function count(): int
    {
        return \iterator_count($this->getIterator());
    }

    /**
     * @param mixed[] $dimensionAttributes
     */
    private function buildCriteriaFromAttributes(array $dimensionAttributes): Criteria
    {
        $criteria = Criteria::create();

        foreach ($dimensionAttributes as $key => $value) {
            if (null === $value) {
                $expr = $criteria->expr()->isNull($key);
            } else {
                $expr = $criteria->expr()->eq($key, $value);
            }

            $criteria->andWhere($expr);
        }

        return $criteria;
    }
}
