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
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;
use Sulu\Component\Rest\AbstractRestController;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReferenceController extends AbstractRestController implements ClassResourceInterface
{
    use RequestParametersTrait;

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
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var string
     */
    private $referenceClass;

    /**
     * @var array<string, int>
     */
    private $permissions;

    /**
     * @param array<string, int> $permissions Inject `sulu_security.permissions` parameter
     */
    public function __construct(
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        RestHelperInterface $restHelper,
        TranslatorInterface $translator,
        SecurityCheckerInterface $securityChecker,
        string $referenceClass,
        array $permissions,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->listBuilderFactory = $listBuilderFactory;
        $this->restHelper = $restHelper;
        $this->translator = $translator;
        $this->securityChecker = $securityChecker;
        $this->referenceClass = $referenceClass;
        $this->permissions = $permissions;
    }

    public function cgetAction(Request $request): Response
    {
        $this->securityChecker->checkPermission(
            ReferenceAdmin::SECURITY_CONTEXT,
            PermissionTypes::VIEW
        );

        /** @var string|null $locale */
        $locale = $this->getRequestParameter($request, 'locale');
        /** @var string|null $resourceId */
        $resourceId = $this->getRequestParameter($request, 'resourceId');
        /** @var string|null $resourceKey */
        $resourceKey = $this->getRequestParameter($request, 'resourceKey');

        /** @var UserInterface $user */
        $user = $this->getUser();

        /** @var array<string, FieldDescriptorInterface> $configurationFieldDescriptors */
        $configurationFieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(
            ReferenceInterface::LIST_KEY
        );

        $hiddenFieldDescriptors = $this->getHiddenFieldDescriptors();
        $requiredFieldDescriptors = $this->getRequiredFieldDescriptors();
        $fieldDescriptors = \array_merge(
            $hiddenFieldDescriptors,
            $requiredFieldDescriptors,
            $configurationFieldDescriptors
        );

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($this->referenceClass);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        foreach ($hiddenFieldDescriptors as $fieldDescriptor) {
            $listBuilder->addSelectField($fieldDescriptor);
        }

        foreach ($requiredFieldDescriptors as $fieldDescriptor) {
            $listBuilder->addSelectField($fieldDescriptor);
        }

        $this->addResourceSecurityContextCondition($listBuilder, $fieldDescriptors, $user);
        $this->addResourceObjectSecurityCondition($listBuilder, $fieldDescriptors, $user);

        if (null !== $locale) {
            $this->addLocaleCondition($listBuilder, $fieldDescriptors, $locale);
        }

        if (null !== $resourceKey) {
            $this->addResourceKeyCondition($listBuilder, $fieldDescriptors, $resourceKey);

            if (null !== $resourceId) {
                $this->addResourceIdCondition($listBuilder, $fieldDescriptors, $resourceId);
            }
        }

        $references = $listBuilder->execute();
        $translationLocale = $user->getLocale();
        $references = \array_map(
            function(array $reference) use ($translationLocale) {
                $referenceResourceKey = $this->translator->trans(
                    \sprintf(
                        'sulu_reference.resource.%s',
                        $reference['referenceResourceKey']
                    ),
                    [],
                    'admin',
                    $translationLocale
                );

                return \array_merge(
                    $reference,
                    [
                        'referenceResourceKey' => $referenceResourceKey,
                    ]
                );
            },
            $references
        );

        $listRepresentation = new ListRepresentation(
            $references,
            ReferenceInterface::RESOURCE_KEY,
            'sulu_reference.get_references',
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
     * @return array<string, FieldDescriptorInterface>
     */
    private function getHiddenFieldDescriptors(): array
    {
        return [
            'referenceSecurityContext' => $this->createFieldDescriptor('referenceSecurityContext'),
            'referenceSecurityObjectType' => $this->createFieldDescriptor('referenceSecurityObjectType'),
            'referenceSecurityObjectId' => $this->createFieldDescriptor('referenceSecurityObjectId'),
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
        $entityName = $entityName ?? $this->referenceClass;
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

    /**
     * @return array<string, FieldDescriptorInterface>
     */
    private function getRequiredFieldDescriptors(): array
    {
        return [
            'resourceId' => $this->createFieldDescriptor('resourceId'),
            'resourceKey' => $this->createFieldDescriptor('resourceKey'),
            'locale' => $this->createFieldDescriptor('locale'),
            'referenceResourceId' => $this->createFieldDescriptor('referenceResourceId'),
            'referenceResourceKey' => $this->createFieldDescriptor('referenceResourceKey'),
        ];
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
        $resourceSecurityContextFieldDescriptor = $fieldDescriptors['referenceSecurityContext'];
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
                        $fieldDescriptors['referenceSecurityContext'],
                        $securityContexts
                    ),
                    $listBuilder->createWhereExpression(
                        $fieldDescriptors['referenceSecurityContext'],
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
        $resourceSecurityObjectIdFieldDescriptor = $fieldDescriptors['referenceSecurityObjectId'];
        $listBuilder->addPermissionCheckField($resourceSecurityObjectIdFieldDescriptor);

        /** @var DoctrineFieldDescriptor $resourceSecurityObjectTypeFieldDescriptor */
        $resourceSecurityObjectTypeFieldDescriptor = $fieldDescriptors['referenceSecurityObjectType'];
        $listBuilder->addPermissionCheckField($resourceSecurityObjectTypeFieldDescriptor);

        $listBuilder->setPermissionCheckWithDynamicEntityClass(
            $user,
            PermissionTypes::VIEW,
            'referenceSecurityObjectType',
            'referenceSecurityObjectId'
        );
    }

    /**
     * @param array<string, FieldDescriptorInterface> $fieldDescriptors
     */
    private function addLocaleCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        string $resourceLocale
    ): void {
        $listBuilder->addExpression(
            $listBuilder->createOrExpression(
                [
                    $listBuilder->createWhereExpression(
                        $fieldDescriptors['locale'],
                        $resourceLocale,
                        ListBuilderInterface::WHERE_COMPARATOR_EQUAL
                    ),
                    $listBuilder->createWhereExpression(
                        $fieldDescriptors['locale'],
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
    private function addResourceKeyCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        string $resourceKey
    ): void {
        $listBuilder->where(
            $fieldDescriptors['resourceKey'],
            $resourceKey,
            ListBuilderInterface::WHERE_COMPARATOR_EQUAL
        );
    }

    /**
     * @param array<string, FieldDescriptorInterface> $fieldDescriptors
     */
    private function addResourceIdCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        string $resourceId
    ): void {
        $listBuilder->where(
            $fieldDescriptors['resourceId'],
            $resourceId,
            ListBuilderInterface::WHERE_COMPARATOR_EQUAL
        );
    }
}
