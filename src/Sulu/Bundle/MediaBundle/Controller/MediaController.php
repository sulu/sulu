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
        $fieldDescriptors = $this->getMediaManager()->getFieldDescriptors();
        return $this->handleView($this->view($fieldDescriptors, 200));
    }

    /**
     * persists a setting
     * @Put("media/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Shows a single media with the given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $locale = $this->getLocale($request->get('locale'));
        $mm = $this->getMediaManager();
        $view = $this->responseGetById(
            $id,
            function ($id) use ($locale, $mm) {
                $mediaEntity = $mm->findById($id);
                return $mm->getApiObject($mediaEntity, $locale);
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all media
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $collection = $request->get('collection');
        $limit = $request->get('limit');
        $ids = $request->get('ids');
        if ($ids !== null) {
            $ids = explode(',', $ids);
        }

        /** @var ListRestHelperInterface $listRestHelper */
        $listRestHelper = $this->get('sulu_core.list_rest_helper');

        $mm = $this->getMediaManager();
        $mediaEntities = $mm->find($collection, $ids, $limit);
        $media = $mm->getApiObjects($mediaEntities, $this->getLocale($request->get('locale')));

        $all = count($media); // TODO

        $list = new ListRepresentation(
            $media,
            self::$entityKey,
            'get_collections',
            $request->query->all(),
            $listRestHelper->getPage(),
            $listRestHelper->getLimit(),
            $all
        );


        $view = $this->view($list, 200);

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
            $this->getMediaManager()->delete($id);
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
            $mm = $this->getMediaManager();
            $data = $this->getData($request);
            $data['id'] = $id;
            $uploadedFile = $this->getUploadedFile($request, 'fileVersion');
            $categoryEntity = $mm->save($uploadedFile, $data, $this->getUser()->getId());
            $categoryWrapper = $mm->getApiObject($categoryEntity, $this->getLocale($request->get('locale')));

            $view = $this->view($categoryWrapper, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
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
     * @return array
     */
    protected function getData(Request $request)
    {
        return array(
            'id' => $request->get('id'),
            'locale' => $request->get('locale', $this->getLocale($request->get('locale'))),
            'type' => $request->get('type'),
            'collection' => $request->get('collection'),
            'versions' => $request->get('versions'),
            'version' => $request->get('version'),
            'size' => $request->get('size'),
            'contentLanguages' => $request->get('contentLanguages', array()),
            'publishLanguages' => $request->get('publishLanguages', array()),
            'tags' => $request->get('tags', array()),
            'formats' => $request->get('formats', array()),
            'url' => $request->get('url'),
            'name' => $request->get('name'),
            'title' => $request->get('title', $this->getTitleFromUpload($request, 'fileVersion')),
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
     * @return MediaManagerInterface|MediaFieldDescriptorInterface
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
