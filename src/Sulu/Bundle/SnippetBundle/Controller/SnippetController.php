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

use PHPCR\NodeInterface;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use FOS\RestBundle\Controller\Annotations\Get;

/**
 * handles snippets
 */
class SnippetController
{
    /**
     * @var ContentMapper
     */
    protected $contentMapper;

    /**
     * @var StructureManager
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
     * Constructor
     */
    public function __construct(
        ViewHandler $viewHandler,
        ContentMapper $contentMapper,
        StructureManager $structureManager,
        SnippetRepository $snippetRepository,
        SecurityContext $securityContext,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->viewHandler = $viewHandler;
        $this->contentMapper = $contentMapper;
        $this->structureManager = $structureManager;
        $this->snippetRepository = $snippetRepository;
        $this->securityContext = $securityContext;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Returns list of snippets
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getSnippetsAction(Request $request) {
        $this->initEnv($request);

        // if the type parameter is falsy, assign NULL to $type
        $type = $request->query->get('type', null) ?: null;
        $page = $request->get('page', 1);

        $limit = $request->get('limit');
        if ($limit === '') {
            $limit = null;
        }

        $search = $request->get('search');
        if ($search === '') {
            $search = null;
        }

        $sortBy = $request->get('sortBy');
        if ($sortBy === '') {
            $sortBy = null;
        }

        $sortOrder = $request->get('sortOrder');
        if ($sortOrder === '') {
            $sortOrder = null;
        }

        if ($limit === null) {
            $uuidsString = $request->get('ids');
            $pages = null;

            if ($uuidsString) {
                $uuids = explode(',', $uuidsString);
                $snippets = $this->snippetRepository->getSnippetsByUuids($uuids, $this->languageCode);
            } else {
                $snippets = $this->snippetRepository->getSnippets(
                    $this->languageCode,
                    $type,
                    null,
                    null,
                    $search,
                    $sortBy,
                    $sortOrder
                );
            }

            $total = count($snippets);
        } else {
            $snippets = $this->snippetRepository->getSnippets(
                $this->languageCode,
                $type,
                ($page - 1) * $limit,
                $limit,
                $search,
                $sortBy,
                $sortOrder
            );

            $total = $this->snippetRepository->getSnippetsAmount(
                $this->languageCode,
                $type,
                $search,
                $sortBy,
                $sortOrder
            );

            $pages = floor($total / $limit) + 1;
        }

        $data = array();

        foreach ($snippets as $snippet) {
            $data[] = $snippet->toArray();
        }

        $data = $this->decorateList($data, $this->languageCode, $limit, $page, $pages, $sortBy, $sortOrder, $search, $total);

        return $this->viewHandler->handle(View::create($data));
    }

    /**
     * Returns snippet by ID
     * @param Request $request
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Get(defaults={"uuid" = ""})
     */
    public function getSnippetAction(Request $request, $uuid = null) {
        $this->initEnv($request);

        $snippet = $this->contentMapper->load($uuid, null, $this->languageCode, true);

        $view = View::create($this->decorateSnippet($snippet->toArray(), $this->languageCode));

        return $this->viewHandler->handle($view);
    }

    /**
     * Saves a new snippet
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postSnippetAction(Request $request) {
        $this->initEnv($request);
        $data = $request->request->all();

        $mapperRequest = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey($this->getRequired($request, 'template'))
            ->setLocale($this->languageCode)
            ->setUserId($this->getUser()->getId())
            ->setData($data)
            ->setState(intval($request->get('state', StructureInterface::STATE_TEST)));

        $snippet = $this->contentMapper->saveRequest($mapperRequest);
        $view = View::create($this->decorateSnippet($snippet->toArray(), $this->languageCode));

        return $this->viewHandler->handle($view);
    }

    /**
     * Saves a new existing snippet
     * @param Request $request
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSnippetAction(Request $request, $uuid) {
        $this->initEnv($request);
        $data = $request->request->all();

        $mapperRequest = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey($this->getRequired($request, 'template'))
            ->setUuid($uuid)
            ->setLocale($this->languageCode)
            ->setUserId($this->getUser()->getId())
            ->setData($data)
            ->setState(intval($request->get('state', StructureInterface::STATE_TEST)));

        $snippet = $this->contentMapper->saveRequest($mapperRequest);
        $view = View::create($this->decorateSnippet($snippet->toArray(), $this->languageCode));

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes an existing Snippet
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function deleteSnippetAction(Request $request, $uuid) {
        $webspaceKey = $request->query->get('webspace', null);

        $references = $this->snippetRepository->getReferences($uuid);

        if (count($references) > 0) {
            $force = $request->headers->get('SuluForceRemove', false);
            if ($force) {
                $this->contentMapper->delete($uuid, $webspaceKey, true);
            } else {
                return $this->getReferentialIntegrityResponse($webspaceKey, $references);
            }
        } else {
            $this->contentMapper->delete($uuid, $webspaceKey);
        }

        return new JsonResponse();
    }

    /**
     * TODO refactor
     * @return JsonResponse
     */
    public function getSnippetFieldsAction() {
        return new JsonResponse(
            array(
                array(
                    "name" => "title",
                    "translation" => "public.title",
                    "disabled" => false,
                    "default" => true,
                    "sortable" => true,
                    "type" => "",
                    "width" => "",
                    "minWidth" => "100px",
                    "editable" => false
                ),
                array(
                    "name" => "template",
                    "translation" => "snippets.list.template",
                    "disabled" => false,
                    "default" => true,
                    "sortable" => true,
                    "type" => "",
                    "width" => "",
                    "minWidth" => "",
                    "editable" => false
                ),
                array(
                    "name" => "id",
                    "translation" => "public.id",
                    "disabled" => true,
                    "default" => false,
                    "sortable" => true,
                    "type" => "",
                    "width" => "50px",
                    "minWidth" => "",
                    "editable" => false
                ),
                array(
                    "name" => "created",
                    "translation" => "public.created",
                    "disabled" => true,
                    "default" => false,
                    "sortable" => true,
                    "type" => "date",
                    "width" => "",
                    "minWidth" => "",
                    "editable" => false
                ),
                array(
                    "name" => "changed",
                    "translation" => "public.changed",
                    "disabled" => true,
                    "default" => false,
                    "sortable" => true,
                    "type" => "date",
                    "width" => "",
                    "minWidth" => "",
                    "editable" => false
                ),
            )
        );
    }

    /**
     * Returns user
     */
    private function getUser() {
        $token = $this->securityContext->getToken();

        if (null === $token) {
            throw new \InvalidArgumentException('No user is set');
        }

        return $token->getUser();
    }

    /**
     * Initiates the environment
     */
    private function initEnv(Request $request) {
        $this->languageCode = $request->query->get('language', null);

        if (!$this->languageCode) {
            throw new \InvalidArgumentException('You must provide the "language" query parameter');
        }
    }

    /**
     * Returns a required parameter
     */
    private function getRequired(Request $request, $parameterName) {
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
     * Decorate the list for HATEOAS
     *
     * TODO: Use the HateoasBundle / JMSSerializer to do this.
     */
    private function decorateList(
        array $data,
        $locale,
        $limit = null,
        $page = null,
        $pages = null,
        $sortBy = null,
        $sortOrder = null,
        $search = null,
        $total = null
    ) {
        $result = array(
            'total' => $total,
            '_links' => array(
                'find' => array(
                    'href' => $this->urlGenerator->generate(
                        'get_snippets',
                        array(
                            'language' => $locale,
                            'search' => '{searchString}',
                            'sortBy' => $sortBy ?: '{sortBy}',
                            'sortOrder' => $sortOrder ?: '{sortOrder}',
                            'page' => $page ?: '{page}',
                            'limit' => $limit ?: '{limit}'
                        )
                    ),
                ),
                'sortable' => array(
                    'href' => $this->urlGenerator->generate(
                        'get_snippets',
                        array(
                            'language' => $locale,
                            'search' => $search ?: '{searchString}',
                            'sortBy' => $sortBy ?: '{sortBy}',
                            'sortOrder' => $sortOrder ?: '{sortOrder}',
                            'page' => $page ?: '{page}',
                            'limit' => $limit ?: '{limit}'
                        )
                    ),
                ),
                'pagination' => array(
                    'href' => $this->urlGenerator->generate(
                        'get_snippets',
                        array(
                            'language' => $locale,
                            'search' => $search ?: '{searchString}',
                            'sortBy' => $sortBy ?: '{sortBy}',
                            'sortOrder' => $sortOrder ?: '{sortOrder}',
                            'page' => '{page}',
                            'limit' => '{limit}'
                        )
                    ),
                ),
                'first' => array(
                    'href' => $this->urlGenerator->generate(
                        'get_snippets',
                        array(
                            'language' => $locale,
                            'search' => $search ?: '{searchString}',
                            'sortBy' => $sortBy ?: '{sortBy}',
                            'sortOrder' => $sortOrder ?: '{sortOrder}',
                            'page' => '1',
                            'limit' => $limit ?: '{limit}'
                        )
                    ),
                ),
                'last' => array(
                    'href' => $this->urlGenerator->generate(
                        'get_snippets',
                        array(
                            'language' => $locale,
                            'search' => $search ?: '{searchString}',
                            'sortBy' => $sortBy ?: '{sortBy}',
                            'sortOrder' => $sortOrder ?: '{sortOrder}',
                            'page' => $pages,
                            'limit' => $limit ?: '{limit}'
                        )
                    ),
                ),
                'all' => array(
                    'href' => $this->urlGenerator->generate(
                        'get_snippets',
                        array(
                            'language' => $locale,
                            'search' => $search ?: '{searchString}',
                            'sortBy' => $sortBy ?: '{sortBy}',
                            'sortOrder' => $sortOrder ?: '{sortOrder}'
                        )
                    ),
                )
            ),
            '_embedded' => array(
                'snippets' => $this->decorateSnippets($data, $locale)
            ),
        );

        $selfLinkArgument = array('language' => $locale);
        $nextLinkAttributes = array(
            'language' => $locale,
            'page' => $page + 1,
            'limit' => $limit ?: '{limit}'
        );
        $previousLinkAttributes = array(
            'language' => $locale,
            'page' => $page - 1,
            'limit' => $limit ?: '{limit}'
        );

        if ($limit !== null) {
            $result['limit'] = intval($limit);
            $selfLinkArgument['limit'] = $limit;
        }

        if ($page !== null) {
            $result['page'] = intval($page);
            $selfLinkArgument['page'] = $page;
        }

        if ($pages !== null) {
            $result['pages'] = intval($pages);
        }

        if ($sortBy !== null) {
            $selfLinkArgument['sortBy'] = $sortBy;
            $nextLinkAttributes['sortBy'] = $sortBy;
            $previousLinkAttributes['sortBy'] = $sortBy;
        }

        if ($sortOrder !== null) {
            $selfLinkArgument['sortOrder'] = $sortOrder;
            $nextLinkAttributes['sortOrder'] = $sortOrder;
            $previousLinkAttributes['sortOrder'] = $sortOrder;
        }

        if ($search !== null) {
            $selfLinkArgument['search'] = $search;
            $nextLinkAttributes['search'] = $search;
            $previousLinkAttributes['search'] = $search;
        }

        $selfLink = $this->urlGenerator->generate('get_snippets', $selfLinkArgument);

        $result['_links']['self'] = array(
            'href' => $selfLink,
        );

        if ($page > 1) {
            $result['_links']['previous'] = array(
                'href' => $this->urlGenerator->generate('get_snippets', $previousLinkAttributes)
            );
        }

        if ($page < $pages) {
            $result['_links']['next'] = array(
                'href' => $this->urlGenerator->generate('get_snippets', $nextLinkAttributes),
            );
        }

        return $result;
    }

    /**
     * Decorate snippets for HATEOAS
     */
    private function decorateSnippets(array $snippets, $locale) {
        $res = array();
        foreach ($snippets as $snippet) {
            $res[] = $this->decorateSnippet($snippet, $locale);
        }

        return $res;
    }

    /**
     * Decorate snippet for HATEOAS
     */
    private function decorateSnippet(array $snippet, $locale) {
        return array_merge(
            $snippet,
            array(
                '_links' => array(
                    'self' => $this->urlGenerator->generate(
                        'get_snippet',
                        array('uuid' => $snippet['id'], 'language' => $locale)
                    ),
                    'delete' => $this->urlGenerator->generate(
                        'delete_snippet',
                        array('uuid' => $snippet['id'], 'language' => $locale)
                    ),
                    'new' => $this->urlGenerator->generate('post_snippet', array('language' => $locale)),
                    'update' => $this->urlGenerator->generate(
                        'put_snippet',
                        array('uuid' => $snippet['id'], 'language' => $locale)
                    ),
                ),
            )
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
     *
     * @return Response
     */
    private function getReferentialIntegrityResponse($webspace, $references) {
        $data = array(
            'structures' => array(),
            'other' => array(),
        );

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
}
