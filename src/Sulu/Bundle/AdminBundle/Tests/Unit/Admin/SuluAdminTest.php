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
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\SuluAdmin;
use Sulu\Bundle\AdminBundle\Admin\View\View;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
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

    private array $resources = [
        'tags' => [
            'endpoint' => [
                'list' => 'sulu_tag.get_tags',
                'detail' => 'sulu_tag.get_tag',
            ],
        ],
    ];

    public function setUp(): void
    {
        $viewRegistry = $this->prophesize(ViewRegistry::class);
        $views = [
            new View('sulu_snippet.list', '/snippets', 'sulu_admin.list'),
        ];
        $viewRegistry->getViews()->willReturn($views);

        $navigationRegistry = $this->prophesize(NavigationRegistry::class);
        $navigationItem1 = new NavigationItem('navigation_item1');
        $navigationItem2 = new NavigationItem('navigation_item2');
        $navigationRegistry->getNavigationItems()->willReturn([$navigationItem1, $navigationItem2]);

        $user = $this->prophesize(User::class);
        $user->getLocale()->willReturn('de');

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $token = $this->prophesize(TokenInterface::class);
        $tokenStorage->getToken()->willReturn($token->reveal());
        $token->getUser()->willReturn($user->reveal());

        $contactManager = $this->prophesize(ContactManagerInterface::class);
        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $user->getContact()->willReturn($contact->reveal());

        $fieldTypeOptionRegistry = $this->prophesize(FieldTypeOptionRegistryInterface::class);
        $fieldTypeOptionRegistry->toArray()->willReturn(['selection' => []]);

        $smartContentProviders = new \ArrayIterator([]);
        $linkProviderPool = new LinkProviderPool([]);

        $localizationManager = $this->prophesize(LocalizationManagerInterface::class);
        $localizationManager->getLocalizations()->willReturn([
            new Localization('de', 'DE'),
            new Localization('en', 'US'),
        ]);

        $this->suluAdmin = new SuluAdmin(
            $tokenStorage->reveal(),
            $viewRegistry->reveal(),
            $navigationRegistry->reveal(),
            $fieldTypeOptionRegistry->reveal(),
            $contactManager->reveal(),
            $smartContentProviders,
            $linkProviderPool,
            $localizationManager->reveal(),
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
        $this->assertSame(true, $config['collaborationEnabled']);
        $this->assertSame(10000, $config['collaborationInterval']);
    }
}
