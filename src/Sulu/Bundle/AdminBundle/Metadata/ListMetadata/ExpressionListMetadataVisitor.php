<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\ListMetadata;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal This class is internal. Create a separate visitor if you want to manipulate the metadata in your project.
 */
class ExpressionListMetadataVisitor implements ListMetadataVisitorInterface
{
    public function __construct(
        private ExpressionLanguage $expressionLanguage
    ) {
    }

    public function visitListMetadata(ListMetadata $listMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        $expressionContext = $this->getExpressionContext($locale, $metadataOptions);

        foreach ($listMetadata->getFields() as $fieldMetadata) {
            $filterTypeParameters = $fieldMetadata->getFilterTypeParameters();

            if (!\is_array($filterTypeParameters)) {
                continue;
            }

            foreach ($filterTypeParameters as $name => $filterTypeParameter) {
                if (!\is_array($filterTypeParameter) || ($filterTypeParameter['type'] ?? null) !== 'expression') {
                    continue;
                }

                /** @var string $value */
                $value = $filterTypeParameter['value'];
                $filterTypeParameters[$name] = $this->expressionLanguage->evaluate($value, $expressionContext);
            }

            $fieldMetadata->setFilterTypeParameters($filterTypeParameters);
        }
    }

    /**
     * @param mixed[] $metadataOptions
     *
     * @return mixed[]
     */
    private function getExpressionContext(string $locale, array $metadataOptions): array
    {
        return \array_merge(['locale' => $locale], $metadataOptions);
    }
}
