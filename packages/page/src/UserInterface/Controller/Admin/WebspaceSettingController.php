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

namespace Sulu\Page\UserInterface\Controller\Admin;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionWebspaceSettingMessage;
use Sulu\Page\Application\Message\CopyLocaleWebspaceSettingMessage;
use Sulu\Page\Application\Message\ModifyWebspaceSettingMessage;
use Sulu\Page\Application\Message\RestoreWebspaceSettingVersionMessage;
use Sulu\Page\Domain\Exception\WebspaceSettingNotFoundException;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Sulu\Page\Domain\Repository\WebspaceSettingRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\WebspaceSettingAdmin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The settings of a webspace are identified by the key of the webspace, every webspace with a settings form
 * has exactly one which is created with its first save.
 *
 * @internal no backwards compatibility promise is given for this class it could be removed or changed at any time
 */
final class WebspaceSettingController implements SecuredControllerInterface
{
    use HandleTrait;

    private const ACTION_PERMISSIONS = [
        WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH => PermissionTypes::LIVE,
        WorkflowInterface::WORKFLOW_TRANSITION_REMOVE_DRAFT => PermissionTypes::LIVE,
    ];

    public function __construct(
        private WebspaceSettingRepositoryInterface $webspaceSettingRepository,
        MessageBusInterface $messageBus,
        private NormalizerInterface $normalizer,
        private ContentManagerInterface $contentManager,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private SecurityCheckerInterface $securityChecker,
        private RequestStack $requestStack,
        private WebspaceManagerInterface $webspaceManager,
    ) {
        $this->messageBus = $messageBus;
    }

    public function getVersionsAction(Request $request, string $id): JsonResponse
    {
        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('webspace_settings_versions');

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(WebspaceSettingInterface::class);
        $listBuilder->setParameter('locale', $this->getLocale($request));
        $listBuilder->setParameter('id', $id);
        $listBuilder->setIdField($fieldDescriptors['id']);
        $listBuilder->sort($fieldDescriptors['version'], 'DESC');
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            'webspace_settings_versions',
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse($this->normalizer->normalize($listRepresentation->toArray(), 'json'));
    }

    public function getAction(Request $request, string $id): Response
    {
        $webspaceSettingsForm = $this->webspaceManager->findWebspaceByKey($id)?->getWebspaceSettingsForm();

        if (null === $webspaceSettingsForm) {
            return $this->createNotFoundResponse($id);
        }

        $dimensionAttributes = [
            'locale' => $this->getLocale($request),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        $webspaceSetting = $this->webspaceSettingRepository->getOrNew(
            $id,
            ['loadGhost' => true, ...$dimensionAttributes],
            [
                WebspaceSettingRepositoryInterface::SELECT_WEBSPACE_SETTING_CONTENT => [
                    DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true,
                ],
            ],
        );

        if ($webspaceSetting->getDimensionContents()->isEmpty()) {
            // The settings are created with the first save, until then the form shows an empty draft of the new
            // settings, which are not persisted.
            $this->contentManager->persist($webspaceSetting, ['template' => $webspaceSettingsForm], $dimensionAttributes);
        }

        $normalizedContent = $this->contentManager->normalize(
            $this->contentManager->resolve($webspaceSetting, $dimensionAttributes),
        );

        $normalizedContent['webspace'] = $id;

        return new JsonResponse($this->normalizer->normalize(
            $normalizedContent,
            'json',
            ['sulu_admin' => true, 'sulu_admin_webspace_setting' => true, 'sulu_admin_webspace_setting_content' => true],
        ));
    }

    public function putAction(Request $request, string $id): Response
    {
        $this->checkActionPermission($request);

        $message = new ModifyWebspaceSettingMessage($id, $this->getData($request));

        try {
            /** @see \Sulu\Page\Application\MessageHandler\ModifyWebspaceSettingMessageHandler */
            $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } catch (WebspaceSettingNotFoundException) {
            return $this->createNotFoundResponse($id);
        }

        $this->handleAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function postTriggerAction(Request $request, string $id): Response
    {
        $this->checkActionPermission($request);

        try {
            $this->handleAction($request, $id);
        } catch (WebspaceSettingNotFoundException) {
            // the settings are created with the first save, until then there is nothing to apply an action to
            return $this->createNotFoundResponse($id);
        }

        return $this->getAction($request, $id);
    }

    public function getSecurityContext(): string
    {
        return WebspaceSettingAdmin::getSecurityContext($this->getWebspaceKey());
    }

    public function getLocale(Request $request): string
    {
        return $request->query->getString('locale', $request->getLocale());
    }

    private function getWebspaceKey(): string
    {
        $id = $this->requestStack->getCurrentRequest()?->attributes->get('id');

        return \is_string($id) ? $id : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function getData(Request $request): array
    {
        $data = \array_replace($request->request->all(), ['locale' => $this->getLocale($request)]);

        if ($request->query->getBoolean('force')) {
            unset($data['_hash']);
        }

        return $data;
    }

    private function handleAction(Request $request, string $webspaceKey): void
    {
        $action = $request->query->getString('action');

        if ('' === $action || 'draft' === $action) {
            return;
        }

        $locale = $this->getLocale($request);

        $messages = match ($action) {
            'copy_locale' => $this->createCopyLocaleMessages($request, $webspaceKey, $locale),
            'restore' => [new RestoreWebspaceSettingVersionMessage(
                $webspaceKey,
                $request->query->getInt('version') ?: throw new BadRequestHttpException('The "version" query parameter is required for restoring a version.'),
                $locale,
            )],
            default => [new ApplyWorkflowTransitionWebspaceSettingMessage($webspaceKey, $locale, $action)],
        };

        foreach ($messages as $message) {
            $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }
    }

    /**
     * @return CopyLocaleWebspaceSettingMessage[]
     */
    private function createCopyLocaleMessages(Request $request, string $webspaceKey, string $locale): array
    {
        $destinationLocales = \array_filter(\array_map('trim', \explode(',', $request->query->getString('dest'))));
        $webspaceLocales = \array_map(
            static fn (Localization $localization) => $localization->getLocale(),
            $this->webspaceManager->findWebspaceByKey($webspaceKey)?->getAllLocalizations() ?? [],
        );

        if ([] === $destinationLocales || [] !== \array_diff($destinationLocales, $webspaceLocales)) {
            throw new BadRequestHttpException(\sprintf(
                'The "dest" query parameter must list locales of the webspace "%s", "%s" given.',
                $webspaceKey,
                $request->query->getString('dest'),
            ));
        }

        return \array_map(
            fn (string $destinationLocale) => new CopyLocaleWebspaceSettingMessage(
                $webspaceKey,
                $request->query->getString('src') ?: $locale,
                $destinationLocale,
            ),
            \array_values($destinationLocales),
        );
    }

    private function createNotFoundResponse(string $id): JsonResponse
    {
        return new JsonResponse(
            (new EntityNotFoundException(WebspaceSettingInterface::class, $id))->toArray(),
            Response::HTTP_NOT_FOUND,
        );
    }

    private function checkActionPermission(Request $request): void
    {
        $permission = self::ACTION_PERMISSIONS[$request->query->getString('action')] ?? null;

        if (null === $permission) {
            return;
        }

        $this->securityChecker->checkPermission(
            new SecurityCondition($this->getSecurityContext(), $this->getLocale($request)),
            $permission,
        );
    }
}
