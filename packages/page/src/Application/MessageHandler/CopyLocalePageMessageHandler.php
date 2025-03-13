<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\MessageHandler;

use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Application\Message\CopyLocalePageMessage;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class CopyLocalePageMessageHandler
{
    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var ContentCopierInterface
     */
    private $contentCopier;

    public function __construct(
        PageRepositoryInterface $pageRepository,
        ContentCopierInterface $contentCopier
    ) {
        $this->pageRepository = $pageRepository;
        $this->contentCopier = $contentCopier;
    }

    public function __invoke(CopyLocalePageMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());

        $this->contentCopier->copy(
            $page,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getSourceLocale(),
            ],
            $page,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getTargetLocale(),
            ]
        );
    }
}
