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

use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Uid\Uuid;

class PublicationRequestReviewer implements AuditableInterface
{
    use AuditableTrait;

    private string $id;

    private PublicationRequest $publicationRequest;

    private PublicationRequestReviewerStatusEnum $status;

    private ?string $comment;

    public function __construct(
        UserInterface $user,
        PublicationRequestReviewerStatusEnum $status,
        ?string $comment = null,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->status = $status;
        $this->comment = $comment;
        $this->setCreator($user);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPublicationRequest(): PublicationRequest
    {
        return $this->publicationRequest;
    }

    public function setPublicationRequest(PublicationRequest $publicationRequest): void
    {
        $this->publicationRequest = $publicationRequest;
    }

    public function getStatus(): PublicationRequestReviewerStatusEnum
    {
        return $this->status;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function update(PublicationRequestReviewerStatusEnum $status, ?string $comment = null): void
    {
        $this->status = $status;
        $this->comment = $comment;
    }
}
