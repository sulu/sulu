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
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
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

    public function __construct(
        ContentRepositoryInterface $contentRepository,
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage = null
    ) {
        $this->contentRepository = $contentRepository;
        $this->viewHandler = $viewHandler;
        $this->tokenStorage = $tokenStorage;
    }

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
}
