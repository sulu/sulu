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

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Hateoas\Representation\CollectionRepresentation;
use PHPCR\ItemNotFoundException;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides api for content querying.
 */
class ContentController extends RestController implements ClassResourceInterface
{
    private static $relationName = 'content';

    use RequestParametersTrait;

    /**
     * @var ContentRepositoryInterface
     */
    private $contentRepository;

    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        ContentRepositoryInterface $contentRepository,
        ViewHandlerInterface $viewHandler,
        RouterInterface $router,
        TokenStorageInterface $tokenStorage = null
    ) {
        $this->contentRepository = $contentRepository;
        $this->viewHandler = $viewHandler;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns content array by parent or webspace root.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $parent = $request->get('parent');
        $properties = array_filter(explode(',', $request->get('mapping', '')));
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);
        $excludeShadows = $this->getBooleanRequestParameter($request, 'exclude-shadows', false, false);
        $locale = $this->getRequestParameter($request, 'locale', true);
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);

        $user = $this->tokenStorage->getToken()->getUser();

        $mapping = MappingBuilder::create()
            ->disableHydrateGhost(!$excludeGhosts)
            ->disableHydrateShadow(!$excludeShadows)
            ->addProperties($properties)
            ->getMapping();

        if (!$parent) {
            $contents = $this->contentRepository->findByWebspaceRoot($locale, $webspaceKey, $mapping, $user);
        } else {
            $contents = $this->contentRepository->findByParentUuid($parent, $locale, $webspaceKey, $mapping, $user);
        }

        $list = new CollectionRepresentation($contents, self::$relationName);
        $view = $this->view($list);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns single content (tree=false) or all parents with the siblings to the given uuid.
     *
     * @param string $uuid
     * @param Request $request
     *
     * @return Response
     */
    public function getAction($uuid, Request $request)
    {
        $properties = array_filter(explode(',', $request->get('mapping', '')));
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);
        $excludeShadows = $this->getBooleanRequestParameter($request, 'exclude-shadows', false, false);
        $tree = $this->getBooleanRequestParameter($request, 'tree', false, false);
        $locale = $this->getRequestParameter($request, 'locale', true);
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);

        $user = $this->tokenStorage->getToken()->getUser();

        $mapping = MappingBuilder::create()
            ->disableHydrateGhost(!$excludeGhosts)
            ->disableHydrateShadow(!$excludeShadows)
            ->addProperties($properties)
            ->getMapping();

        try {
            if ($tree) {
                $contents = $this->contentRepository->findParentsWithSiblingsByUuid(
                    $uuid,
                    $locale,
                    $webspaceKey,
                    $mapping,
                    $user
                );
                $data = new CollectionRepresentation($contents, self::$relationName);
            } else {
                $data = $this->contentRepository->find($uuid, $locale, $webspaceKey, $mapping, $user);
            }
        } catch (ItemNotFoundException $ex) {
            // TODO return 404 and handle this edge case on client side
            return $this->redirect(
                $this->router->generate(
                    'get_contents',
                    [
                        'locale' => $locale,
                        'webspace' => $webspaceKey,
                        'exclude-ghosts' => $excludeGhosts,
                        'exclude-shadows' => $excludeShadows,
                        'mapping' => implode(',', $properties),
                    ]
                )
            );
        }

        $view = $this->view($data);

        return $this->viewHandler->handle($view);
    }
}
