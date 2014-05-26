<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use PHPCR\ItemNotFoundException;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * handles content nodes
 */
class NodeController extends RestController implements ClassResourceInterface
{

    use RequestParametersTrait;

    /**
     * returns language code from request
     * @return string
     */
    private function getLanguage()
    {
        return $this->getRequestParameter($this->getRequest(), 'language', true);
    }

    /**
     * returns webspace key from request
     * @return string
     */
    private function getWebspace()
    {
        return $this->getRequestParameter($this->getRequest(), 'webspace', true);
    }

    /**
     * returns entry point (webspace as node)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function entryAction()
    {
        $language = $this->getLanguage();
        $webspace = $this->getWebspace();

        $depth = $this->getRequestParameter($this->getRequest(), 'depth', false, 1);
        $ghostContent = $this->getBooleanRequestParameter($this->getRequest(), 'ghost-content', false, false);

        $view = $this->responseGetById(
            '',
            function ($id) use ($language, $webspace, $depth, $ghostContent) {
                try {
                    return $this->getRepository()->getWebspaceNode(
                        $webspace,
                        $language,
                        $depth,
                        $ghostContent
                    );
                } catch (ItemNotFoundException $ex) {
                    return null;
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * returns a content item with given UUID as JSON String
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($uuid)
    {
        if ($uuid === 'tree') {
            return $this->cgetTree();
        }

        $language = $this->getLanguage();
        $webspace = $this->getWebspace();
        $breadcrumb = $this->getBooleanRequestParameter($this->getRequest(), 'breadcrumb', false, false);
        $complete = $this->getBooleanRequestParameter($this->getRequest(), 'complete', false, true);
        $ghostContent = $this->getBooleanRequestParameter($this->getRequest(), 'ghost-content', false, false);

        $view = $this->responseGetById(
            $uuid,
            function ($id) use ($language, $webspace, $breadcrumb, $complete, $ghostContent) {
                try {
                    return $this->getRepository()->getNode(
                        $id,
                        $webspace,
                        $language,
                        $breadcrumb,
                        $complete,
                        $ghostContent
                    );
                } catch (ItemNotFoundException $ex) {
                    return null;
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * return a tree for given path
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetTree()
    {
        $language = $this->getLanguage();
        $webspace = $this->getWebspace();
        $excludeGhosts = $this->getBooleanRequestParameter($this->getRequest(), 'exclude-ghosts', false, false);

        $uuid = $this->getRequest()->get('uuid');
        $appendWebspaceNode = $this->getBooleanRequestParameter($this->getRequest(), 'webspace-node', false, false);

        $result = $this->getRepository()->getNodesTree(
            $uuid,
            $webspace,
            $language,
            $excludeGhosts,
            $appendWebspaceNode
        );

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * returns a content item for startpage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $language = $this->getLanguage();
        $webspace = $this->getWebspace();

        $result = $this->getRepository()->getIndexNode($webspace, $language);

        return $this->handleView($this->view($result));
    }

    /**
     * returns all content items as JSON String
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        $language = $this->getLanguage();
        $webspace = $this->getWebspace();
        $excludeGhosts = $this->getBooleanRequestParameter($this->getRequest(), 'exclude-ghosts', false, false);

        $parentUuid = $this->getRequest()->get('parent');
        $depth = $this->getRequest()->get('depth', 1);
        $depth = intval($depth);
        $flat = $this->getRequest()->get('flat', 'true');
        $flat = ($flat === 'true');

        // TODO pagination
        $result = $this->getRepository()->getNodes($parentUuid, $webspace, $language, $depth, $flat, false, $excludeGhosts);

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * returns history of resourcelocator of given node
     * @param string $uuid
     * @return JsonResponse
     */
    public function cgetHistoryAction($uuid)
    {
        $languageCode = $this->getLanguage();
        $webspaceKey = $this->getWebspace();
        $result = $this->getRepository()->getHistory($uuid, $webspaceKey, $languageCode);

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * Returns the title of the pages for a given smart content configuration
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterAction()
    {
        // load data from request
        $dataSource = $this->getRequestParameter($this->getRequest(), 'dataSource');
        $includeSubFolders = $this->getBooleanRequestParameter($this->getRequest(), 'includeSubFolders', false, false);
        $limitResult = $this->getRequestParameter($this->getRequest(), 'limitResult');
        $tagNames = $this->getRequestParameter($this->getRequest(), 'tags');
        $sortBy = $this->getRequestParameter($this->getRequest(), 'sortBy');
        $sortMethod = $this->getRequestParameter($this->getRequest(), 'sortMethod', false, 'asc');

        // resolve tag names
        $resolvedTags = array();

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
        $sortColumns = array();
        if (isset($sortBy)) {
            $columns = explode(',', $sortBy);
            foreach ($columns as $column) {
                $sortColumns[] = $column;
            }
        }

        $filterConfig = array(
            'dataSource' => $dataSource,
            'includeSubFolders' => $includeSubFolders,
            'limitResult' => $limitResult,
            'tags' => $resolvedTags,
            'sortBy' => $sortColumns,
            'sortMethod' => $sortMethod
        );

        $webspaceKey = $this->getWebspace();
        $languageCode = $this->getLanguage();

        $content = $this->get('sulu_content.node_repository')->getFilteredNodes(
            $filterConfig,
            $languageCode,
            $webspaceKey,
            true,
            true
        );

        return $this->handleView($this->view($content));
    }

    /**
     * saves node with given uuid and data
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($uuid)
    {
        if ($uuid === 'index') {
            return $this->putIndex();
        }

        $language = $this->getLanguage();
        $webspace = $this->getWebspace();
        $template = $this->getRequestParameter($this->getRequest(), 'template', true);
        $navigation = $this->getRequestParameter($this->getRequest(), 'navigation');
        if ($navigation === false || $navigation === '0') {
            $navigation = false;
        } else {
            // default navigation
            $navigation = 'main';
        }
        $state = $this->getRequestParameter($this->getRequest(), 'state');
        if ($state !== null) {
            $state = intval($state);
        }
        $data = $this->getRequest()->request->all();

        $result = $this->getRepository()->saveNode(
            $data,
            $template,
            $webspace,
            $language,
            $this->getUser()->getId(),
            $uuid,
            null, // parentUuid
            $state,
            $navigation
        );

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * put index page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function putIndex()
    {
        $language = $this->getLanguage();
        $webspace = $this->getWebspace();
        $template = $this->getRequestParameter($this->getRequest(), 'template', true);
        $data = $this->getRequest()->request->all();

        try {
            if ($data['url'] != '/') {
                throw new InvalidArgumentException('Content', 'url', 'url of index page can not be changed');
            }

            $result = $this->getRepository()->saveIndexNode(
                $data,
                $template,
                $webspace,
                $language,
                $this->getUser()->getId()
            );
            $view = $this->view($result);
        } catch (RestException $ex) {
            $view = $this->view(
                $ex->toArray(),
                400
            );
        }

        return $this->handleView($view);
    }

    /**
     * Updates a content item and returns result as JSON String
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        $language = $this->getLanguage();
        $webspace = $this->getWebspace();
        $template = $this->getRequestParameter($this->getRequest(), 'template', true);
        $navigation = $this->getRequestParameter($this->getRequest(), 'navigation');
        $parent = $this->getRequestParameter($this->getRequest(), 'parent');
        $data = $this->getRequest()->request->all();

        if ($navigation === '0') {
            $navigation = false;
        } else {
            // default navigation
            $navigation = 'main';
        }

        $result = $this->getRepository()->saveNode(
            $data,
            $template,
            $webspace,
            $language,
            $this->getUser()->getId(),
            null, // uuid
            $parent,
            null, // state
            $navigation
        );

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * deletes node with given uuid
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($uuid)
    {
        $language = $this->getLanguage();
        $webspace = $this->getWebspace();

        $view = $this->responseDelete(
            $uuid,
            function ($id) use ($language, $webspace) {
                try {
                    $this->getRepository()->deleteNode($id, $webspace, $language);
                } catch (ItemNotFoundException $ex) {
                    throw new EntityNotFoundException('Content', $id);
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * @return NodeRepositoryInterface
     */
    protected function getRepository()
    {
        return $this->get('sulu_content.node_repository');
    }
}
