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

namespace Sulu\Snippet\Tests\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Content\Application\ContentDataMapper\ContentDataMapper;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Model\SnippetArea;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Webmozart\Assert\Assert;

trait CreateSnippetTrait
{
    /**
     * @param array<string, array{ draft?: array<string, mixed>, live?: array<string, mixed> }> $dataSet
     */
    protected static function createSnippet(array $dataSet = []): Snippet
    {
        $entityManager = static::getEntityManager();
        $contentDataMapper = new ContentDataMapper([
            static::getContainer()->get('sulu_content.template_data_mapper'),
            static::getContainer()->get('sulu_content.excerpt_data_mapper'),
            static::getContainer()->get('sulu_content.workflow_data_mapper'),
        ]);

        $snippet = new Snippet();

        $draftUnlocalizedDimension = null;

        if (\count($dataSet)) {
            $draftUnlocalizedDimension = new SnippetDimensionContent($snippet);
            $snippet->addDimensionContent($draftUnlocalizedDimension);
            $entityManager->persist($draftUnlocalizedDimension);
        }

        $liveUnlocalizedDimension = null;
        $createdPublishedUnlocalizedDimension = false;

        foreach ($dataSet as $locale => $data) {
            /** @var array<string, mixed> $draftData */
            $draftData = $data['draft'] ?? $data['live'] ?? [];
            $liveData = $data['live'] ?? null;

            // Create localized draft dimension
            $draftLocalizedDimension = new SnippetDimensionContent($snippet);
            $draftLocalizedDimension->setLocale($locale);
            $snippet->addDimensionContent($draftLocalizedDimension);
            $entityManager->persist($draftLocalizedDimension);

            // Map Draft Data
            $draftDimensionContentCollection = new DimensionContentCollection(
                [$draftUnlocalizedDimension, $draftLocalizedDimension],
                ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => $locale],
                SnippetDimensionContent::class
            );
            $contentDataMapper->map(
                $draftDimensionContentCollection,
                $draftDimensionContentCollection->getDimensionAttributes(),
                $draftData
            );
            $draftLocalizedDimension->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_DRAFT);

            if ($liveData) {
                if (!$createdPublishedUnlocalizedDimension) {
                    $liveUnlocalizedDimension = new SnippetDimensionContent($snippet);
                    $liveUnlocalizedDimension->setStage(DimensionContentInterface::STAGE_LIVE);
                    $snippet->addDimensionContent($liveUnlocalizedDimension);
                    $entityManager->persist($liveUnlocalizedDimension);
                    $createdPublishedUnlocalizedDimension = true;
                }

                // Create localized live dimension
                $liveLocalizedDimension = new SnippetDimensionContent($snippet);
                $liveLocalizedDimension->setStage(DimensionContentInterface::STAGE_LIVE);
                $liveLocalizedDimension->setLocale($locale);
                $snippet->addDimensionContent($liveLocalizedDimension);
                $entityManager->persist($liveLocalizedDimension);

                // Set published state
                if (isset($data['draft'])) {
                    $draftLocalizedDimension->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_DRAFT);
                } else {
                    $draftLocalizedDimension->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_PUBLISHED);
                }

                $draftLocalizedDimension->setWorkflowPublished(new \DateTimeImmutable());
                $liveLocalizedDimension->setWorkflowPublished(new \DateTimeImmutable());

                // Map live data
                $liveDimensionContentCollection = new DimensionContentCollection(
                    \array_filter([$liveUnlocalizedDimension, $liveLocalizedDimension]),
                    ['stage' => DimensionContentInterface::STAGE_LIVE, 'locale' => $locale],
                    SnippetDimensionContent::class
                );
                $liveData['published'] = \date('Y-m-d H:i:s');
                $contentDataMapper->map(
                    $liveDimensionContentCollection,
                    $liveDimensionContentCollection->getDimensionAttributes(),
                    $liveData
                );
            }
        }

        $entityManager->persist($snippet);

        return $snippet;
    }

    protected static function publishSnippet(SnippetInterface $snippet, string $locale): void
    {
        $entityManager = static::getEntityManager();

        $draftUnlocalizedDimension = null;
        $draftLocalizedDimension = null;

        foreach ($snippet->getDimensionContents() as $dimensionContent) {
            Assert::isInstanceOf($dimensionContent, SnippetDimensionContent::class);

            if (DimensionContentInterface::STAGE_DRAFT === $dimensionContent->getStage()) {
                if (null === $dimensionContent->getLocale()) {
                    $draftUnlocalizedDimension = $dimensionContent;
                } elseif ($locale === $dimensionContent->getLocale()) {
                    $draftLocalizedDimension = $dimensionContent;
                }
            }
        }

        Assert::notNull($draftLocalizedDimension);

        // Create live dimensions if they don't exist
        $liveUnlocalizedDimension = null;
        $liveLocalizedDimension = null;

        foreach ($snippet->getDimensionContents() as $dimensionContent) {
            Assert::isInstanceOf($dimensionContent, SnippetDimensionContent::class);

            if (DimensionContentInterface::STAGE_LIVE === $dimensionContent->getStage()) {
                if (null === $dimensionContent->getLocale()) {
                    $liveUnlocalizedDimension = $dimensionContent;
                } elseif ($locale === $dimensionContent->getLocale()) {
                    $liveLocalizedDimension = $dimensionContent;
                }
            }
        }

        if (!$liveUnlocalizedDimension) {
            $liveUnlocalizedDimension = new SnippetDimensionContent($snippet);
            $liveUnlocalizedDimension->setStage(DimensionContentInterface::STAGE_LIVE);
            $snippet->addDimensionContent($liveUnlocalizedDimension);
            $entityManager->persist($liveUnlocalizedDimension);
        }

        if (!$liveLocalizedDimension) {
            $liveLocalizedDimension = new SnippetDimensionContent($snippet);
            $liveLocalizedDimension->setStage(DimensionContentInterface::STAGE_LIVE);
            $liveLocalizedDimension->setLocale($locale);
            $snippet->addDimensionContent($liveLocalizedDimension);
            $entityManager->persist($liveLocalizedDimension);
        }

        // Copy template data from draft to live
        $liveLocalizedDimension->setTemplateKey($draftLocalizedDimension->getTemplateKey());
        $liveLocalizedDimension->setTemplateData($draftLocalizedDimension->getTemplateData());

        $draftLocalizedDimension->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_PUBLISHED);
        $draftLocalizedDimension->setWorkflowPublished(new \DateTimeImmutable());
        $liveLocalizedDimension->setWorkflowPublished(new \DateTimeImmutable());
    }

    protected static function createSnippetArea(string $areaKey, string $webspaceKey, SnippetInterface $snippet): SnippetArea
    {
        $entityManager = static::getEntityManager();

        $snippetAreaRepository = static::getContainer()->get('sulu_snippet.snippet_area_repository');
        $existingSnippetArea = $snippetAreaRepository->findOneBy([
            'areaKey' => $areaKey,
            'webspaceKey' => $webspaceKey,
        ]);

        if ($existingSnippetArea instanceof SnippetArea) {
            $existingSnippetArea->setSnippet($snippet);

            return $existingSnippetArea;
        }

        $snippetArea = new SnippetArea($areaKey, $webspaceKey);
        $snippetArea->setSnippet($snippet);
        $entityManager->persist($snippetArea);

        return $snippetArea;
    }

    abstract protected static function getEntityManager(): EntityManagerInterface;
}
