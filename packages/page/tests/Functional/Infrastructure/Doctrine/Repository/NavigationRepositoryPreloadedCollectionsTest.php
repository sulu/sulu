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

namespace Sulu\Page\Tests\Functional\Infrastructure\Doctrine\Repository;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Functional\Traits\QueryCountTrait;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;

class NavigationRepositoryPreloadedCollectionsTest extends SuluTestCase
{
    use CreatePageTrait;
    use QueryCountTrait;

    public function testQueriesDoNotGrowWithTheNumberOfPages(): void
    {
        $fewQueries = $this->countQueriesOfNavigationWithPages(2);
        $manyQueries = $this->countQueriesOfNavigationWithPages(20);

        $this->assertSame($fewQueries, $manyQueries);
        $this->assertArrayNotHasKey('at_target_groups', $manyQueries);
    }

    /**
     * @return array<string, int>
     */
    private function countQueriesOfNavigationWithPages(int $count): array
    {
        self::purgeDatabase();

        $targetGroup = new TargetGroup();
        $targetGroup->setTitle('Target Group');
        $targetGroup->setPriority(1);
        self::getEntityManager()->persist($targetGroup);
        self::getEntityManager()->flush();

        for ($i = 0; $i < $count; ++$i) {
            self::createPage([
                'en' => ['live' => [
                    'template' => 'default',
                    'title' => 'Page ' . $i,
                    'url' => '/page-' . $i,
                    'navigationContexts' => ['main'],
                    'excerptAudienceTargetGroups' => [$targetGroup->getId()],
                ]],
            ]);
        }

        self::getEntityManager()->clear();

        /** @var NavigationRepositoryInterface $navigationRepository */
        $navigationRepository = self::getContainer()->get('sulu_page.navigation_repository');

        $pagesCount = 0;
        $queries = self::countQueriesByTable(function() use ($navigationRepository, &$pagesCount): void {
            $pagesCount = \count($navigationRepository->getNavigationFlat('main', 'en', 'sulu-io', null));
        });

        $this->assertSame($count, $pagesCount);

        return $queries;
    }
}
