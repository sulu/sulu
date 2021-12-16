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

namespace Sulu\Bundle\CustomUrlBundle\Trash;

use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRestoredEvent;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Webmozart\Assert\Assert;

final class CustomUrlTrashItemHandler implements
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
     * @param CustomUrlDocument $customUrl
     */
    public function store(object $customUrl, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($customUrl, CustomUrlDocument::class);

        $data = [
            'title' => $customUrl->getTitle(),
            'parentUuid' => $this->documentInspector->getUuid($customUrl->getParent()),
            'creator' => $customUrl->getCreator(),
            'created' => $customUrl->getCreated()->format('c'),
            'baseDomain' => $customUrl->getBaseDomain(),
            'domainParts' => $customUrl->getDomainParts(),
            'canonical' => $customUrl->isCanonical(),
            'redirect' => $customUrl->isRedirect(),
            'noFollow' => $customUrl->isNoFollow(),
            'noIndex' => $customUrl->isNoIndex(),
            'targetUuid' => $this->documentInspector->getUuid($customUrl->getTargetDocument()),
            'targetLocale' => $customUrl->getTargetLocale(),
        ];

        $webspaceKey = $this->documentInspector->getWebspace($customUrl);

        return $this->trashItemRepository->create(
            CustomUrlDocument::RESOURCE_KEY,
            (string) $customUrl->getUuid(),
            $customUrl->getTitle(),
            $data,
            null,
            $options,
            CustomUrlAdmin::getCustomUrlSecurityContext($webspaceKey),
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $uuid = $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();

        /** @var CustomUrlDocument $customUrl */
        $customUrl = $this->documentManager->create('custom_url');
        $customUrlAccessor = new DocumentAccessor($customUrl);
        $customUrlAccessor->set('uuid', $uuid);

        $customUrl->setTitle($data['title']);
        $customUrl->setParent($this->documentManager->find($data['parentUuid']));
        $customUrl->setCreator($data['creator']);
        $customUrl->setCreated(new \DateTime($data['created']));
        $customUrl->setBaseDomain($data['baseDomain']);
        $customUrl->setDomainParts($data['domainParts']);
        $customUrl->setCanonical($data['canonical']);
        $customUrl->setRedirect($data['redirect']);
        $customUrl->setNoFollow($data['noFollow']);
        $customUrl->setNoIndex($data['noIndex']);
        $customUrl->setTargetDocument($this->documentManager->find($data['targetUuid']));
        $customUrl->setTargetLocale($data['targetLocale']);
        $customUrl->setPublished(false);

        $this->documentManager->persist($customUrl, CustomUrlDocument::DOCUMENT_LOCALE);
        $this->documentManager->publish($customUrl, CustomUrlDocument::DOCUMENT_LOCALE);

        $webspaceKey = $this->documentInspector->getWebspace($customUrl);
        $this->documentDomainEventCollector->collect(new CustomUrlRestoredEvent($customUrl, $webspaceKey, $data));

        $this->documentManager->flush();

        return $customUrl;
    }

    public static function getResourceKey(): string
    {
        return CustomUrlDocument::RESOURCE_KEY;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            null,
            CustomUrlAdmin::LIST_VIEW,
            ['webspace' => 'webspace']
        );
    }
}
