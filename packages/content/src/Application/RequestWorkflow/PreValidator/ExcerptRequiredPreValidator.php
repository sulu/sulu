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

use Sulu\Content\Application\RequestWorkflow\Validator\ValidationResult;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\DecisionMessage;

/**
 * Requires the configured excerpt fields to be filled; content without an excerpt passes.
 *
 * @internal
 */
class ExcerptRequiredPreValidator implements RequestWorkflowPreValidatorInterface
{
    public static function getKey(): string
    {
        return 'excerpt_required';
    }

    public function check(PreValidationContext $context): ValidationResult
    {
        $dimensionContent = $context->dimensionContent;
        if (!$dimensionContent instanceof ExcerptInterface) {
            return ValidationResult::approve();
        }

        // The config tree passes this through unvalidated, so the shape is genuinely unknown here:
        // a scalar `fields: title` would iterate zero times and silently approve everything.
        $fields = $context->config['fields'] ?? null;
        if (!\is_array($fields) || [] === $fields) {
            throw new \LogicException(\sprintf(
                'The "%s" pre-validator needs a non-empty list of fields, got %s.',
                self::getKey(),
                \get_debug_type($context->config['fields'] ?? null),
            ));
        }

        // The data bag holds every field of the excerpt form, built-ins and anything a project
        // adds by overriding the template, so a configured field needs no mapping here.
        $excerptData = $dimensionContent->getExcerptData();

        $missing = [];
        foreach ($fields as $field) {
            if (!\is_string($field)) {
                throw new \LogicException(\sprintf(
                    'The "%s" pre-validator needs field names, got %s.',
                    self::getKey(),
                    \get_debug_type($field),
                ));
            }

            $value = $excerptData[$field] ?? null;
            if (!\is_string($value) || '' === \trim($value)) {
                $missing[] = $field;
            }
        }

        if ([] === $missing) {
            return ValidationResult::approve();
        }

        return ValidationResult::reject(DecisionMessage::translated(
            'sulu_content.workflow_transition_request.excerpt_required.missing',
            ['fields' => \implode(', ', $missing)],
        ));
    }
}
