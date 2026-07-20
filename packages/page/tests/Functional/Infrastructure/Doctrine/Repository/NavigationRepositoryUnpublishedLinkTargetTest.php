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

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Routing\RequestContext;

/**
 * Regression test for an internal link page that points to a page which was
 * published and then unpublished. Resolving the navigation must not fail even
 * though the link target has no published content anymore.
 *
 * @see https://github.com/sulu/SuluHeadlessBundle/issues/164
 */
#[CoversNothing]
class NavigationRepositoryUnpublishedLinkTargetTest extends SuluTestCase
{
    use CreatePageTrait;

    private NavigationRepositoryInterface $navigationRepository;

    private static string $internalLinkPageUuid;

    private static string $targetPageUuid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->navigationRepository = $this->getContainer()->get('sulu_page.navigation_repository');

        /** @var RequestContext $requestContext */
        $requestContext = self::getContainer()->get('router')->getContext();
        $requestContext->setParameter('webspace', 'sulu-io');
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();

        $homepage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Homepage',
                    'url' => '/',
                    'template' => 'default',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        // Target page is published so that live dimensions (including the
        // unlocalized base) are created, then unpublished below.
        $targetPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Target Page',
                    'url' => '/target-page',
                    'template' => 'default',
                    'navigationContexts' => [],
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);
        self::$targetPageUuid = $targetPage->getUuid();

        $internalLinkPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Internal Link Page',
                    'url' => '/internal-link',
                    'template' => 'default',
                    'navigationContexts' => ['main'],
                    'linkOn' => true,
                    'linkData' => [
                        'href' => $targetPage->getUuid(),
                        'provider' => PageLinkProvider::ALIAS,
                    ],
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);
        self::$internalLinkPageUuid = $internalLinkPage->getUuid();

        // Unpublish the target page: its localized live content is removed while the
        // link page keeps pointing at it. Aggregating the target for the live stage
        // now yields a dimension content without a locale.
        self::unpublishPage($targetPage->getUuid(), 'en');
    }

    private static function unpublishPage(string $uuid, string $locale): void
    {
        $messageBus = self::getContainer()->get('sulu_message_bus');

        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionPageMessage(
                    identifier: ['uuid' => $uuid],
                    locale: $locale,
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH
                ),
                [new EnableFlushStamp()]
            )
        );
    }

    /**
     * @return array<string, string>
     */
    private function getDefaultProperties(): array
    {
        return [
            'uuid' => 'object.resource.id',
            'title' => 'title',
            'url' => 'url',
            'template' => 'object.templateKey',
            'linkProvider' => 'object.linkData[provider]',
        ];
    }

    public function testGetNavigationTreeExcludesInternalLinkWithUnpublishedTarget(): void
    {
        $navigation = $this->navigationRepository->getNavigationTree(
            'main',
            'en',
            'sulu-io',
            null,
            2,
            $this->getDefaultProperties()
        );

        // Resolving the navigation must not fail (regression: it previously threw a
        // TypeError because the unpublished target has no locale).
        /** @var array<string, mixed> $homepageNav */
        $homepageNav = $navigation[0];
        $this->assertSame('Homepage', $homepageNav['title']);

        // The internal link page points at an unpublished page and therefore cannot
        // resolve to a url, so it is dropped from the navigation entirely.
        /** @var array<int, array<string, mixed>> $homepageChildren */
        $homepageChildren = $homepageNav['children'];
        $childUuids = \array_map(static fn (array $item) => $item['uuid'], $homepageChildren);
        $this->assertNotContains(self::$internalLinkPageUuid, $childUuids);
        $this->assertNotContains(self::$targetPageUuid, $childUuids);
    }

    public function testGetNavigationFlatExcludesInternalLinkWithUnpublishedTarget(): void
    {
        $navigation = $this->navigationRepository->getNavigationFlat(
            'main',
            'en',
            'sulu-io',
            null,
            2,
            $this->getDefaultProperties()
        );

        $uuids = \array_map(static fn (array $item) => $item['uuid'], $navigation);
        $this->assertNotContains(self::$internalLinkPageUuid, $uuids);
        $this->assertNotContains(self::$targetPageUuid, $uuids);
    }
}
