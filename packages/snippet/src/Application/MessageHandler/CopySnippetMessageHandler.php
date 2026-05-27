<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Application\MessageHandler;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Snippet\Application\Message\CopySnippetMessage;
use Sulu\Snippet\Domain\Event\SnippetCopiedEvent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class CopySnippetMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private ContentCopierInterface $contentCopier,
        private LocalizationManagerInterface $localizationManager,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(CopySnippetMessage $message): SnippetInterface
    {
        $allLocales = [];
        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $allLocales[] = $localization->getLocale();
        }

        $sourceSnippet = $this->snippetRepository->getOneBy(
            $message->getSourceIdentifier(),
            [
                SnippetRepositoryInterface::SELECT_SNIPPET_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true],
                    'dimensionAttributes' => [
                        'locale' => $allLocales,
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                    ],
                ],
            ]
        );

        $targetSnippet = $this->snippetRepository->createNew($message->getTargetUuid());
        $this->snippetRepository->add($targetSnippet);

        $dimensionContentCollection = new DimensionContentCollection(
            $sourceSnippet->getDimensionContents(),
            [],
            SnippetDimensionContent::class
        );

        foreach ($allLocales as $locale) {
            $sourceDimensionContent = $dimensionContentCollection->getDimensionContent([
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]);

            if ($sourceDimensionContent?->getLocale() !== $locale) {
                // If the snippet does not exist in the target locale, we cannot copy content for that locale.
                continue;
            }

            $this->contentCopier->copy(
                $sourceSnippet,
                [
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                    'locale' => $locale,
                ],
                $targetSnippet,
                [
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                    'locale' => $locale,
                ]
            );
        }

        $sourceDimensionContent = $dimensionContentCollection->getDimensionContent([
            'locale' => $message->getLocale(),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ]);

        $this->domainEventCollector->collect(new SnippetCopiedEvent(
            $targetSnippet,
            (string) $sourceSnippet->getUuid(),
            $sourceDimensionContent instanceof SnippetDimensionContent ? $sourceDimensionContent->getTitle() : null,
            $message->getLocale(),
        ));

        return $targetSnippet;
    }
}
