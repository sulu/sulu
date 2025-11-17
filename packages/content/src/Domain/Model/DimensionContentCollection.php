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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * @template-covariant T of DimensionContentInterface
 *
 * @implements \IteratorAggregate<T>
 * @implements DimensionContentCollectionInterface<T>
 */
class DimensionContentCollection implements DimensionContentCollectionInterface
{
    /**
     * @var Collection<int, T>
     */
    private $dimensionContents;

    /**
     * @var mixed[]
     */
    private $dimensionAttributes;

    /**
     * @var class-string<T>
     */
    private $dimensionContentClass;

    /**
     * @var mixed[]
     */
    private $defaultDimensionAttributes;

    /**
     * DimensionContentCollection constructor.
     *
     * @param array<int, T>|Collection<int, T> $dimensionContents
     * @param mixed[] $dimensionAttributes
     * @param class-string<T> $dimensionContentClass
     */
    public function __construct(
        array|Collection $dimensionContents,
        array $dimensionAttributes,
        string $dimensionContentClass
    ) {
        $this->dimensionContentClass = $dimensionContentClass;
        $this->defaultDimensionAttributes = $dimensionContentClass::getDefaultDimensionAttributes();
        $this->dimensionAttributes = $dimensionContentClass::getEffectiveDimensionAttributes($dimensionAttributes);

        // TODO remove the array conversion
        $this->dimensionContents = $dimensionContents instanceof Collection ? $dimensionContents : new ArrayCollection($dimensionContents);
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
        return \count($this->dimensionContents);
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
