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

namespace Sulu\Bundle\ReferenceBundle\UserInterface\Controller\Admin;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReferenceController extends AbstractRestController implements ClassResourceInterface
{
    /**
     * @param class-string $referenceClass
     */
    public function __construct(
        private ReferenceRepositoryInterface $referenceRepository,
        private TranslatorInterface $translator,
        private SecurityCheckerInterface $securityChecker,
        private string $referenceClass,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(Request $request): Response
    {
        $this->securityChecker->checkPermission(
            ReferenceAdmin::SECURITY_CONTEXT,
            PermissionTypes::VIEW
        );

        /** @var string|null $resourceId */
        $resourceId = $request->query->get('resourceId');
        /** @var string|null $resourceKey */
        $resourceKey = $request->query->get('resourceKey');

        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);
        $offset = ($page - 1) * $limit;

        /** @var string|null $sortBy */
        $sortBy = $request->query->get('sortBy');
        /** @var string|null $sortOrder */
        $sortOrder = $request->query->get('sortOrder');

        $fields = \explode(',', $request->query->get('fields', ''));

        // the frontend always add the id field, but we don't need it in this case as we group by other fields
        $removeFields = ['id'];
        $removeFields[] = 'referenceContext';
        $removeFields[] = 'referenceProperty';
        foreach ($removeFields as $removeField) {
            $fieldIndex = \array_search($removeField, $fields, true);
            if (false !== $fieldIndex) {
                unset($fields[$fieldIndex]);
            }
        }

        $rows = $this->referenceRepository->findFlatBy([
            'resourceId' => $resourceId,
            'resourceKey' => $resourceKey,
            'limit' => $limit,
            'offset' => $offset,
        ], \array_filter([
            $sortBy => $sortOrder,
        ]), \array_filter($fields), [], distinct: true);

        $total = $this->referenceRepository->count([
            'resourceId' => $resourceId,
            'resourceKey' => $resourceKey,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $listRepresentation = new ListRepresentation(
            $rows,
            ReferenceInterface::RESOURCE_KEY,
            'sulu_reference.get_references',
            $request->query->all(),
            $page,
            $limit,
            $total
        );

        return $this->handleView(
            $this->view($listRepresentation, 200)
        );
    }
}
