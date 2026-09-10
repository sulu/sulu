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

namespace Sulu\Content\Application\ContentNormalizer\Normalizer;

use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\WorkflowTransitionRequestViewFactoryInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Adds the active request to a normalized draft dimension content, which the publish toolbar renders.
 *
 * @internal
 */
class WorkflowTransitionRequestNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly ActiveWorkflowTransitionRequestProviderInterface $activeWorkflowTransitionRequestProvider,
        private readonly RequestWorkflowResolverInterface $requestWorkflowResolver,
        private readonly WorkflowTransitionRequestViewFactoryInterface $viewFactory,
    ) {
    }

    public function getIgnoredAttributes(object $object): array
    {
        return [];
    }

    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof DimensionContentInterface) {
            return $normalizedData;
        }

        if (DimensionContentInterface::STAGE_DRAFT !== $object->getStage()) {
            return $normalizedData;
        }

        $locale = $object->getLocale();
        if (null === $locale || '' === $locale) {
            return $normalizedData;
        }

        // Asked before the lookup: content outside every workflow carries none of the three fields, so a
        // project without a review workflow keeps the response it had and pays no extra query.
        if (null === $this->requestWorkflowResolver->resolveForContent($object)) {
            return $normalizedData;
        }

        try {
            $resourceId = (string) $object->getResource()->getId();
        } catch (\TypeError) {
            return $normalizedData;
        }

        $request = $this->activeWorkflowTransitionRequestProvider->find($object::getResourceKey(), $resourceId, $locale);

        $normalizedData['activeWorkflowTransitionRequest'] = null === $request
            ? null
            : $this->viewFactory->build($request);

        // Signals to the toolbar that this template participates in a request workflow, so the admin
        // hides the direct "Save & publish"/"Publish" actions and routes publishes through the review.
        $normalizedData['workflowTransitionRequestEnabled'] = true;

        // Frontend uses this to put the form into a readonly state and render the snackbar.
        // `_locked` is a generic channel, future lock sources (mandatory translations, …) reuse the key.
        $normalizedData['_locked'] = null !== $request;

        return $normalizedData;
    }
}
