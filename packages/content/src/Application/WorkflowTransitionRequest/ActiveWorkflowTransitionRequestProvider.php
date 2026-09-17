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

namespace Sulu\Content\Application\WorkflowTransitionRequest;

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;

/**
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class ActiveWorkflowTransitionRequestProvider implements ActiveWorkflowTransitionRequestProviderInterface
{
    public function __construct(
        private readonly WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository,
    ) {
    }

    public function find(string $resourceKey, string $resourceId, string $locale): ?WorkflowTransitionRequest
    {
        return $this->workflowTransitionRequestRepository->findOneBy([
            'resourceKey' => $resourceKey,
            'resourceId' => $resourceId,
            'locale' => $locale,
            'active' => true,
        ]);
    }

    public function findForContent(DimensionContentInterface $dimensionContent): ?WorkflowTransitionRequest
    {
        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        return $this->find(
            $dimensionContent::getResourceKey(),
            (string) $dimensionContent->getResource()->getId(),
            $locale,
        );
    }
}
