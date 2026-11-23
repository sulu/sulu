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

namespace Sulu\Page\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Content\Tests\Functional\Traits\QueryCountTrait;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Component\Routing\RequestContext;

#[CoversNothing]
class PageReferencesQueryCountTest extends SuluTestCase
{
    use CreatePageTrait;
    use QueryCountTrait;

    public function testQueriesDoNotGrowWithTheNumberOfReferencedPages(): void
    {
        $fewQueries = $this->countQueriesOfResolvingPageReferencing(2);
        $manyQueries = $this->countQueriesOfResolvingPageReferencing(20);

        $this->assertSame($fewQueries, $manyQueries);
        $this->assertArrayNotHasKey('pa_page_dimension_content_navigation_contexts', $manyQueries);
        $this->assertArrayNotHasKey('at_target_groups', $manyQueries);
    }

    /**
     * @return array<string, int>
     */
    private function countQueriesOfResolvingPageReferencing(int $count): array
    {
        self::purgeDatabase();

        $targetGroups = [new TargetGroup(), new TargetGroup()];
        foreach ($targetGroups as $key => $targetGroup) {
            $targetGroup->setTitle('Target Group ' . $key);
            $targetGroup->setPriority($key);
            self::getEntityManager()->persist($targetGroup);
        }
        self::getEntityManager()->flush();
        $targetGroupIds = \array_map(static fn (TargetGroup $targetGroup) => $targetGroup->getId(), $targetGroups);

        $uuids = [];
        for ($i = 0; $i < $count; ++$i) {
            $uuids[] = self::createPage([
                'en' => ['live' => [
                    'template' => 'default',
                    'title' => 'Target ' . $i,
                    'url' => '/target-' . $i,
                    'navigationContexts' => ['main', 'footer'],
                    'excerptAudienceTargetGroups' => $targetGroupIds,
                ]],
            ])->getUuid();
        }

        $sourceUuid = self::createPage([
            'en' => ['live' => [
                'template' => 'page-references',
                'title' => 'Source',
                'url' => '/source',
                'navigationContexts' => ['main'],
                'excerptAudienceTargetGroups' => $targetGroupIds,
                'pages' => $uuids,
                'single_page' => $uuids[0],
                'link' => ['provider' => 'page', 'href' => $uuids[1]],
                'teasers' => ['items' => \array_map(static fn (string $uuid) => ['id' => $uuid, 'type' => 'pages'], $uuids)],
            ]],
        ])->getUuid();

        self::getEntityManager()->clear();

        /** @var RequestContext $requestContext */
        $requestContext = self::getContainer()->get('router')->getContext();
        $requestContext->setParameter('webspace', 'sulu-io');

        /** @var PageRepositoryInterface $pageRepository */
        $pageRepository = self::getContainer()->get('sulu_page.page_repository');
        /** @var ContentAggregatorInterface $contentAggregator */
        $contentAggregator = self::getContainer()->get('sulu_content.content_aggregator');
        /** @var ContentResolverInterface $contentResolver */
        $contentResolver = self::getContainer()->get('sulu_content.content_resolver');

        return self::countQueriesByTable(function() use ($pageRepository, $contentAggregator, $contentResolver, $sourceUuid): void {
            $page = $pageRepository->findOneBy(
                ['uuid' => $sourceUuid, 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
                [PageRepositoryInterface::SELECT_PAGE_CONTENT => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true]],
            );
            $this->assertNotNull($page);

            $dimensionContent = $contentAggregator->aggregate($page, [
                'locale' => 'en',
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ]);

            $contentResolver->resolve($dimensionContent);
        });
    }
}
