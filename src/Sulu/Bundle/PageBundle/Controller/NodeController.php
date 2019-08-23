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
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use PHPCR\ItemNotFoundException;
use PHPCR\PropertyInterface;
use Sulu\Bundle\PageBundle\Repository\NodeRepositoryInterface;
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
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles content nodes.
 */
class NodeController extends RestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    const WEBSPACE_NODE_SINGLE = 'single';

    const WEBSPACE_NODES_ALL = 'all';

    protected static $relationName = 'pages';

    public function __construct()
    {
        if (self::class === get_class($this)) {
            @trigger_error('Controller "' . self::class . '" is deprecated. Use "' . PageController::class . '" instead.');
        }
    }

    /**
     * returns language code from request.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getLocale(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', false, null);

        if ($locale) {
            return $locale;
        }

        return $this->getRequestParameter($request, 'language', true);
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
     * returns a content item with given UUID as JSON String.
     *
     * @param Request $request
     * @param string $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $breadcrumb = $this->getBooleanRequestParameter($request, 'breadcrumb', false, false);
        $complete = $this->getBooleanRequestParameter($request, 'complete', false, true);
        $ghostContent = $this->getBooleanRequestParameter($request, 'ghost-content', false, false);
        $template = $this->getRequestParameter($request, 'template', false, null);

        $view = $this->responseGetById(
            $id,
            function($id) use ($locale, $ghostContent, $template, $request) {
                try {
                    $document = $this->getDocumentManager()->find(
                        $id,
                        $locale,
                        [
                            'load_ghost_content' => $ghostContent,
                            'structure_type' => $template,
                        ]
                    );

                    $this->get('sulu_security.security_checker')->checkPermission(
                        $this->getSecurityCondition($request, $document),
                        'view'
                    );

                    return $document;
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

        $context = new Context();
        $context->setGroups($groups);

        // preview needs also null value to work correctly
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Returns tree response for given id.
     *
     * @param string $id
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
        $id,
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
            $contents = $this->get('sulu_page.content_repository')->findParentsWithSiblingsByUuid(
                $id,
                $locale,
                $webspaceKey,
                $mapping,
                $user
            );
        } catch (ItemNotFoundException $e) {
            throw new EntityNotFoundException('node', $id, $e);
        }

        if ($webspaceNodes === static::WEBSPACE_NODES_ALL) {
            $contents = $this->getWebspaceNodes($mapping, $contents, $locale, $user);
        } elseif ($webspaceNodes === static::WEBSPACE_NODE_SINGLE) {
            $contents = $this->getWebspaceNode($mapping, $contents, $webspaceKey, $locale, $user);
        }

        $view = $this->view(new CollectionRepresentation($contents, static::$relationName));

        return $this->handleView($view);
    }

    /**
     * Returns a tree along the given path with the siblings of all nodes on the path.
     * This functionality is required for preloading the content navigation.
     *
     * @param Request $request
     * @param string $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getTreeForUuid(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $webspace = $this->getWebspace($request, false);
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);
        $excludeShadows = $this->getBooleanRequestParameter($request, 'exclude-shadows', false, false);

        try {
            if (null !== $id && '' !== $id) {
                $result = $this->getRepository()->getNodesTree(
                    $id,
                    $webspace,
                    $locale,
                    $excludeGhosts,
                    $excludeShadows
                );
            } elseif (null !== $webspace) {
                $result = $this->getRepository()->getWebspaceNode($webspace, $locale);
            } else {
                $result = $this->getRepository()->getWebspaceNodes($locale);
            }
        } catch (DocumentNotFoundException $ex) {
            // TODO return 404 and handle this edge case on client side
            return $this->redirect(
                $this->generateUrl(
                    'get_nodes',
                    [
                        'tree' => 'false',
                        'depth' => 1,
                        'language' => $locale,
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
        $locale = $this->getLocale($request);
        $webspace = $this->getWebspace($request, false);

        $result = $this->getRepository()->getNodesByIds(
            preg_split('/[,]/', $idString, -1, PREG_SPLIT_NO_EMPTY),
            $webspace,
            $locale
        );

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
        if (null !== $request->get('fields')) {
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

        if (true === $tree) {
            return $this->getTreeForUuid($request, $this->getRequestParameter($request, 'id', false, null));
        } elseif (null !== $ids) {
            return $this->getNodesByIds($request, $ids);
        }

        $locale = $this->getLocale($request);
        $webspace = $this->getWebspace($request);
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);

        $parentUuid = $request->get('parentId');
        $depth = $request->get('depth', 1);
        $depth = intval($depth);
        $flat = $request->get('flat', 'true');
        $flat = ('true' === $flat);

        // TODO pagination
        $result = $this->getRepository()->getNodes(
            $parentUuid,
            $webspace,
            $locale,
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
    protected function cgetContent(Request $request)
    {
        $parent = $request->get('parentId');
        $properties = array_filter(explode(',', $request->get('fields', '')));
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);
        $excludeShadows = $this->getBooleanRequestParameter($request, 'exclude-shadows', false, false);
        $expandedIds = $this->getRequestParameter(
            $request,
            'expandedIds',
            false,
            $this->getRequestParameter($request, 'selectedIds', false, null)
        );
        $webspaceNodes = $this->getRequestParameter($request, 'webspace-nodes');
        $locale = $this->getLocale($request);
        $webspaceKey = $this->getRequestParameter($request, 'webspace', false);

        if (!$locale) {
            throw new MissingParameterException(get_class($this), 'locale');
        }

        if (!$webspaceKey && !$webspaceNodes && !$parent) {
            throw new MissingParameterChoiceException(get_class($this), ['webspace', 'webspace-nodes', 'parentId']);
        }

        if (!in_array($webspaceNodes, [self::WEBSPACE_NODE_SINGLE, static::WEBSPACE_NODES_ALL, null])) {
            throw new ParameterDataTypeException(get_class($this), 'webspace-nodes');
        }

        $contentRepository = $this->get('sulu_page.content_repository');
        $user = $this->getUser();

        $mapping = MappingBuilder::create()
            ->setHydrateGhost(!$excludeGhosts)
            ->setHydrateShadow(!$excludeShadows)
            ->setResolveConcreteLocales(true)
            ->addProperties($properties)
            ->setResolveUrl(true)
            ->getMapping();

        try {
            if ($expandedIds) {
                return $this->getTreeContent($expandedIds, $locale, $webspaceKey, $webspaceNodes, $mapping, $user);
            }
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);

            return $this->handleView($view);
        }

        $contents = [];

        if ($parent) {
            $contents = $contentRepository->findByParentUuid($parent, $locale, $webspaceKey, $mapping, $user);
        } elseif ($webspaceKey) {
            $contents = $contentRepository->findByWebspaceRoot($locale, $webspaceKey, $mapping, $user);
        }

        if ($webspaceNodes === static::WEBSPACE_NODES_ALL) {
            $contents = $this->getWebspaceNodes($mapping, $contents, $locale, $user);
        } elseif ($webspaceNodes === static::WEBSPACE_NODE_SINGLE) {
            $contents = $this->getWebspaceNode($mapping, $contents, $webspaceKey, $locale, $user);
        }

        $list = new CollectionRepresentation($contents, static::$relationName);
        $view = $this->view($list);

        return $this->handleView($view);
    }

    /**
     * saves node with given id and data.
     *
     * @param Request $request
     * @param string $id
     *
     * @return Response
     *
     * @throws InvalidFormException
     * @throws InvalidHashException
     * @throws MissingParameterException
     */
    public function putAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $action = $request->get('action');

        $document = $this->getDocumentManager()->find(
            $id,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        $this->get('sulu_security.security_checker')->checkPermission(
            $this->getSecurityCondition($request, $document),
            'edit'
        );

        $this->checkActionParameterSecurity($action, $request, $document);

        $formType = $this->getMetadataFactory()->getMetadataForClass(get_class($document))->getFormType();

        $this->get('sulu_hash.request_hash_checker')->checkHash($request, $document, $document->getUuid());

        $this->persistDocument($request, $formType, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->getDocumentManager()->flush();

        $context = new Context();
        $context->setGroups(['defaultPage']);

        return $this->handleView($this->view($document)->setContext($context));
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
        $locale = $this->getLocale($request);
        $action = $request->get('action');

        $parentDocument = $this->getDocumentManager()->find(
            $this->getRequestParameter($request, 'parentId', true),
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        $this->get('sulu_security.security_checker')->checkPermission(
            $this->getSecurityCondition($request, $parentDocument),
            'add'
        );

        $this->checkActionParameterSecurity($action, $request, $parentDocument);

        $document = $this->getDocumentManager()->create($type);
        $formType = $this->getMetadataFactory()->getMetadataForAlias($type)->getFormType();

        $this->persistDocument($request, $formType, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->getDocumentManager()->flush();

        $context = new Context();
        $context->setGroups(['defaultPage']);

        return $this->handleView($this->view($document)->setContext($context));
    }

    /**
     * deletes node with given id.
     *
     * @param Request $request
     * @param string $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $webspace = $this->getWebspace($request);
        $force = $this->getBooleanRequestParameter($request, 'force', false, false);

        if (!$force) {
            $references = array_filter(
                $this->getRepository()->getReferences($id),
                function(PropertyInterface $reference) {
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
                        $locale,
                        true
                    );
                    $data['structures'][] = $content->toArray();
                }

                return $this->handleView($this->view($data, 409));
            }
        }

        $view = $this->responseDelete(
            $id,
            function($id) use ($request) {
                try {
                    $document = $this->getDocumentManager()->find($id);

                    $this->get('sulu_security.security_checker')->checkPermission(
                            $this->getSecurityCondition($request, $document),
                            'delete'
                        );

                    $this->getDocumentManager()->remove($document);
                    $this->getDocumentManager()->flush();
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
     * @Post("/nodes/{id}")
     *
     * @param string $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postTriggerAction($id, Request $request)
    {
        // extract parameter
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getLocale($request);
        $userId = $this->getUser()->getId();

        // prepare vars
        $repository = $this->getRepository();
        $view = null;
        $data = null;

        try {
            switch ($action) {
                case 'move':
                    $data = $this->getDocumentManager()->find($id, $locale);

                    $this->get('sulu_security.security_checker')->checkPermission(
                        $this->getSecurityCondition($request, $data),
                        'edit'
                    );

                    $this->getDocumentManager()->move(
                        $data,
                        $this->getRequestParameter($request, 'destination', true)
                    );
                    $this->getDocumentManager()->flush();
                    break;
                case 'copy':
                    $document = $this->getDocumentManager()->find($id, $locale);

                    $this->get('sulu_security.security_checker')->checkPermission(
                        $this->getSecurityCondition($request, $document),
                        'edit'
                    );

                    $copiedPath = $this->getDocumentManager()->copy(
                        $document,
                        $this->getRequestParameter($request, 'destination', true)
                    );
                    $this->getDocumentManager()->flush();

                    $data = $this->getDocumentManager()->find($copiedPath, $locale);
                    break;
                case 'order':
                    $position = (int) $this->getRequestParameter($request, 'position', true);
                    $webspace = $this->getWebspace($request);

                    // call repository method
                    $data = $repository->orderAt($id, $position, $webspace, $locale, $userId);
                    break;
                case 'copy-locale':
                    $destLocale = $this->getRequestParameter($request, 'dest', true);
                    $webspace = $this->getWebspace($request);

                    // call repository method
                    $data = $repository->copyLocale($id, $userId, $webspace, $locale, explode(',', $destLocale));
                    break;
                case 'unpublish':
                    $document = $this->getDocumentManager()->find($id, $locale);

                    $this->get('sulu_security.security_checker')->checkPermission(
                        $this->getSecurityCondition($request, $document),
                        'live'
                    );

                    $this->getDocumentManager()->unpublish($document, $locale);
                    $this->getDocumentManager()->flush();

                    $data = $this->getDocumentManager()->find($id, $locale);
                    break;
                case 'remove-draft':
                    $webspace = $this->getWebspace($request);
                    $data = $this->getDocumentManager()->find($id, $locale);

                    $this->get('sulu_security.security_checker')->checkPermission(
                        $this->getSecurityCondition($request, $data),
                        'live'
                    );

                    $this->getDocumentManager()->removeDraft($data, $locale);
                    $this->getDocumentManager()->flush();
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            $context = new Context();
            $context->setGroups(['defaultPage']);

            // prepare view
            $view = $this->view($data, null !== $data ? 200 : 204);

            $view->setContext($context);
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
        return $this->get('sulu_page.node_repository');
    }

    /**
     * @return BaseMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->get('sulu_document_manager.metadata_factory.base');
    }

    /**
     * Returns content for all webspaces.
     * If a webspaceKey is given the $contents array will be set as children of this webspace.
     *
     * @param MappingInterface $mapping
     * @param array $contents
     * @param string $locale
     * @param UserInterface $user
     *
     * @return Content[]
     */
    private function getWebspaceNodes(
        MappingInterface $mapping,
        array $contents,
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

        return $this->getWebspaceNodesByPaths($paths, $locale, $mapping, $webspaces, $contents, $user);
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
            $locale,
            $mapping,
            $webspaces,
            $contents,
            $user
        );
    }

    /**
     * @param string[] $paths
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
        $locale,
        MappingInterface $mapping,
        array $webspaces,
        array $contents,
        UserInterface $user
    ) {
        $webspaceKey = null;
        if ($firstContent = reset($contents)) {
            $webspaceKey = $firstContent->getWebspaceKey();
        }

        $webspaceContents = $this->get('sulu_page.content_repository')->findByPaths(
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
     * @param $formType
     * @param $document
     * @param $locale
     *
     * @throws InvalidFormException
     * @throws MissingParameterException
     */
    private function persistDocument(Request $request, $formType, $document, $locale)
    {
        $data = $request->request->all();

        if ($request->query->has('parentId')) {
            $data['parent'] = $request->query->get('parentId');
        }

        $form = $this->createForm(
            $formType,
            $document,
            [
                // disable csrf protection, since we can't produce a token, because the form is cached on the client
                'csrf_protection' => false,
                'webspace_key' => $this->getWebspace($request),
            ]
        );
        $form->submit($data, false);

        if (array_key_exists('author', $data) && null === $data['author']) {
            $document->setAuthor(null);
        }

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->getDocumentManager()->persist(
            $document,
            $locale,
            [
                'user' => $this->getUser()->getId(),
                'clear_missing_content' => false,
            ]
        );
    }

    /**
     * Checks if the user has the required permissions for the given action with the given locale. The additional
     * id parameter will also include checks for the document identified by it.
     *
     * @param string $actionParameter
     * @param string $locale
     * @param string $id
     */
    private function checkActionParameterSecurity($actionParameter, Request $request, $document = null)
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
            $this->getSecurityCondition($request, $document),
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

    private function getSecurityCondition(Request $request, $document = null)
    {
        return new SecurityCondition(
            'sulu.webspaces.' . $document->getWebspaceName(),
            $this->getLocale($request),
            SecurityBehavior::class,
            $request->get('id')
        );
    }
}
