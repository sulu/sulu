<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountMediaAddedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountMediaRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\ListBuilderFactory\MediaListBuilderFactory;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class AccountMediaController.
 *
 * @RouteResource("Medias")
 */
class AccountMediaController extends AbstractMediaController implements ClassResourceInterface
{
    protected static $mediaEntityKey = 'account_media';

    /**
     * @var AbstractContactManager
     */
    private $accountManager;

    /**
     * @var string
     */
    private $accountClass;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        RestHelperInterface $restHelper,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        EntityManagerInterface $entityManager,
        MediaRepositoryInterface $mediaRepository,
        MediaManagerInterface $mediaManager,
        AbstractContactManager $accountManager,
        DomainEventCollectorInterface $domainEventCollector,
        string $accountClass,
        string $mediaClass,
        ?MediaListBuilderFactory $mediaListBuilderFactory = null,
        ?MediaListRepresentationFactory $mediaListRepresentationFactory = null,
        ?FieldDescriptorFactoryInterface $fieldDescriptorFactory = null
    ) {
        parent::__construct(
            $viewHandler,
            $tokenStorage,
            $restHelper,
            $listBuilderFactory,
            $entityManager,
            $mediaRepository,
            $mediaManager,
            $mediaClass,
            $mediaListBuilderFactory,
            $mediaListRepresentationFactory,
            $fieldDescriptorFactory
        );

        $this->accountManager = $accountManager;
        $this->accountClass = $accountClass;
        $this->domainEventCollector = $domainEventCollector;
    }

    public function deleteAction(int $contactId, int $id)
    {
        $dispatchDomainEventCallback = function(AccountInterface $account, MediaInterface $media) {
            $this->domainEventCollector->collect(
                new AccountMediaRemovedEvent($account, $media)
            );
        };

        return $this->removeMediaFromEntity($this->accountClass, $contactId, $id, $dispatchDomainEventCallback);
    }

    public function postAction(int $contactId, Request $request)
    {
        $dispatchDomainEventCallback = function(AccountInterface $account, MediaInterface $media) {
            $this->domainEventCollector->collect(
                new AccountMediaAddedEvent($account, $media)
            );
        };

        $mediaId = $request->get('mediaId', '');

        return $this->addMediaToEntity($this->accountClass, $contactId, $mediaId, $dispatchDomainEventCallback);
    }

    public function cgetAction(int $contactId, Request $request)
    {
        return $this->getMultipleView(
            $this->accountClass,
            'sulu_contact.get_account_medias',
            $this->accountManager,
            $contactId,
            $request
        );
    }
}
