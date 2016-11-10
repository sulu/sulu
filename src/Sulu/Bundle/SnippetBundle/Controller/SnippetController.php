<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
use JMS\Serializer\SerializationContext;
use PHPCR\NodeInterface;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\DocumentManager\DocumentManager;
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
        ListRestHelper $listRestHelper
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

        $uuidsString = $request->get('ids');

        if ($uuidsString) {
            $uuids = explode(',', $uuidsString);
            $snippets = $this->snippetRepository->getSnippetsByUuids($uuids, $locale);
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
     * @param string $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Get(defaults={"uuid" = ""})
     */
    public function getAction(Request $request, $uuid = null)
    {
        $locale = $this->getLocale($request);

        $snippet = $this->documentManager->find($uuid, $locale);
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
        $document = $this->documentManager->create('snippet');
        $form = $this->processForm($request, $document);

        return $this->handleView($form->getData());
    }

    /**
     * Saves a new existing snippet.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $uuid)
    {
        $document = $this->findDocument($uuid, $this->getLocale($request));

        $this->requestHashChecker->checkHash($request, $document, $document->getUuid());
        $this->processForm($request, $document);

        return $this->handleView($document);
    }

    /**
     * Deletes an existing Snippet.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $uuid)
    {
        $locale = $this->getLocale($request);
        $webspaceKey = $request->query->get('webspace', null);

        $references = $this->snippetRepository->getReferences($uuid);

        if (count($references) > 0) {
            $force = $request->headers->get('SuluForceRemove', false);
            if ($force) {
                $this->contentMapper->delete($uuid, $webspaceKey, true);
            } else {
                return $this->getReferentialIntegrityResponse($webspaceKey, $references, $uuid, $locale);
            }
        } else {
            $this->contentMapper->delete($uuid, $webspaceKey);
        }

        return new JsonResponse();
    }

    /**
     * trigger a action for given snippet specified over get-action parameter.
     *
     * @Post("/snippets/{uuid}")
     *
     * @param string $uuid
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postTriggerAction($uuid, Request $request)
    {
        $view = null;
        $snippet = null;

        $locale = $this->getLocale($request);
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'copy-locale':
                    $destLocale = $this->getRequestParameter($request, 'dest', true);

                    // call repository method
                    $snippet = $this->snippetRepository->copyLocale(
                        $uuid,
                        $this->getUser()->getId(),
                        $locale,
                        explode(',', $destLocale)
                    );
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // prepare view
            $view = View::create(
                $this->decorateSnippet($snippet->toArray(), $locale),
                $snippet !== null ? 200 : 204
            );
        } catch (RestException $exc) {
            $view = View::create($exc->toArray(), 400);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * TODO refactor.
     *
     * @return JsonResponse
     */
    public function getFieldsAction()
    {
        return new JsonResponse(
            [
                [
                    'name' => 'title',
                    'translation' => 'public.title',
                    'disabled' => false,
                    'default' => true,
                    'sortable' => true,
                    'type' => '',
                    'width' => '',
                    'minWidth' => '100px',
                    'editable' => false,
                ],
                [
                    'name' => 'localizedTemplate',
                    'translation' => 'snippets.list.template',
                    'disabled' => false,
                    'default' => true,
                    'sortable' => true,
                    'type' => '',
                    'width' => '',
                    'minWidth' => '',
                    'editable' => false,
                ],
                [
                    'name' => 'id',
                    'translation' => 'public.id',
                    'disabled' => true,
                    'default' => false,
                    'sortable' => true,
                    'type' => '',
                    'width' => '50px',
                    'minWidth' => '',
                    'editable' => false,
                ],
                [
                    'name' => 'created',
                    'translation' => 'public.created',
                    'disabled' => true,
                    'default' => false,
                    'sortable' => true,
                    'type' => 'date',
                    'width' => '',
                    'minWidth' => '',
                    'editable' => false,
                ],
                [
                    'name' => 'changed',
                    'translation' => 'public.changed',
                    'disabled' => true,
                    'default' => false,
                    'sortable' => true,
                    'type' => 'date',
                    'width' => '',
                    'minWidth' => '',
                    'editable' => false,
                ],
            ]
        );
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
                        ['uuid' => $snippet['id'], 'language' => $locale]
                    ),
                    'delete' => $this->urlGenerator->generate(
                        'delete_snippet',
                        ['uuid' => $snippet['id'], 'language' => $locale]
                    ),
                    'new' => $this->urlGenerator->generate('post_snippet', ['language' => $locale]),
                    'update' => $this->urlGenerator->generate(
                        'put_snippet',
                        ['uuid' => $snippet['id'], 'language' => $locale]
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
     * @param string $uuid
     *
     * @return Response
     */
    private function getReferentialIntegrityResponse($webspace, $references, $uuid, $locale)
    {
        $data = [
            'structures' => [],
            'other' => [],
            'isDefault' => $this->defaultSnippetManager->isDefault($uuid),
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

    private function findDocument($uuid, $locale)
    {
        return $this->documentManager->find(
            $uuid,
            $locale,
            [
                'load_ghost_content' => false,
            ]
        );
    }

    private function processForm(Request $request, $document)
    {
        $locale = $this->getLocale($request);
        $data = $request->request->all();
        $data['workflowStage'] = $request->get('state', WorkflowStage::PUBLISHED);

        $form = $this->formFactory->create('snippet', $document, [
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
        $view->setSerializationContext(
            SerializationContext::create()->setSerializeNull(true)
        );

        return $this->viewHandler->handle($view);
    }
}
