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

use FOS\RestBundle\Controller\Annotations\Prefix,
    FOS\RestBundle\Controller\Annotations\NamePrefix,
    FOS\RestBundle\Controller\Annotations\RouteResource,
    FOS\RestBundle\Controller\Annotations\QueryParam;

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

    protected $webspaceKey;
    protected $languageCode;

    public function __construct(
        ViewHandler $viewHandler,
        ContentMapper $contentMapper,
        StructureManager $structureManager,
        SnippetRepository $snippetRepository,
        SecurityContext $securityContext
    )
    {
        $this->viewHandler = $viewHandler;
        $this->contentMapper = $contentMapper;
        $this->structureManager = $structureManager;
        $this->snippetRepository = $snippetRepository;
        $this->securityContext = $securityContext;
    }

    public function getSnippetsAction(Request $request)
    {
        $this->initEnv($request);

        $type = $request->query->get('type', null);
        $offset = $request->query->get('offset', null);
        $max = $request->query->get('max', null);

        $snippets = $this->snippetRepository->getSnippets(
            $this->languageCode,
            $this->webspaceKey,
            $type,
            $offset,
            $max
        );

        $data = array();

        foreach ($snippets as $snippet) {
            $data[] = $snippet->toArray();
        }

        $view = View::create($data);

        return $this->viewHandler->handle($view);
    }

    public function getSnippetAction(Request $request, $uuid)
    {
        $this->initEnv($request);

        $snippet = $this->contentMapper->load($uuid, $this->webspaceKey, $this->languageCode);
        $view = View::create($snippet->toArray());

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
        $view = View::create($snippet->toArray());

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
        $view = View::create($snippet->toArray());

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
}
