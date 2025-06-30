<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal This class is internal. Create a separate visitor if you want to manipulate the metadata in your project.
 */
class ExpressionFormMetadataVisitor implements FormMetadataVisitorInterface, TypedFormMetadataVisitorInterface
{
    public function __construct(
        private ExpressionLanguage $expressionLanguage
    ) {
    }

    public static function getDefaultPriority(): int
    {
        return -90;
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        $expressionContext = $this->getExpressionContext($locale, $metadataOptions);

        $this->evaluateFormItemExpressions($formMetadata->getItems(), $expressionContext);
    }

    public function visitTypedFormMetadata(TypedFormMetadata $formMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        $expressionContext = $this->getExpressionContext($locale, $metadataOptions);

        foreach ($formMetadata->getForms() as $formType) {
            $this->evaluateFormItemExpressions($formType->getItems(), $expressionContext);
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

    /**
     * @param ItemMetadata[] $items
     * @param mixed[] $context
     */
    private function evaluateFormItemExpressions(array $items, array $context): void
    {
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                $this->evaluateFormItemExpressions($item->getItems(), $context);
            }

            if ($item instanceof FieldMetadata) {
                foreach ($item->getTypes() as $type) {
                    $this->evaluateFormItemExpressions($type->getItems(), $context);
                }

                foreach ($item->getOptions() as $option) {
                    if (OptionMetadata::TYPE_EXPRESSION === $option->getType()) {
                        /** @var string $value */
                        $value = $option->getValue();
                        $option->setValue($this->expressionLanguage->evaluate($value, $context));
                    }
                }
            }
        }
    }
}
