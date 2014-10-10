<?php

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\StructureManager;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\View;
use Symfony\Component\Security\Core\SecurityContext;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use FOS\RestBundle\Controller\Annotations\Prefix;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Prefix("/api")
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

    protected $webspaceKey;
    protected $languageCode;

    public function __construct(
        ViewHandler $viewHandler,
        ContentMapper $contentMapper,
        StructureManager $structureManager,
        SnippetRepository $snippetRepository,
        SecurityContext $securityContext,
        UrlGeneratorInterface $urlGenerator
    )
    {
        $this->viewHandler = $viewHandler;
        $this->contentMapper = $contentMapper;
        $this->structureManager = $structureManager;
        $this->snippetRepository = $snippetRepository;
        $this->securityContext = $securityContext;
        $this->urlGenerator = $urlGenerator;
    }

    public function getSnippetsAction(Request $request)
    {
        $this->initEnv($request);

        $type = $request->query->get('type', null);

        $snippets = $this->snippetRepository->getSnippets(
            $this->languageCode,
            $this->webspaceKey,
            $type
        );

        $data = array();

        foreach ($snippets as $snippet) {
            $data[] = $snippet->toArray();
        }

        $data = $this->decorateList($data);

        $view = View::create($data);

        return $this->viewHandler->handle($view);
    }

    public function getSnippetAction(Request $request, $uuid)
    {
        $this->initEnv($request);

        $snippet = $this->contentMapper->load($uuid, $this->webspaceKey, $this->languageCode);
        $view = View::create($this->decorateSnippet($snippet->toArray()));

        return $this->viewHandler->handle($view);
    }

    public function postSnippetAction(Request $request)
    {
        $this->initEnv($request);
        $data = $request->request->all();

        $mapperRequest = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey($this->getRequired($request, 'template'))
            ->setLocale($this->languageCode)
            ->setUserId($this->getUser()->getId())
            ->setData($data);

        $snippet = $this->contentMapper->saveRequest($mapperRequest);
        $view = View::create($this->decorateSnippet($snippet->toArray()));

        return $this->viewHandler->handle($view);
    }

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
            ->setData($data);

        $snippet = $this->contentMapper->saveRequest($mapperRequest);
        $view = View::create($this->decorateSnippet($snippet->toArray()));

        return $this->viewHandler->handle($view);
    }

    private function getUser()
    {
        $token = $this->securityContext->getToken();

        if (null === $token) {
            throw new \InvalidArgumentException('No user is set');
        }

        return $token->getUser();
    }

    private function initEnv(Request $request)
    {
        $this->webspaceKey = $request->query->get('webspace', null);
        $this->languageCode = $request->query->get('language', null);

        if (null === $this->webspaceKey) {
            throw new \InvalidArgumentException('You must provide the "webspace" query parameter');
        }

        if (null === $this->languageCode) {
            throw new \InvalidArgumentException('You must provide the "language" query parameter');
        }
    }

    private function getRequired(Request $request, $parameterName)
    {
        $value = $request->request->get($parameterName);

        if (null === $value) {
            throw new \InvalidArgumentException(sprintf(
                'You must provide a value for the POST parameter "%s"', $parameterName
            ));
        }

        return $value;
    }

    /**
     * Decorate the list for HATEOAS
     *
     * TODO: Use the HateoasBundle / JMSSerializer to do this.
     */
    private function decorateList(array $data)
    {
        return array(
            'page' => 1,
            'limit' => PHP_INT_MAX,
            'pages' => 1,
            'total' => count($data),
            '_links' => array(
                'self' => array(
                    'href' => $this->urlGenerator->generate('get_snippets'),
                ),
                'first' => array(
                    'href' => $this->urlGenerator->generate('get_snippets'),
                ),
                'last' => array(
                    'href' => $this->urlGenerator->generate('get_snippets'),
                ),
                'next' => array(
                    'href' => $this->urlGenerator->generate('get_snippets'),
                ),
                'filter' => array(
                    'href' => $this->urlGenerator->generate('get_snippets'),
                ),
                'find' => array(
                    'href' => $this->urlGenerator->generate('get_snippets'),
                ),
                'pagination' => array(
                    'href' => $this->urlGenerator->generate('get_snippets'),
                ),
                'sortable' => array(
                    'href' => $this->urlGenerator->generate('get_snippets'),
                ),
            ),
            '_embedded' => array(
                'snippets' => $this->decorateSnippets($data)
            ),
        );

    }

    /**
     * Decorate snippets for HATEOAS
     *
     * @param array $snippets
     */
    private function decorateSnippets(array $snippets)
    {
        $res = array();
        foreach ($snippets as $snippet) {
            $res[] = $this->decorateSnippet($snippet);
        }

        return $res;
    }

    /**
     * Decorate snippet for HATEOAS
     *
     * @param array $snippets
     */
    private function decorateSnippet(array $snippet)
    {
        return array_merge(
            $snippet,
            array(
                '_links' => array(
                    'self' => $this->urlGenerator->generate('get_snippet', array('uuid' => $snippet['id'])),
                    'delete' => 'TODO',
                    'new' => $this->urlGenerator->generate('post_snippet'),
                    'update' => $this->urlGenerator->generate('put_snippet', array('uuid' => $snippet['id'])),
                ),
            )
        );
    }
}
