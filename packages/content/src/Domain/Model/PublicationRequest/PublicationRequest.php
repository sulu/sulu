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

namespace Sulu\Content\Domain\Model\PublicationRequest;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Domain\Exception\PublicationRequestClosedException;
use Symfony\Component\Uid\Uuid;

class PublicationRequest implements AuditableInterface
{
    use AuditableTrait;

    private string $id;

    private PublicationRequestStatusEnum $status = PublicationRequestStatusEnum::PENDING;

    private ?string $activeKey = null;

    private \DateTimeImmutable $requestedAt;

    /**
     * @var Collection<int, PublicationRequestReviewer>
     */
    private Collection $reviewers;

    public function __construct(
        private readonly string $resourceKey,
        private readonly string $resourceId,
        private readonly string $locale,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->requestedAt = new \DateTimeImmutable();
        $this->reviewers = new ArrayCollection();
        $this->syncActiveKey();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getStatus(): PublicationRequestStatusEnum
    {
        return $this->status;
    }

    public function getActiveKey(): ?string
    {
        return $this->activeKey;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    /**
     * @return list<PublicationRequestReviewer>
     */
    public function getReviewers(): array
    {
        return \array_values($this->reviewers->toArray());
    }

    public function addApproval(UserInterface $user, ?string $comment = null): void
    {
        if ($this->status->isClosed()) {
            throw new PublicationRequestClosedException($this);
        }

        $this->updateOrAddReviewer($user, PublicationRequestReviewerStatusEnum::APPROVED, $comment);
        $this->synchronizeStatus();
    }

    public function addRejection(UserInterface $user, ?string $comment = null): void
    {
        if ($this->status->isClosed()) {
            throw new PublicationRequestClosedException($this);
        }

        $this->updateOrAddReviewer($user, PublicationRequestReviewerStatusEnum::REJECTED, $comment);
        $this->synchronizeStatus();
    }

    public function cancel(): void
    {
        $this->transitionTo(PublicationRequestStatusEnum::CANCELLED);
    }

    public function publish(): void
    {
        $this->transitionTo(PublicationRequestStatusEnum::PUBLISHED);
    }

    private function updateOrAddReviewer(UserInterface $user, PublicationRequestReviewerStatusEnum $status, ?string $comment): void
    {
        foreach ($this->reviewers as $reviewer) {
            if ($reviewer->getCreator() === $user) {
                $reviewer->update($status, $comment);

                return;
            }
        }

        $reviewer = new PublicationRequestReviewer($user, $status, $comment);
        $reviewer->setPublicationRequest($this);
        $this->reviewers->add($reviewer);
    }

    private function synchronizeStatus(): void
    {
        if ($this->hasRejectedReviewer()) {
            $this->transitionTo(PublicationRequestStatusEnum::REJECTED);

            return;
        }

        if ($this->reviewers->count() > 0) {
            $this->transitionTo(PublicationRequestStatusEnum::APPROVED);
        }
    }

    private function hasRejectedReviewer(): bool
    {
        foreach ($this->reviewers as $reviewer) {
            if (PublicationRequestReviewerStatusEnum::REJECTED === $reviewer->getStatus()) {
                return true;
            }
        }

        return false;
    }

    private function transitionTo(PublicationRequestStatusEnum $toStatus): void
    {
        if ($this->status === $toStatus) {
            return;
        }

        $this->status = $toStatus;
        $this->syncActiveKey();
    }

    private function syncActiveKey(): void
    {
        $this->activeKey = $this->status->isActive()
            ? \sprintf('%s:%s:%s', $this->resourceKey, $this->resourceId, $this->locale)
            : null;
    }
}
