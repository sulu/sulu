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

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadataVisitorInterface;

/**
 * Resolves enum() and const() placeholders in visibleCondition and disabledCondition.
 *
 * This allows using PHP backed enums and constants in form metadata conditions:
 *
 *     <property name="title" visibleCondition="status == enum(App\Enum\Status::ACTIVE)">
 *     <property name="title" disabledCondition="type == const(App\MyClass::TYPE_DRAFT)">
 *
 * @author Pascal CESCON <pascal.cescon@gmail.com>
 */
class ConditionEnumResolverFormMetadataVisitor implements FormMetadataVisitorInterface, TypedFormMetadataVisitorInterface
{
    private const PATTERN = '/\b(enum|const)\(([^)]+)\)/';

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        $this->resolveConditions($formMetadata->getItems());
    }

    public function visitTypedFormMetadata(TypedFormMetadata $formMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        foreach ($formMetadata->getForms() as $formType) {
            $this->resolveConditions($formType->getItems());
        }
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function resolveConditions(array $items): void
    {
        foreach ($items as $item) {
            $this->resolveItemCondition($item);

            if ($item instanceof SectionMetadata) {
                $this->resolveConditions($item->getItems());
            }

            if ($item instanceof FieldMetadata) {
                foreach ($item->getTypes() as $type) {
                    $this->resolveConditions($type->getItems());
                }
            }
        }
    }

    private function resolveItemCondition(ItemMetadata $item): void
    {
        $visibleCondition = $item->getVisibleCondition();
        if (null !== $visibleCondition && \preg_match(self::PATTERN, $visibleCondition)) {
            $item->setVisibleCondition($this->resolveExpression($visibleCondition));
        }

        $disabledCondition = $item->getDisabledCondition();
        if (null !== $disabledCondition && \preg_match(self::PATTERN, $disabledCondition)) {
            $item->setDisabledCondition($this->resolveExpression($disabledCondition));
        }
    }

    private function resolveExpression(string $expression): string
    {
        return (string) \preg_replace_callback(self::PATTERN, static function (array $matches): string {
            $type = $matches[1];
            $reference = \trim($matches[2]);

            if ('enum' === $type) {
                return self::resolveEnum($reference);
            }

            return self::resolveConst($reference);
        }, $expression);
    }

    private static function resolveEnum(string $reference): string
    {
        if (!\str_contains($reference, '::')) {
            throw new \InvalidArgumentException(\sprintf('Invalid enum reference "%s". Expected format: "App\Enum\MyEnum::CASE".', $reference));
        }

        [$class, $case] = \explode('::', $reference, 2);

        if (!\enum_exists($class)) {
            throw new \InvalidArgumentException(\sprintf('Enum class "%s" does not exist.', $class));
        }

        $reflection = new \ReflectionEnum($class);
        if (!$reflection->isBacked()) {
            throw new \InvalidArgumentException(\sprintf('Enum "%s" must be a backed enum to be used in conditions.', $class));
        }

        if (!$reflection->hasCase($case)) {
            throw new \InvalidArgumentException(\sprintf('Enum case "%s::%s" does not exist.', $class, $case));
        }

        $value = $reflection->getCase($case)->getValue()->value;

        return \is_string($value) ? "'" . $value . "'" : (string) $value;
    }

    private static function resolveConst(string $reference): string
    {
        if (!\defined($reference)) {
            throw new \InvalidArgumentException(\sprintf('Constant "%s" is not defined.', $reference));
        }

        $value = \constant($reference);

        if (\is_string($value)) {
            return "'" . $value . "'";
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        throw new \InvalidArgumentException(\sprintf('Constant "%s" must resolve to a scalar value, got "%s".', $reference, \get_debug_type($value)));
    }
}