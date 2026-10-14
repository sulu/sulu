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
use Sulu\Content\Application\MetadataResolver\GlobalBlockMetadataResolver;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationResult;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;

/**
 * Requires the template properties tagged with {@see self::TAG} to be filled before the content goes live.
 * The tagged properties stay optional in a draft. A shadow locale is left out, because it publishes
 * the content of its source locale.
 *
 * @internal
 */
class PublishRequiredPreValidator implements RequestWorkflowPreValidatorInterface
{
    public const TAG = 'sulu_content.publish_required_pre_validator';

    // The tag is structural, not translated, so the locale is irrelevant; 'en' is a fixed sentinel.
    private const METADATA_LOCALE_FALLBACK = 'en';

    public function __construct(
        private readonly MetadataProviderRegistry $metadataProviderRegistry,
        private readonly GlobalBlockMetadataResolver $globalBlockMetadataResolver,
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

        $emptyFields = $this->collectEmptyPublishRequired($form->getFlatFieldMetadata(), $dimensionContent->getTemplateData(), '');

        if ([] === $emptyFields) {
            return ValidationResult::approve();
        }

        return ValidationResult::reject(WorkflowTransitionRequestDecisionMessage::translated(
            'sulu_content.workflow_transition_request.publish_required.missing',
            ['fields' => \implode(', ', $emptyFields)],
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
     * @param array<FieldMetadata> $fields
     * @param array<mixed> $data
     *
     * @return list<string>
     */
    private function collectEmptyPublishRequired(array $fields, array $data, string $path): array
    {
        $emptyFields = [];

        foreach ($fields as $field) {
            $name = \explode('/', $field->getName(), 2)[0];
            $value = $data[$name] ?? null;

            if ($field->hasTag(self::TAG) && $this->isEmpty($value)) {
                $emptyFields[] = $path . $name;
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

                $emptyFields = \array_merge($emptyFields, $this->collectEmptyPublishRequired(
                    $this->globalBlockMetadataResolver->resolve($blockType, self::METADATA_LOCALE_FALLBACK)->getFlatFieldMetadata(),
                    $block,
                    $path . $name . '/' . $index . '/',
                ));
            }
        }

        return $emptyFields;
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
            // Beside its values, a selection holds options such as a display option, which are set by default.
            $nested = \array_filter($value, \is_array(...));

            foreach ([] !== $nested ? $nested : $value as $item) {
                if (!$this->isEmpty($item)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
