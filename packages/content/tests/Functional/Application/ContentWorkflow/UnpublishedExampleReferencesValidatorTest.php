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

namespace Sulu\Content\Tests\Functional\Application\ContentWorkflow;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\Kernel;
use Sulu\Content\Tests\Traits\ConsumeTransportTrait;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;

/**
 * Proves a validator that does real work rather than echoing a configured verdict: it reads the
 * reference table for what the draft links to, checks each target's publication state, and either
 * approves or rejects with one comment naming everything it found.
 */
#[CoversNothing]
class UnpublishedExampleReferencesValidatorTest extends SuluTestCase
{
    use ConsumeTransportTrait;
    use WorkflowTransitionRequestTrait;

    private const REFERENCES_TEMPLATE = 'example-references-workflow';

    private ContentManagerInterface $contentManager;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected function setUp(): void
    {
        static::bootKernel();
        self::purgeDatabase();

        $this->contentManager = static::getContainer()->get(ContentManagerInterface::class);
        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);

        $this->authenticateAsRequestCreator();
    }

    public function testUnpublishedTargetRejectsWithACommentNamingIt(): void
    {
        $target = $this->createSelectionExample(['title' => 'Never published'], publish: false);
        $referrer = $this->createExampleReferencing($target);

        $request = $this->sendForReview($referrer);

        // Nothing has reported yet, so the check counts as nothing.
        $this->assertSame(WorkflowTransitionRequestDecisionStatusEnum::PENDING, $request->getDecisions()[0]->getStatus());
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $this->resolveRequestStatus($request));

        $this->consumeTransport(Kernel::VALIDATION_TRANSPORT);
        $reviewer = $this->reloadRequest($referrer)->getDecisions()[0];

        $this->assertSame(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, $reviewer->getStatus());
        $this->assertSame(
            \sprintf('1 selected example is not published: %s', $target->getId()),
            $reviewer->getMessages()[0]->text,
        );
        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $this->resolveRequestStatus($this->reloadRequest($referrer)),
            'A rejecting check does not count as an approval, and it does not block either.',
        );
    }

    public function testPublishedTargetPassesTheCheckWithoutApprovingTheRequest(): void
    {
        $target = $this->createSelectionExample(['title' => 'Already live'], publish: true);
        $referrer = $this->createExampleReferencing($target);

        $this->sendForReview($referrer);
        $this->consumeTransport(Kernel::VALIDATION_TRANSPORT);

        $request = $this->reloadRequest($referrer);
        $this->assertSame(WorkflowTransitionRequestDecisionStatusEnum::APPROVED, $request->getDecisions()[0]->getStatus());
        $this->assertSame([], $request->getDecisions()[0]->getMessages(), 'A passing check says nothing.');
        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $this->resolveRequestStatus($request),
            'A check reports on the content, it never stands in for the required human approval.',
        );
    }

    public function testNamesEveryUnpublishedTargetInOneComment(): void
    {
        $first = $this->createSelectionExample(['title' => 'First draft only'], publish: false);
        $second = $this->createSelectionExample(['title' => 'Second draft only'], publish: false);
        $referrer = $this->createExampleReferencing($first, $second);

        $this->sendForReview($referrer);
        $this->consumeTransport(Kernel::VALIDATION_TRANSPORT);

        $comment = (string) $this->reloadRequest($referrer)->getDecisions()[0]->getMessages()[0]->text;

        $this->assertStringStartsWith('2 selected examples are not published: ', $comment);
        $this->assertStringContainsString((string) $first->getId(), $comment);
        $this->assertStringContainsString((string) $second->getId(), $comment);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createSelectionExample(array $data, bool $publish): Example
    {
        $dimensions = ['en' => ['draft' => \array_merge(['template' => self::REFERENCES_TEMPLATE], $data)]];

        if ($publish) {
            $dimensions['en']['live'] = \array_merge(['template' => self::REFERENCES_TEMPLATE], $data);
        }

        $example = static::createExample($dimensions);
        static::getEntityManager()->flush();

        return $example;
    }

    private function createExampleReferencing(Example ...$targets): Example
    {
        $referrer = $this->createSelectionExample([
            'title' => 'Referrer',
            'examples' => \array_map(static fn (Example $target) => $target->getId(), $targets),
        ], publish: false);

        // References are written by the refresher on flush, so they exist before the review starts.
        static::getEntityManager()->flush();

        return $referrer;
    }

    private function sendForReview(Example $example): WorkflowTransitionRequest
    {
        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        return $this->reloadRequest($example);
    }

    private function reloadRequest(Example $example): WorkflowTransitionRequest
    {
        static::getEntityManager()->clear();

        return $this->workflowTransitionRequestRepository->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);
    }
}
