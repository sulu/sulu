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

namespace Sulu\Content\Application\RequestWorkflow\PreValidator;

use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationResult;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;

/**
 * Requires the template properties tagged with {@see self::TAG} to be filled before the content goes live.
 * The tagged properties stay optional in a draft. It checks every publish and request of an editor,
 * whatever the request workflow of the content configures. A shadow locale is left out, because it
 * publishes the content of its source locale.
 *
 * @internal
 */
class PublishRequiredPreValidator implements RequestWorkflowPreValidatorInterface
{
    public const TAG = 'sulu_content.publish_required_pre_validator';

    // The tag is structural, not translated, so the locale is irrelevant; 'en' is a fixed sentinel.
    private const METADATA_LOCALE_FALLBACK = 'en';

    private const ROUTE_TYPES = ['route', 'page_tree_route'];

    // A selection holds display options beside its value, and those are set by default.
    private const SELECTION_VALUE_KEYS = ['ids', 'id', 'items'];

    /**
     * @var array<FormMetadata>|null
     */
    private ?array $globalBlocks = null;

    public function __construct(
        private readonly MetadataProviderRegistry $metadataProviderRegistry,
    ) {
    }

    public static function getKey(): string
    {
        return 'publish_required';
    }

    public function check(PreValidationContext $context): ValidationResult
    {
        $dimensionContent = $context->dimensionContent;
        if (!$dimensionContent instanceof TemplateInterface) {
            return ValidationResult::approve();
        }

        if ($dimensionContent instanceof ShadowInterface && null !== $dimensionContent->getShadowLocale()) {
            return ValidationResult::approve();
        }

        $form = $this->findForm($dimensionContent);
        if (!$form instanceof FormMetadata) {
            return ValidationResult::approve();
        }

        $missing = $this->collectMissing(
            $form->getFlatFieldMetadata(),
            $this->getTemplateData($dimensionContent),
            '',
            $dimensionContent,
        );

        if ([] === $missing) {
            return ValidationResult::approve();
        }

        return ValidationResult::reject(WorkflowTransitionRequestDecisionMessage::translated(
            'sulu_content.workflow_transition_request.publish_required.missing',
            ['fields' => \implode(', ', $missing)],
        ));
    }

    private function findForm(TemplateInterface $template): ?FormMetadata
    {
        $templateKey = $template->getTemplateKey();
        if (null === $templateKey || '' === $templateKey) {
            return null;
        }

        try {
            $typed = $this->metadataProviderRegistry
                ->getMetadataProvider('form')
                ->getMetadata($template::getTemplateType(), self::METADATA_LOCALE_FALLBACK, []);
        } catch (MetadataNotFoundException) {
            return null;
        }

        if (!$typed instanceof TypedFormMetadata) {
            return null;
        }

        return $typed->getForms()[$templateKey] ?? null;
    }

    /**
     * A property that is not multilingual lives on the unlocalized dimension of the same stage, while
     * the transition runs on the localized one.
     *
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T>&TemplateInterface $dimensionContent
     *
     * @return array<string, mixed>
     */
    private function getTemplateData(DimensionContentInterface $dimensionContent): array
    {
        foreach ($dimensionContent->getResource()->getDimensionContents() as $sibling) {
            if ($sibling !== $dimensionContent
                && $sibling instanceof TemplateInterface
                && null === $sibling->getLocale()
                && $sibling->getStage() === $dimensionContent->getStage()
                && DimensionContentInterface::CURRENT_VERSION === $sibling->getVersion()
            ) {
                return \array_merge($sibling->getTemplateData(), $dimensionContent->getTemplateData());
            }
        }

        return $dimensionContent->getTemplateData();
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param array<FieldMetadata> $fields
     * @param array<mixed> $data
     * @param DimensionContentInterface<T> $dimensionContent
     *
     * @return list<string>
     */
    private function collectMissing(array $fields, array $data, string $path, DimensionContentInterface $dimensionContent): array
    {
        $missing = [];

        foreach ($fields as $field) {
            $name = \explode('/', $field->getName(), 2)[0];
            $value = $data[$name] ?? null;

            if ($field->hasTag(self::TAG) && $this->isMissing($field, $value, $dimensionContent)) {
                $missing[] = $path . $name;
            }

            if ([] === $field->getTypes() || !\is_array($value)) {
                continue;
            }

            foreach ($value as $index => $block) {
                $typeKey = \is_array($block) ? ($block['type'] ?? null) : null;
                $blockType = \is_string($typeKey) ? ($field->getTypes()[$typeKey] ?? null) : null;
                if (!\is_array($block) || !$blockType instanceof FormMetadata) {
                    continue;
                }

                $missing = \array_merge($missing, $this->collectMissing(
                    $this->getBlockFields($blockType),
                    $block,
                    $path . $name . '/' . $index . '/',
                    $dimensionContent,
                ));
            }
        }

        return $missing;
    }

    /**
     * A block type that references a global block carries no fields of its own.
     *
     * @return array<FieldMetadata>
     */
    private function getBlockFields(FormMetadata $blockType): array
    {
        $tag = $blockType->getTagsByName('sulu.global_block')[0] ?? null;
        $globalBlockName = $tag?->getAttribute('global_block');

        if (\is_string($globalBlockName)) {
            $blockType = $this->getGlobalBlocks()[$globalBlockName] ?? $blockType;
        }

        return $blockType->getFlatFieldMetadata();
    }

    /**
     * @return array<FormMetadata>
     */
    private function getGlobalBlocks(): array
    {
        if (null === $this->globalBlocks) {
            try {
                $typed = $this->metadataProviderRegistry
                    ->getMetadataProvider('form')
                    ->getMetadata('block', self::METADATA_LOCALE_FALLBACK, []);
            } catch (MetadataNotFoundException) {
                $typed = null;
            }

            $this->globalBlocks = $typed instanceof TypedFormMetadata ? $typed->getForms() : [];
        }

        return $this->globalBlocks;
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     */
    private function isMissing(FieldMetadata $field, mixed $value, DimensionContentInterface $dimensionContent): bool
    {
        // The route is not kept in the template data: saving it creates the route of the dimension.
        if (\in_array($field->getType(), self::ROUTE_TYPES, true) && $dimensionContent instanceof RoutableInterface) {
            return null === $dimensionContent->getRoute();
        }

        return $this->isEmpty($value);
    }

    private function isEmpty(mixed $value): bool
    {
        if (null === $value) {
            return true;
        }

        if (\is_string($value)) {
            return '' === \trim($value);
        }

        if (\is_array($value)) {
            $selectionValue = \array_intersect_key($value, \array_flip(self::SELECTION_VALUE_KEYS));

            foreach ([] !== $selectionValue ? $selectionValue : $value as $item) {
                if (!$this->isEmpty($item)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
