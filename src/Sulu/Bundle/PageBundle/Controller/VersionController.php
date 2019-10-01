<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Version;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Handles the versions of pages.
 */
class VersionController extends AbstractRestController implements
    ClassResourceInterface,
    SecuredControllerInterface,
    SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    /**
     * @var ListRestHelperInterface
     */
    private $listRestHelper;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        ListRestHelperInterface $listRestHelper,
        UserRepositoryInterface $userRepository,
        DocumentManagerInterface $documentManager,
        RequestAnalyzerInterface $requestAnalyzer
    ) {
        parent::__construct($viewHandler);
        $this->listRestHelper = $listRestHelper;
        $this->userRepository = $userRepository;
        $this->documentManager = $documentManager;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * Returns the versions for the page with the given UUID.
     *
     * @param Request $request
     * @param string $id
     *
     * @return Response
     */
    public function cgetAction(Request $request, $id)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);

        $document = $this->documentManager->find($id, $request->query->get('locale'));
        $versions = array_reverse(array_filter($document->getVersions(), function($version) use ($locale) {
            /* @var Version $version */
            return $version->getLocale() === $locale;
        }));
        $total = count($versions);

        $limit = $this->listRestHelper->getLimit();

        $versions = array_slice($versions, $this->listRestHelper->getOffset(), $limit);

        $userIds = array_unique(array_map(function($version) {
            /* @var Version $version */
            return $version->getAuthor();
        }, $versions));

        $users = $this->userRepository->findUsersById($userIds);
        $fullNamesByIds = [];
        foreach ($users as $user) {
            $fullNamesByIds[$user->getId()] = $user->getContact()->getFullName();
        }

        $versionData = [];
        foreach ($versions as $version) {
            $versionData[] = [
                'id' => str_replace('.', '_', $version->getId()),
                'locale' => $version->getLocale(),
                'author' => array_key_exists($version->getAuthor(), $fullNamesByIds)
                    ? $fullNamesByIds[$version->getAuthor()] : '',
                'authored' => $version->getAuthored(),
            ];
        }

        $versionCollection = new ListRepresentation(
            $versionData,
            'page_versions',
            'sulu_page.get_page_versions',
            [
                'id' => $id,
                'locale' => $locale,
                'webspace' => $request->get('webspace'),
            ],
            $this->listRestHelper->getPage(),
            $limit,
            $total
        );

        return $this->handleView($this->view($versionCollection));
    }

    /**
     * @param Request $request
     * @param string $id
     * @param int $version
     *
     * @return Response
     */
    public function postTriggerAction(Request $request, $id, $version)
    {
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getLocale($request);

        $data = null;

        switch ($action) {
            case 'restore':
                $document = $this->documentManager->find($id, $locale);
                $this->documentManager->restore(
                    $document,
                    $locale,
                    str_replace('_', '.', $version)
                );
                $this->documentManager->flush();

                $data = $this->documentManager->find($id, $locale);
                break;
        }

        $view = $this->view($data, null !== $data ? 200 : 204);

        $context = new Context();
        $context->setGroups(['defaultPage']);

        return $this->handleView($view->setContext($context));
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        $webspace = $this->requestAnalyzer->getWebspace();

        if (!$webspace) {
            throw new MissingMandatoryParametersException('The webspace parameter is missing!');
        }

        if ($webspace) {
            return 'sulu.webspaces.' . $webspace->getKey();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(Request $request)
    {
        return $this->getRequestParameter($request, 'locale', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getSecuredClass()
    {
        return SecurityBehavior::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecuredObjectId(Request $request)
    {
        return $request->get('id');
    }
}
