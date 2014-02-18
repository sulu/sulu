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
use Sulu\Component\Rest\RestController;

class NodeController extends RestController implements ClassResourceInterface
{
    /**
     * returns a content item with given UUID as JSON String
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($uuid)
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

        $view = $this->responseGetById(
            $uuid,
            function ($id) use ($language, $webspace) {
                try {
                    return $this->getRepository()->getNode($id, $webspace, $language);
                } catch (ItemNotFoundException $ex) {
                    return null;
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * returns a content item for startpage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

        $result = $this->getRepository()->getIndexNode($webspace, $language);

        return $this->handleView($this->view($result));
    }

    /**
     * returns all content items as JSON String
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

        $parentUuid = $this->getRequest()->get('parent');
        $depth = $this->getRequest()->get('depth', 1);
        $depth = intval($depth);
        $flat = $this->getRequest()->get('flat', 'true');
        $flat = ($flat === 'true');

        // TODO pagination
        $result = $this->getRepository()->getNodes($parentUuid, $webspace, $language, $depth, $flat);

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * Returns the title of the pages for a given smart content configuration
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function smartcontentAction()
    {
        // load data from request
        $dataSource = $this->getRequest()->get('dataSource', null);
        $includeSubFolders = $this->getRequest()->get('includeSubFolders', 'false');
        $limitResult = $this->getRequest()->get('limitResult', null);
        $tagNames = $this->getRequest()->get('tags', null);
        $sortBy = $this->getRequest()->get('sortBy', null);
        $sortMethod = $this->getRequest()->get('sortMethod', 'asc');

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

        $smartContentConfig = array(
            'dataSource' => $dataSource,
            'includeSubFolders' => ($includeSubFolders == 'false') ? false : true,
            'limitResult' => $limitResult,
            'tags' => $resolvedTags,
            'sortBy' => $sortColumns,
            'sortMethod' => $sortMethod
        );

        $languageCode = 'en';
        $webspaceKey = 'sulu_io';

        $structures = array();

        $content = $this->get('sulu_content.node_repository')->getSmartContentNodes(
            $smartContentConfig,
            $languageCode,
            $webspaceKey,
            true
        );

        $i = 0;
        foreach ($content as $structure) {
            /** @var StructureInterface $structure */
            $structures[] = array(
                'id' => $i++,
                'name' => $structure->getProperty('title')->getValue()
            );
        }

        return $this->handleView($this->view(array('_embedded' => $structures)));
    }

    /**
     * saves node with given uuid and data
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($uuid)
    {
        // TODO portal
        // TODO language
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');
        $template = $this->getRequest()->get('template');
        $state = $this->getRequest()->get('state');
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
            null,
            $state
        );

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * save action for index page /nodes/index
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cputIndexAction()
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');
        $template = $this->getRequest()->get('template', 'overview');
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
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');
        $template = $this->getRequest()->get('template', 'overview');
        $data = $this->getRequest()->request->all();
        $parent = $this->getRequest()->get('parent');

        $result = $this->getRepository()->saveNode(
            $data,
            $template,
            $webspace,
            $language,
            $this->getUser()->getId(),
            null,
            $parent
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
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

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
