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
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\SerializationContext;
use PHPCR\ItemNotFoundException;
use PHPCR\PropertyInterface;
use Sulu\Bundle\ContentBundle\Repository\NodeRepository;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Content\Repository\Mapping\MappingInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\InvalidHashException;
use Sulu\Component\Rest\Exception\MissingParameterChoiceException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\ParameterDataTypeException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles content nodes.
 */
class NodeController extends RestController implements ClassResourceInterface, SecuredControllerInterface, SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    const WEBSPACE_NODE_SINGLE = 'single';
    const WEBSPACE_NODES_ALL = 'all';

    private static $relationName = 'nodes';

    /**
     * returns language code from request.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getLanguage(Request $request)
    {
        return $this->getRequestParameter($request, 'language', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(Request $request)
    {
        return $this->getLanguage($request);
    }

    /**
     * returns webspace key from request.
     *
     * @param Request $request
     * @param bool $force
     *
     * @return string
     */
    private function getWebspace(Request $request, $force = true)
    {
        return $this->getRequestParameter($request, 'webspace', $force);
    }

    /**
     * returns entry point (webspace as node).
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function entryAction(Request $request)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);

        $depth = $this->getRequestParameter($request, 'depth', false, 1);
        $ghostContent = $this->getBooleanRequestParameter($request, 'ghost-content', false, false);

        $view = $this->responseGetById(
            null,
            function () use ($language, $webspace, $depth, $ghostContent) {
                try {
                    return $this->getRepository()->getWebspaceNode(
                        $webspace,
                        $language,
                        $depth,
                        $ghostContent
                    );
                } catch (DocumentNotFoundException $ex) {
                    return;
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * returns a content item with given UUID as JSON String.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(Request $request, $uuid)
    {
        if ($request->get('fields') !== null) {
            return $this->getContent($request, $uuid);
        }

        return $this->getSingleNode($request, $uuid);
    }

    /**
     * Returns single content.
     *
     * @param Request $request
     * @param $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Sulu\Component\Rest\Exception\MissingParameterException
     * @throws \Sulu\Component\Rest\Exception\ParameterDataTypeException
     */
    private function getContent(Request $request, $uuid)
    {
        $properties = array_filter(explode(',', $request->get('fields', '')));
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);
        $excludeShadows = $this->getBooleanRequestParameter($request, 'exclude-shadows', false, false);
        $webspaceNodes = $this->getRequestParameter($request, 'webspace-nodes');
        $tree = $this->getBooleanRequestParameter($request, 'tree', false, false);
        $locale = $this->getRequestParameter($request, 'language', true);
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);

        $user = $this->getUser();

        $mapping = MappingBuilder::create()
            ->setHydrateGhost(!$excludeGhosts)
            ->setHydrateShadow(!$excludeShadows)
            ->setResolveConcreteLocales(true)
            ->addProperties($properties)
            ->getMapping();

        try {
            if ($tree) {
                return $this->getTreeContent($uuid, $locale, $webspaceKey, $webspaceNodes, $mapping, $user);
            }
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);

            return $this->handleView($view);
        }

        $data = $this->get('sulu_content.content_repository')->find($uuid, $locale, $webspaceKey, $mapping, $user);
        $view = $this->view($data);

        return $this->handleView($view);
    }

    /**
     * Returns tree response for given uuid.
     *
     * @param string $uuid
     * @param string $locale
     * @param string $webspaceKey
     * @param bool $webspaceNodes
     * @param MappingInterface $mapping
     * @param UserInterface $user
     *
     * @return Response
     *
     * @throws ParameterDataTypeException
     * @throws EntityNotFoundException
     */
    private function getTreeContent(
        $uuid,
        $locale,
        $webspaceKey,
        $webspaceNodes,
        MappingInterface $mapping,
        UserInterface $user
    ) {
        if (!in_array($webspaceNodes, [static::WEBSPACE_NODE_SINGLE, static::WEBSPACE_NODES_ALL, null])) {
            throw new ParameterDataTypeException(get_class($this), 'webspace-nodes');
        }

        try {
            $contents = $this->get('sulu_content.content_repository')->findParentsWithSiblingsByUuid(
                $uuid,
                $locale,
                $webspaceKey,
                $mapping,
                $user
            );
        } catch (ItemNotFoundException $e) {
            throw new EntityNotFoundException('node', $uuid, $e);
        }

        if ($webspaceNodes === static::WEBSPACE_NODES_ALL) {
            $contents = $this->getWebspaceNodes($mapping, $contents, $webspaceKey, $locale, $user);
        } elseif ($webspaceNodes === static::WEBSPACE_NODE_SINGLE) {
            $contents = $this->getWebspaceNode($mapping, $contents, $webspaceKey, $locale, $user);
        }

        $view = $this->view(new CollectionRepresentation($contents, static::$relationName));

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @param string $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getSingleNode(Request $request, $uuid)
    {
        $language = $this->getLanguage($request);
        $breadcrumb = $this->getBooleanRequestParameter($request, 'breadcrumb', false, false);
        $complete = $this->getBooleanRequestParameter($request, 'complete', false, true);
        $ghostContent = $this->getBooleanRequestParameter($request, 'ghost-content', false, false);

        $view = $this->responseGetById(
            $uuid,
            function ($id) use ($language, $ghostContent) {
                try {
                    return $this->getDocumentManager()->find(
                        $id,
                        $language,
                        [
                            'load_ghost_content' => $ghostContent,
                        ]
                    );
                } catch (DocumentNotFoundException $ex) {
                    return;
                }
            }
        );

        $groups = [];
        if (!$complete) {
            $groups[] = 'smallPage';
        } else {
            $groups[] = 'defaultPage';
        }

        if ($breadcrumb) {
            $groups[] = 'breadcrumbPage';
        }

        // preview needs also null value to work correctly
        $view->setSerializationContext(SerializationContext::create()->setSerializeNull(true)->setGroups($groups));

        return $this->handleView($view);
    }

    /**
     * Returns a tree along the given path with the siblings of all nodes on the path.
     * This functionality is required for preloading the content navigation.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getTreeForUuid(Request $request, $uuid)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request, false);
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);
        $excludeShadows = $this->getBooleanRequestParameter($request, 'exclude-shadows', false, false);
        $appendWebspaceNode = $this->getBooleanRequestParameter($request, 'webspace-node', false, false);

        try {
            if ($uuid !== null && $uuid !== '') {
                $result = $this->getRepository()->getNodesTree(
                    $uuid,
                    $webspace,
                    $language,
                    $excludeGhosts,
                    $excludeShadows,
                    $appendWebspaceNode
                );
            } elseif ($webspace !== null) {
                $result = $this->getRepository()->getWebspaceNode($webspace, $language);
            } else {
                $result = $this->getRepository()->getWebspaceNodes($language);
            }
        } catch (DocumentNotFoundException $ex) {
            // TODO return 404 and handle this edge case on client side
            return $this->redirect(
                $this->generateUrl(
                    'get_nodes',
                    [
                        'tree' => 'false',
                        'depth' => 1,
                        'language' => $language,
                        'webspace' => $webspace,
                        'exclude-ghosts' => $excludeGhosts,
                    ]
                )
            );
        }

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * Returns nodes by given ids.
     *
     * @param Request $request
     * @param array $idString
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getNodesByIds(Request $request, $idString)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request, false);

        $result = $this->getRepository()->getNodesByIds(
            preg_split('/[,]/', $idString, -1, PREG_SPLIT_NO_EMPTY),
            $webspace,
            $language
        );

        return $this->handleView($this->view($result));
    }

    /**
     * returns a content item for startpage.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);

        $result = $this->getRepository()->getIndexNode($webspace, $language);

        return $this->handleView($this->view($result));
    }

    /**
     * returns all content items as JSON String.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        if ($request->get('fields') !== null) {
            return $this->cgetContent($request);
        }

        return $this->cgetNodes($request);
    }

    /**
     * Returns complete nodes.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws MissingParameterException
     * @throws ParameterDataTypeException
     *
     * @deprecated this will be removed when the content-repository is able to solve all requirements
     */
    public function cgetNodes(Request $request)
    {
        $tree = $this->getBooleanRequestParameter($request, 'tree', false, false);
        $ids = $this->getRequestParameter($request, 'ids');

        if ($tree === true) {
            return $this->getTreeForUuid($request, $this->getRequestParameter($request, 'id', false, null));
        } elseif ($ids !== null) {
            return $this->getNodesByIds($request, $ids);
        }

        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);

        $parentUuid = $request->get('parent');
        $depth = $request->get('depth', 1);
        $depth = intval($depth);
        $flat = $request->get('flat', 'true');
        $flat = ($flat === 'true');

        // TODO pagination
        $result = $this->getRepository()->getNodes(
            $parentUuid,
            $webspace,
            $language,
            $depth,
            $flat,
            false,
            $excludeGhosts
        );

        return $this->handleView($this->view($result));
    }

    /**
     * Returns content array by parent or webspace root.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws MissingParameterChoiceException
     * @throws MissingParameterException
     * @throws ParameterDataTypeException
     */
    private function cgetContent(Request $request)
    {
        $parent = $request->get('parent');
        $properties = array_filter(explode(',', $request->get('fields', '')));
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);
        $excludeShadows = $this->getBooleanRequestParameter($request, 'exclude-shadows', false, false);
        $webspaceNodes = $this->getRequestParameter($request, 'webspace-nodes');
        $locale = $this->getRequestParameter($request, 'language', true);
        $webspaceKey = $this->getRequestParameter($request, 'webspace', false);

        if (!$webspaceKey && !$webspaceNodes) {
            throw new MissingParameterChoiceException(get_class($this), ['webspace', 'webspace-nodes']);
        }

        if (!in_array($webspaceNodes, [self::WEBSPACE_NODE_SINGLE, static::WEBSPACE_NODES_ALL, null])) {
            throw new ParameterDataTypeException(get_class($this), 'webspace-nodes');
        }

        $contentRepository = $this->get('sulu_content.content_repository');
        $user = $this->getUser();

        $mapping = MappingBuilder::create()
            ->setHydrateGhost(!$excludeGhosts)
            ->setHydrateShadow(!$excludeShadows)
            ->setResolveConcreteLocales(true)
            ->addProperties($properties)
            ->setResolveUrl(true)
            ->getMapping();

        $contents = [];
        if ($webspaceKey) {
            if (!$parent) {
                $contents = $contentRepository->findByWebspaceRoot($locale, $webspaceKey, $mapping, $user);
            } else {
                $contents = $contentRepository->findByParentUuid($parent, $locale, $webspaceKey, $mapping, $user);
            }
        }

        if ($webspaceNodes === static::WEBSPACE_NODES_ALL) {
            $contents = $this->getWebspaceNodes($mapping, $contents, $webspaceKey, $locale, $user);
        } elseif ($webspaceNodes === static::WEBSPACE_NODE_SINGLE) {
            $contents = $this->getWebspaceNode($mapping, $contents, $webspaceKey, $locale, $user);
        }

        $list = new CollectionRepresentation($contents, static::$relationName);
        $view = $this->view($list);

        return $this->handleView($view);
    }

    /**
     * Returns the title of the pages for a given smart content configuration.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @deprecated will be removed with version 1.2
     */
    public function filterAction(Request $request)
    {
        // load data from request
        $dataSource = $this->getRequestParameter($request, 'dataSource');
        $includeSubFolders = $this->getBooleanRequestParameter($request, 'includeSubFolders', false, false);
        $limitResult = $this->getRequestParameter($request, 'limitResult');
        $tagNames = $this->getRequestParameter($request, 'tags');
        $tagOperator = $this->getRequestParameter($request, 'tagOperator', false, 'or');
        $sortBy = $this->getRequestParameter($request, 'sortBy');
        $sortMethod = $this->getRequestParameter($request, 'sortMethod', false, 'asc');
        $exclude = $this->getRequestParameter($request, 'exclude');
        $webspaceKey = $this->getWebspace($request);
        $languageCode = $this->getLanguage($request);

        // resolve tag names
        $resolvedTags = [];

        /** @var TagManagerInterface $tagManager */
        $tagManager = $this->get('sulu_tag.tag_manager');

        if (isset($tagNames)) {
            $tags = explode(',', $tagNames);
            foreach ($tags as $tag) {
                $resolvedTag = $tagManager->findByName($tag);
                if ($resolvedTag) {
                    $resolvedTags[] = $resolvedTag->getId();
                }
            }
        }

        // get sort columns
        $sortColumns = [];
        if (isset($sortBy)) {
            $columns = explode(',', $sortBy);
            foreach ($columns as $column) {
                if ($column) {
                    $sortColumns[] = $column;
                }
            }
        }

        $filterConfig = [
            'dataSource' => $dataSource,
            'includeSubFolders' => $includeSubFolders,
            'limitResult' => $limitResult,
            'tags' => $resolvedTags,
            'tagOperator' => $tagOperator,
            'sortBy' => $sortColumns,
            'sortMethod' => $sortMethod,
        ];

        /** @var NodeRepository $repository */
        $repository = $this->get('sulu_content.node_repository');
        $content = $repository->getFilteredNodes(
            $filterConfig,
            $languageCode,
            $webspaceKey,
            true,
            true,
            $exclude !== null ? [$exclude] : []
        );

        return $this->handleView($this->view($content));
    }

    /**
     * saves node with given uuid and data.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return Response
     *
     * @throws InvalidFormException
     * @throws InvalidHashException
     * @throws MissingParameterException
     */
    public function putAction(Request $request, $uuid)
    {
        $language = $this->getLanguage($request);
        $action = $request->get('action');

        $this->checkActionParameterSecurity($action, $language, $uuid);

        $document = $this->getDocumentManager()->find(
            $uuid,
            $language,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        $type = $this->getMetadataFactory()->getMetadataForClass(get_class($document))->getAlias();

        $this->get('sulu_hash.request_hash_checker')->checkHash($request, $document, $document->getUuid());

        $this->persistDocument($request, $type, $document, $language);
        $this->handleActionParameter($action, $document, $language);
        $this->getDocumentManager()->flush();

        $view = $this->view($document);
        $view->setSerializationContext(
            SerializationContext::create()->setSerializeNull(true)->setGroups(['defaultPage'])
        );

        return $this->handleView($view);
    }

    /**
     * Updates a content item and returns result as JSON String.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws InvalidFormException
     * @throws MissingParameterException
     */
    public function postAction(Request $request)
    {
        $type = 'page';
        $language = $this->getLanguage($request);
        $action = $request->get('action');

        $this->checkActionParameterSecurity($action, $language);

        $document = $this->getDocumentManager()->create($type);

        $this->persistDocument($request, $type, $document, $language);
        $this->handleActionParameter($action, $document, $language);
        $this->getDocumentManager()->flush();

        $view = $this->view($document);
        $view->setSerializationContext(
            SerializationContext::create()->setSerializeNull(true)->setGroups(['defaultPage'])
        );

        return $this->handleView($view);
    }

    /**
     * deletes node with given uuid.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $uuid)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);
        $force = $this->getBooleanRequestParameter($request, 'force', false, false);

        if (!$force) {
            $references = array_filter(
                $this->getRepository()->getReferences($uuid),
                function (PropertyInterface $reference) {
                    return $reference->getParent()->isNodeType('sulu:page');
                }
            );

            if (count($references) > 0) {
                $data = [
                    'structures' => [],
                    'other' => [],
                ];

                foreach ($references as $reference) {
                    $content = $this->get('sulu.content.mapper')->load(
                        $reference->getParent()->getIdentifier(),
                        $webspace,
                        $language,
                        true
                    );
                    $data['structures'][] = $content->toArray();
                }

                return $this->handleView($this->view($data, 409));
            }
        }

        $view = $this->responseDelete(
            $uuid,
            function ($id) use ($webspace) {
                try {
                    $this->getRepository()->deleteNode($id, $webspace);
                } catch (DocumentNotFoundException $ex) {
                    throw new EntityNotFoundException('Content', $id);
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * trigger a action for given node specified over get-action parameter
     * - move: moves a node
     *   + destination: specifies the destination node
     * - copy: copy a node
     *   + destination: specifies the destination node.
     *
     * @Post("/nodes/{uuid}")
     *
     * @param string $uuid
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postTriggerAction($uuid, Request $request)
    {
        // extract parameter
        $action = $this->getRequestParameter($request, 'action', true);
        $language = $this->getLanguage($request);
        $userId = $this->getUser()->getId();

        // prepare vars
        $repository = $this->getRepository();
        $view = null;
        $data = null;

        try {
            switch ($action) {
                case 'move':
                    $data = $this->getDocumentManager()->find($uuid, $language);

                    $this->getDocumentManager()->move(
                        $data,
                        $this->getRequestParameter($request, 'destination', true)
                    );
                    $this->getDocumentManager()->flush();
                    break;
                case 'copy':
                    $copiedPath = $this->getDocumentManager()->copy(
                        $this->getDocumentManager()->find($uuid, $language),
                        $this->getRequestParameter($request, 'destination', true)
                    );
                    $this->getDocumentManager()->flush();

                    $data = $this->getDocumentManager()->find($copiedPath, $language);
                    break;
                case 'order':
                    $position = (int) $this->getRequestParameter($request, 'position', true);
                    $webspace = $this->getWebspace($request);

                    // call repository method
                    $data = $repository->orderAt($uuid, $position, $webspace, $language, $userId);
                    break;
                case 'copy-locale':
                    $destLocale = $this->getRequestParameter($request, 'dest', true);
                    $webspace = $this->getWebspace($request);

                    // call repository method
                    $data = $repository->copyLocale($uuid, $userId, $webspace, $language, explode(',', $destLocale));
                    break;
                case 'unpublish':
                    $document = $this->getDocumentManager()->find($uuid, $language);
                    $this->getDocumentManager()->unpublish($document, $language);
                    $this->getDocumentManager()->flush();

                    $data = $this->getDocumentManager()->find($uuid, $language);
                    break;
                case 'remove-draft':
                    $data = $this->getDocumentManager()->find($uuid, $language);
                    $this->getDocumentManager()->removeDraft($data, $language);
                    $this->getDocumentManager()->flush();
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // prepare view
            $view = $this->view($data, $data !== null ? 200 : 204);
            $view->setSerializationContext(SerializationContext::create()->setGroups(['defaultPage']));
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

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
     * @return NodeRepositoryInterface
     */
    protected function getRepository()
    {
        return $this->get('sulu_content.node_repository');
    }

    /**
     * @return BaseMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->get('sulu_document_manager.metadata_factory.base');
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        $requestAnalyzer = $this->get('sulu_core.webspace.request_analyzer');
        $webspace = $requestAnalyzer->getWebspace();

        if ($webspace) {
            return 'sulu.webspaces.' . $webspace->getKey();
        }
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
        $id = null;

        if (null !== ($uuid = $request->get('uuid'))) {
            $id = $uuid;
        } elseif (null !== ($parent = $request->get('parent')) && $request->getMethod() !== Request::METHOD_GET) {
            // the user is always allowed to get the children of a node
            // so the security check only applies for requests not being GETs
            $id = $parent;
        }

        return $id;
    }

    /**
     * Returns content for all webspaces.
     * If a webspaceKey is given the $contents array will be set as children of this webspace.
     *
     * @param MappingInterface $mapping
     * @param array $contents
     * @param string|null $webspaceKey
     * @param string $locale
     * @param UserInterface $user
     *
     * @return Content[]
     */
    private function getWebspaceNodes(
        MappingInterface $mapping,
        array $contents,
        $webspaceKey,
        $locale,
        UserInterface $user
    ) {
        $webspaceManager = $this->get('sulu_core.webspace.webspace_manager');
        $sessionManager = $this->get('sulu.phpcr.session');

        $paths = [];
        $webspaces = [];
        /** @var Webspace $webspace */
        foreach ($webspaceManager->getWebspaceCollection() as $webspace) {
            if (null === $webspace->getLocalization($locale)) {
                continue;
            }

            $paths[] = $sessionManager->getContentPath($webspace->getKey());
            $webspaces[$webspace->getKey()] = $webspace;
        }

        return $this->getWebspaceNodesByPaths($paths, $webspaceKey, $locale, $mapping, $webspaces, $contents, $user);
    }

    /**
     * Returns content for all webspaces.
     * If a webspaceKey is given the $contents array will be set as children of this webspace.
     *
     * @param MappingInterface $mapping
     * @param array $contents
     * @param string $webspaceKey
     * @param string $locale
     * @param UserInterface $user
     *
     * @return Content[]
     */
    private function getWebspaceNode(
        MappingInterface $mapping,
        array $contents,
        $webspaceKey,
        $locale,
        UserInterface $user
    ) {
        $webspaceManager = $this->get('sulu_core.webspace.webspace_manager');
        $sessionManager = $this->get('sulu.phpcr.session');

        $webspace = $webspaceManager->findWebspaceByKey($webspaceKey);
        $paths = [$sessionManager->getContentPath($webspace->getKey())];
        $webspaces = [$webspace->getKey() => $webspace];

        return $this->getWebspaceNodesByPaths(
            $paths,
            $webspaceKey,
            $locale,
            $mapping,
            $webspaces,
            $contents,
            $user
        );
    }

    /**
     * @param string[] $paths
     * @param string $webspaceKey
     * @param string $locale
     * @param MappingInterface $mapping
     * @param Webspace[] $webspaces
     * @param Content[] $contents
     * @param UserInterface $user
     *
     * @return Content[]
     */
    private function getWebspaceNodesByPaths(
        array $paths,
        $webspaceKey,
        $locale,
        MappingInterface $mapping,
        array $webspaces,
        array $contents,
        UserInterface $user
    ) {
        $webspaceContents = $this->get('sulu_content.content_repository')->findByPaths(
            $paths,
            $locale,
            $mapping,
            $user
        );

        foreach ($webspaceContents as $webspaceContent) {
            $webspaceContent->setDataProperty('title', $webspaces[$webspaceContent->getWebspaceKey()]->getName());

            if ($webspaceContent->getWebspaceKey() === $webspaceKey) {
                $webspaceContent->setChildren($contents);
            }
        }

        return $webspaceContents;
    }

    /**
     * Persists the document using the given information.
     *
     * @param Request $request
     * @param $type
     * @param $document
     * @param $language
     *
     * @throws InvalidFormException
     * @throws MissingParameterException
     */
    private function persistDocument(Request $request, $type, $document, $language)
    {
        $data = $request->request->all();

        if ($request->query->has('parent')) {
            $data['parent'] = $request->query->get('parent');
        }

        $form = $this->createForm(
            $type,
            $document,
            [
                // disable csrf protection, since we can't produce a token, because the form is cached on the client
                'csrf_protection' => false,
                'webspace_key' => $this->getWebspace($request),
            ]
        );
        $form->submit($data, false);

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->getDocumentManager()->persist(
            $document,
            $language,
            [
                'user' => $this->getUser()->getId(),
                'clear_missing_content' => false,
            ]
        );
    }

    /**
     * Checks if the user has the required permissions for the given action with the given locale. The additional
     * uuid parameter will also include checks for the document identified by it.
     *
     * @param string $actionParameter
     * @param string $locale
     * @param string $uuid
     */
    private function checkActionParameterSecurity($actionParameter, $locale, $uuid = null)
    {
        $permission = null;
        switch ($actionParameter) {
            case 'publish':
                $permission = 'live';
                break;
        }

        if (!$permission) {
            return;
        }

        $this->get('sulu_security.security_checker')->checkPermission(
            new SecurityCondition(
                $this->getSecurityContext(),
                $locale,
                $this->getSecuredClass(),
                $uuid
            ),
            $permission
        );
    }

    /**
     * Delegates actions by given actionParameter, which can be retrieved from the request.
     *
     * @param string $actionParameter
     * @param object $document
     * @param string $locale
     */
    private function handleActionParameter($actionParameter, $document, $locale)
    {
        switch ($actionParameter) {
            case 'publish':
                $this->getDocumentManager()->publish($document, $locale);
                break;
        }
    }
}
