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

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\ContactBundle\Api\Contact as ContactApi;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Webmozart\Assert\Assert;

/**
 * @internal no backwards compatibility promise is given for this method it could be removed or changed at any time
 *           create your own service class extending `Admin` instead
 */
final class SuluAdmin extends Admin
{
    /**
     * @param array<string, array{routes: array<string, string>}> $resources
     * @param iterable<SmartContentProviderInterface> $smartContentProviders
     * @param ContactManagerInterface<ContactInterface, ContactApi, ContactAddress> $contactManager
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ViewRegistry $viewRegistry,
        private readonly NavigationRegistry $navigationRegistry,
        private readonly FieldTypeOptionRegistryInterface $fieldTypeOptionRegistry,
        private readonly ContactManagerInterface $contactManager,
        private readonly iterable $smartContentProviders,
        private readonly LinkProviderPoolInterface $linkProviderPool,
        private readonly LocalizationManagerInterface $localizationManager,
        private readonly array $resources,
        private readonly int $collaborationInterval,
        private readonly bool $collaborationEnabled,
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        $settingsNavigationItem = new NavigationItem(Admin::SETTINGS_NAVIGATION_ITEM);
        $settingsNavigationItem->setPosition(1000);
        $settingsNavigationItem->setIcon('su-cog');

        $navigationItemCollection->add($settingsNavigationItem);
    }

    /**
     * @return array{
     * 'fieldTypeOptions' : array<mixed>,
     * 'internalLinkTypes' : array<mixed>,
     * 'localizations' : array<mixed>,
     * 'navigation' : list<array<mixed>>,
     * 'routes' : array<mixed>,
     * 'resources' : array<mixed>,
     * 'smartContent' : array<mixed>,
     * 'user' :User,
     * 'contact' : ContactApi,
     * 'collaborationEnabled' : bool,
     * 'collaborationInterval' : int,
     * }
     */
    public function getConfig(): array
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        Assert::isInstanceOf($user, User::class, 'The logged in user has to be an instance of "%2$s". Got: "%s"');

        $contactEntity = $user->getContact();
        Assert::notNull($contactEntity, 'The logged in user has to have an associated contact');

        $locale = $user->getLocale();
        $contact = $this->contactManager->getById($contactEntity->getId(), $locale);

        return [
            'fieldTypeOptions' => $this->fieldTypeOptionRegistry->toArray(),
            'internalLinkTypes' => $this->linkProviderPool->getConfiguration(),
            'localizations' => \array_values($this->localizationManager->getLocalizations()),
            'navigation' => \array_map(
                fn (NavigationItem $navigationItem): array => $navigationItem->toArray(),
                \array_values($this->navigationRegistry->getNavigationItems())
            ),
            'routes' => $this->viewRegistry->getViews(),
            'resources' => $this->resources,
            'smartContent' => \array_map(
                static fn (SmartContentProviderInterface $dataProvider) => $dataProvider->getConfiguration(),
                \iterator_to_array($this->smartContentProviders)
            ),
            'user' => $user,
            'contact' => $contact,
            'collaborationEnabled' => $this->collaborationEnabled,
            'collaborationInterval' => $this->collaborationInterval * 1000,
        ];
    }

    public function getConfigKey(): string
    {
        return 'sulu_admin';
    }

    public static function getPriority(): int
    {
        return \PHP_INT_MAX - 1;
    }
}
