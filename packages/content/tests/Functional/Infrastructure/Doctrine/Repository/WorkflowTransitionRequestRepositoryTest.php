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

namespace Sulu\Content\Tests\Functional\Infrastructure\Doctrine\Repository;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;

#[CoversNothing]
class WorkflowTransitionRequestRepositoryTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);
    }

    public function testFindByReturnsClosedAndOpenRequestsNewestFirst(): void
    {
        $creator = $this->createRequestCreator();

        // Only one request per content may be open, so each one is closed before the next is added.
        $cancelled = $this->persistRequest('examples', '1', 'en', $creator);
        $cancelled->cancel();
        static::getEntityManager()->flush();
        $published = $this->persistRequest('examples', '1', 'en', $creator);
        $published->publish();
        static::getEntityManager()->flush();
        $open = $this->persistRequest('examples', '1', 'en', $creator);

        $rows = $this->workflowTransitionRequestRepository->findBy([
            'resourceKey' => 'examples',
            'resourceId' => '1',
            'locale' => 'en',
        ]);

        $this->assertSame(
            [$open->getId(), $published->getId(), $cancelled->getId()],
            \array_map(static fn (WorkflowTransitionRequest $row) => $row->getId(), $rows),
            'Newest first, with the uuid breaking a tie inside the same second.',
        );
        $this->assertSame(
            ['open', 'published', 'cancelled'],
            \array_map(static fn (WorkflowTransitionRequest $row) => $row->getPlace()->value, $rows),
        );
        $this->assertSame(
            ['Request Creator', 'Request Creator', 'Request Creator'],
            \array_map(static fn (WorkflowTransitionRequest $row) => $row->getCreator()?->getFullName(), $rows),
        );
    }

    public function testFindByReturnsNullRequesterWithoutCreator(): void
    {
        $this->persistRequest('examples', '1', 'en');

        $rows = $this->workflowTransitionRequestRepository->findBy(['resourceKey' => 'examples']);

        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]->getCreator());
    }

    public function testFindByFiltersByResourceAndLocale(): void
    {
        $wanted = $this->persistRequest('examples', '1', 'en');
        $this->persistRequest('examples', '1', 'de');
        $this->persistRequest('examples', '2', 'en');
        $this->persistRequest('snippets', '1', 'en');

        $rows = $this->workflowTransitionRequestRepository->findBy([
            'resourceKey' => 'examples',
            'resourceId' => '1',
            'locale' => 'en',
        ]);

        $this->assertSame([$wanted->getId()], \array_map(static fn (WorkflowTransitionRequest $row) => $row->getId(), $rows));
    }

    public function testFindByPaginates(): void
    {
        $first = $this->persistRequest('examples', '1', 'en');
        $first->cancel();
        static::getEntityManager()->flush();
        $second = $this->persistRequest('examples', '1', 'en');
        $second->cancel();
        static::getEntityManager()->flush();
        $third = $this->persistRequest('examples', '1', 'en');

        $filters = ['resourceKey' => 'examples', 'resourceId' => '1', 'locale' => 'en'];

        $this->assertSame(
            [$third->getId()],
            \array_map(static fn (WorkflowTransitionRequest $row) => $row->getId(), $this->workflowTransitionRequestRepository->findBy($filters, 1, 0)),
        );
        $this->assertSame(
            [$second->getId()],
            \array_map(static fn (WorkflowTransitionRequest $row) => $row->getId(), $this->workflowTransitionRequestRepository->findBy($filters, 1, 1)),
        );
        $this->assertSame(3, $this->workflowTransitionRequestRepository->countBy($filters));
    }

    public function testFindByRejectsUnknownFilterKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore argument.type
        $this->workflowTransitionRequestRepository->findBy(['unknown' => 'value']);
    }

    private function persistRequest(
        string $resourceKey,
        string $resourceId,
        string $locale,
        ?UserInterface $creator = null,
    ): WorkflowTransitionRequest {
        $workflowTransitionRequest = new WorkflowTransitionRequest($resourceKey, $resourceId, $locale, 'review');
        if (null !== $creator) {
            $workflowTransitionRequest->setCreator($creator);
        }

        $this->workflowTransitionRequestRepository->add($workflowTransitionRequest);
        static::getEntityManager()->flush();

        return $workflowTransitionRequest;
    }
}
