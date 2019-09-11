<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\AdminBundle\Entity\Collaboration;
use Sulu\Bundle\AdminBundle\Entity\CollaborationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CollaborationController implements ClassResourceInterface
{
    private static $resourceKey = 'collaborations';

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var CollaborationRepository
     */
    private $collaborationRepository;

    /**
     * @var ViewHandler
     */
    private $viewHandler;

    /**
     * @var string
     */
    private $secret;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        CollaborationRepository $collaborationRepository,
        ViewHandler $viewHandler,
        string $secret
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->collaborationRepository = $collaborationRepository;
        $this->viewHandler = $viewHandler;
        $this->secret = $secret;
    }

    public function postAction(Request $request)
    {
        $collaborations = array_values(array_filter(
            $this->collaborationRepository->update($this->createCollaboration($request)),
            function(Collaboration $collaboration) use ($request) {
                return $collaboration->getConnectionId() !== $this->getConnectionId($request);
            }
        ));

        return $this->viewHandler->handle(
            View::create(new CollectionRepresentation($collaborations, static::$resourceKey))
        );
    }

    public function cdeleteAction(Request $request)
    {
        $collaborations = array_values($this->collaborationRepository->delete($this->createCollaboration($request)));

        return $this->viewHandler->handle(
            View::create(new CollectionRepresentation($collaborations, static::$resourceKey))
        );
    }

    private function createCollaboration(Request $request)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        return new Collaboration(
            $this->getConnectionId($request),
            $user->getId(),
            $user->getUserName(),
            $user->getFullName(),
            $request->query->get('resourceKey'),
            $request->query->get('id')
        );
    }

    private function getConnectionId(Request $request)
    {
        return crypt($request->getSession()->getId(), $this->secret);
    }
}
