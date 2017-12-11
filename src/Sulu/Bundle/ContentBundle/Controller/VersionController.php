<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use JMS\Serializer\SerializationContext;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Version;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Handles the versions of pages.
 */
class VersionController extends FOSRestController implements
    ClassResourceInterface,
    SecuredControllerInterface,
    SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    /**
     * Returns the versions for the node with the given UUID.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return Response
     */
    public function cgetAction(Request $request, $uuid)
    {
        $locale = $this->getRequestParameter($request, 'language', true);

        $document = $this->get('sulu_document_manager.document_manager')->find($uuid, $request->query->get('language'));
        $versions = array_reverse(array_filter($document->getVersions(), function($version) use ($locale) {
            /** @var Version $version */
            return $version->getLocale() === $locale;
        }));
        $total = count($versions);

        $listRestHelper = $this->get('sulu_core.list_rest_helper');
        $limit = $listRestHelper->getLimit();

        $versions = array_slice($versions, $listRestHelper->getOffset(), $limit);

        $userIds = array_unique(array_map(function($version) {
            /** @var Version $version */
            return $version->getAuthor();
        }, $versions));

        $users = $this->get('sulu_security.user_repository')->findUsersById($userIds);
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
            'versions',
            'get_node_versions',
            [
                'uuid' => $uuid,
                'language' => $locale,
                'webspace' => $request->get('webspace'),
            ],
            $listRestHelper->getPage(),
            $limit,
            $total
        );

        return $this->handleView($this->view($versionCollection));
    }

    /**
     * @param Request $request
     * @param string $uuid
     * @param int $version
     *
     * @Post("/nodes/{uuid}/versions/{version}")
     *
     * @return Response
     */
    public function postTriggerAction(Request $request, $uuid, $version)
    {
        $action = $this->getRequestParameter($request, 'action', true);
        $language = $this->getLocale($request);

        switch ($action) {
            case 'restore':
                $document = $this->getDocumentManager()->find($uuid, $language);
                $this->getDocumentManager()->restore(
                    $document,
                    $language,
                    str_replace('_', '.', $version)
                );
                $this->getDocumentManager()->flush();

                $data = $this->getDocumentManager()->find($uuid, $language);
                break;
        }

        $view = $this->view($data, null !== $data ? 200 : 204);
        $view->setSerializationContext(SerializationContext::create()->setGroups(['defaultPage']));

        return $this->handleView($view);
    }

    /**
     * @return DocumentManagerInterface
     */
    protected function getDocumentManager()
    {
        return $this->get('sulu_document_manager.document_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        $requestAnalyzer = $this->get('sulu_core.webspace.request_analyzer');
        $webspace = $requestAnalyzer->getWebspace();

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
        return $this->getRequestParameter($request, 'language', true);
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
        return $request->get('uuid');
    }
}
