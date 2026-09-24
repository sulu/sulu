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
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * The dimension contents are fetch joined restricted to the requested dimension. Doctrine keeps an
 * already initialized collection as it is, so reading a page in one locale and writing it in another
 * one leaves the page without the dimension content which is about to be written. Writing must not
 * create a second dimension content - and with it a second route - for that dimension.
 */
#[CoversNothing]
class PageStaleDimensionContentCollectionTest extends SuluTestCase
{
    private PageRepositoryInterface $pageRepository;

    private RouteRepositoryInterface $routeRepository;

    private MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        self::purgeDatabase();

        self::assertInstanceOf(KernelInterface::class, self::$kernel);
        $command = (new Application(self::$kernel))->find('sulu:page:initialize');
        (new CommandTester($command))->execute([]);

        // start with a cold identity map, like a fixture or a command which runs afterwards does
        self::ensureKernelShutdown();
        self::bootKernel();

        $this->pageRepository = $this->getContainer()->get('sulu_page.page_repository');
        $this->routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->messageBus = $this->getContainer()->get('sulu_message_bus');
    }

    public function testModifyLocaleAfterReadingAnotherLocale(): void
    {
        $route = $this->routeRepository->getOneBy(['webspace' => 'sulu-io', 'locale' => 'de', 'slug' => '/']);

        $homepage = $this->getHomepage('en');
        self::assertSame($homepage->getUuid(), $this->getHomepage('de')->getUuid());

        $this->messageBus->dispatch(new Envelope(
            new ModifyPageMessage(
                ['uuid' => $homepage->getUuid()],
                [
                    'locale' => 'de',
                    'template' => 'homepage',
                    'url' => '/',
                    'title' => 'Startseite',
                ]
            ),
            [new EnableFlushStamp()],
        ));

        $dimensionContents = self::getEntityManager()->getRepository(PageDimensionContentInterface::class)->findBy([
            'page' => $homepage->getUuid(),
            'locale' => 'de',
            'stage' => DimensionContentInterface::STAGE_DRAFT,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ]);

        self::assertCount(1, $dimensionContents);
        self::assertSame('Startseite', $dimensionContents[0]->getTitle());
        self::assertSame($route->getId(), $dimensionContents[0]->getRoute()?->getId());

        $routes = $this->routeRepository->findBy(['webspace' => 'sulu-io', 'locale' => 'de', 'slug' => '/']);
        self::assertCount(1, \iterator_to_array($routes));
    }

    private function getHomepage(string $locale): PageInterface
    {
        return $this->pageRepository->getOneBy(
            [
                'webspaceKey' => 'sulu-io',
                'parentId' => null,
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ],
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => $locale,
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ]
        );
    }
}
