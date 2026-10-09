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

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\SuluAdmin;
use Sulu\Bundle\AdminBundle\Admin\View\View;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\ContactBundle\Api\Contact as ContactApi;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPool;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SuluAdminTest extends TestCase
{
    use ProphecyTrait;

    private SuluAdmin $suluAdmin;

    /**
     * @var ObjectProphecy<ViewRegistry>
     */
    private ObjectProphecy $viewRegistry;

    /**
     * @var ObjectProphecy<NavigationRegistry>
     */
    private ObjectProphecy $navigationRegistry;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private ObjectProphecy $tokenStorage;

    /**
     * @var ObjectProphecy<ContactManagerInterface<ContactInterface, ContactApi, ContactAddress>>
     */
    private ObjectProphecy $contactManager;

    /**
     * @var ObjectProphecy<FieldTypeOptionRegistryInterface>
     */
    private ObjectProphecy $fieldTypeOptionRegistry;

    /** @var \ArrayIterator<array-key, SmartContentProviderInterface> */
    private \ArrayIterator $smartContentProviders;

    private LinkProviderPool $linkProviderPool;

    /**
     * @var ObjectProphecy<LocalizationManagerInterface>
     */
    private ObjectProphecy $localizationManager;

    /** @var array<string, array{routes: array<string, string>}> */
    private array $resources = [
        'tags' => [
            'routes' => [
                'list' => 'sulu_tag.get_tags',
                'detail' => 'sulu_tag.get_tag',
            ],
        ],
    ];

    public function setUp(): void
    {
        $this->viewRegistry = $this->prophesize(ViewRegistry::class);
        $views = [
            new View('sulu_snippet.list', '/snippets', 'sulu_admin.list'),
        ];
        $this->viewRegistry->getViews()->willReturn($views);

        $this->navigationRegistry = $this->prophesize(NavigationRegistry::class);
        $navigationItem1 = new NavigationItem('navigation_item1');
        $navigationItem2 = new NavigationItem('navigation_item2');
        $this->navigationRegistry->getNavigationItems()->willReturn([$navigationItem1, $navigationItem2]);

        $user = $this->prophesize(User::class);
        $user->getLocale()->willReturn('de');

        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $token = $this->prophesize(TokenInterface::class);
        $this->tokenStorage->getToken()->willReturn($token->reveal());
        $token->getUser()->willReturn($user->reveal());

        $this->contactManager = $this->prophesize(ContactManagerInterface::class);
        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $user->getContact()->willReturn($contact->reveal());

        $this->fieldTypeOptionRegistry = $this->prophesize(FieldTypeOptionRegistryInterface::class);
        $this->fieldTypeOptionRegistry->toArray()->willReturn(['selection' => []]);

        $this->smartContentProviders = new \ArrayIterator([]);
        $this->linkProviderPool = new LinkProviderPool([]);

        $this->localizationManager = $this->prophesize(LocalizationManagerInterface::class);
        $this->localizationManager->getLocalizations()->willReturn([
            new Localization('de', 'DE'),
            new Localization('de', 'at'),
            new Localization('en', 'US'),
        ]);

        $this->suluAdmin = new SuluAdmin(
            $this->tokenStorage->reveal(),
            $this->viewRegistry->reveal(),
            $this->navigationRegistry->reveal(),
            $this->fieldTypeOptionRegistry->reveal(),
            $this->contactManager->reveal(),
            $this->smartContentProviders,
            $this->linkProviderPool,
            $this->localizationManager->reveal(),
            $this->resources,
            10,
            true,
        );
    }

    public function testHasNavigationItems(): void
    {
        $navigationCollection = $this->prophesize(NavigationItemCollection::class);
        $navigationCollection->add(Argument::type(NavigationItem::class))->shouldBeCalled();

        $this->suluAdmin->configureNavigationItems($navigationCollection->reveal());
    }

    public function testHasNoViews(): void
    {
        $viewCollection = $this->prophesize(ViewCollection::class);

        $viewCollection->add(Argument::any())->shouldNotBeCalled();

        $this->suluAdmin->configureViews($viewCollection->reveal());
    }

    public function testHasConfig(): void
    {
        $config = $this->suluAdmin->getConfig();

        $this->assertSame(['selection' => []], $config['fieldTypeOptions']);
        $this->assertSame([], $config['smartContent']);
        $this->assertCount(1, $config['routes']);
        $this->assertSame('navigation_item1', $config['navigation'][0]['title']);
        $this->assertSame('navigation_item2', $config['navigation'][1]['title']);
        $this->assertSame($config['resources'], $this->resources);
        $this->assertTrue($config['collaborationEnabled']);
        $this->assertSame(10000, $config['collaborationInterval']);
        $this->assertSame(['de', 'en'], $config['textEditorContentLocales']);
    }

    public function testConfiguredTextEditorContentLocales(): void
    {
        $this->suluAdmin = new SuluAdmin(
            $this->tokenStorage->reveal(),
            $this->viewRegistry->reveal(),
            $this->navigationRegistry->reveal(),
            $this->fieldTypeOptionRegistry->reveal(),
            $this->contactManager->reveal(),
            $this->smartContentProviders,
            $this->linkProviderPool,
            $this->localizationManager->reveal(),
            $this->resources,
            10,
            true,
            [
                'keys should be ignored' => 'ar',
            ]
        );

        $this->assertSame(['ar'], $this->suluAdmin->getConfig()['textEditorContentLocales']);
    }
}
