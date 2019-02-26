<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PHPCR\NodeInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\Hash\RequestHashChecker;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * handles snippets.
 */
class SnippetController implements SecuredControllerInterface, ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * @var ContentMapper
     */
    private $contentMapper;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ViewHandler
     */
    private $viewHandler;

    /**
     * @var SnippetRepository
     */
    private $snippetRepository;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var DefaultSnippetManagerInterface
     */
    private $defaultSnippetManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var RequestHashChecker
     */
    private $requestHashChecker;

    /**
     * @var ListRestHelper
     */
    private $listRestHelper;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    public function __construct(
        ViewHandler $viewHandler,
        ContentMapper $contentMapper,
        StructureManagerInterface $structureManager,
        SnippetRepository $snippetRepository,
        TokenStorageInterface $tokenStorage,
        UrlGeneratorInterface $urlGenerator,
        DefaultSnippetManagerInterface $defaultSnippetManager,
        DocumentManager $documentManager,
        FormFactory $formFactory,
        RequestHashChecker $requestHashChecker,
        ListRestHelper $listRestHelper,
        MetadataFactoryInterface $metadataFactory
    ) {
        $this->viewHandler = $viewHandler;
        $this->contentMapper = $contentMapper;
        $this->structureManager = $structureManager;
        $this->snippetRepository = $snippetRepository;
        $this->tokenStorage = $tokenStorage;
        $this->urlGenerator = $urlGenerator;
        $this->defaultSnippetManager = $defaultSnippetManager;
        $this->documentManager = $documentManager;
        $this->formFactory = $formFactory;
        $this->requestHashChecker = $requestHashChecker;
        $this->listRestHelper = $listRestHelper;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Returns list of snippets.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $locale = $this->getLocale($request);

        // if the type parameter is falsy, assign NULL to $type
        $type = $request->query->get('type', null) ?: null;

        $idsString = $request->get('ids');

        if ($idsString) {
            $ids = explode(',', $idsString);
            $snippets = $this->snippetRepository->getSnippetsByUuids($ids, $locale);
            $total = count($snippets);
        } else {
            $snippets = $this->snippetRepository->getSnippets(
                $locale,
                $type,
                $this->listRestHelper->getOffset(),
                $this->listRestHelper->getLimit(),
                $this->listRestHelper->getSearchPattern(),
                $this->listRestHelper->getSortColumn(),
                $this->listRestHelper->getSortOrder()
            );

            $total = $this->snippetRepository->getSnippetsAmount(
                $locale,
                $type,
                $this->listRestHelper->getSearchPattern(),
                $this->listRestHelper->getSortColumn(),
                $this->listRestHelper->getSortOrder()
            );
        }

        $data = new ListRepresentation(
            $snippets,
            'snippets',
            'get_snippets',
            $request->query->all(),
            $this->listRestHelper->getPage(),
            $this->listRestHelper->getLimit(),
            $total
        );

        return $this->viewHandler->handle(View::create($data));
    }

    /**
     * Returns snippet by ID.
     *
     * @param Request $request
     * @param string $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Get(defaults={"id" = ""})
     */
    public function getAction(Request $request, $id = null)
    {
        $locale = $this->getLocale($request);

        $snippet = $this->findDocument($id, $locale);
        $view = View::create($snippet);

        return $this->viewHandler->handle($view);
    }

    /**
     * Saves a new snippet.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        $document = $this->documentManager->create(Structure::TYPE_SNIPPET);
        $form = $this->processForm($request, $document);

        return $this->handleView($form->getData());
    }

    /**
     * Saves a new existing snippet.
     *
     * @param Request $request
     * @param string $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        $document = $this->findDocument($id, $this->getLocale($request));

        $this->requestHashChecker->checkHash($request, $document, $document->getUuid());
        if (!$this->checkAreaSnippet($request, $document)) {
            return new JsonResponse(
                [
                    'structures' => [],
                    'other' => [],
                    'isDefault' => true,
                ],
                409
            );
        }

        $this->processForm($request, $document);

        return $this->handleView($document);
    }

    /**
     * Deletes an existing Snippet.
     *
     * @param Request $request
     * @param string $id
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $webspaceKey = $request->query->get('webspace', null);

        $references = $this->snippetRepository->getReferences($id);

        if (count($references) > 0) {
            $force = $request->headers->get('SuluForceRemove', false);
            if ($force) {
                $this->contentMapper->delete($id, $webspaceKey, true);
            } else {
                return $this->getReferentialIntegrityResponse($webspaceKey, $references, $id, $locale);
            }
        } else {
            $this->contentMapper->delete($id, $webspaceKey);
        }

        return new JsonResponse();
    }

    /**
     * trigger a action for given snippet specified over get-action parameter.
     *
     * @Post("/snippets/{id}")
     *
     * @param string $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postTriggerAction($id, Request $request)
    {
        $view = null;
        $snippet = null;

        $locale = $this->getLocale($request);
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'copy-locale':
                    $destLocales = explode(',', $this->getRequestParameter($request, 'dest', true));

                    // call repository method
                    $snippet = $this->snippetRepository->copyLocale(
                        $id,
                        $this->getUser()->getId(),
                        $locale,
                        $destLocales
                    );

                    // publish the snippet in every dest locale, otherwise it's not in the live workspace.
                    foreach ($destLocales as $destLocale) {
                        $destSnippet = $this->findDocument($id, $destLocale);
                        $this->documentManager->publish($destSnippet, $destLocale);
                    }

                    // flush all published snippets
                    $this->documentManager->flush();

                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // prepare view
            $view = View::create(
                $this->decorateSnippet($snippet->toArray(), $locale),
                null !== $snippet ? 200 : 204
            );
        } catch (RestException $exc) {
            $view = View::create($exc->toArray(), 400);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns user.
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \InvalidArgumentException('No user is set');
        }

        return $token->getUser();
    }

    /**
     * Decorate snippet for HATEOAS.
     */
    private function decorateSnippet(array $snippet, $locale)
    {
        return array_merge(
            $snippet,
            [
                '_links' => [
                    'self' => $this->urlGenerator->generate(
                        'get_snippet',
                        ['id' => $snippet['id'], 'language' => $locale]
                    ),
                    'delete' => $this->urlGenerator->generate(
                        'delete_snippet',
                        ['id' => $snippet['id'], 'language' => $locale]
                    ),
                    'new' => $this->urlGenerator->generate('post_snippet', ['language' => $locale]),
                    'update' => $this->urlGenerator->generate(
                        'put_snippet',
                        ['id' => $snippet['id'], 'language' => $locale]
                    ),
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(Request $request)
    {
        if ($request->query->has('locale')) {
            return $request->query->get('locale');
        }

        if ($request->query->has('language')) {
            @trigger_error('The usage of the "language" parameter in the SnippetController is deprecated. Please use "locale" instead.', E_USER_DEPRECATED);
        }

        return $request->query->get('language', null);
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.global.snippets';
    }

    /**
     * Return a response for the case where there is an referential integrity violation.
     *
     * It will return a 409 (Conflict) response with an array of structures which reference
     * the node and an array of "other" nodes (i.e. non-structures) which reference the node.
     *
     * @param string $webspace
     * @param NodeInterface[] $references
     * @param string $id
     *
     * @return Response
     */
    private function getReferentialIntegrityResponse($webspace, $references, $id, $locale)
    {
        $data = [
            'structures' => [],
            'other' => [],
            'isDefault' => $this->defaultSnippetManager->isDefault($id),
        ];

        foreach ($references as $reference) {
            if ($reference->getParent()->isNodeType('sulu:page')) {
                $content = $this->contentMapper->load(
                    $reference->getParent()->getIdentifier(),
                    $webspace,
                    $locale,
                    true
                );
                $data['structures'][] = $content->toArray();
            } else {
                $data['other'][] = $reference->getParent()->getPath();
            }
        }

        return new JsonResponse($data, 409);
    }

    private function findDocument($id, $locale)
    {
        return $this->documentManager->find(
            $id,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );
    }

    private function processForm(Request $request, $document)
    {
        $locale = $this->getLocale($request);
        $data = $request->request->all();
        $data['workflowStage'] = $request->get('state', WorkflowStage::PUBLISHED);

        $formType = $this->metadataFactory->getMetadataForAlias(Structure::TYPE_SNIPPET)->getFormType();
        $form = $this->formFactory->create($formType, $document, [
            'csrf_protection' => false,
        ]);
        $form->submit($data, false);

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->documentManager->persist(
            $document,
            $locale,
            [
                'user' => $this->getUser()->getId(),
                'clear_missing_content' => false,
            ]
        );
        $this->documentManager->publish($document, $locale);
        $this->documentManager->flush();

        return $form;
    }

    private function handleView($document)
    {
        $view = View::create($document);

        return $this->viewHandler->handle($view);
    }

    private function checkAreaSnippet(Request $request, SnippetDocument $document)
    {
        $force = $request->headers->get('SuluForcePut', false);
        $structureType = $request->request->get('template');

        return $force
            || $structureType === $document->getStructureType()
            || !$this->defaultSnippetManager->isDefault($document->getUuid())
            || $structureType === $this->defaultSnippetManager->loadType($document->getUuid());
    }
}
