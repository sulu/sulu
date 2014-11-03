<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Hateoas\Representation\CollectionRepresentation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;

use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaFieldDescriptorInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;

/**
 * Makes media available through a REST API
 * @package Sulu\Bundle\MediaBundle\Controller
 */
class MediaController extends RestController implements ClassResourceInterface
{

    /**
     * @var string
     */
    protected static $entityName = 'SuluMediaBundle:Media';

    /**
     * @var string
     */
    protected static $entityKey = 'media';

    /**
     * returns all fields that can be used by list
     * @Get("media/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        $fieldDescriptors = array_values($this->getMediaManager()->getFieldDescriptors());
        return $this->handleView($this->view($fieldDescriptors, 200));
    }

    /**
     * Shows a single media with the given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        try {
            $locale = $this->getLocale($request->get('locale'));
            $mediaManager = $this->getMediaManager();
            $view = $this->responseGetById(
                $id,
                function ($id) use ($locale, $mediaManager) {
                    return $mediaManager->getById($id, $locale);
                }
            );
        } catch (MediaNotFoundException $me) {
            $view = $this->view($me->toArray(), 404);
        } catch (MediaException $me) {
            $view = $this->view($me->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * lists all media
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        try {
            /** @var ListRestHelperInterface $listRestHelper */
            $listRestHelper = $this->get('sulu_core.list_rest_helper');

            $collection = $request->get('collection');
            $limit = $request->get('limit', $listRestHelper->getLimit());
            $offset = ($request->get('page', 1) - 1 ) * $limit;
            $ids = $request->get('ids');
            $search = $request->get('search');
            if ($ids !== null) {
                $ids = explode(',', $ids);
            }
            $types = $request->get('types');
            if ($types !== null) {
                $types = explode(',', $types);
            }

            $mediaManager = $this->getMediaManager();
            $media = $mediaManager->get($this->getLocale($request->get('locale')), array(
                'collection' => $collection,
                'ids' => $ids,
                'types' => $types,
                'search' => $search
            ), $limit, $offset);

            $all = $mediaManager->getCount();

            $list = new ListRepresentation(
                $media,
                self::$entityKey,
                'cget_media',
                $request->query->all(),
                $listRestHelper->getPage(),
                $listRestHelper->getLimit(),
                $all
            );

            $view = $this->view($list, 200);
        } catch (MediaNotFoundException $me) {
            $view = $this->view($me->toArray(), 404);
        } catch (MediaException $me) {
            $view = $this->view($me->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new media
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
    }

    /**
     * Edits the existing media with the given id
     * @param integer $id The id of the media to update
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @Post("media/{id}")
     */
    public function fileVersionUpdateAction($id, Request $request)
    {
        return $this->saveEntity($id, $request);
    }

    /**
     * Edits the existing media with the given id
     * @param integer $id The id of the media to update
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        return $this->saveEntity($id, $request);
    }

    /**
     * Delete a media with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            try {
                $this->getMediaManager()->delete($id);
            } catch (MediaNotFoundException $cnf) {
                throw new EntityNotFoundException(self::$entityName, $id); // will throw 404 Entity not found
            } catch (MediaException $me) {
                throw new RestException($me->getMessage(), $me->getCode()); // will throw 400 Bad Request
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function saveEntity($id, Request $request)
    {
        try {
            $mediaManager = $this->getMediaManager();
            $data = $this->getData($request, $id === null);
            $data['id'] = $id;
            $uploadedFile = $this->getUploadedFile($request, 'fileVersion');
            $media = $mediaManager->save($uploadedFile, $data, $this->getUser()->getId());

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $me) {
            $view = $this->view($me->toArray(), 404);
        } catch (MediaException $me) {
            $view = $this->view($me->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @param $name
     * @return UploadedFile
     */
    protected function getUploadedFile(Request $request, $name)
    {
        return $request->files->get($name);
    }

    /**
     * @param Request $request
     * @param bool $fallback
     * @return array
     */
    protected function getData(Request $request, $fallback = true)
    {
        return array(
            'id' => $request->get('id'),
            'locale' => $request->get('locale', $fallback ? $this->getLocale($request->get('locale')) : null),
            'type' => $request->get('type'),
            'collection' => $request->get('collection'),
            'versions' => $request->get('versions'),
            'version' => $request->get('version'),
            'size' => $request->get('size'),
            'contentLanguages' => $request->get('contentLanguages', array()),
            'publishLanguages' => $request->get('publishLanguages', array()),
            'tags' => $request->get('tags'),
            'formats' => $request->get('formats', array()),
            'url' => $request->get('url'),
            'name' => $request->get('name'),
            'title' => $request->get('title', $fallback ? $this->getTitleFromUpload($request, 'fileVersion') : null),
            'description' => $request->get('description'),
            'changer' => $request->get('changer'),
            'creator' => $request->get('creator'),
            'changed' => $request->get('changed'),
            'created' => $request->get('created'),
        );
    }

    /**
     * @param Request $request
     * @return null
     */
    protected function getTitleFromUpload($request)
    {
        $title = null;

        $uploadedFile = $this->getUploadedFile($request, 'fileVersion');

        if ($uploadedFile) {
            $title = $part = implode('.', explode('.', $uploadedFile->getClientOriginalName(), -1));
        }

        return $title;
    }

    /**
     * getMediaManager
     * @return MediaManagerInterface
     */
    protected function getMediaManager()
    {
        return $this->get('sulu_media.media_manager');
    }

    /**
     * @param $requestLocale
     * @return mixed
     */
    protected function getLocale($requestLocale)
    {
        if ($requestLocale) {
            return $requestLocale;
        }

        return $this->getUser()->getLocale();
    }
}
