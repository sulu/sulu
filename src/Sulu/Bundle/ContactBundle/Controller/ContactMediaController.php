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
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class ContactMediaController.
 *
 * @RouteResource("Medias")
 */
class ContactMediaController extends AbstractMediaController implements ClassResourceInterface
{
    protected static $mediaEntityKey = 'contact_media';

    /**
     * @var AbstractContactManager
     */
    private $contactManager;

    /**
     * @var string
     */
    private $contactClass;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        MediaRepositoryInterface $mediaRepository,
        AbstractContactManager $contactManager,
        MediaListRepresentationFactory $mediaListRepresentationFactory,
        string $contactClass,
        string $mediaClass
    ) {
        parent::__construct(
            $viewHandler,
            $tokenStorage,
            $entityManager,
            $mediaRepository,
            $mediaListRepresentationFactory,
            $mediaClass
        );

        $this->contactManager = $contactManager;
        $this->contactClass = $contactClass;
    }

    public function deleteAction(int $contactId, int $id)
    {
        return $this->removeMediaFromEntity($this->contactClass, $contactId, $id);
    }

    public function postAction(int $contactId, Request $request)
    {
        return $this->addMediaToEntity($this->contactClass, $contactId, $request->get('mediaId', ''));
    }

    public function cgetAction(int $contactId, Request $request)
    {
        return $this->getMultipleView(
            $this->contactClass,
            'sulu_contact.get_contact_medias',
            $this->contactManager,
            $contactId,
            $request
        );
    }
}
