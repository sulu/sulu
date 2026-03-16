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
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Webmozart\Assert\Assert;

class SuluAdmin extends Admin
{
    /**
     * @param array<mixed> $resources
     * @param iterable<SmartContentProviderInterface> $smartContentProviders
     */
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ViewRegistry $viewRegistry,
        private NavigationRegistry $navigationRegistry,
        private FieldTypeOptionRegistryInterface $fieldTypeOptionRegistry,
        private ContactManagerInterface $contactManager,
        private iterable $smartContentProviders,
        private LinkProviderPoolInterface $linkProviderPool,
        private LocalizationManagerInterface $localizationManager,
        private array $resources,
        private int $collaborationInterval,
        private bool $collaborationEnabled,
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        $settingsNavigationItem = new NavigationItem(Admin::SETTINGS_NAVIGATION_ITEM);
        $settingsNavigationItem->setPosition(1000);
        $settingsNavigationItem->setIcon('su-cog');

        $navigationItemCollection->add($settingsNavigationItem);
    }

    public function getConfig(): ?array
    {
        /** @var User|null $user */
        $user = $this->tokenStorage->getToken()?->getUser();
        Assert::notNull($user, 'The user has to be logged in to see this endpoint');

        $locale = $user->getLocale();
        $contact = $this->contactManager->getById($user->getContact()->getId(), $locale);

        return [
            'fieldTypeOptions' => $this->fieldTypeOptionRegistry->toArray(),
            'internalLinkTypes' => $this->linkProviderPool->getConfiguration(),
            'localizations' => \array_values($this->localizationManager->getLocalizations()),
            'navigation' => \array_map(
                fn (NavigationItem $navigationItem) => $navigationItem->toArray(),
                \array_values($this->navigationRegistry->getNavigationItems())
            ),
            'routes' => $this->viewRegistry->getViews(),
            'resources' => $this->resources,
            'smartContent' => \array_map(
                fn (SmartContentProviderInterface $dataProvider) => $dataProvider->getConfiguration(),
                \iterator_to_array($this->smartContentProviders)
            ),
            'user' => $user,
            'contact' => $contact,
            'collaborationEnabled' => $this->collaborationEnabled,
            'collaborationInterval' => $this->collaborationInterval * 1000,
        ];
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_admin';
    }

    public static function getPriority(): int
    {
        return 10000;
    }
}
