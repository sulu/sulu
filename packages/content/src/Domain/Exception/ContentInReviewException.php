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

namespace Sulu\Content\Domain\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

/**
 * Thrown when a draft save targets content sitting in a review place. The content workflow has no
 * `edit` transition out of `review`/`review_draft`, so the draft is frozen until the review is
 * published, rejected or cancelled.
 */
class ContentInReviewException extends \RuntimeException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(
        private readonly string $resourceKey,
        private readonly string $resourceId,
        private readonly string $locale,
    ) {
        parent::__construct(\sprintf(
            'The content "%s" "%s" in locale "%s" is in review and cannot be modified until the review is resolved.',
            $resourceKey,
            $resourceId,
            $locale,
        ));
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.content_in_review';
    }

    public function getMessageTranslationParameters(): array
    {
        return [
            '{resourceKey}' => $this->resourceKey,
            '{resourceId}' => $this->resourceId,
            '{locale}' => $this->locale,
        ];
    }
}
