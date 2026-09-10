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

namespace Sulu\Content\Application\RequestWorkflow\PreValidator\Builtin;

use Sulu\Content\Application\RequestWorkflow\PreValidator\PreValidationContext;
use Sulu\Content\Application\RequestWorkflow\PreValidator\PreValidationFailure;
use Sulu\Content\Application\RequestWorkflow\PreValidator\RequestWorkflowPreValidatorInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;

/**
 * Requires the configured excerpt fields to be filled; content without an excerpt passes.
 */
final class ExcerptRequiredPreValidator implements RequestWorkflowPreValidatorInterface
{
    private const DEFAULT_FIELDS = ['title', 'description'];

    public static function getKey(): string
    {
        return 'excerpt_required';
    }

    public function check(PreValidationContext $context): array
    {
        $dimensionContent = $context->dimensionContent;
        if (!$dimensionContent instanceof ExcerptInterface) {
            return [];
        }

        // The config tree passes this through unvalidated, so the shape is genuinely unknown here:
        // a scalar `fields: title` would iterate zero times and silently approve everything.
        $fields = $context->config['fields'] ?? self::DEFAULT_FIELDS;
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
            return [];
        }

        return [new PreValidationFailure(
            'sulu_content.workflow_transition_request.excerpt_required.missing',
            ['fields' => \implode(', ', $missing)],
        )];
    }
}
