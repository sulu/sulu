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

namespace Sulu\Bundle\PageBundle\Trash;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Domain\Event\PageRestoredEvent;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Webmozart\Assert\Assert;

final class PageTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var DocumentDomainEventCollectorInterface
     */
    private $documentDomainEventCollector;

    public function __construct(
        TrashItemRepositoryInterface $trashItemRepository,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        DocumentDomainEventCollectorInterface $documentDomainEventCollector
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->documentDomainEventCollector = $documentDomainEventCollector;
    }

    /**
     * @param PageDocument $page
     */
    public function store(object $page): TrashItemInterface
    {
        Assert::isInstanceOf($page, PageDocument::class);

        $pageTitles = [];

        /** @var BasePageDocument $parent */
        $parent = $page->getParent();
        $data = [
            'parentUuid' => $parent->getUuid(),
            'suluOrder' => $page->getSuluOrder(),
            'locales' => [],
        ];

        foreach ($this->documentInspector->getLocales($page) as $locale) {
            /** @var PageDocument $localizedPage */
            $localizedPage = $this->documentManager->find($page->getUuid(), $locale);
            /** @var BasePageDocument|null $redirectTarget */
            $redirectTarget = $localizedPage->getRedirectTarget();

            $pageTitles[$localizedPage->getLocale()] = $localizedPage->getTitle();

            $data['locales'][] = [
                'title' => $localizedPage->getTitle(),
                'locale' => $localizedPage->getLocale(),
                'originalLocale' => $localizedPage->getOriginalLocale(),
                'creator' => $localizedPage->getCreator(),
                'created' => $localizedPage->getCreated()->format('c'),
                'author' => $localizedPage->getAuthor(),
                'authored' => $localizedPage->getAuthored()->format('c'),
                'structureType' => $localizedPage->getStructureType(),
                'structureData' => $localizedPage->getStructure()->toArray(),
                'extensionsData' => $localizedPage->getExtensionsData(),
                'permissions' => $localizedPage->getPermissions(),
                'navigationContexts' => $localizedPage->getNavigationContexts(),
                'shadowLocaleEnabled' => $localizedPage->isShadowLocaleEnabled(),
                'shadowLocale' => $localizedPage->getShadowLocale(),
                'redirectType' => $localizedPage->getRedirectType(),
                'redirectExternal' => $localizedPage->getRedirectExternal(),
                'redirectTargetUuid' => $redirectTarget ? $redirectTarget->getUUid() : null,
                'resourceSegment' => $localizedPage->getResourceSegment(),
            ];
        }

        return $this->trashItemRepository->create(
            BasePageDocument::RESOURCE_KEY,
            (string) $page->getUuid(),
            $data,
            $pageTitles,
            PageAdmin::getPageSecurityContext($page->getWebspaceName()),
            SecurityBehavior::class,
            (string) $page->getUuid()
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData): object
    {
        $uuid = $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();
        $parentUuid = $restoreFormData['parentUuid'];
        $localizedPage = null;

        foreach ($data['locales'] as $localeData) {
            $locale = $localeData['locale'];

            try {
                /** @var PageDocument $localizedPage */
                $localizedPage = $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false]);
            } catch (DocumentNotFoundException $exception) {
                /** @var PageDocument $localizedPage */
                $localizedPage = $this->documentManager->create('page');
                $localizedPage->setParent($this->documentManager->find($parentUuid));
                $localizedPage->setSuluOrder($data['suluOrder']);

                $localizedPageAccessor = new DocumentAccessor($localizedPage);
                $localizedPageAccessor->set('uuid', $uuid);
            }

            $localizedPage->setTitle($localeData['title']);
            $localizedPage->setLocale($locale);
            $localizedPage->setOriginalLocale($localeData['originalLocale']);
            $localizedPage->setCreator($localeData['creator']);
            $localizedPage->setCreated(new \DateTime($localeData['created']));
            $localizedPage->setAuthor($localeData['author']);
            $localizedPage->setAuthored(new \DateTime($localeData['authored']));
            $localizedPage->setStructureType($localeData['structureType']);
            $localizedPage->getStructure()->bind($localeData['structureData']);
            $localizedPage->setExtensionsData($localeData['extensionsData']);
            $localizedPage->setPermissions($localeData['permissions']);
            $localizedPage->setNavigationContexts($localeData['navigationContexts']);
            $localizedPage->setShadowLocaleEnabled($localeData['shadowLocaleEnabled']);
            $localizedPage->setShadowLocale($localeData['shadowLocale']);
            $localizedPage->setRedirectType($localeData['redirectType']);
            $localizedPage->setRedirectExternal($localeData['redirectExternal']);
            $localizedPage->setResourceSegment($localeData['resourceSegment']);

            if ($localeData['redirectTargetUuid']) {
                $localizedPage->setRedirectTarget($this->documentManager->find($localeData['redirectTargetUuid']));
            }

            $this->documentManager->persist($localizedPage, $locale, ['omit_modified_domain_event' => true]);
        }

        Assert::isInstanceOf($localizedPage, PageDocument::class);
        $this->documentDomainEventCollector->collect(new PageRestoredEvent($localizedPage, $data));
        $this->documentManager->flush();

        return $localizedPage;
    }

    public static function getResourceKey(): string
    {
        return BasePageDocument::RESOURCE_KEY;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            'restore_page',
            PageAdmin::EDIT_FORM_VIEW,
            ['id' => 'id', 'webspace' => 'webspace']
        );
    }
}
