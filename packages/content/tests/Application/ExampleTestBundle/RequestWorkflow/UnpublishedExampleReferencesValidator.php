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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\RequestWorkflow;

use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\RequestWorkflowValidatorInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationContext;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationDecision;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;

/**
 * Reference implementation: reports linked examples readers cannot reach. Reads nothing from the
 * HTTP request, so the verdict is the same inline and on a worker. Only selection properties
 * produce references, so it never claims completeness.
 */
final class UnpublishedExampleReferencesValidator implements RequestWorkflowValidatorInterface
{
    public function __construct(
        private readonly ReferenceRepositoryInterface $referenceRepository,
        private readonly ExampleRepository $exampleRepository,
    ) {
    }

    public static function getKey(): string
    {
        return 'unpublished_example_references';
    }

    public function check(ValidationContext $context): ValidationDecision
    {
        $request = $context->request;
        $locale = $request->getLocale();

        $unpublished = [];
        $missing = [];
        foreach ($this->findReferencedExampleIds($request->getResourceKey(), $request->getResourceId(), $locale) as $resourceId) {
            if (null === $this->exampleRepository->findOneBy(['id' => $resourceId])) {
                $missing[] = $resourceId;

                continue;
            }

            $live = $this->exampleRepository->findOneBy([
                'id' => $resourceId,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'locale' => $locale,
            ]);

            if (null === $live) {
                $unpublished[] = $resourceId;
            }
        }

        $findings = [];
        if ([] !== $unpublished) {
            $findings[] = $this->describe($unpublished, 'example is not published', 'examples are not published');
        }

        // Two lookups, so "deleted" and "not published yet" stay distinguishable: one asks the author to
        // publish something, the other to remove a dead link.
        if ([] !== $missing) {
            $findings[] = $this->describe($missing, 'example no longer exists', 'examples no longer exist');
        }

        return [] === $findings
            ? ValidationDecision::approve()
            : ValidationDecision::reject(\implode(' ', $findings));
    }

    /**
     * @param list<string> $resourceIds
     */
    private function describe(array $resourceIds, string $singular, string $plural): string
    {
        return \sprintf(
            '%d selected %s: %s',
            \count($resourceIds),
            1 === \count($resourceIds) ? $singular : $plural,
            \implode(', ', $resourceIds),
        );
    }

    /**
     * @return iterable<string>
     */
    private function findReferencedExampleIds(string $resourceKey, string $resourceId, string $locale): iterable
    {
        /** @var iterable<array{resourceKey?: string, resourceId?: string}> $rows */
        $rows = $this->referenceRepository->findFlatBy(
            [
                'referenceResourceKey' => $resourceKey,
                'referenceResourceId' => $resourceId,
                'referenceLocale' => $locale,
                // The draft stage is what is under review. Filtering on `live` would only see the links
                // the already published version has, which is the opposite of the question being asked.
                'referenceContext' => DimensionContentInterface::STAGE_DRAFT,
            ],
            [],
            ['resourceKey', 'resourceId'],
            true,
        );

        foreach ($rows as $row) {
            if (Example::RESOURCE_KEY !== ($row['resourceKey'] ?? null)) {
                continue;
            }

            $referencedResourceId = $row['resourceId'] ?? null;
            if (null !== $referencedResourceId) {
                yield $referencedResourceId;
            }
        }
    }
}
