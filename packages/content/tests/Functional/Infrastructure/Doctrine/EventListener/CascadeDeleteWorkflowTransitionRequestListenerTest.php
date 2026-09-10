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

namespace Sulu\Content\Tests\Functional\Infrastructure\Doctrine\EventListener;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Content\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Content\Tests\Traits\CreateExampleTrait;

#[CoversNothing]
class CascadeDeleteWorkflowTransitionRequestListenerTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateExampleTrait;
    use CreateMediaTrait;
    use CreateTagTrait;

    private ContentManagerInterface $contentManager;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected function setUp(): void
    {
        self::purgeDatabase();
        $this->contentManager = static::getContainer()->get(ContentManagerInterface::class);
        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);
    }

    public function testUnpublishKeepsTheWorkflowTransitionRequestsOfTheLocale(): void
    {
        $example = static::createExample([
            'en' => [
                'draft' => ['template' => 'example-2', 'title' => 'Title EN', 'url' => '/en'],
                'live' => ['template' => 'example-2', 'title' => 'Title EN', 'url' => '/en'],
            ],
        ]);
        static::getEntityManager()->flush();

        $resourceId = (string) $example->getId();
        $this->persistRequest($resourceId, 'en');

        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH,
        );
        static::getEntityManager()->flush();

        $this->assertSame(1, $this->workflowTransitionRequestRepository->countBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => $resourceId,
            'locale' => 'en',
        ]), 'An unpublish removes only the live dimension, so the review history of the locale must survive.');
    }

    public function testRemovingTheContentRichEntityPurgesOnlyItsWorkflowTransitionRequests(): void
    {
        $example = $this->createExampleFixture();
        $unrelated = $this->createExampleFixture();
        $resourceId = (string) $example->getId();

        $this->persistRequest($resourceId, 'en');
        $this->persistRequest($resourceId, 'de');
        $this->persistRequest((string) $unrelated->getId(), 'en');

        $entityManager = static::getEntityManager();
        $entityManager->remove($example);
        $entityManager->flush();

        $this->assertSame(0, $this->workflowTransitionRequestRepository->countBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => $resourceId,
        ]));
        $this->assertSame(1, $this->workflowTransitionRequestRepository->countBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $unrelated->getId(),
        ]));
    }

    public function testRemovingAllDimensionContentsPurgesAllWorkflowTransitionRequests(): void
    {
        $example = $this->createExampleFixture();
        $resourceId = (string) $example->getId();

        $this->persistRequest($resourceId, 'en');
        $this->persistRequest($resourceId, 'de');
        $unrelated = $this->createExampleFixture();
        $this->persistRequest((string) $unrelated->getId(), 'en');

        $entityManager = static::getEntityManager();
        foreach ($example->getDimensionContents() as $dimensionContent) {
            $entityManager->remove($dimensionContent);
        }
        $entityManager->flush();

        $this->assertSame(0, $this->workflowTransitionRequestRepository->countBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => $resourceId,
        ]));
        $this->assertSame(1, $this->workflowTransitionRequestRepository->countBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $unrelated->getId(),
        ]));
    }

    public function testRemovingDimensionContentPurgesOnlyMatchingLocale(): void
    {
        $example = $this->createExampleFixture();
        $resourceId = (string) $example->getId();

        $this->persistRequest($resourceId, 'en');
        $this->persistRequest($resourceId, 'de');

        $entityManager = static::getEntityManager();
        foreach ($example->getDimensionContents() as $dimensionContent) {
            if ('en' === $dimensionContent->getLocale()) {
                $entityManager->remove($dimensionContent);
            }
        }
        $entityManager->flush();

        $this->assertSame(0, $this->workflowTransitionRequestRepository->countBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => $resourceId,
            'locale' => 'en',
        ]));
        $this->assertSame(1, $this->workflowTransitionRequestRepository->countBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => $resourceId,
            'locale' => 'de',
        ]));
    }

    private function createExampleFixture(): Example
    {
        $example = static::createExample([
            'en' => [
                'draft' => ['template' => 'example-2', 'title' => 'Title EN', 'url' => '/en'],
            ],
            'de' => [
                'draft' => ['template' => 'example-2', 'title' => 'Title DE', 'url' => '/de'],
            ],
        ]);
        static::getEntityManager()->flush();

        return $example;
    }

    private function persistRequest(string $resourceId, string $locale): void
    {
        $request = new WorkflowTransitionRequest(Example::RESOURCE_KEY, $resourceId, $locale, 'default');
        $this->workflowTransitionRequestRepository->add($request);
        static::getEntityManager()->flush();
    }
}
