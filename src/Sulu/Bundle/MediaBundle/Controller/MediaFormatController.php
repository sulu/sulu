<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Media\FormatOptions\FormatOptionsManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("Format")
 */
class MediaFormatController extends AbstractRestController implements ClassResourceInterface
{
    public function __construct(
        ViewHandlerInterface $viewHandler,
        private FormatOptionsManagerInterface $formatOptionsManager,
        private EntityManagerInterface $entityManager,
        private ?MediaManagerInterface $mediaManager = null,
        private ?SecurityCheckerInterface $securityChecker = null,
    ) {
        parent::__construct($viewHandler);

        if (null === $this->mediaManager || null === $this->securityChecker) {
            @trigger_deprecation('sulu/sulu', '2.6', 'Instantiating MediaFormatController without the $mediaManager or $securityChecker argument is deprecated.');
        }
    }

    /**
     * Returns all format resources.
     *
     * @param int $id
     *
     * @return Response
     */
    public function cgetAction($id, Request $request)
    {
        $this->checkPermission($id, PermissionTypes::VIEW);

        $formatOptions = $this->formatOptionsManager->getAll($id);

        return $this->handleView($this->view(\count($formatOptions) > 0 ? $formatOptions : new \stdClass()));
    }

    /**
     * Edits a format resource.
     *
     * @param int $id
     * @param string $key
     *
     * @return Response
     */
    public function putAction($id, $key, Request $request)
    {
        $this->checkPermission($id, PermissionTypes::EDIT);

        $options = $request->request->all();

        if (empty($options)) {
            $this->formatOptionsManager->delete($id, $key);
        } else {
            $this->formatOptionsManager->save($id, $key, $options);
        }
        $this->entityManager->flush();

        $formatOptions = $this->formatOptionsManager->get($id, $key);

        return $this->handleView($this->view($formatOptions));
    }

    /**
     * @param int $id
     *
     * @return Response
     */
    public function cpatchAction($id, Request $request)
    {
        $this->checkPermission($id, PermissionTypes::EDIT);

        $formatOptions = $request->request->all();
        foreach ($formatOptions as $formatKey => $formatOption) {
            if (empty($formatOption)) {
                $this->formatOptionsManager->delete($id, $formatKey);
                continue;
            }

            $this->formatOptionsManager->save($id, $formatKey, $formatOption);
        }

        $this->entityManager->flush();

        return $this->handleView($this->view($formatOptions));
    }

    /**
     * @param int $id
     */
    private function checkPermission($id, string $permission): void
    {
        if (null === $this->mediaManager || null === $this->securityChecker) {
            return;
        }

        $collectionId = $this->mediaManager->getEntityById($id)->getCollection()->getId();

        $this->securityChecker->checkPermission(
            new SecurityCondition(MediaAdmin::SECURITY_CONTEXT, null, Collection::class, $collectionId),
            $permission
        );
    }
}
