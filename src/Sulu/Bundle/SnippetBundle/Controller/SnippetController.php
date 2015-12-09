<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PHPCR\NodeInterface;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * handles snippets.
 */
class SnippetController implements SecuredControllerInterface
{
    use RequestParametersTrait;

    /**
     * @var ContentMapper
     */
    protected $contentMapper;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var ViewHandler
     */
    protected $viewHandler;

    /**
     * @Var SnippetRepository
     */
    protected $snippetRepository;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var string
     */
    protected $languageCode;

    /**
     * @var DefaultSnippetManagerInterface
     */
    protected $defaultSnippetManager;

    public function __construct(
        ViewHandler $viewHandler,
        ContentMapper $contentMapper,
        StructureManagerInterface $structureManager,
        SnippetRepository $snippetRepository,
        SecurityContext $securityContext,
        UrlGeneratorInterface $urlGenerator,
        DefaultSnippetManagerInterface $defaultSnippetManager
    ) {
        $this->viewHandler = $viewHandler;
        $this->contentMapper = $contentMapper;
        $this->structureManager = $structureManager;
        $this->snippetRepository = $snippetRepository;
        $this->securityContext = $securityContext;
        $this->urlGenerator = $urlGenerator;
        $this->defaultSnippetManager = $defaultSnippetManager;
    }

    /**
     * Returns list of snippets.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getSnippetsAction(Request $request)
    {
        $this->initEnv($request);

        $listRestHelper = new ListRestHelper($request);

        // if the type parameter is falsy, assign NULL to $type
        $type = $request->query->get('type', null) ?: null;

        $uuidsString = $request->get('ids');

        if ($uuidsString) {
            $uuids = explode(',', $uuidsString);
            $snippets = $this->snippetRepository->getSnippetsByUuids($uuids, $this->languageCode);
            $total = count($snippets);
        } else {
            $snippets = $this->snippetRepository->getSnippets(
                $this->languageCode,
                $type,
                $listRestHelper->getOffset(),
                $listRestHelper->getLimit(),
                $listRestHelper->getSearchPattern(),
                $listRestHelper->getSortColumn(),
                $listRestHelper->getSortOrder()
            );

            $total = $this->snippetRepository->getSnippetsAmount(
                $this->languageCode,
                $type,
                $listRestHelper->getSearchPattern(),
                $listRestHelper->getSortColumn(),
                $listRestHelper->getSortOrder()
            );
        }

        $data = [];

        foreach ($snippets as $snippet) {
            $snippetData = $snippet->toArray();
            $snippetData['localizedTemplate'] = $snippet->getLocalizedTitle($this->languageCode);
            $data[] = $snippetData;
        }

        $data = new ListRepresentation(
            $this->decorateSnippets($data, $this->languageCode),
            'snippets',
            'get_snippets',
            $request->query->all(),
            $listRestHelper->getPage(),
            $listRestHelper->getLimit(),
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
    public function getSnippetAction(Request $request, $uuid = null)
    {
        $this->initEnv($request);

        $snippet = $this->contentMapper->load($uuid, null, $this->languageCode);

        $view = View::create($this->decorateSnippet($snippet->toArray(), $this->languageCode));

        return $this->viewHandler->handle($view);
    }

    /**
     * Saves a new snippet.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postSnippetAction(Request $request)
    {
        $this->initEnv($request);
        $data = $request->request->all();

        $mapperRequest = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey($this->getRequired($request, 'template'))
            ->setLocale($this->languageCode)
            ->setUserId($this->getUser()->getId())
            ->setData($data)
            ->setState(intval($request->get('state', StructureInterface::STATE_PUBLISHED)));

        $snippet = $this->contentMapper->saveRequest($mapperRequest);
        $view = View::create($this->decorateSnippet($snippet->toArray(), $this->languageCode));

        return $this->viewHandler->handle($view);
    }

    /**
     * Saves a new existing snippet.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSnippetAction(Request $request, $uuid)
    {
        $this->initEnv($request);
        $data = $request->request->all();

        $mapperRequest = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey($this->getRequired($request, 'template'))
            ->setUuid($uuid)
            ->setLocale($this->languageCode)
            ->setUserId($this->getUser()->getId())
            ->setData($data)
            ->setState(intval($request->get('state', StructureInterface::STATE_PUBLISHED)));

        $snippet = $this->contentMapper->saveRequest($mapperRequest);
        $view = View::create($this->decorateSnippet($snippet->toArray(), $this->languageCode));

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes an existing Snippet.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return JsonResponse
     */
    public function deleteSnippetAction(Request $request, $uuid)
    {
        $webspaceKey = $request->query->get('webspace', null);

        $references = $this->snippetRepository->getReferences($uuid);

        if (count($references) > 0) {
            $force = $request->headers->get('SuluForceRemove', false);
            if ($force) {
                $this->contentMapper->delete($uuid, $webspaceKey, true);
            } else {
                return $this->getReferentialIntegrityResponse($webspaceKey, $references, $uuid);
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

        $this->initEnv($request);
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'copy-locale':
                    $destLocale = $this->getRequestParameter($request, 'dest', true);

                    // call repository method
                    $snippet = $this->snippetRepository->copyLocale(
                        $uuid,
                        $this->getUser()->getId(),
                        $this->languageCode,
                        explode(',', $destLocale)
                    );
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // prepare view
            $view = View::create(
                $this->decorateSnippet($snippet->toArray(), $this->languageCode),
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
    public function getSnippetFieldsAction()
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
       $token = $this->securityContext->getToken();

       if (null === $token) {
           throw new \InvalidArgumentException('No user is set');
       }

       return $token->getUser();
   }

    /**
     * Initiates the environment.
     */
    private function initEnv(Request $request)
    {
        $this->languageCode = $this->getLocale($request);

        if (!$this->languageCode) {
            throw new \InvalidArgumentException('You must provide the "language" query parameter');
        }
    }

    /**
     * Returns a required parameter.
     */
    private function getRequired(Request $request, $parameterName)
    {
        $value = $request->request->get($parameterName);

        if (null === $value) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You must provide a value for the POST parameter "%s"',
                    $parameterName
                )
            );
        }

        return $value;
    }

    /**
     * Decorate snippets for HATEOAS.
     */
    private function decorateSnippets(array $snippets, $locale)
    {
        $res = [];
        foreach ($snippets as $snippet) {
            $res[] = $this->decorateSnippet($snippet, $locale);
        }

        return $res;
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
    private function getReferentialIntegrityResponse($webspace, $references, $uuid)
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
                    $this->languageCode,
                    true
                );
                $data['structures'][] = $content->toArray();
            } else {
                $data['other'] = $reference->getPath();
            }
        }

        return new JsonResponse($data, 409);
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
}
