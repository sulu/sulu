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

namespace Sulu\Bundle\TrashBundle\UserInterface\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Bundle\TrashBundle\Infrastructure\Sulu\Admin\TrashAdmin;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @RouteResource("trash-item")
 *
 * This controller cannot implement the SecuredControllerInterface, because then the SuluSecurityListener would check
 * for the "edit" permission in the "postTriggerAction", but the TrashAdmin::SECURITY_CONTEXT doesn't define an "edit" permission.
 * Therefore the "view" permissions are checked explicitly in this controller.
 */
class TrashItemController extends AbstractRestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $listBuilderFactory;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TrashManagerInterface
     */
    private $trashManager;

    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var string
     */
    private $trashItemClass;

    /**
     * @var array<string, int>
     */
    private $permissions;

    /**
     * @param array<string, int> $permissions Inject `sulu_security.permissions` parameter
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        RestHelperInterface $restHelper,
        TranslatorInterface $translator,
        TrashManagerInterface $trashManager,
        TrashItemRepositoryInterface $trashItemRepository,
        SecurityCheckerInterface $securityChecker,
        string $trashItemClass,
        array $permissions,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->entityManager = $entityManager;
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->listBuilderFactory = $listBuilderFactory;
        $this->restHelper = $restHelper;
        $this->translator = $translator;
        $this->trashManager = $trashManager;
        $this->trashItemRepository = $trashItemRepository;
        $this->securityChecker = $securityChecker;
        $this->trashItemClass = $trashItemClass;
        $this->permissions = $permissions;
    }

    public function cgetAction(Request $request): Response
    {
        $this->securityChecker->checkPermission(
            TrashAdmin::SECURITY_CONTEXT,
            PermissionTypes::VIEW
        );

        /** @var string|null $locale */
        $locale = $this->getLocale($request);
        /** @var UserInterface $user */
        $user = $this->getUser();

        /** @var array<string, FieldDescriptorInterface> $configurationFieldDescriptors */
        $configurationFieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(
            TrashItemInterface::LIST_KEY
        );

        $hiddenFieldDescriptors = $this->getHiddenFieldDescriptors();
        $requiredFieldDescriptors = $this->getRequiredFieldDescriptors();
        $fieldDescriptors = \array_merge(
            $hiddenFieldDescriptors,
            $requiredFieldDescriptors,
            $configurationFieldDescriptors
        );

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($this->trashItemClass);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listBuilder->setParameter('locale', $locale);

        foreach ($hiddenFieldDescriptors as $fieldDescriptor) {
            $listBuilder->addSelectField($fieldDescriptor);
        }

        foreach ($requiredFieldDescriptors as $fieldDescriptor) {
            $listBuilder->addSelectField($fieldDescriptor);
        }

        $this->addResourceSecurityContextCondition($listBuilder, $fieldDescriptors, $user);
        $this->addResourceObjectSecurityCondition($listBuilder, $fieldDescriptors, $user);

        $trashItems = $listBuilder->execute();

        $trashItems = \array_map(
            function(array $trashItem) use ($hiddenFieldDescriptors) {
                if (isset($trashItem['resourceType'])) {
                    $trashItem['resourceType'] = $this->getResourceTranslation($trashItem['resourceType']);
                }

                foreach ($hiddenFieldDescriptors as $fieldDescriptor) {
                    unset($trashItem[$fieldDescriptor->getName()]);
                }

                return $trashItem;
            },
            $trashItems
        );

        $listRepresentation = new ListRepresentation(
            $trashItems,
            TrashItemInterface::RESOURCE_KEY,
            'sulu_trash.get_trash-items',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $this->handleView(
            $this->view($listRepresentation, 200)
        );
    }

    /**
     * This action is not used by sulu, but it still needs to be implemented, because the "detail" url of the TrashItem
     * resource needs to be configured in order for the "deleteAction" and the "postTriggerAction" to work.
     */
    public function getAction(int $id): Response
    {
        $this->securityChecker->checkPermission(
            TrashAdmin::SECURITY_CONTEXT,
            PermissionTypes::VIEW
        );

        $trashItem = $this->trashItemRepository->getOneBy(['id' => $id]);

        $this->securityChecker->checkPermission(
            new SecurityCondition(
                $trashItem->getResourceSecurityContext(),
                null,
                $trashItem->getResourceSecurityObjectType(),
                $trashItem->getResourceSecurityObjectId()
            ),
            PermissionTypes::VIEW
        );

        return $this->handleView(
            $this->view($trashItem)
        );
    }

    public function deleteAction(int $id): Response
    {
        $this->securityChecker->checkPermission(
            TrashAdmin::SECURITY_CONTEXT,
            PermissionTypes::DELETE
        );

        $trashItem = $this->trashItemRepository->getOneBy(['id' => $id]);

        $this->trashManager->remove($trashItem);
        $this->entityManager->flush();

        return $this->handleView(
            $this->view(null, 204)
        );
    }

    public function postTriggerAction(int $id, Request $request): Response
    {
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'restore':
                    return $this->restoreTrashItem($id, $request->request->all());
                default:
                    throw new RestException(\sprintf('Unrecognized action: "%s"', $action));
            }
        } catch (RestException $ex) {
            $view = $this->view($ex->toArray(), 400);

            return $this->handleView($view);
        }
    }

    /**
     * @param mixed[] $restoreFormData
     */
    private function restoreTrashItem(int $id, array $restoreFormData): Response
    {
        $trashItem = $this->trashItemRepository->getOneBy(['id' => $id]);

        $this->securityChecker->checkPermission(
            new SecurityCondition(
                $trashItem->getResourceSecurityContext(),
                null,
                $trashItem->getResourceSecurityObjectType(),
                $trashItem->getResourceSecurityObjectId()
            ),
            PermissionTypes::ADD
        );

        $restoredObject = $this->trashManager->restore($trashItem, $restoreFormData);
        $this->entityManager->flush();

        return $this->handleView(
            $this->view($restoredObject)
        );
    }

    /**
     * @param array<string, FieldDescriptorInterface> $fieldDescriptors
     */
    private function addResourceSecurityContextCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        UserInterface $user
    ): void {
        /** @var DoctrineFieldDescriptor $resourceSecurityContextFieldDescriptor */
        $resourceSecurityContextFieldDescriptor = $fieldDescriptors['resourceSecurityContext'];
        $listBuilder->addPermissionCheckField($resourceSecurityContextFieldDescriptor);

        $securityContexts = [];
        $viewPermission = $this->permissions[PermissionTypes::VIEW];

        foreach ($user->getRoleObjects() as $role) {
            foreach ($role->getPermissions() as $permission) {
                if (($permission->getPermissions() & $viewPermission) === $viewPermission) {
                    $securityContexts[] = $permission->getContext();
                }
            }
        }

        $securityContexts = \array_unique($securityContexts);

        $listBuilder->addExpression(
            $listBuilder->createOrExpression(
                [
                    $listBuilder->createInExpression(
                        $fieldDescriptors['resourceSecurityContext'],
                        $securityContexts
                    ),
                    $listBuilder->createWhereExpression(
                        $fieldDescriptors['resourceSecurityContext'],
                        null,
                        ListBuilderInterface::WHERE_COMPARATOR_EQUAL
                    ),
                ]
            )
        );
    }

    /**
     * @param array<string, FieldDescriptorInterface> $fieldDescriptors
     */
    private function addResourceObjectSecurityCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        UserInterface $user
    ): void {
        /** @var DoctrineFieldDescriptor $resourceSecurityObjectIdFieldDescriptor */
        $resourceSecurityObjectIdFieldDescriptor = $fieldDescriptors['resourceSecurityObjectId'];
        $listBuilder->addPermissionCheckField($resourceSecurityObjectIdFieldDescriptor);

        /** @var DoctrineFieldDescriptor $resourceSecurityObjectTypeFieldDescriptor */
        $resourceSecurityObjectTypeFieldDescriptor = $fieldDescriptors['resourceSecurityObjectType'];
        $listBuilder->addPermissionCheckField($resourceSecurityObjectTypeFieldDescriptor);

        $listBuilder->setPermissionCheckWithDynamicEntityClass(
            $user,
            PermissionTypes::VIEW,
            'resourceSecurityObjectType',
            'resourceSecurityObjectId'
        );
    }

    private function getResourceTranslation(string $resourceKey): string
    {
        return $this->translator->trans(
            \sprintf(
                'sulu_activity.resource.%s',
                $resourceKey
            ),
            [],
            'admin'
        );
    }

    /**
     * @return array<string, FieldDescriptorInterface>
     */
    private function getHiddenFieldDescriptors(): array
    {
        return [
            'resourceSecurityContext' => $this->createFieldDescriptor('resourceSecurityContext'),
            'resourceSecurityObjectType' => $this->createFieldDescriptor('resourceSecurityObjectType'),
            'resourceSecurityObjectId' => $this->createFieldDescriptor('resourceSecurityObjectId'),
        ];
    }

    /**
     * @return array<string, FieldDescriptorInterface>
     */
    private function getRequiredFieldDescriptors(): array
    {
        return [
            'id' => $this->createFieldDescriptor('id'),
            'resourceKey' => $this->createFieldDescriptor('resourceKey'),
        ];
    }

    /**
     * @param DoctrineJoinDescriptor[]|null $joins
     */
    private function createFieldDescriptor(
        string $name,
        ?string $fieldName = null,
        ?string $entityName = null,
        ?array $joins = null,
        ?string $type = null
    ): DoctrineFieldDescriptor {
        $fieldName = $fieldName ?? $name;
        $entityName = $entityName ?? $this->trashItemClass;
        $joins = $joins ?? [];
        $type = $type ?? 'string';

        return new DoctrineFieldDescriptor(
            $fieldName,
            $name,
            $entityName,
            null,
            $joins,
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            $type,
            false
        );
    }
}
